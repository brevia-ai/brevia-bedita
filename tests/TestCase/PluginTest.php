<?php
declare(strict_types=1);

namespace BEdita\Brevia\Test\TestCase;

use BEdita\Brevia\Plugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\TestSuite\TestCase;

/**
 * @coversDefaultClass \BEdita\Brevia\Plugin
 */
class PluginTest extends TestCase
{
    protected $plugin;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->plugin = new class extends Plugin {
            public function bootstrap(PluginApplicationInterface $app): void
            {
                parent::bootstrap($app);
                Configure::write('TestPluginBoostrap', 'done');
            }
        };
    }

    /**
     * Test `bootstrap` method.
     *
     * @return void
     * @covers ::bootstrap()
     */
    public function testBootstrap(): void
    {
        $app = $this->createMock(PluginApplicationInterface::class);
        $this->assertFalse(Configure::check('TestPluginBoostrap'));
        $this->plugin->bootstrap($app);
        $this->assertTrue(Configure::check('TestPluginBoostrap'));
    }
}
