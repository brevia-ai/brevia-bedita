<?php
declare(strict_types=1);

namespace Brevia\Test\TestCase\Event;

use ArrayObject;
use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\ORM\Association\RelatedTo;
use Brevia\Event\BreviaEventHandler;
use Brevia\Test\TestMockTrait;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;

/**
 * @coversDefaultClass \Brevia\Event\BreviaEventHandler
 */
class BreviaEventHandlerTest extends TestCase
{
    use TestMockTrait;

    /**
     * Test `implementedEvents` method.
     *
     * @return void
     * @covers ::implementedEvents()
     */
    public function testImplementedEvents(): void
    {
        $expected = [
            'Model.afterDelete' => 'afterDelete',
            'Model.afterSave' => 'afterSave',
            'Associated.afterSave' => 'afterSaveAssociated',
        ];
        $evtHandler = new BreviaEventHandler();
        static::assertSame($expected, $evtHandler->implementedEvents());
    }

    /**
     * Data provider for testAfterSave()
     *
     * @return array
     */
    public function afterSaveProvider(): array
    {
        return [
            'bad entity' => [
                new Entity(),
                ['expect' => ['index_updated' => null]],
            ],
            'skip save' => [
                $this->mockEntity('documents'),
                ['expect' => ['index_updated' => null]],
            ],
            'create collection' => [
                $this->mockEntity('collections'),
                [
                    'response' => ['body' => json_encode(['uuid' => '1234567'])],
                    'expect' => ['collection_uuid' => true],
                ],
            ],
            'update collection' => [
                $this->mockEntity('collections')->setNew(false),
                [
                    'response' => ['body' => ''],
                    'expect' => [
                        'collection_uuid' => null,
                        'collection_updated' => true,
                    ],
                ],
            ],
            'no assoc' => [
                $this->mockEntity('documents'),
                [
                    'expect' => ['index_updated' => null],
                ],
            ],
            'no documents' => [
                $this->mockEntity('documents', [], ['DocumentOf']),
                [
                    'expect' => ['index_updated' => null],
                ],
            ],
            'add document' => [
                $this->mockEntity(
                    'documents',
                    ['document_of' => [new ObjectEntity()], 'status' => 'on'],
                    ['DocumentOf']
                ),
                [
                    'response' => ['body' => ''],
                    'expect' => ['index_updated' => true],
                ],
            ],
        ];
    }

    /**
     * Test `afterSave` method.
     *
     * @return void
     * @dataProvider afterSaveProvider
     * @covers ::afterSave()
     */
    public function testAfterSave($entity, array $options): void
    {
        if (!empty($options['response'])) {
            $this->mockClientResponse($options['response']['body']);
        }
        $handler = new BreviaEventHandler();
        $event = new Event('test');
        $handler->afterSave($event, $entity, new ArrayObject($options));
        foreach ($options['expect'] as $key => $value) {
            if ($value === null) {
                static::assertNull($entity->get($key));
            } else {
                static::assertNotNull($entity->get($key));
            }
        }
    }

    /**
     * Data provider for testAssociated()
     *
     * @return array
     */
    public function associatedProvider(): array
    {
        return [
            'no assoc' => [
                [
                    'entity' => new Entity(),
                ],
                [
                    'test' => 'entity',
                    'expect' => ['index_updated' => null],
                ],
            ],
            'bad assoc' => [
                [
                    'association' => new RelatedTo('Test'),
                    'entity' => new Entity(),
                ],
                [
                    'test' => 'entity',
                    'expect' => ['index_updated' => null],
                ],
            ],
            'bad entity' => [
                [
                    'association' => new RelatedTo('DocumentOf'),
                    'entity' => new Entity(),
                ],
                [
                    'test' => 'entity',
                    'expect' => ['index_updated' => null],
                ],
            ],
            'document of' => [
                [
                    'association' => new RelatedTo('DocumentOf'),
                    'entity' => $this->mockEntity('documents'),
                    'relatedEntities' => [new ObjectEntity()],
                ],
                [
                    'test' => 'entity',
                    'data' => ['index_updated' => null, 'status' => 'on'],
                    'expect' => ['index_updated' => date('c')],
                    'call' => true,
                ],
            ],
            'remove document' => [
                [
                    'association' => new RelatedTo('HasDocuments'),
                    'entity' => new ObjectEntity(),
                    'relatedEntities' => [$this->mockEntity('documents')],
                    'action' => 'remove',
                ],
                [
                    'test' => 'relatedEntities.0',
                    'data' => ['index_updated' => date('c')],
                    'expect' => ['index_updated' => null],
                    'call' => true,
                ],
            ],
        ];
    }

    /**
     * Test `afterSaveAssociated` method.
     *
     * @return void
     * @dataProvider associatedProvider
     * @covers ::afterSaveAssociated()
     */
    public function testAssociated(array $data, array $options): void
    {
        $test = Hash::get($data, $options['test']);
        $test->set((array)Hash::get($options, 'data'));

        if (!empty($options['call'])) {
            $this->mockClientResponse();
        }
        $handler = new BreviaEventHandler();
        $event = new Event('test', null, $data);
        $handler->afterSaveAssociated($event);
        foreach ($options['expect'] as $key => $value) {
            if ($value === null) {
                static::assertNull($test->get($key));
            } else {
                static::assertNotNull($test->get($key));
            }
        }
    }

    /**
     * Data provider for testAfterDelete()
     *
     * @return array
     */
    public function afterDeleteProvider(): array
    {
        return [
            'not deleted' => [
                'documents',
                false,
            ],
            'deleted' => [
                'collections',
                true,
            ],
        ];
    }

    /**
     * Test `afterDelete` method.
     *
     * @return void
     * @dataProvider afterDeleteProvider
     * @covers ::afterDelete()
     */
    public function testAfterDelete(string $type, bool $deleted): void
    {
        $entity = $this->mockEntity($type);
        if ($deleted) {
            $this->mockClientResponse();
        }
        $entity->set('collection_uuid', '1234567');
        $evtHandler = new BreviaEventHandler();
        $evtHandler->afterDelete(new Event('test'), $entity);
        if ($deleted) {
            static::assertNull($entity->get('collection_uuid'));
        } else {
            static::assertNotNull($entity->get('collection_uuid'));
        }
    }
}
