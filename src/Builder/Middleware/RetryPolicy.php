<?php

declare(strict_types=1);

namespace ApiClientWrapper\Builder\Middleware;

use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

class RetryPolicy
{
    public static function createRetryPolicy(int $maxRetries, int $retryDelay): callable
    {
        return Middleware::retry(static::retryPolicy($maxRetries), static::retryDelay($retryDelay));
    }

    /**
     * @param int $maxRetries
     *
     * @return Closure(int,RequestInterface,ResponseInterface): bool
     */
    protected static function retryPolicy(int $maxRetries): \Closure
    {
        return static function (int $retries, RequestInterface $request, ResponseInterface $response = null) use (
            $maxRetries,
        ) {
            if ($retries >= $maxRetries) {
                return false;
            }

            if ($response && $response->getStatusCode() >= 500) {
                return true;
            }

            return false;
        };
    }

    /**
     * @param int $delay
     *
     * @return Closure(): int
     */
    protected static function retryDelay(int $delay): \Closure
    {
        return static function ($maxRetries) use ($delay) {
            return $delay * $maxRetries;
        };
    }
}
