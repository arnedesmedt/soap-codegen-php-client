<?php

namespace Phpro\SoapClient\Soap;

use Http\Client\Common\Exception\ClientErrorException;
use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ClientErrorPlugin implements Plugin
{
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $promise = $next($request);

        return $promise->then(
            static function (ResponseInterface $response) use ($request) {
                if ($response->getStatusCode() === 401) {
                    throw new ClientErrorException('Unauthorized', $request, $response);
                }

                return $response;
            },
        );
    }
}
