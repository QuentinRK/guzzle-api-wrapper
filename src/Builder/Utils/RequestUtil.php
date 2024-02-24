<?php

declare(strict_types=1);

namespace ApiClientWrapper\Builder\Utils;

use ApiClientWrapper\Builder\Middleware\RetryPolicy;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class RequestUtil
{
    public const HTTP_GET = "GET";
    public const HTTP_POST = "POST";
    public const HTTP_PUT = "PUT";
    public const HTTP_DELETE = "DELETE";
    public const CONTENT_TYPE_FORM_ENCODED = 'application/x-www-form-urlencoded';
    public const CONTENT_TYPE_JSON = 'application/json';
    public const CONTENT_TYPE_MULTIPART = 'multipart/form-data';

    public string $requestLogName = 'Request & Response';

    protected ?Client $client = null;

    public function __construct(
        protected string $baseUri,
        protected int $maxRetries,
        protected int $maxRetryDelay,
        protected ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $headers
     * @param array $options
     *
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws \JsonException
     * @throws \Throwable
     */
    public function sendRequest(
        string $method,
        string $uri,
        array $headers,
        array $options,
    ): ResponseInterface {
        $body = null;
        if ( ! empty($options['body'])) {
            $body                    = json_encode($options['body'], JSON_THROW_ON_ERROR);
            $headers['Content-Type'] = self::CONTENT_TYPE_JSON;
        }

        if ( ! empty($options['form_params'])) {
            $body                    = http_build_query($options['form_params'], "", '&');
            $headers['Content-Type'] = self::CONTENT_TYPE_FORM_ENCODED;
        }

        if ( ! empty($options['query'])) {
            $uri = sprintf('%s?%s', $uri, http_build_query($options['query'], "", '&'));
        }

        if ( ! empty($options['multipart'])) {
            $body                    = new MultipartStream($options['multipart']);
            $headers['Content-Type'] = self::CONTENT_TYPE_MULTIPART;
        }

        $request = new Request(
            method: $method,
            uri: $uri,
            headers: $headers,
            body: $body
        );


        if ($this->logger) {
            $loggerUtil = new LoggerUtil($this->logger, $this->baseUri);
            try {
                $response = $this->getClient()->send($request);
                $loggerUtil->logRequest($this->requestLogName, $request, $response, $body);

                return $response;
            } catch (\Throwable $e) {
                $loggerUtil->logRequest($this->requestLogName, $request, $response ?? null, $body, $e);
                throw $e;
            }
        }

        return $this->getClient()->send($request);
    }

    /**
     * @return Client
     */
    protected function getClient(): Client
    {
        if ($this->client === null) {
            $handler = HandlerStack::create();
            $handler->push(
                RetryPolicy::createRetryPolicy($this->maxRetries, $this->maxRetryDelay),
            );

            $this->client = new Client(
                [
                    'base_uri' => $this->baseUri,
                    'handler'  => $handler,
                ]
            );
        }

        return $this->client;
    }
}
