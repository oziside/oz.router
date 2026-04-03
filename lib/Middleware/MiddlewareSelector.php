<?php
declare(strict_types=1);
namespace Oz\Router\Middleware;

use Oz\Router\Routing\Route;

final class MiddlewareSelector
{
    private MiddlewareRegistry $registry;

    public function __construct(MiddlewareRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function select(Route $route): array
    {
        $classes = array_values(array_unique(array_merge(
            $this->registry->all(),
            $route->getMiddlewares()
        )));

        $excluded = array_flip($route->getWithoutMiddlewares());

        return array_values(array_filter(
            $classes,
            static fn (string $className): bool => !isset($excluded[$className])
        ));
    }
}
