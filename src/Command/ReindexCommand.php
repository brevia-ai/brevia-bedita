<?php
declare(strict_types=1);

/**
 * BEdita Brevia plugin
 *
 * Copyright 2024 Atlas Srl
 */
namespace Brevia\BEdita\Command;

use Brevia\BEdita\Client\BreviaClient;
use Brevia\BEdita\Index\CollectionHandler;
use Brevia\BEdita\Utility\ReadCSVTrait;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\LogTrait;
use Cake\Utility\Hash;
use Exception;

/**
 * Reindex collection contents
 *
 * @property \BEdita\Core\Model\Table\ObjectsTable $Collections
 */
class ReindexCommand extends Command
{
    use LogTrait;
    use ReadCSVTrait;

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
                'help' => 'Collection to reindex (use the unique collection name)',
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
        $collection = $this->Collections->get($collectionId, ['contain' => ['HasDocuments']]);
        $io->out('Start reindexing collection contents...');
        $documents = (array)$collection->get('has_documents');
        $handler = new CollectionHandler();
        foreach ($documents as $doc) {
            $this->log(sprintf('Reindexing [%s] "%s" - id: %s', $doc->get('type'), $doc->get('title'), $doc->id), 'info');
            try {
                $handler->addDocument($collection, $doc, false);
            } catch (Exception $e) {
                $this->log(sprintf('Error reindexing [%s] "%s" - id: %s', $doc->get('type'), $doc->get('title'), $doc->id), 'error');
                $this->log($e->getMessage(), 'error');
            }
        }

        $io->out('Done');

        return null;
    }
}
