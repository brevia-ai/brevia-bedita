<?php
declare(strict_types=1);

namespace BEdita\Brevia\Test\TestCase\Client;

use BEdita\Brevia\Client\BreviaClient;
use BEdita\Brevia\Test\TestMockTrait;
use Cake\Core\Configure;
use Cake\Http\Client\FormData;
use Cake\Http\Exception\HttpException;
use Cake\TestSuite\TestCase;

/**
 * @coversDefaultClass \BEdita\Brevia\Client\BreviaClient
 */
class BreviaClientTest extends TestCase
{
    use TestMockTrait;

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
        $breviaClient = new BreviaClient();
        $response = $breviaClient->get();
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
        $breviaClient = new BreviaClient();
        $response = $breviaClient->get('/test');
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
        $breviaClient = new BreviaClient();
        $response = $breviaClient->post('/test', ['input' => 'data']);
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
        $breviaClient = new BreviaClient();
        $response = $breviaClient->postMultipart('/test', new FormData());
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
        $breviaClient = new BreviaClient();
        $response = $breviaClient->patch('/test');
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
        $breviaClient = new BreviaClient();
        $response = $breviaClient->delete('/test');
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
        $this->expectExceptionMessage('Brevia API error: ');
        $this->expectExceptionCode(500);
        $this->mockClientResponse('', 500);
        $breviaClient = new BreviaClient();
        $breviaClient->delete('/test');
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
        $this->expectExceptionMessage('Brevia API error: The source URI string appears to be malformed');
        $this->expectExceptionCode(500);
        Configure::write('Brevia.apiUrl', 'bad url');
        $breviaClient = new BreviaClient();
        $breviaClient->get('/test');
    }
}
