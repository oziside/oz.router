<?php
declare(strict_types=1);

namespace Oz\Router\Problem;

use Bitrix\Main\HttpRequest;

final class RequestFormatResolver
{
    public function prefersProblemJson(HttpRequest $request): bool
    {
        $path = parse_url($request->getRequestUri() ?: '/', PHP_URL_PATH);

        if (is_string($path) && preg_match('#^/api(?:/|$)#', $path) === 1)
        {
            return true;
        }

        $accept = strtolower((string)$request->getHeader('Accept'));
        if (
            str_contains($accept, 'application/problem+json')
            || str_contains($accept, 'application/json')
        ) {
            return true;
        }

        $contentType = strtolower((string)$request->getHeader('Content-Type'));
        if (
            str_contains($contentType, 'application/problem+json')
            || str_contains($contentType, 'application/json')
        ) {
            return true;
        }

        return strtolower((string)$request->getHeader('X-Requested-With')) === 'xmlhttprequest';
    }
}
