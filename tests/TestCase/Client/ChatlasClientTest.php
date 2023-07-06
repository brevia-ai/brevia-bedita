<?php
declare(strict_types=1);

namespace BEdita\Chatlas\Client\Test\TestCase;

use BEdita\Chatlas\Client\ChatlasClient;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \BEdita\Chatlas\Client\ChatlasClient
 */
class ChatlasClientTest extends TestCase
{
    /**
     * @var ChatlasClient
     */
    protected $client;

    /**
     * @var MockObject|Client
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
     */
    public function testDelete(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `apiRequest()` method.
     *
     * @return void
     * @covers ::apiRequest()
     */
    public function testApiRequest(): void
    {
        static::markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test `sendRequest()` method.
     *
     * @return void
     * @covers ::sendRequest()
     */
    public function testSendRequest(): void
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
        static::markTestIncomplete('This test has not been implemented yet.');
    }
}
