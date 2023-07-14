<?php
declare(strict_types=1);

namespace BEdita\Chatlas\Test\TestCase\Client;

use BEdita\Chatlas\Client\ChatlasClient;
use BEdita\Chatlas\Test\ClientMockTrait;
use Cake\Core\Configure;
use Cake\Http\Client\FormData;
use Cake\Http\Exception\HttpException;
use Cake\TestSuite\TestCase;

/**
 * @coversDefaultClass \BEdita\Chatlas\Client\ChatlasClient
 */
class ChatlasClientTest extends TestCase
{
    use ClientMockTrait;

    /**
     * Test `__construct()` method.
     *
     * @return void
     * @covers ::__construct()
     * @covers ::initialize()
     */
    public function testConstruct(): void
    {
        $this->mockClientResponse();
        $chatlasClient = new ChatlasClient();
        $response = $chatlasClient->get();
        static::assertEquals(200, $response->getStatusCode());
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
        $this->mockClientResponse(json_encode($expected));
        $chatlasClient = new ChatlasClient();
        $response = $chatlasClient->get('/test');
        static::assertEquals($expected, $response->getJson());
        static::assertEquals(200, $response->getStatusCode());
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
        $this->mockClientResponse(json_encode($expected));
        $chatlasClient = new ChatlasClient();
        $response = $chatlasClient->post('/test', ['input' => 'data']);
        static::assertEquals($expected, $response->getJson());
        static::assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test `postMultipart()` method.
     *
     * @return void
     * @covers ::postMultipart()
     */
    public function testPostMultipart(): void
    {
        $expected = ['test' => 'post multipart response'];
        $this->mockClientResponse(json_encode($expected));
        $chatlasClient = new ChatlasClient();
        $response = $chatlasClient->postMultipart('/test', new FormData());
        static::assertEquals($expected, $response->getJson());
        static::assertEquals(200, $response->getStatusCode());
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
        $expected = ['test' => 'patch response'];
        $this->mockClientResponse(json_encode($expected));
        $chatlasClient = new ChatlasClient();
        $response = $chatlasClient->patch('/test');
        static::assertEquals($expected, $response->getJson());
        static::assertEquals(200, $response->getStatusCode());
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
        $this->mockClientResponse();
        $chatlasClient = new ChatlasClient();
        $response = $chatlasClient->delete('/test');
        static::assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test `handleError()` method.
     *
     * @return void
     * @covers ::handleError()
     * @covers ::apiRequest()
     */
    public function testHandleError(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Chatlas API error: []');
        $this->expectExceptionCode(500);
        $this->mockClientResponse('', 500);
        $chatlasClient = new ChatlasClient();
        $chatlasClient->delete('/test');
    }


    /**
     * Test `handleError()` method with a bad url exception
     *
     * @return void
     * @covers ::handleError()
     * @covers ::apiRequest()
     */
    public function testHandleErrorUrl(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Chatlas API error: The source URI string appears to be malformed');
        $this->expectExceptionCode(500);
        Configure::write('Chatlas.apiUrl', 'bad url');
        $chatlasClient = new ChatlasClient();
        $chatlasClient->get('/test');
    }

}
