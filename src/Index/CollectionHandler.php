<?php
declare(strict_types=1);

/**
 * Chatlas BEdita plugin
 *
 * Copyright 2023 Atlas Srl
 */
namespace BEdita\Chatlas\Index;

use BEdita\Chatlas\Client\ChatlasClient;
use BEdita\Core\Filesystem\FilesystemRegistry;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Http\Client\FormData;
use Cake\Log\LogTrait;
use Cake\Utility\Hash;
use Laminas\Diactoros\UploadedFile;

/**
 * Handle Chatlas collection via API.
 */
class CollectionHandler
{
    use LogTrait;

    /**
     * Chatlas API client
     *
     * @var \BEdita\Chatlas\Client\ChatlasClient
     */
    protected ChatlasClient $chatlas;

    /**
     * List of properties to exclude when saving Chatlas collection metadata
     *
     * @var array
     */
    public const COLLECTION_FIELDS_EXCLUDED = [
        'uname',
        'type',
        'created',
        'modified',
        'locked',
        'published',
        'created_by',
        'modified_by',
        'collection_uuid',
        'collection_updated',
        '_meta',
    ];

    /**
     * List of properties to check when updating a Chatlas collection index
     *
     * @var array
     */
    public const DOCUMENT_PROPERTIES = [
        'title',
        'description',
        'body',
    ];

    /**
     * Handler constructor
     */
    public function __construct()
    {
        $this->chatlas = new ChatlasClient();
    }

    /**
     * Create Chatlas collection
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @return void
     */
    public function createCollection(ObjectEntity $collection): void
    {
        $msg = sprintf('Creating collection "%s"', $collection->get('title'));
        $this->log($msg, 'info');

        $response = $this->chatlas->post('/collections', $this->chatlasCollection($collection));
        $collection->set('collection_uuid', Hash::get($response->getJson(), 'uuid'));
        $collection->set('collection_updated', date('c'));
        $collection->getTable()->saveOrFail($collection, ['_skipAfterSave' => true]);
    }

    /**
     * Update Chatlas collection
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @return void
     */
    public function updateCollection(ObjectEntity $collection): void
    {
        $msg = sprintf('Updating collection "%s"', $collection->get('title'));
        $this->log($msg, 'info');
        $path = sprintf('/collections/%s', $collection->get('collection_uuid'));
        $this->chatlas->patch($path, $this->chatlasCollection($collection));
        $collection->set('collection_updated', date('c'));
        $collection->getTable()->saveOrFail($collection, ['_skipAfterSave' => true]);
    }

    /**
     * Fetch Chatlas collection fields
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @return array
     */
    protected function chatlasCollection(ObjectEntity $collection): array
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
     * Remove Chatlas collection
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @return void
     */
    public function removeCollection(ObjectEntity $collection): void
    {
        $msg = sprintf('Removing collection "%s"', $collection->get('title'));
        $this->log($msg, 'info');
        $path = sprintf('/collections/%s', $collection->get('collection_uuid'));
        $this->chatlas->delete($path);
        $collection->set('collection_uuid', null);
    }

    /**
     * Add document to collection index
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @param \BEdita\Core\Model\Entity\ObjectEntity $entity Document entity
     * @return void
     */
    protected function addDocument(ObjectEntity $collection, ObjectEntity $entity): void
    {
        if ($entity->get('status') !== 'on' || $entity->get('deleted')) {
            $msg = sprintf('Skipping doc "%s" - ', $entity->get('title')) .
                sprintf('status "%s" - deleted %b', $entity->get('status'), (bool)$entity->get('deleted'));
            $this->log($msg, 'info');

            return;
        }
        if ($entity->get('type') === 'files') {
            $this->uploadDocument($collection, $entity);

            return;
        }
        $content = sprintf("%s\n%s", (string)$entity->get('title'), strip_tags((string)$entity->get('body')));
        $body = [
            'content' => $content,
            'collection_id' => $collection->get('collection_uuid'),
            'document_id' => $entity->get('id'),
            'metadata' => ['type' => $entity->get('type')],
        ];
        $this->chatlas->post('/index', $body);
        $entity->set('index_updated', date('c'));
        $entity->getTable()->saveOrFail($entity, ['_skipAfterSave' => true]);
    }

    /**
     * Upload file to index
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $collection Collection entity
     * @param \BEdita\Core\Model\Entity\ObjectEntity $entity Document entity
     * @return void
     */
    protected function uploadDocument(ObjectEntity $collection, ObjectEntity $entity): void
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
        $form->addMany([
            'collection_id' => $collection->get('collection_uuid'),
            'document_id' => $entity->get('id'),
            'metadata' => json_encode([
                'type' => $entity->get('type'),
                'file' => $stream->file_name,
            ]),
        ]);
        $this->chatlas->postMultipart(
            '/index/upload',
            $form
        );
        $entity->set('index_updated', date('c'));
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
        $this->chatlas->delete($path);
        $entity->set('index_updated', null);
        $entity->getTable()->saveOrFail($entity, ['_skipAfterSave' => true]);
    }
}
