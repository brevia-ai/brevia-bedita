<?php
declare(strict_types=1);

/**
 * BEdita Brevia plugin
 *
 * Copyright 2024 Atlas Srl
 */
namespace Brevia\BEdita\Command;

use BEdita\Core\Utility\LoggedUser;
use Brevia\BEdita\Client\BreviaClient;
use Brevia\BEdita\Utility\ReadCSVTrait;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\LogTrait;
use Cake\Utility\Hash;

/**
 * Import links from sitemap and create links
 *
 * @property \BEdita\Core\Model\Table\ObjectsTable $Collections
 */
class ImportSitemapCommand extends Command
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
        return $parser->addOption('sitemap', [
                'help' => 'File path or URL of sitemap to import',
                'short' => 's',
                'required' => true,
            ])
            ->addOption('prefix', [
                'help' => 'Optional path prefix of URLs to import',
                'short' => 'p',
                'required' => false,
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
        $this->client = new BreviaClient();
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $sitemap = $args->getOption('sitemap');
        $content = file_get_contents($sitemap);
        if (!$content) {
            $io->abort(sprintf('Sitemap content not found: %s', $sitemap));
        }

        $name = $args->getOption('collection');
        $response = $this->client->get('/collections', compact('name'));
        $collectionId = Hash::get($response->getJson(), '0.cmetadata.id');
        if (empty($collectionId)) {
            $io->abort(sprintf('Collection not found: %s', $name));
        }
        $collection = $this->Collections->get($collectionId, ['contain' => ['HasDocuments']]);
        $currentUrls = array_filter(array_map(function ($link) {
                return $link->get('url');
            },
            $collection->has_documents));
        $prefix = $args->getOption('prefix');

        $xml = simplexml_load_string($content);
        $json = json_encode($xml);
        $data = (array)json_decode($json, true);
        $urls = Hash::combine($data, 'url.{n}.loc');
        if (empty($urls)) {
            $io->abort('No URLs found in sitemap');
        }
        $entities = [];
        LoggedUser::setUserAdmin();
        $this->Links = $this->fetchTable('Links');
        foreach ($urls as $url) {
            if (in_array($url, $currentUrls) || ($prefix && strpos($url, $prefix) !== 0)) {
                continue;
            }
            $data = [
                'status' => 'on',
                'title' => $url,
                'url' => $url,
            ];
            $entity = $this->Links->newEntity($data);
            $entities[] = $this->Links->saveOrFail($entity);
        }
        /** @phpstan-ignore-next-line */
        $this->Collections->addRelated($collection, 'has_documents', $entities);

        $io->out('Done');

        return null;
    }
}
