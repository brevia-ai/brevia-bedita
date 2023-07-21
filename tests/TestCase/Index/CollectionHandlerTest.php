<?php
declare(strict_types=1);

namespace BEdita\Chatlas\Test\TestCase\Index;

use BEdita\Chatlas\Index\CollectionHandler;
use BEdita\Chatlas\Test\TestMockTrait;
use BEdita\Core\Filesystem\FilesystemRegistry;
use BEdita\Core\Model\Entity\AsyncJob;
use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\Model\Entity\Stream;
use Cake\TestSuite\TestCase;

/**
 * @coversDefaultClass \BEdita\Chatlas\Index\CollectionHandler
 */
class CollectionHandlerTest extends TestCase
{
    use TestMockTrait;

    /**
     * Test `createCollection()` method.
     *
     * @return void
     * @covers ::createCollection()
     * @covers ::__construct()
     * @covers ::chatlasCollection()
     */
    public function testCreateCollection(): void
    {
        $collection = $this->mockEntity();
        $value = '123456789';
        $this->mockClientResponse(json_encode(['uuid' => $value]));

        $handler = new CollectionHandler();
        $handler->createCollection($collection);
        static::assertEquals($value, $collection->get('collection_uuid'));
        static::assertNotEmpty($collection->get('collection_updated'));
    }

    /**
     * Test `updateCollection()` method.
     *
     * @return void
     * @covers ::updateCollection()
     */
    public function testUpdateCollection(): void
    {
        $collection = $this->mockEntity();
        $this->mockClientResponse();
        $handler = new CollectionHandler();
        $handler->updateCollection($collection);
        static::assertNotEmpty($collection->get('collection_updated'));
    }

    /**
     * Test `removeCollection()` method.
     *
     * @return void
     * @covers ::removeCollection()
     */
    public function testRemoveCollection(): void
    {
        $collection = $this->mockEntity();
        $this->mockClientResponse();
        $handler = new CollectionHandler();
        $collection->set('collection_uuid', '123456');
        $handler->removeCollection($collection);
        static::assertNull($collection->get('collection_uuid'));
    }

    /**
     * Test `uploadDocumentJob()` method.
     *
     * @return void
     * @covers ::uploadDocumentJob()
     * @covers ::updateDocument()
     * @covers ::addDocument()
     */
    public function testUploadDocumentJob(): void
    {
        $entity = $this->mockEntity('files', [
            'index_updated' => null,
            'index_status' => null,
            'status' => 'on',
        ]);
        $entity->setNew(false);
        $handler = new CollectionHandler();
        $collection = new ObjectEntity();
        $this->mockTable('AsyncJobs', new AsyncJob());
        $handler->updateDocument($collection, $entity);
        static::assertNull($entity->get('index_updated'));
        static::assertEquals('processing', $entity->get('index_status'));
    }

    /**
     * Test `uploadDocument()` method.
     *
     * @return void
     * @covers ::uploadDocument()
     */
    public function testUploadEmptyStream(): void
    {
        $entity = $this->mockEntity('files', [
            'index_updated' => null,
            'status' => 'on',
        ]);
        $entity->setNew(false);
        $handler = new CollectionHandler();
        $collection = new ObjectEntity();
        $handler->uploadDocument($collection, $entity);
        static::assertNull($entity->get('index_updated'));
    }

    /**
     * Test `uploadDocument()` method.
     *
     * @return void
     * @covers ::uploadDocument()
     */
    public function testUploadStream(): void
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
        $entity = $this->mockEntity('files', [
            'index_updated' => null,
            'status' => 'on',
            'streams' => [$stream],
        ]);
        $entity->setNew(false);
        $this->mockClientResponse();

        $handler = new CollectionHandler();
        $collection = new ObjectEntity();
        $handler->uploadDocument($collection, $entity);
        static::assertNotEmpty($entity->get('index_updated'));
    }

    /**
     * Test `updateDocument()` method.
     *
     * @return void
     * @covers ::updateDocument()
     * @covers ::addDocument()
     */
    public function testUpdateNewDocument(): void
    {
        $entity = $this->mockEntity('documents', [
            'index_updated' => null,
            'index_status' => null,
            'status' => 'on',
        ]);
        $entity->setNew(false);
        $this->mockClientResponse();

        $handler = new CollectionHandler();
        $handler->updateDocument(new ObjectEntity(), $entity);
        static::assertNotEmpty($entity->get('index_updated'));
        static::assertEquals('done', $entity->get('index_status'));
    }

    /**
     * Test `updateDocument()` method.
     *
     * @return void
     * @covers ::updateDocument()
     * @covers ::addDocument()
     */
    public function testDocumentNotUpdated(): void
    {
        $entity = $this->mockEntity('documents', [
            'index_updated' => null,
            'index_status' => null,
        ]);
        $handler = new CollectionHandler();
        $handler->updateDocument(new ObjectEntity(), $entity);
        static::assertNull($entity->get('index_updated'));
        static::assertNull($entity->get('index_status'));
    }

    /**
     * Test `updateDocument()` method with document changed.
     *
     * @return void
     * @covers ::updateDocument()
     */
    public function testDocumentChanged(): void
    {
        $entity = $this->mockEntity('documents', [
            'title' => 'new title',
            'status' => 'on',
            'index_updated' => null,
        ]);
        $entity->setNew(false);
        $entity->setDirty('status', false);
        $this->mockClientResponse();

        $handler = new CollectionHandler();
        $handler->updateDocument(new ObjectEntity(), $entity);
        static::assertNotNull($entity->get('index_updated'));
    }

    /**
     * Test `updateDocument()` with documents to remove.
     *
     * @return void
     * @covers ::updateDocument()
     */
    public function testDocumentToRemove(): void
    {
        $entity = $this->mockEntity('documents', [
            'index_updated' => date('c'),
            'status' => 'off',
        ]);
        $entity->setNew(false);
        $this->mockClientResponse();

        $handler = new CollectionHandler();
        $handler->updateDocument(new ObjectEntity(), $entity);
        static::assertNull($entity->get('index_updated'));
    }

    /**
     * Test `updateDocument()` with documents to restore.
     *
     * @return void
     * @covers ::updateDocument()
     */
    public function testDocumentRestored(): void
    {
        $entity = $this->mockEntity('documents', [
            'index_updated' => null,
            'status' => 'on',
        ]);
        $entity->setNew(false);
        $entity->setDirty('status', false);
        $entity->set('deleted', false, ['guard' => false]);
        $this->mockClientResponse();

        $handler = new CollectionHandler();
        $handler->updateDocument(new ObjectEntity(), $entity);
        static::assertNotNull($entity->get('index_updated'));
    }

    /**
     * Test `updateDocument()` with changes on `files` entity to ignore.
     *
     * @return void
     * @covers ::updateDocument()
     */
    public function testFilesIgnore(): void
    {
        $entity = $this->mockEntity('files', [
            'index_updated' => null,
            'title' => 'changed title',
        ]);
        $entity->setNew(false);

        $handler = new CollectionHandler();
        $handler->updateDocument(new ObjectEntity(), $entity);
        static::assertNull($entity->get('index_updated'));
    }

    /**
     * Test `removeDocument()` method.
     *
     * @return void
     * @covers ::removeDocument()
     * @covers ::logMessage()
     */
    public function testRemoveDocument(): void
    {
        $entity = $this->mockEntity();
        $this->mockClientResponse();
        $handler = new CollectionHandler();
        $entity->set('index_updated', date('c'));
        $entity->set('index_status', 'done');
        $handler->removeDocument(new ObjectEntity(), $entity);
        static::assertNull($entity->get('index_updated'));
        static::assertNull($entity->get('index_status'));
    }
}
