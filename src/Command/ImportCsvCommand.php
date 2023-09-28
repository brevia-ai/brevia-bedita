<?php
declare(strict_types=1);

/**
 * Chatlas BEdita plugin
 *
 * Copyright 2023 Atlas Srl
 */
namespace BEdita\Chatlas\Command;

use BEdita\Chatlas\Client\ChatlasClient;
use BEdita\Chatlas\Utility\ReadCSVTrait;
use BEdita\Core\Model\Table\RolesTable;
use BEdita\Core\Model\Table\UsersTable;
use BEdita\Core\Utility\LoggedUser;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\LogTrait;
use Cake\Utility\Hash;

/**
 * Import CSV and create questions or documents
 *
 * @property \BEdita\Core\Model\Table\ObjectsTable $Collections
 */
class ImportCsvCommand extends Command
{
    use LogTrait;
    use ReadCSVTrait;

    /**
     * Chatlas API client
     *
     * @var \BEdita\Chatlas\Client\ChatlasClient
     */
    protected ChatlasClient $chatlas;

    /**
     * @inheritDoc
     */
    public $defaultTable = 'Collections';

    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->addOption('file', [
                'help' => 'Path of CSV file to import',
                'short' => 'f',
                'required' => true,
            ])
            ->addOption('type', [
                'help' => 'Type of object to import: documents or questions',
                'short' => 't',
                'default' => 'documents',
                'choices' => ['documents', 'questions'],
                'required' => true,
            ])
            ->addOption('collection', [
                'help' => 'Collection used to index (use the unique collection name)',
                'short' => 'c',
                'required' => true,
            ]);
    }

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->chatlas = new ChatlasClient();
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $filepath = $args->getOption('file');
        if (!file_exists($filepath)) {
            $io->abort(sprintf('File not found: %s', $filepath));
        }

        $name = $args->getOption('collection');
        $response = $this->chatlas->get('/collections', compact('name'));
        $collectionId = Hash::get($response->getJson(), '0.cmetadata.id');
        if (empty($collectionId)) {
            $io->abort(sprintf('Collection not found: %s', $name));
        }
        $collection = $this->Collections->get($collectionId);
        $this->readCSVFile($filepath);
        $Table = $this->fetchTable($args->getOption('type'));
        $entities = [];
        LoggedUser::setUserAdmin();
        foreach ($this->csvData as $item) {
            $item['status'] = 'on';
            $entity = $Table->newEntity($item);
            $entities[] = $Table->saveOrFail($entity);
        }
        /** @phpstan-ignore-next-line */
        $this->Collections->addRelated($collection, 'has_documents', $entities);

        $io->out('Done');

        return null;
    }
}
