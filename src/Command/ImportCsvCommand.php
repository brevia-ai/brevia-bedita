<?php
declare(strict_types=1);

/**
 * BEdita, API-first content management framework
 * Copyright 2022 Atlas Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
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
                'help' => 'Collection used to index, ID or unique name',
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
            $io->abort(sprintf('Collection ID not found: %s', $name));
        }
        $collection = $this->Collections->get($collectionId);
        $this->readCSVFile($filepath);
        $Table = $this->fetchTable($args->getOption('type'));
        $entities = [];
        LoggedUser::setUser(['id' => UsersTable::ADMIN_USER, 'roles' => [['id' => RolesTable::ADMIN_ROLE]]]);
        foreach ($this->csvData as $item) {
            $item['status'] = 'on';
            $entity = $Table->newEntity($item);
            $entities[] = $Table->saveOrFail($entity);
        }
        $this->Collections->addRelated($collection, 'has_documents', $entities);

        $io->out('Done');

        return null;
    }
}
