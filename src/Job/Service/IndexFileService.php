<?php
declare(strict_types=1);

/**
 * Chatlas BEdita plugin
 *
 * Copyright 2023 Atlas Srl
 */
namespace BEdita\Chatlas\Job\Service;

use BEdita\Chatlas\Index\CollectionHandler;
use BEdita\Core\Job\JobService;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

class IndexFileService implements JobService
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * Run an async job using $payload input data and optional $options.
     *
     * It can return:
     * - a boolean i.e. `true` on success, `false` on failure
     * - an array with keys:
     *   - 'success' (required) => `true` on success, `false` on failure
     *   - 'messages' (optional) => array of messages
     *
     * @param array $payload Input data for running this job.
     * @param array $options Options for running this job.
     * @return bool
     */
    public function run(array $payload, array $options = []): bool
    {
        $handler = new CollectionHandler();
        try {
            /** @var \BEdita\Core\Model\Entity\ObjectEntity $collection */
            $collection = $this->fetchTable('Collections')->get($payload['collection_id']);
            /** @var \BEdita\Core\Model\Entity\ObjectEntity $file */
            $file = $this->fetchTable('Files')->get($payload['file_id']);
            $handler->uploadDocument($collection, $file);

            return true;
        } catch (Throwable $th) {
            $this->log(sprintf('IndexFile async job error - %s', $th->getMessage()), 'error');
        }

        return false;
    }
}
