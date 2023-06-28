<?php
declare(strict_types=1);

/**
 * Chatlas BEdita plugin
 *
 * Copyright 2023 Atlas Srl
 */
namespace BEdita\Chatlas;

use BEdita\Chatlas\Event\ChatlasEventHandler;
use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManager;

/**
 * Plugin for BEdita Chatlas
 */
class Plugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);
        EventManager::instance()->on(new ChatlasEventHandler());
    }
}
