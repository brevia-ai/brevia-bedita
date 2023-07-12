<?php
declare(strict_types=1);

namespace BEdita\Chatlas\Test\TestCase\Event;

use BEdita\Chatlas\Event\ChatlasEventHandler;
use Cake\TestSuite\TestCase;

/**
 * @coversDefaultClass \BEdita\Chatlas\Event\ChatlasEventHandler
 */
class ChatlasEventHandlerTest extends TestCase
{
    /**
     * @var ChatlasEventHandler
     */
    protected $handler;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->handler = new ChatlasEventHandler();
    }

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
        static::assertSame($expected, $this->handler->implementedEvents());
    }

    /**
     * Test `beforeSave` method.
     *
     * @return void
     * @covers ::beforeSave()
     */
    public function testAfterSave(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `afterSaveAssociated` method.
     *
     * @return void
     * @covers ::afterSaveAssociated()
     */
    public function testAfterSaveAssociated(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `afterDelete` method.
     *
     * @return void
     * @covers ::afterDelete()
     */
    public function testAfterDelete(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }
}
