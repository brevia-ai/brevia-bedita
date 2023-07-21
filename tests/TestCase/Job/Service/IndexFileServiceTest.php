<?php
declare(strict_types=1);

namespace BEdita\Chatlas\Test\TestCase\Job\Service;

use BEdita\Chatlas\Job\Service\IndexFileService;
use BEdita\Chatlas\Test\TestMockTrait;
use BEdita\Core\Filesystem\FilesystemRegistry;
use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\Model\Entity\Stream;
use Cake\TestSuite\TestCase;

/**
 * @coversDefaultClass \BEdita\Chatlas\Job\Service\IndexFileServiceTest
 */
class IndexFileServiceTest extends TestCase
{
    use TestMockTrait;

    /**
     * Test `run()` method.
     *
     * @return void
     * @covers ::run()
     */
    public function testRun(): void
    {
        FilesystemRegistry::dropAll();
        FilesystemRegistry::setConfig([
            'default' => [
                'className' => 'BEdita/Core.Local',
                'path' => './tests' . DS . 'uploads',
            ],
        ]);

        $stream = new Stream();
        $stream->set('uri', 'default://test.txt', ['guard' => false]);
        $stream->set('file_size', 1, ['guard' => false]);
        $file = $this->mockEntity('files', [
            'index_updated' => null,
            'status' => 'on',
            'streams' => [$stream],
        ]);
        $this->mockClientResponse();
        $this->mockTable('Collections', new ObjectEntity());
        $this->mockTable('Files', $file);

        $service = new IndexFileService();
        $success = $service->run([
            'collection_id' => 1,
            'file_id' => 2,
        ]);
        static::assertTrue($success);
        static::assertNotEmpty($file->get('index_updated'));
        static::assertEquals('done', $file->get('index_status'));
    }

    /**
     * Test `run()` method with failure.
     *
     * @return void
     * @covers ::run()
     */
    public function testFailure(): void
    {
        $service = new IndexFileService();
        $success = $service->run([
            'collection_id' => 1,
            'file_id' => 2,
        ]);
        static::assertFalse($success);
    }
}
