<?php
declare(strict_types=1);
namespace Oz\Router\Middleware;


final class MiddlewareRegistry
{
    private array $globalMiddlewares;

    public function __construct()
    {
        $this->globalMiddlewares = [];
    }

    public function add(array $classes): void
    {
        $this->globalMiddlewares = array_values(array_unique(array_merge(
            $this->globalMiddlewares,
            $classes
        )));
    }

    public function all(): array
    {
        return $this->globalMiddlewares;
    }
}
