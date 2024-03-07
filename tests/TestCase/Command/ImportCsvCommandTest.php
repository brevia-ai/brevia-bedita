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
 * {@see \Brevia\BEdita\Command\ImportCsvCommand} Test Case
 *
 * @coversDefaultClass \Brevia\BEdita\Command\ImportCsvCommand
 */
class ImportCsvCommandTest extends TestCase
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
        $this->exec('import_csv --help');
        $this->assertOutputContains('Path of CSV file to import');
        $this->assertOutputContains('Type of object to import: documents, links');
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
        $this->exec('import_csv --file /not/existing/path --collection gustavo');
        $this->assertExitError('File not found: /not/existing/path');

        $this->mockClientResponse(json_encode([]));
        $csvPath = sprintf('%s/tests/files/import.csv', getcwd());
        $this->exec(sprintf('import_csv --file %s --collection gustavo', $csvPath));
        $this->assertExitError('Collection not found: gustavo');
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
        $this->mockTable('documents', new ObjectEntity());
        $this->mockClientResponse('[{"cmetadata": {"id":"1"}}]');
        $csvPath = sprintf('%s/tests/files/import.csv', getcwd());
        $this->exec(sprintf('import_csv --file %s --collection gustavo', $csvPath));
        $this->assertExitSuccess('Done');
    }
}
