<?php
declare(strict_types=1);

namespace BEdita\Chatlas\Test\TestCase\Index;

use BEdita\Chatlas\Index\CollectionHandler;
use BEdita\Chatlas\Test\ClientMockTrait;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \BEdita\Chatlas\Index\CollectionHandler
 */
class CollectionHandlerTest extends TestCase
{
    use ClientMockTrait;

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
     * Create Entity mock
     */
    protected function mockEntity()
    {
        $mockTable = $this->getMockBuilder(Table::class)
            ->onlyMethods(['saveOrFail', 'loadInto'])
            ->getMock();
        $mockTable->method('saveOrFail')
            ->willReturn(new ObjectEntity());
        $mockTable->method('loadInto')
            ->willReturn([]);

        $mockEntity = $this->getMockBuilder(ObjectEntity::class)
            ->onlyMethods(['toArray', 'getTable'])
            ->getMock();
        $mockEntity->method('toArray')
            ->willReturn([]);
        $mockEntity->method('getTable')
            ->willReturn($mockTable);

        return $mockEntity;
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
     * Test `chatlasCollection()` method.
     *
     * @return void
     * @covers ::chatlasCollection()
     */
    public function testChatlasCollection(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
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
     * Test `addDocument()` method.
     *
     * @return void
     * @covers ::addDocument()
     */
    public function testAddDocument(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `uploadDocument()` method.
     *
     * @return void
     * @covers ::uploadDocument()
     */
    public function testUploadDocument(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `updateDocument()` method.
     *
     * @return void
     * @covers ::updateDocument()
     */
    public function testUpdateDocument(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `documentToRemove()` method.
     *
     * @return void
     * @covers ::documentToRemove()
     */
    public function testDocumentToRemove(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `documentToAdd()` method.
     *
     * @return void
     * @covers ::documentToAdd()
     */
    public function testDocumentToAdd(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
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
        $handler->removeDocument(new ObjectEntity(), $entity);
        static::assertNull($entity->get('index_updated'));
    }
}
