<?php
declare(strict_types=1);
namespace Oz\Router\Middleware;

use Bitrix\Main\HttpRequest;
use Bitrix\Main\HttpResponse;
use Oz\Router\Http\ResponseNormalizer;

final class MiddlewareRunner
{
    private ResponseNormalizer $responseNormalizer;

    public function __construct(ResponseNormalizer $responseNormalizer)
    {
        $this->responseNormalizer = $responseNormalizer;
    }

    public function run(
        array $resolvedMiddlewares,
        HttpRequest $request,
        \Closure $destination
    ): HttpResponse
    {
        $next = function (HttpRequest $request) use ($destination): HttpResponse
        {
            $result = $destination($request);

            return $this->responseNormalizer->normalize($result);
        };

        foreach (array_reverse($resolvedMiddlewares) as $middleware)
        {
            $next = function (HttpRequest $request) use ($middleware, $next): HttpResponse
            {
                $called = false;
                $nextResponse = null;
                $nextWrapper = function (HttpRequest $request) use ($next, &$called, &$nextResponse): HttpResponse
                {
                    $called = true;
                    $nextResponse = $next($request);

                    return $nextResponse;
                };

                $result = $middleware->handle($request, $nextWrapper);

                if ($result === null)
                {
                    if ($called && $nextResponse instanceof HttpResponse)
                    {
                        return $nextResponse;
                    }

                    return $nextWrapper($request);
                }

                return $this->responseNormalizer->normalize($result);
            };
        }

        return $next($request);
    }
}
