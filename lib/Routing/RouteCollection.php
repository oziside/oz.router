<?php
declare(strict_types=1);
namespace Oz\Router\Routing;


final class RouteCollection
{
    private array $staticRoutes = [];
    private array $dynamicRoutes = [];


    /**
     * Добавляет маршрут в коллекцию.
     * 
     * @param Route $route
     * 
     * @return void 
    */
    public function add(Route $route): void
    {
        $methods = $route->getMethods();

        foreach ($methods as $method)
        {
            if ($route->isDynamic())
            {
                $this->dynamicRoutes[$method][] = $route;
                continue;
            }

            $this->staticRoutes[$method][$route->getPath()] = $route;
        }
    }


    /**
     * Сопостовляем http метод и маршрут, по которым
     * идет обращение к серверу. Возвращает маршрут и
     * параметры запроса
     * 
     * @param string $method
     * @param string $path
     * 
     * @return ?array
    */
    public function match(
        string $method, 
        string $path
    ): ?array
    {
        foreach ([$method, 'ANY'] as $candidateMethod)
        {
            if (isset($this->staticRoutes[$candidateMethod][$path]))
            {
                return [$this->staticRoutes[$candidateMethod][$path], []];
            }

            if (!isset($this->dynamicRoutes[$candidateMethod]))
            {
                continue;
            }

            foreach ($this->dynamicRoutes[$candidateMethod] as $route)
            {
                $params = $route->matchPath($path);

                if ($params !== null)
                    return [$route, $params];
            }
        }

        return null;
    }


    /**
     * Ищем переход по тому же эндпоинту, но с другим методом
     * Http запроса  
     * 
     * @param string $path
     * @param string $requestMethod
     * 
     * @return bool
    */
    public function allowsPath(
        string $path, 
        string $requestMethod
    ): bool
    {
        return $this->getAllowedMethods($path, $requestMethod) !== [];
    }

    public function getAllowedMethods(string $path, ?string $requestMethod = null): array
    {
        $allowedMethods = [];

        foreach ($this->staticRoutes as $method => $routes)
        {
            if($method === $requestMethod || $method === 'ANY')
                continue;

            if (isset($routes[$path]))
                $allowedMethods[] = $method;
        }

        foreach ($this->dynamicRoutes as $method => $routes)
        {
            if ($method === $requestMethod || $method === 'ANY')
                continue;

            foreach ($routes as $route)
            {
                if ($route->matchPath($path) !== null)
                {
                    $allowedMethods[] = $method;
                    break;
                }
            }
        }

        $allowedMethods = array_values(array_unique($allowedMethods));
        sort($allowedMethods);

        return $allowedMethods;
    }
}
