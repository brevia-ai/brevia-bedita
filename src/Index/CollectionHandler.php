<?php
declare(strict_types=1);

/**
 * BEdita Brevia plugin
 *
 * Copyright 2023 Atlas Srl
 */
namespace Brevia\BEdita\Index;

use BEdita\Core\Filesystem\FilesystemRegistry;
use BEdita\Core\Model\Entity\AsyncJob;
use BEdita\Core\Model\Entity\ObjectEntity;
use Brevia\BEdita\Client\BreviaClient;
use Cake\Http\Client\FormData;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;
use Laminas\Diactoros\UploadedFile;

/**
 * Handle Brevia collection via API.
 */
class CollectionHandler
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * Brevia API client
     *
     * @var \Brevia\BEdita\Client\BreviaClient
     */
    protected BreviaClient $client;

    /**
     * List of properties to exclude when saving Brevia collection metadata
     *
     * @var array
     */
    public const COLLECTION_FIELDS_EXCLUDED = [
        'uname',
        'type',
        'created',
        'modified',
        'lang',
        'locked',
        'published',
        'created_by',
        'modified_by',
        'collection_uuid',
        'collection_updated',
        '_meta',
        '_joinData',
    ];

    /**
     * List of properties to check when updating a Brevia collection index
     *
     * @var array
     */
    public const DOCUMENT_PROPERTIES = [
        'title',
        'description',
        'body',
        'url',
    ];

    /**
     * Handler constructor
     */
    public function __construct()
    {
        $this->client = new BreviaClient();
    }

    /**
     * Create Brevia collection
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @return void
     */
    public function createCollection(ObjectEntity $collection): void
    {
        $msg = sprintf('Creating collection "%s"', $collection->get('title'));
        $this->log($msg, 'info');

        $response = $this->client->post('/collections', $this->breviaCollection($collection));
        $collection->set('collection_uuid', Hash::get($response->getJson(), 'uuid'));
        $collection->set('collection_updated', date('c'));
        $collection->getTable()->saveOrFail($collection, ['_skipAfterSave' => true]);
    }

    /**
     * Update Brevia collection
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @return void
     */
    public function updateCollection(ObjectEntity $collection): void
    {
        $msg = sprintf('Updating collection "%s"', $collection->get('title'));
        $this->log($msg, 'info');
        $path = sprintf('/collections/%s', $collection->get('collection_uuid'));
        $this->client->patch($path, $this->breviaCollection($collection));
        $collection->set('collection_updated', date('c'));
        $collection->getTable()->saveOrFail($collection, ['_skipAfterSave' => true]);
    }

    /**
     * Fetch Brevia collection fields
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @return array
     */
    protected function breviaCollection(ObjectEntity $collection): array
    {
        $fields = array_diff_key(
            $collection->toArray(),
            array_flip(static::COLLECTION_FIELDS_EXCLUDED)
        ) + ['deleted' => $collection->get('deleted')];

        return [
            'name' => $collection->get('uname'),
            'cmetadata' => array_filter($fields),
        ];
    }

    /**
     * Remove Brevia collection
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @return void
     */
    public function removeCollection(ObjectEntity $collection): void
    {
        $msg = sprintf('Removing collection "%s"', $collection->get('title'));
        $this->log($msg, 'info');
        $path = sprintf('/collections/%s', $collection->get('collection_uuid'));
        $this->client->delete($path);
        $collection->set('collection_uuid', null);
    }

    /**
     * Add document to collection index
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @param \BEdita\Core\Model\Entity\ObjectEntity $entity Document entity
     * @param bool $useJob Use async job to upload files
     * @return void
     */
    public function addDocument(ObjectEntity $collection, ObjectEntity $entity, bool $useJob = true): void
    {
        if ($entity->get('status') !== 'on' || $entity->get('deleted')) {
            $msg = sprintf('Skipping doc "%s" - ', $entity->get('title')) .
                sprintf('status "%s" - deleted %b', $entity->get('status'), (bool)$entity->get('deleted'));
            $this->log($msg, 'info');

            return;
        }
        if ($entity->get('type') === 'files') {
            if ($useJob) {
                $this->uploadDocumentJob($collection, $entity);
            } else {
                $this->uploadDocument($collection, $entity);
            }

            return;
        }
        $body = [
            'collection_id' => $collection->get('collection_uuid'),
            'document_id' => (string)$entity->get('id'),
            'metadata' => $this->documentMetadata($collection, $entity),
        ];
        if ($entity->get('type') === 'links') {
            $body['link'] = (string)$entity->get('url');
            $body['options'] = Hash::get((array)$entity->get('extra'), 'brevia.options');
            $body['metadata']['url'] = $body['link'];
            $this->client->post('/index/link', array_filter($body));
        } else {
            $body['content'] = sprintf("%s\n%s", (string)$entity->get('title'), strip_tags((string)$entity->get('body')));
            $this->client->post('/index', $body);
        }
        $entity->set('index_updated', date('c'));
        $entity->set('index_status', 'done');
        $entity->getTable()->saveOrFail($entity, ['_skipAfterSave' => true]);
    }

    /**
     * Define metadata looking at current metadata and `extra.brevia.metadata`
     */
    protected function documentMetadata(ObjectEntity $collection, ObjectEntity $entity, array $addMeta = []): array
    {
        $defaultMetadata = ['type' => $entity->get('type')];
        $metadata = Hash::get((array)$entity->get('extra'), 'brevia.metadata', $defaultMetadata);

        $path = sprintf('/index/%s/%s', $collection->get('collection_uuid'), $entity->get('id'));
        $response = $this->client->get($path);

        return (array)Hash::get((array)$response->getJson(), '0.cmetadata', $metadata) + $addMeta;
    }

    /**
     * Upload file to index
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @param \BEdita\Core\Model\Entity\ObjectEntity $entity Document entity
     * @return void
     */
    public function uploadDocument(ObjectEntity $collection, ObjectEntity $entity): void
    {
        $form = new FormData();
        if (empty($entity->get('streams'))) {
            $entity->getTable()->loadInto($entity, ['Streams']);
        }
        /** @var \BEdita\Core\Model\Entity\Stream|null $stream */
        $stream = Hash::get($entity, 'streams.0');
        if (empty($stream)) {
            return;
        }
        $resource = FilesystemRegistry::getMountManager()->readStream($stream->uri);
        $file = new UploadedFile(
            $resource,
            $stream->file_size,
            UPLOAD_ERR_OK,
            $stream->file_name,
            $stream->mime_type,
        );
        $form->addFile('file', $file);
        // read options in `extra.brevia` if available
        $options = Hash::get((array)$entity->get('extra'), 'brevia.options');
        $fileMetadata = ['file' => $stream->file_name];
        $form->addMany(array_filter([
            'collection_id' => $collection->get('collection_uuid'),
            'document_id' => (string)$entity->get('id'),
            'metadata' => json_encode($this->documentMetadata($collection, $entity, $fileMetadata)),
            'options' => $options ? json_encode($options) : null,
        ]));
        $this->client->postMultipart(
            '/index/upload',
            $form
        );
        $entity->set('index_updated', date('c'));
        $entity->set('index_status', 'done');
        $entity->getTable()->saveOrFail($entity, ['_skipAfterSave' => true]);
    }

    /**
     * Create async job to upload file to index
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @param \BEdita\Core\Model\Entity\ObjectEntity $entity File entity
     * @return void
     */
    protected function uploadDocumentJob(ObjectEntity $collection, ObjectEntity $entity): void
    {
        $asyncJob = new AsyncJob([
            'service' => 'Brevia/BEdita.IndexFile',
            'max_attempts' => 3,
            'priority' => 5,
            'payload' => [
                'collection_id' => $collection->id,
                'file_id' => $entity->id,
            ],
        ]);
        $this->fetchTable('AsyncJobs')->saveOrFail($asyncJob);
        $entity->set('index_status', 'processing');
        $entity->getTable()->saveOrFail($entity, ['_skipAfterSave' => true]);
    }

    /**
     * Update collection index for a document
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @param \BEdita\Core\Model\Entity\ObjectEntity $entity Document entity
     * @param bool $forceAdd Force add document action
     * @return void
     */
    public function updateDocument(ObjectEntity $collection, ObjectEntity $entity, bool $forceAdd = false): void
    {
        if (
            $entity->isNew() ||
            ($entity->isDirty('deleted') && !$entity->get('deleted')) ||
            ($entity->isDirty('status') && $entity->get('status') === 'on') ||
            $forceAdd
        ) {
            $this->log($this->logMessage('Add', $collection, $entity), 'info');
            $this->addDocument($collection, $entity);

            return;
        }
        if (
            ($entity->isDirty('deleted') && $entity->get('deleted')) ||
            ($entity->isDirty('status') && in_array($entity->get('status'), ['draft', 'off']))
        ) {
            $this->removeDocument($collection, $entity);

            return;
        }
        // see if some object properties have changed (no effect on `files` objects)
        if ($entity->get('type') === 'files') {
            return;
        }

        foreach (static::DOCUMENT_PROPERTIES as $field) {
            if ($entity->isDirty($field)) {
                $this->log($this->logMessage('Update', $collection, $entity), 'info');
                $this->addDocument($collection, $entity);

                return;
            }
        }
    }

    /**
     * Log message on index action
     *
     * @param string $action Action to log
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @param \BEdita\Core\Model\Entity\ObjectEntity $entity Document entity
     * @return string
     */
    protected function logMessage(string $action, ObjectEntity $collection, ObjectEntity $entity): string
    {
        return sprintf('%s document "%s"', $action, $entity->get('title')) .
            sprintf(' [collection "%s"]', $collection->get('title'));
    }

    /**
     * Remove document from collection index
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @param \BEdita\Core\Model\Entity\ObjectEntity $entity Document entity
     * @return void
     */
    public function removeDocument(ObjectEntity $collection, ObjectEntity $entity): void
    {
        $this->log($this->logMessage('Remove', $collection, $entity), 'info');
        $path = sprintf('/index/%s/%s', $collection->get('collection_uuid'), $entity->get('id'));
        $this->client->delete($path);
        $entity->set('index_status', null);
        $entity->set('index_updated', null);
        $entity->getTable()->saveOrFail($entity, ['_skipAfterSave' => true]);
    }
}
