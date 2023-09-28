<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for Chatlas.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 */
use BEdita\Chatlas\Test\TestApp\Application;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new Exception('Cannot find the root of the application, unable to run tests');
};
$root = $findRoot(__FILE__);
unset($findRoot);

chdir($root);

require_once 'vendor/cakephp/cakephp/src/basics.php';
require_once 'vendor/autoload.php';

define('ROOT', $root . DS . 'tests' . DS . 'test_app' . DS);
define('APP', ROOT . 'TestApp' . DS);
define('TMP', sys_get_temp_dir() . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('CONFIG', ROOT . DS . 'config' . DS);
define('TEST_APP', ROOT);
define('WWW_ROOT', TEST_APP . 'webroot' . DS);
define('CAKE_CORE_INCLUDE_PATH', $root . DS . 'vendor' . DS . 'cakephp' . DS . 'cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);

Configure::write('debug', true);

Configure::write('App', [
    'namespace' => 'BEdita\Chatlas\Test\TestApp',
    'encoding' => 'utf-8',
    'paths' => [
        'plugins' => [ROOT . 'Plugin' . DS],
        'templates' => [$root . DS . 'templates' . DS],
    ],
]);

Cache::setConfig([
    '_cake_core_' => [
        'engine' => 'File',
        'prefix' => 'cake_core_',
        'serialize' => true,
    ],
    '_cake_model_' => [
        'engine' => 'File',
        'prefix' => 'cake_model_',
        'serialize' => true,
    ],
    'default' => [
        'engine' => 'File',
        'prefix' => 'default_',
        'serialize' => true,
    ],
]);

// Ensure default test connection is defined
if (!getenv('db_dsn')) {
    putenv('db_dsn=sqlite:///:memory:');
}

ConnectionManager::setConfig('test', [
    'url' => getenv('db_dsn'),
    'timezone' => 'UTC',
]);

$app = new Application(dirname(__DIR__) . '/config');
$app->bootstrap();
$app->pluginBootstrap();
