<?php
declare(strict_types=1);

namespace Oz\Router\Http\ArgumentResolver;

use Bitrix\Main\HttpRequest;

final class ArgumentInputBuilder
{
    public function build(HttpRequest $request, array $routeParams): array
    {
        return array_replace(
            $request->getQueryList()->toArray(),
            $request->getPostList()->toArray(),
            $request->getJsonList()->toArray(),
            $routeParams
        );
    }
}
