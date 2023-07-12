<?php
declare(strict_types=1);

namespace BEdita\Chatlas\Client\Test\TestCase;

use BEdita\Chatlas\Client\ChatlasClient;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\FormData;
use Cake\Http\Exception\HttpException;
use Cake\TestSuite\TestCase;

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

            public array $mockResponse = [];

            /**
             * Wrapper for handleError() method.
             *
             * @param int $code Error code
             * @param string $message Error message
             * @return void
             */
            public function myHandleError(int $code, string $message): void
            {
                $this->handleError($code, $message);
            }

            /**
             * @inheritDoc
             */
            public function apiRequest(array $options = []): array
            {
                return $this->mockResponse + $options;
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
     */
    public function testGet(): void
    {
        $mockResponse = ['test' => 'get response'];
        $this->client->mockResponse = $mockResponse;
        $expected = $mockResponse + [
            'path' => '/test',
            'query' => [],
            'headers' => [],
            'method' => 'get',
        ];
        $actual = $this->client->get('/test');
        static::assertEquals($expected, $actual);
    }

    /**
     * Test `post()` method.
     *
     * @return void
     * @covers ::post()
     */
    public function testPost(): void
    {
        $mockResponse = ['test' => 'post response'];
        $this->client->mockResponse = $mockResponse;
        $expected = $mockResponse + [
            'path' => '/test',
            'headers' => [],
            'method' => 'post',
            'body' => [],
        ];
        $actual = $this->client->post('/test');
        static::assertEquals($expected, $actual);
    }

    /**
     * Test `postMultipart()` method.
     *
     * @return void
     * @covers ::postMultipart()
     */
    public function testPostMultipart(): void
    {
        $mockResponse = ['test' => 'post multipart response'];
        $this->client->mockResponse = $mockResponse;
        $expected = $mockResponse + [
            'path' => '/test',
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'method' => 'post',
            'body' => '',
        ];
        $actual = $this->client->postMultipart('/test', new FormData());
        static::assertEquals($expected, $actual);
    }

    /**
     * Test `patch()` method.
     *
     * @return void
     * @covers ::patch()
     */
    public function testPatch(): void
    {
        $mockResponse = ['test' => 'patch response'];
        $this->client->mockResponse = $mockResponse;
        $expected = $mockResponse + [
            'path' => '/test',
            'headers' => [],
            'method' => 'patch',
            'body' => [],
        ];
        $actual = $this->client->patch('/test');
        static::assertEquals($expected, $actual);
    }

    /**
     * Test `delete()` method.
     *
     * @return void
     * @covers ::delete()
     */
    public function testDelete(): void
    {
        $mockResponse = ['test' => 'delete response'];
        $this->client->mockResponse = $mockResponse;
        $expected = $mockResponse + [
            'path' => '/test',
            'headers' => [],
            'method' => 'delete',
            'body' => [],
        ];
        $actual = $this->client->delete('/test');
        static::assertEquals($expected, $actual);
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
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('test');
        $this->expectExceptionCode(500);
        $this->client->myHandleError(500, 'test');
    }
}
