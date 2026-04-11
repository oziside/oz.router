<?php
declare(strict_types=1);
namespace Oz\Router\Routing;


final class Route
{
    /**
     * The HTTP methods the route responds to.
    */
    private array $methods;

    /**
     * The path pattern of the route, e.g. /users/{id}
    */
    private string $path;

    /**
     * The handler for the route, 
     * which can be a callable or a class method string like 'UserController@show'
    */
    private mixed $handler;

    private string $regex;
    private array $paramNames;
    private bool $dynamic;
    
    private Path $pathHelper;
    private RoutePolicy $policy;


    public function __construct(
        array $methods, 
        string $path, 
        mixed $handler
    )
    {
        $this->pathHelper           = new Path;
        $this->policy = new RoutePolicy();

        $this->methods = $this->normalizeMethods($methods);
        $this->path    = $this->pathHelper->normalize($path);
        $this->handler = $handler;

        [
            $this->regex, 
            $this->paramNames, 
            $this->dynamic
        ] = $this->pathHelper->compile($this->path);
    }


    /**
     * Возвращает список http методов, 
     * который может обработать маршрут
     * 
     * @return string[]
    */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Возвращает ендпоинт маршрута
     * 
     * @return string
    */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Возвращает обработчик (класс/функция) маршрута
     * 
     * @return string
    */
    public function getHandler(): mixed
    {
        return $this->handler;
    }


    /**
     * Возвращает признак, является ли 
     * этот муршрут динамическим, например:
     * по маске /path/to/{id}
     * 
     * @return bool
    */
    public function isDynamic(): bool
    {
        return $this->dynamic;
    }

    
    public function matchPath(string $path): ?array
    {
        if (!$this->dynamic)
        {
            return $path === $this->path 
                ? [] 
                : null;
        }

        if (!preg_match($this->regex, $path, $matches))
        {
            return null;
        }

        $params = [];

        foreach ($this->paramNames as $name)
        {
            $params[$name] = isset($matches[$name]) 
                ? urldecode((string)$matches[$name]) 
                : null;
        }

        return $params;
    }

    private function normalizeMethods(
        array $methods,
        string $default = 'GET'
    ): array
    {
        $normalized = [];

        foreach ($methods as $method)
        {
            $normalized[] = $this->normalizeMethod(
                (string)$method,
                $default
            );
        }

        $normalized = array_values(array_unique($normalized));

        if ($normalized === [])
        {
            return [$this->normalizeMethod($default, $default)];
        }

        return $normalized;
    }

    private function normalizeMethod(
        string $method,
        string $default = 'GET'
    ): string
    {
        $method = strtoupper(trim($method));

        return $method !== ''
            ? $method
            : strtoupper(trim($default));
    }


    public function middleware(
        string|array $middlewares
    ): self
    {
        $this->policy->middlewares()->add($middlewares);

        return $this;
    }

    public function guard(
        string|array $guards
    ): self
    {
        $this->policy->guards()->add($guards);

        return $this;
    }

    public function exceptMiddleware(
        string|array $middlewares
    ): self
    {
        $this->policy->middlewares()->except($middlewares);
        
        return $this;
    }

    public function exceptGuard(
        string|array $guards
    ): self
    {
        $this->policy->guards()->except($guards);

        return $this;
    }

    public function applyPolicy(
        RoutePolicy $policy
    ): self
    {
        $this->policy->merge($policy);

        return $this;
    }

    public function getPolicy(): RoutePolicy
    {
        return $this->policy;
    }
}
