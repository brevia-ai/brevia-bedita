<?php
declare(strict_types=1);

namespace BEdita\Chatlas\Client\Test\TestCase;

use BEdita\Chatlas\Client\ChatlasClient;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\Adapter\Stream;
use Cake\Http\Client\FormData;
use Cake\Http\Client\Response;
use Cake\Http\Exception\HttpException;
use Cake\TestSuite\TestCase;

/**
 * @coversDefaultClass \BEdita\Chatlas\Client\ChatlasClient
 */
class ChatlasClientTest extends TestCase
{
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
    }

    /**
     * Create client Mock with response
     */
    protected function mockWithResponse(string $body): void
    {
        $response = new Response([], (string)$body);

        $mock = $this->getMockBuilder(Stream::class)
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->will($this->returnValue([$response]));

        Configure::write('Chatlas', [
            'client' => ['adapter' => $mock, 'protocolVersion' => '2'],
        ]);
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
        $this->mockWithResponse(json_encode(['gustavo']));
        $chatlasClient = new ChatlasClient();
        $result = $chatlasClient->get();
        static::assertEquals('gustavo', $result);
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
        $expected = ['test' => 'get response'];
        $this->mockWithResponse(json_encode($expected));
        $chatlasClient = new ChatlasClient();
        $result = $chatlasClient->get('/test');
        static::assertEquals($expected, $result);
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
        $expected = ['test' => 'post response'];
        $this->mockWithResponse(json_encode($expected));
        $chatlasClient = new ChatlasClient();
        $result = $chatlasClient->get('/test');
        static::assertEquals($expected, $result);
    }

    /**
     * Test `postMultipart()` method.
     *
     * @return void
     * @covers ::postMultipart()
     */
    // public function testPostMultipart(): void
    // {
    //     $mockResponse = ['test' => 'post multipart response'];
    //     $this->client->mockResponse = $mockResponse;
    //     $expected = $mockResponse + [
    //         'path' => '/test',
    //         'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
    //         'method' => 'post',
    //         'body' => '',
    //     ];
    //     $actual = $this->client->postMultipart('/test', new FormData());
    //     static::assertEquals($expected, $actual);
    // }

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
        $expected = ['test' => 'patch response'];
        $this->mockWithResponse(json_encode($expected));
        $chatlasClient = new ChatlasClient();
        $result = $chatlasClient->patch('/test');
        static::assertEquals($expected, $result);
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
        $expected = ['test' => 'delete response'];
        $this->mockWithResponse(json_encode($expected));
        $chatlasClient = new ChatlasClient();
        $result = $chatlasClient->patch('/test');
        static::assertEquals($expected, $result);
    }

    /**
     * Test `handleError()` method.
     *
     * @return void
     * @covers ::handleError()
     */
    // public function testHandleError(): void
    // {
    //     $this->expectException(HttpException::class);
    //     $this->expectExceptionMessage('test');
    //     $this->expectExceptionCode(500);
    //     $this->client->myHandleError(500, 'test');
    // }
}
