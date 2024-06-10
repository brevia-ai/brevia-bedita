<?php
declare(strict_types=1);

/**
 * BEdita Brevia plugin
 *
 * Copyright 2024 Atlas Srl
 */
namespace Brevia\BEdita\Command;

use Brevia\BEdita\Client\BreviaClient;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\LogTrait;
use Cake\Utility\Hash;
use Exception;

/**
 * Update metadata in collection contents
 *
 * @property \BEdita\Core\Model\Table\ObjectsTable $Collections
 */
class UpdateMetadataCommand extends Command
{
    use LogTrait;

    /**
     * Brevia API client
     *
     * @var \Brevia\BEdita\Client\BreviaClient
     */
    protected BreviaClient $client;

    /**
     * @inheritDoc
     */
    public $defaultTable = 'Collections';

    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->addOption('collection', [
                'help' => 'Collection to update (use the unique collection name)',
                'short' => 'c',
                'required' => true,
            ]);
    }

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->client = new BreviaClient();
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $name = $args->getOption('collection');
        $response = $this->client->get('/collections', compact('name'));
        $collectionId = Hash::get($response->getJson(), '0.cmetadata.id');
        if (empty($collectionId)) {
            $io->abort(sprintf('Collection not found: %s', $name));
        }
        $collectionUuid = Hash::get($response->getJson(), '0.uuid');
        $collection = $this->Collections->get($collectionId, ['contain' => ['HasDocuments']]);
        $io->out('Start reindexing collection contents...');
        $documents = (array)$collection->get('has_documents');
        foreach ($documents as $doc) {
            if ($doc->get('type') !== 'files') {
                continue;
            }
            $this->log(sprintf('Updating [%s] "%s" - id: %s', $doc->get('type'), $doc->get('title'), $doc->id), 'info');
            try {
                $doc = $doc->getTable()->get($doc->id);
                $path = sprintf('/index/%s/%s', $collectionUuid, $doc->id);
                $response = $this->client->get($path);
                $metadata = (array)Hash::get((array)$response->getJson(), '0.cmetadata');
                if (!empty($metadata['url'])) {
                    continue;
                }
                $metadata['url'] = $doc->get('media_url');
                $this->client->post('/index/metadata', [
                    'collection_id' => $collectionUuid,
                    'document_id' => (string)$doc->id,
                    'metadata' => $metadata,
                ]);
            } catch (Exception $e) {
                $this->log(sprintf('Error updating [%s] "%s" - id: %s', $doc->get('type'), $doc->get('title'), $doc->id), 'error');
                $this->log($e->getMessage(), 'error');
            }
        }

        $io->out('Done');

        return null;
    }
}
