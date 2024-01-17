<?php

declare(strict_types=1);

namespace ApiClientWrapper\Builder\Utils;

use Monolog\Logger;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

class LoggerUtil
{
    public function __construct(
        protected LoggerInterface $logger,
        protected string $baseUri,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function logRequest(
        string $logName,
        RequestInterface $request,
        ?ResponseInterface $response,
        ?string $body = null,
        ?\Throwable $error = null,
    ): void {
        $logLevel = 200;
        $log      = [
            "Request" => [
                'http_version' => $request->getProtocolVersion(),
                'host'         => $this->baseUri,
                'path'         => $request->getUri()->getPath(),
                'headers'      => $request->getHeaders(),
                'body'         => $body ? json_decode($body, true, 512, JSON_THROW_ON_ERROR) : null
            ],
        ];

        if ($response) {
            $log["Response"] = [
                'http_version' => $response->getProtocolVersion(),
                'host'         => $this->baseUri,
                'headers'      => $response->getHeaders(),
                'body'         => json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR),
                'status_code'  => $response->getStatusCode(),
            ];
        }

        if ($error) {
            $logLevel      = 400;
            $log['Errors'] = [
                'message' => $error->getMessage(),
                'code'    => $error->getCode(),
                'file'    => $error->getFile(),
                'line'    => $error->getLine(),
            ];
        }

        $this->logger->log(
            $logLevel,
            $logName,
            $log,
        );
    }
}
