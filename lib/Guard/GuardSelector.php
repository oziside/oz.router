<?php
declare(strict_types=1);
namespace Oz\Router\Guard;

use Oz\Router\Routing\{
    Route,
    RoutePolicy
};

final class GuardSelector
{
    public function select(
        RoutePolicy $globalPolicy,
        Route $route
    ): array
    {
        $routePolicy = $route->getPolicy();

        $classes = array_values(array_unique(array_merge(
            $globalPolicy->guards()->included(),
            $routePolicy->guards()->included()
        )));

        $excluded = array_flip(
            $routePolicy->guards()->excluded()
        );

        return array_values(array_filter(
            $classes,
            static fn (string $className): bool => !isset($excluded[$className])
        ));
    }
}
