<?php
declare(strict_types=1);

/**
 * Chatlas BEdita plugin
 *
 * Copyright 2023 Atlas Srl
 */
namespace BEdita\Chatlas\Client;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\FormData;
use Cake\Http\Client\Response;
use Cake\Http\Exception\HttpException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Log\LogTrait;
use Throwable;

/**
 * Chatlas API Client.
 */
class ChatlasClient
{
    use LogTrait;

    /**
     * API internal HTTP client
     *
     * @var \Cake\Http\Client
     */
    protected Client $client;

    /**
     * Default content type in requests
     */
    public const DEFAULT_CONTENT_TYPE = 'application/json';

    /**
     * Client constructor
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialize client from `Chatlas` configuration key
     *
     * @return void
     */
    public function initialize(): void
    {
        $options = parse_url((string)Configure::read('Chatlas.apiUrl')) + array_filter([
            'headers' => [
                'Accept' => 'application/json',
            ],
            'timeout' => Configure::read('Chatlas.timeout'),
            'adapter' => Configure::read('Chatlas.adapter'),
        ]);
        if (Configure::check('Chatlas.token')) {
            $options['headers'] += [
                'Authorization' => sprintf('Bearer %s', (string)Configure::read('Chatlas.token')),
            ];
        }

        $this->client = new Client($options);
    }

    /**
     * Proxy for GET requests to Chatlas API
     *
     * @param string $path The path for API request
     * @param array $query The query params
     * @param array<string, string> $headers The request headers
     * @return \Cake\Http\Response
     */
    public function get(string $path = '', array $query = [], array $headers = []): Response
    {
        return $this->apiRequest(compact('path', 'query', 'headers') + [
            'method' => 'get',
        ]);
    }

    /**
     * Proxy for POST requests to Chatlas API
     *
     * @param string $path The path for API request
     * @param array $body The body data
     * @param array<string, string> $headers The request headers
     * @return \Cake\Http\Response
     */
    public function post(string $path = '', array $body = [], array $headers = []): Response
    {
        return $this->apiRequest(compact('path', 'body', 'headers') + [
            'method' => 'post',
        ]);
    }

    /**
     * Proxy for POST multipart/form requests to Chatlas API
     *
     * @param string $path The path for API request
     * @param \Cake\Http\Client\FormData $form The form data
     * @return \Cake\Http\Response
     */
    public function postMultipart(string $path, FormData $form): Response
    {
        return $this->apiRequest(compact('path') + [
            'method' => 'post',
            'body' => (string)$form,
            'headers' => ['Content-Type' => $form->contentType()],
        ]);
    }

    /**
     * Proxy for PATCH requests to Chatlas API
     *
     * @param string $path The path for API request
     * @param array $body The body data
     * @param array<string, string> $headers The request headers
     * @return \Cake\Http\Response
     */
    public function patch(string $path = '', array $body = [], array $headers = []): Response
    {
        return $this->apiRequest(compact('path', 'body', 'headers') + [
            'method' => 'patch',
        ]);
    }

    /**
     * Proxy for DELETE requests to Chatlas API
     *
     * @param string $path The path for API request
     * @param array $body The body data
     * @param array<string, string> $headers The request headers
     * @return \Cake\Http\Response
     */
    public function delete(string $path = '', array $body = [], array $headers = []): Response
    {
        return $this->apiRequest(compact('path', 'body', 'headers') + [
            'method' => 'delete',
        ]);
    }

    /**
     * Routes a request to the API handling response and errors.
     *
     * `$options` are:
     * - method => the HTTP request method
     * - path => a string representing the complete endpoint path
     * - query => an array of query strings
     * - body => the body sent
     * - headers => an array of headers
     *
     * @param array $options The request options
     * @return \Cake\Http\Response
     */
    protected function apiRequest(array $options): Response
    {
        $options += [
            'method' => '',
            'path' => '',
            'query' => null,
            'body' => null,
            'headers' => null,
        ];

        if (empty($options['body'])) {
            $options['body'] = null;
        }
        if (is_array($options['body'])) {
            $options['body'] = json_encode($options['body']);
        }
        if (!empty($options['body']) && empty($options['headers']['Content-Type'])) {
            $options['headers']['Content-Type'] = static::DEFAULT_CONTENT_TYPE;
        }

        $body = [];
        $statusCode = 0;
        try {
            $response = $this->sendRequest($options);
            $statusCode = $response->getStatusCode();
        } catch (Throwable $e) {
            $this->handleError($e->getCode(), $e->getMessage());
        }
        if ($statusCode >= 400) {
            $this->handleError($statusCode, (string)json_encode($body));
        }

        return $response;
    }

    /**
     * Send request using options
     *
     * @param array $options Request options array
     * @return \Cake\Http\Client\Response
     */
    protected function sendRequest(array $options): Response
    {
        $method = strtolower($options['method']);
        $headers = ['headers' => (array)$options['headers']];
        if ($method === 'get') {
            return $this->client->get(
                (string)$options['path'],
                (array)$options['query'],
                $headers
            );
        }

        return call_user_func_array(
            [$this->client, $method],
            [
                (string)$options['path'],
                $options['body'],
                $headers,
            ]
        );
    }

    /**
     * Handle Chatlas API error: log and throw exception
     *
     * @param int $code Error code
     * @param string $message Error message
     * @throws \Cake\Http\Exception\HttpException
     * @return void
     */
    protected function handleError(int $code, string $message): void
    {
        if ($code < 100 || $code > 599) {
            $code = 500;
        }
        $msg = sprintf('Chatlas API error: %s', $message);
        $this->log(sprintf('[%d] %s', $code, $msg), 'error');
        throw new HttpException($msg, $code);
    }
}
