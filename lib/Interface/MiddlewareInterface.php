<?php
declare(strict_types=1);
namespace Oz\Router\Interface;

use Bitrix\Main\{
    HttpRequest,
    HttpResponse
};


interface MiddlewareInterface
{
    public function handle(HttpRequest $request, \Closure $next): ?HttpResponse;
}