<?php
declare(strict_types=1);

/**
 * BEdita Brevia plugin
 *
 * Copyright 2023 Atlas Srl
 */
namespace Brevia\BEdita\Test\TestCase\Command;

use BEdita\Core\Model\Entity\ObjectEntity;
use Brevia\BEdita\Test\TestMockTrait;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * {@see \Brevia\BEdita\Command\ImportSitemapCommand} Test Case
 *
 * @coversDefaultClass \Brevia\BEdita\Command\ImportSitemapCommand
 */
class ImportSitemapCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use TestMockTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();
        Router::reload();
    }

    /**
     * Test buildOptionParser method
     *
     * @return void
     * @covers ::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('import_sitemap --help');
        $this->assertOutputContains('File path or URL of sitemap to import');
        $this->assertOutputContains('Optional path prefix of URLs to import');
        $this->assertOutputContains('Collection used to index');
    }

    /**
     * Test options failure
     *
     * @return void
     * @covers ::initialize()
     * @covers ::execute()
     */
    public function testOptionFailure(): void
    {
        $this->exec('import_sitemap --sitemap /not/existing/path --collection gustavo');
        $this->assertExitError('File not found: /not/existing/path');

        $this->mockClientResponse(json_encode([]));
        $xmlPath = sprintf('%s/tests/files/sitemap.xml', getcwd());
        $this->exec(sprintf('import_sitemap --sitemap %s --collection gustavo', $xmlPath));
        $this->assertExitError('Collection not found: gustavo');

        $this->mockClientResponse('[{"cmetadata": {"id":"1"}}]');
        $this->mockTable('Collections', new ObjectEntity());
        $xmlPath = sprintf('%s/tests/files/empty.csv', getcwd());
        $this->exec(sprintf('import_sitemap --sitemap %s --collection gustavo', $xmlPath));
        $this->assertExitError('No URLs found in sitemap');
    }

    /**
     * Test command success
     *
     * @return void
     * @covers ::execute()
     */
    public function testCommand(): void
    {
        $this->mockTable('Collections', new ObjectEntity());
        $this->mockTable('Links', new ObjectEntity());
        $this->mockClientResponse('[{"cmetadata": {"id":"1"}}]', 200, 3);
        $xmlPath = sprintf('%s/tests/files/sitemap.xml', getcwd());
        $this->exec(sprintf('import_sitemap --sitemap %s --collection gustavo', $xmlPath));
        $this->assertExitSuccess('Done');
    }
}
