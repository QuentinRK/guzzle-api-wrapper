<?php

declare(strict_types=1);

namespace ApiClientWrapper\Builder;

use ApiClientWrapper\Builder\Utils\RequestUtil;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class ApiClientBuilder
{
    protected string $body;
    protected static ?Client $client = null;
    protected static array $routes;

    /**
     * @param RequestUtil $requestUtil
     * @param array $defaultHeaders
     */
    public function __construct(
        protected RequestUtil $requestUtil,
        protected array $defaultHeaders = [],
    ) {
    }

    /**
     * @param string $name
     * @param string $path
     * @param string $method
     * @param array $options
     * @param string $routeLogName
     *
     * @return ApiClientBuilder
     */
    public function setRoute(
        string $name,
        string $path,
        string $method,
        array $options = [],
        string $routeLogName = "",
    ): self {
        static::$routes[$name] = [
            'path'     => $path,
            'method'   => $method,
            'headers'  => isset($options['headers'])
                ? array_merge($this->defaultHeaders, $options['headers'])
                : $this->defaultHeaders,
            'options'  => [
                'form_params' => $options['form_params'] ?? [],
                'query'       => $options['query'] ?? [],
                'body'        => $options['body'] ?? [],
                'multipart'   => $options['multipart'] ?? [],
            ],
            'log_name' => $routeLogName
        ];

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
        $uri         = $routeConfig['path'];
        $options     = $routeConfig['options'];
        $headers     = $routeConfig['headers'];

        if ( ! empty($routeConfig['log_name'])) {
            $this->requestUtil->requestLogName = $routeConfig['log_name'];
        }

        return $this->requestUtil->sendRequest($method, $uri, $headers, $options);
    }

    public function getRouteNames(): array
    {
        $routeNames = [];
        foreach (static::$routes as $key => $value) {
            $routeNames[] = $key;
        }

        return $routeNames;
    }
}
