<?php
declare(strict_types=1);
namespace Oz\Router\Routing;

use Oz\Router\Middleware\MiddlewareNormalizer;


final class Route
{
    private array $methods;
    private string $path;
    private mixed $handler;
    private string $regex;
    private array $paramNames;
    private bool $dynamic;
    private Path $pathHelper;
    private HttpMethodNormalizer $httpMethodNormalizer;
    private MiddlewareNormalizer $middlewareNormalizer;
    private array $middlewares;
    private array $withoutMiddlewares;


    public function __construct(
        array $methods, 
        string $path, 
        mixed $handler
    )
    {
        $this->pathHelper           = new Path;
        $this->httpMethodNormalizer = new HttpMethodNormalizer;
        $this->middlewareNormalizer = new MiddlewareNormalizer;
        
        $this->middlewares = [];
        $this->withoutMiddlewares = [];

        $this->methods = $this->httpMethodNormalizer->normalizeList($methods);
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


    public function withMiddleware(
        string|array $middleware
    ): self
    {
        $classes = $this->middlewareNormalizer->normalizeClasses($middleware);
        $this->middlewares = array_values(array_unique(array_merge($this->middlewares, $classes)));

        return $this;
    }


    public function withoutMiddleware(
        string|array $middleware
    ): self
    {
        $classes = $this->middlewareNormalizer->normalizeClasses($middleware);
        $this->withoutMiddlewares = array_values(array_unique(array_merge($this->withoutMiddlewares, $classes)));

        return $this;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getWithoutMiddlewares(): array
    {
        return $this->withoutMiddlewares;
    }
}
