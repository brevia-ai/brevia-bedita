<?php
declare(strict_types=1);

/**
 * Chatlas BEdita plugin
 *
 * Copyright 2023 Atlas Srl
 */
namespace BEdita\Chatlas\Index;

use Cake\Datasource\EntityInterface;
use Cake\Log\LogTrait;

/**
 * Handle Chatlas collection via API.
 */
class CollectionHandler
{
    use LogTrait;

    public function updateCollection(EntityInterface $collection): void
    {
        $msg = sprintf('Updating collection "%s"', $collection->get('title'));
        $this->log($msg, 'info');
    }

    public function updateDocument(EntityInterface $collection, EntityInterface $entity): void
    {
        $msg = sprintf('Updating document "%s"', $entity->get('title'));
        $msg .= sprintf(' collection "%s"', $collection->get('title'));
        $this->log($msg, 'info');
    }

    public function removeDocument(EntityInterface $collection, EntityInterface $entity): void
    {
        $msg = sprintf('Removing document "%s"', $entity->get('title'));
        $msg .= sprintf(' from collection "%s"', $collection->get('title'));
        $this->log($msg, 'info');
    }
}
