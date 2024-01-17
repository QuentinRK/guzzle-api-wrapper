<?php

declare(strict_types=1);

namespace ApiClientWrapper\Builder;

use ApiClientWrapper\Builder\Utils\RequestUtil;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ApiClientBuilder
{
    public const HTTP_GET = "GET";
    public const HTTP_POST = "POST";
    public const HTTP_PUT = "PUT";
    public const HTTP_DELETE = "DELETE";
    protected string $body;
    protected static ?Client $client = null;
    protected static array $defaultHeaders;
    protected static array $routes;
    protected RequestUtil $requestUtil;

    /**
     * @param array<int,mixed> $defaultHeaders
     */
    public function __construct(
        string $baseUri,
        int $maxRetries = 3,
        int $maxRetryDelay = 1000,
        array $defaultHeaders = [],
        ?LoggerInterface $loggerInterface = null,
    ) {
        static::$defaultHeaders = $defaultHeaders;
        $this->requestUtil      = new RequestUtil(
            baseUri: $baseUri,
            maxRetries: $maxRetries,
            maxRetryDelay: $maxRetryDelay,
            loggerInterface: $loggerInterface
        );
    }

    /**
     * @param string $name
     * @param string $path
     * @param string $method
     * @param array $options
     *
     * @return ApiClientBuilder
     */
    public function createRoute(
        string $name,
        string $path,
        string $method,
        array $options,
    ): self {
        static::$routes[$name] = [
            'path'    => $path,
            'method'  => $method,
            'headers' => static::$defaultHeaders,
            'options' => [
                'form_params' => [],
                'multipart'   => [],
                'query'       => [],
                'body'        => [],
            ]
        ];

        $routeConfig  = static::$routes[$name];
        $routeOptions = $routeConfig['options'];

        if (isset($options['headers'])) {
            $routeConfig['headers'] = array_merge($routeConfig['headers'], $options['headers']);
        }

        if (isset($options['form_params'])) {
            $routeOptions['form_params'] = $options['form_param'];
        }

        if (isset($options['multipart'])) {
            $routeOptions['multipart'] = $options['multipart'];
        }

        if (isset($options['query'])) {
            $routeOptions['query'] = $options['query'];
        }

        if (isset($options['body'])) {
            $routeOptions['body'] = $options['body'];
        }

        return $this;
    }

    /**
     * @throws \Throwable
     * @throws \JsonException
     */
    public function callRoute(string $name): ResponseInterface
    {
        if ( ! isset(static::$routes[$name])) {
            throw new \RuntimeException("Invalid Route Name - $name");
        }

        $routeConfig = static::$routes[$name];
        $method      = $routeConfig['method'];
        $uri         = $routeConfig['method'];
        $options     = $routeConfig['options'];

        return $this->requestUtil->sendRequest($method, $uri, $options);
    }
}
