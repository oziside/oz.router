<?php
declare(strict_types=1);
namespace Oz\Router\Routing;


final class RouteRegistrar
{
    private RouteCollection $routes;
    private RouteGroup $routeGroup;

    public function __construct(
        RouteCollection $routes,
        RouteGroup $routeGroup
    )
    {
        $this->routes = $routes;
        $this->routeGroup = $routeGroup;
    }

    public function add(
        string|array $methods,
        string $path,
        callable|array|string $handler
    ): Route
    {
        $fullPath   = $this->routeGroup->resolvePath($path);
        $arrMethods = is_array($methods) ? $methods : [$methods];

        $route = new Route($arrMethods, $fullPath, $handler);

        $route->applyPolicy(
            $this->routeGroup->effectivePolicy()
        );

        
        $this->routes->add($route);

        return $route;
    }
}
