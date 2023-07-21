<?php
declare(strict_types=1);

namespace BEdita\Chatlas\Test;

use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\Model\Entity\ObjectType;
use Cake\Core\Configure;
use Cake\Http\Client\Adapter\Stream;
use Cake\Http\Client\Response;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

trait TestMockTrait
{
    /**
     * Create client Mock with response body and status code
     *
     * @param string $body Response body
     * @param int $status Response status code
     * @return void
     */
    protected function mockClientResponse(string $body = '', int $status = 200): void
    {
        // init config if not set
        if (!Configure::check('Chatlas.apiUrl')) {
            Configure::write('Chatlas', [
                'apiUrl' => 'https://api.chatlasapp.com',
                'token' => 'test-token',
            ]);
        }

        $response = new Response([], (string)$body);
        $response = $response->withStatus($status);

        $mock = $this->getMockBuilder(Stream::class)
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->will($this->returnValue([$response]));

        Configure::write('Chatlas.adapter', $mock);
    }

    /**
     * Create Entity mock
     *
     * @param string|null $type Object type
     * @param array $data Entity data
     */
    protected function mockEntity(?string $type = null, array $data = [], array $associations = [])
    {
        $mockTable = $this->getMockBuilder(Table::class)
            ->onlyMethods(['saveOrFail', 'loadInto', 'hasAssociation'])
            ->getMock();
        $mockTable->method('saveOrFail')
            ->willReturn(new ObjectEntity());
        $mockTable->method('loadInto')
            ->willReturn([]);
        $mockTable->method('hasAssociation')
            ->willReturnCallback(function ($assoc) use ($associations) {
                return in_array($assoc, $associations);
            });

        $mockEntity = $this->getMockBuilder(ObjectEntity::class)
            ->onlyMethods(['toArray', 'getTable'])
            ->getMock();
        $mockEntity->method('toArray')
            ->willReturn([]);
        $mockEntity->method('getTable')
            ->willReturn($mockTable);

        if ($type) {
            $objType = new ObjectType();
            $objType->set('name', $type);
            $mockEntity->set('object_type', $objType, ['guard' => false]);
        }
        $mockEntity->set($data);

        return $mockEntity;
    }

    /**
     * Create mock table
     *
     * @param string $alias Table alias
     * @param Entity $entity Entity
     * @return void
     */
    protected function mockTable(string $alias, Entity $entity): void
    {
        $mockTable = $this->getMockBuilder(Table::class)
            ->onlyMethods(['saveOrFail', 'get'])
            ->getMock();
        $mockTable->method('saveOrFail')
            ->willReturn($entity);
        $mockTable->method('get')
            ->willReturn($entity);

        $locator = TableRegistry::getTableLocator();
        $locator->remove($alias);
        $locator->set($alias, $mockTable);
    }
}
