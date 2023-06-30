<?php
declare(strict_types=1);

/**
 * Chatlas BEdita plugin
 *
 * Copyright 2023 Atlas Srl
 */
namespace BEdita\Chatlas\Index;

use BEdita\Chatlas\Client\ChatlasClient;
use Cake\Datasource\EntityInterface;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;

/**
 * Handle Chatlas collection via API.
 */
class CollectionHandler
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * Chatlas API client
     *
     * @var \BEdita\Chatlas\Client\ChatlasClient
     */
    protected $chatlas = null;

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
     * @param \Cake\Datasource\EntityInterface $collection Collection entity
     * @return void
     */
    public function createCollection(EntityInterface $collection): void
    {
        $msg = sprintf('Creating collection "%s"', $collection->get('title'));
        $this->log($msg, 'info');

        $result = $this->chatlas->post('/collections', $this->chatlasCollection($collection));
        $collection->set('collection_uuid', Hash::get($result, 'uuid'));
        $this->fetchTable('Collections')->saveOrFail($collection);
    }

    /**
     * Update Chatlas collection
     *
     * @param \Cake\Datasource\EntityInterface $collection Collection entity
     * @return void
     */
    public function updateCollection(EntityInterface $collection): void
    {
        $msg = sprintf('Updating collection "%s"', $collection->get('title'));
        $this->log($msg, 'info');
        $path = sprintf('/collections/%s', $collection->get('collection_uuid'));
        $this->chatlas->patch($path, $this->chatlasCollection($collection));
    }

    /**
     * Fetch Chatlas collection fields
     *
     * @param \Cake\Datasource\EntityInterface $collection Collection entity
     * @return array
     */
    protected function chatlasCollection(EntityInterface $collection): array
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
     * @param \Cake\Datasource\EntityInterface $collection Collection entity
     * @return void
     */
    public function removeCollection(EntityInterface $collection): void
    {
        $msg = sprintf('Removing collection "%s"', $collection->get('title'));
        $this->log($msg, 'info');
        $path = sprintf('/collections/%s', $collection->get('collection_uuid'));
        $this->chatlas->delete($path);
    }

    /**
     * Add document to collection index
     *
     * @param \Cake\Datasource\EntityInterface $collection Collection entity
     * @param \Cake\Datasource\EntityInterface $entity Document entity
     * @return void
     */
    public function addDocument(EntityInterface $collection, EntityInterface $entity): void
    {
        $body = [
            'content' => strip_tags((string)$entity->get('body')),
            'collection_id' => $collection->get('collection_uuid'),
            'document_id' => $entity->get('id'),
            'metadata' => [],
        ];
        $this->chatlas->post('/index', $body);
    }

    /**
     * Update collection index for a document
     *
     * @param \Cake\Datasource\EntityInterface $collection Collection entity
     * @param \Cake\Datasource\EntityInterface $entity Document entity
     * @param bool $forceAdd Force add document action
     * @return void
     */
    public function updateDocument(EntityInterface $collection, EntityInterface $entity, bool $forceAdd = false): void
    {
        if ($entity->isNew() || $this->documentToAdd($entity) || $forceAdd) {
            $this->log($this->logMessage('Add', $collection, $entity), 'info');
            $this->addDocument($collection, $entity);

            return;
        }
        if ($this->documentToRemove($entity)) {
            $this->removeDocument($collection, $entity);

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
     * See if a document has to be removed from index
     *
     * @param \Cake\Datasource\EntityInterface $entity Document entity
     * @return bool
     */
    protected function documentToRemove(EntityInterface $entity): bool
    {
        if ($entity->isDirty('deleted') && $entity->get('deleted')) {
            return true;
        }

        if ($entity->isDirty('status') && in_array($entity->get('status'), ['draft', 'off'])) {
            return true;
        }

        return false;
    }

    /**
     * See if a document has to be added to index
     *
     * @param \Cake\Datasource\EntityInterface $entity Document entity
     * @return bool
     */
    protected function documentToAdd(EntityInterface $entity): bool
    {
        if ($entity->isDirty('deleted') && !$entity->get('deleted')) {
            return true;
        }

        if ($entity->isDirty('status') && $entity->get('status') === 'on') {
            return true;
        }

        return false;
    }

    /**
     * Log message on index action
     *
     * @param string $action Action to log
     * @param \Cake\Datasource\EntityInterface $collection Collection entity
     * @param \Cake\Datasource\EntityInterface $entity Document entity
     * @return string
     */
    protected function logMessage(string $action, EntityInterface $collection, EntityInterface $entity): string
    {
        return sprintf('%s document "%s"', $action, $entity->get('title')) .
            sprintf(' [collection "%s"]', $collection->get('title'));
    }

    /**
     * Remove document from collection index
     *
     * @param \Cake\Datasource\EntityInterface $collection Collection entity
     * @param \Cake\Datasource\EntityInterface $entity Document entity
     * @return void
     */
    public function removeDocument(EntityInterface $collection, EntityInterface $entity): void
    {
        $this->log($this->logMessage('Remove', $collection, $entity), 'info');
        $path = sprintf('/index/%s/%s', $collection->get('collection_uuid'), $entity->get('id'));
        $this->chatlas->delete($path);
    }
}
