<?php
declare(strict_types=1);

namespace BEdita\Chatlas\Client\Test\TestCase;

use BEdita\Chatlas\Client\ChatlasClient;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\TestSuite\TestCase;
use Exception;
use Throwable;

/**
 * @coversDefaultClass \BEdita\Chatlas\Client\ChatlasClient
 */
class ChatlasClientTest extends TestCase
{
    protected $client;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Cake\Http\Client
     */
    protected $httpClient;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('Chatlas', [
            'apiUrl' => 'https://api.chatlasapp.com',
            'token' => 'test-token',
        ]);
        $client = new class extends ChatlasClient {
            /**
             * API internal HTTP client
             *
             * @var \Cake\Http\Client
             */
            public Client $client;

            /**
             * Wrapper for handleError() method.
             *
             * @param \Throwable $error The error thrown.
             * @return array
             */
            public function myHandleError(Throwable $error): array
            {
                return $this->handleError($error);
            }
        };
        $this->httpClient = $this->createMock(Client::class);
        $this->client = new $client($this->httpClient);
    }

    /**
     * Test `__construct()` method.
     *
     * @return void
     * @covers ::__construct()
     * @covers ::initialize()
     */
    public function testConstruct(): void
    {
        $cakeClient = $this->client->client;
        static::assertInstanceof(Client::class, $cakeClient);
        static::assertInstanceof(ChatlasClient::class, $this->client);
        static::assertSame('api.chatlasapp.com', $cakeClient->getConfig('host'));
    }

    /**
     * Test `get()` method.
     *
     * @return void
     * @covers ::get()
     * @covers ::apiRequest()
     * @covers ::sendRequest()
     */
    public function testGet(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `post()` method.
     *
     * @return void
     * @covers ::post()
     * @covers ::apiRequest()
     * @covers ::sendRequest()
     */
    public function testPost(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `postMultipart()` method.
     *
     * @return void
     * @covers ::postMultipart()
     * @covers ::apiRequest()
     * @covers ::sendRequest()
     */
    public function testPostMultipart(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `patch()` method.
     *
     * @return void
     * @covers ::patch()
     * @covers ::apiRequest()
     * @covers ::sendRequest()
     */
    public function testPatch(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `delete()` method.
     *
     * @return void
     * @covers ::delete()
     * @covers ::apiRequest()
     * @covers ::sendRequest()
     */
    public function testDelete(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `handleError()` method.
     *
     * @return void
     * @covers ::handleError()
     */
    public function testHandleError(): void
    {
        $expected = ['error' => ['status' => 500, 'title' => 'test']];
        $actual = $this->client->myHandleError(new Exception('test'));
        static::assertEquals($expected, $actual);
    }
}
