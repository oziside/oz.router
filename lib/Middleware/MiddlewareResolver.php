<?php
declare(strict_types=1);
namespace Oz\Router\Middleware;

use DI\Container;
use InvalidArgumentException;
use Oz\Router\Interface\MiddlewareInterface;

final class MiddlewareResolver
{
    public function resolve(array $middlewareClasses, Container $container): array
    {
        $resolved = [];

        foreach ($middlewareClasses as $middlewareClass)
        {
            $middleware = $container->get($middlewareClass);

            if (!$middleware instanceof MiddlewareInterface)
            {
                throw new InvalidArgumentException(
                    sprintf(
                        'Middleware "%s" must implement %s',
                        $middlewareClass,
                        MiddlewareInterface::class
                    )
                );
            }

            $resolved[] = $middleware;
        }

        return $resolved;
    }
}
