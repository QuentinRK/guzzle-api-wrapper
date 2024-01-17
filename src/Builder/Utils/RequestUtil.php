<?php

declare(strict_types=1);

namespace ApiClientWrapper\Builder\Utils;

use ApiClientWrapper\Builder\Middleware\RetryPolicy;
use GuzzleHttp\Client;
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
    public const FORM_CONTENT_TYPE = 'application/x-www-form-urlencoded';

    protected static int $maxRetries;
    protected static int $maxRetryDelay;
    protected static string $baseUri;
    protected static Client $client;
    protected static ?LoggerInterface $loggerInterface;

    public function __construct(
        string $baseUri,
        int $maxRetries,
        int $maxRetryDelay,
        ?LoggerInterface $loggerInterface,
    ) {
        static::$maxRetries      = $maxRetries;
        static::$maxRetryDelay   = $maxRetryDelay;
        static::$baseUri         = $baseUri;
        static::$loggerInterface = $loggerInterface;
    }

    /**
     * @param array<int,mixed> $options
     *
     * @throws \JsonException|\Throwable
     */
    public function sendRequest(
        string $method,
        string $uri,
        array $options,
        string $logName = "Request & Response Log",
    ): ResponseInterface {
        $headers = $options['headers'];
        $body    = $options['body'];

        if ( ! empty($options['form_params'])) {
            $body                    = http_build_query($options['form_params'], "", '&');
            $headers['Content-Type'] = self::FORM_CONTENT_TYPE;
        }

        if ( ! empty($options['query'])) {
            $uri = sprintf('%s?%s', $uri, http_build_query($options['query'], "", '&'));
        }

        if ( ! empty($options['multipart'])) {
            $body = new MultipartStream($options['multipart']);
        }

        $request = new Request(
            method: $method,
            uri: $uri,
            headers: $headers,
            body: $body ?? null,
        );

        if (static::$loggerInterface) {
            $loggerUtil = new LoggerUtil(static::$loggerInterface, static::$baseUri);
            try {
                $response = $this->getClient()->send($request);
                $loggerUtil->logRequest($logName, $request, $response, $body);

                return $response;
            } catch (\Throwable $e) {
                $loggerUtil->logRequest($logName, $request, $response ?? null, $body, $e);
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
        if (static::$client === null) {
            $handler = HandlerStack::create();
            $handler->push(
                RetryPolicy::createRetryPolicy(static::$maxRetries, static::$maxRetryDelay),
            );

            static::$client = new Client(
                [
                    'base_uri' => static::$baseUri,
                    'handler'  => $handler,
                ]
            );
        }

        return static::$client;
    }
}
