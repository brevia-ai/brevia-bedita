<?php
declare(strict_types=1);

namespace BEdita\Chatlas\Test;

use Cake\Core\Configure;
use Cake\Http\Client\Adapter\Stream;
use Cake\Http\Client\Response;

trait ClientMockTrait
{
    /**
     * Create client Mock with response body and status code
     *
     * @param string $body Response body
     * @param int $status Response status code
     * @return void
     */
    protected function mockClientResponse(string $body = '', int $status = 200): void
    {
        // init config if not set
        if (!Configure::check('Chatlas.apiUrl')) {
            Configure::write('Chatlas', [
                'apiUrl' => 'https://api.chatlasapp.com',
                'token' => 'test-token',
            ]);
        }

        $response = new Response([], (string)$body);
        $response = $response->withStatus($status);

        $mock = $this->getMockBuilder(Stream::class)
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->will($this->returnValue([$response]));

        Configure::write('Chatlas.adapter', $mock);
    }
}
