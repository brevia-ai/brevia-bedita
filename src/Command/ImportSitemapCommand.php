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
     * Links Table
     *
     * @var \Cake\ORM\Table
     */
    protected $Links;

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
        $this->Links = $this->fetchTable('Links');
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $sitemap = $args->getOption('sitemap');
        if (!file_exists($sitemap)) {
            $io->abort(sprintf('File not found: %s', $sitemap));
        }
        $content = file_get_contents($sitemap);

        $name = $args->getOption('collection');
        $response = $this->client->get('/collections', compact('name'));
        $collectionId = Hash::get($response->getJson(), '0.cmetadata.id');
        if (empty($collectionId)) {
            $io->abort(sprintf('Collection not found: %s', $name));
        }
        $collection = $this->Collections->get($collectionId, ['contain' => ['HasDocuments']]);
        $currentUrls = array_filter(array_map(function ($link) {
                $link = $link->getTable()->get($link->id);

                return $link->get('url');
        },
            (array)$collection->get('has_documents')));
        $prefix = $args->getOption('prefix');

        $xml = simplexml_load_string($content);
        $json = json_encode($xml);
        $data = (array)json_decode($json, true);
        $urls = Hash::extract($data, 'url.{n}.loc');
        if (empty($urls)) {
            $io->abort('No URLs found in sitemap');
        }
        $entities = [];
        LoggedUser::setUserAdmin();
        foreach ($urls as $url) {
            if (
                in_array($url, $currentUrls) ||
                in_array(urldecode($url), $currentUrls) ||
                ($prefix && strpos($url, $prefix) !== 0)
            ) {
                continue;
            }
            $io->info('Adding link: ' . $url);
            $data = [
                'status' => 'on',
                'title' => $url,
                'url' => $url,
                'extra' => [
                    'brevia' => [
                        'metadata' => [
                            'type' => 'links',
                            'url' => $url,
                        ],
                        'options' => $this->linkOptions($url, (array)$collection->get('link_load_options')),
                    ],
                ],
            ];
            $entity = $this->Links->newEntity($data);
            $entities[] = $this->Links->saveOrFail($entity);
        }
        /** @phpstan-ignore-next-line */
        $this->Collections->addRelated($collection, 'has_documents', $entities);

        $io->out('Done. Link added successfully: ' . count($entities));

        return null;
    }

    /**
     * Get link options
     *
     * @param string $url URL
     * @param array $linkLoadOptions Link load options
     * @return array
     */
    protected function linkOptions(string $url, array $linkLoadOptions): array
    {
        $options = array_filter($linkLoadOptions, function ($o) use ($url) {
            return $o['url'] === $url;
        });
        $selector = Hash::get($options, '0.selector');
        if (!empty($selector)) {
            return compact('selector');
        }
        $options = array_filter($linkLoadOptions, function ($o) use ($url) {
            return strpos($url, $o['url']) === 0;
        });

        return ['selector' => Hash::get($options, '0.selector')];
    }
}
