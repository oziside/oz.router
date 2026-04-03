<?php
declare(strict_types=1);
namespace Oz\Router\Routing;

final class RouteGroup
{
    private array $prefixes;
    private array $groupMiddlewares;
    private array $groupWithoutMiddlewares;

    public function __construct()
    {
        $this->prefixes = ['/'];
        $this->groupMiddlewares = [[]];
        $this->groupWithoutMiddlewares = [[]];
    }

    public function push(string $prefix): void
    {
        $currentPrefix = $this->getCurrentPrefix();
        $nextPrefix = $this->joinPaths($currentPrefix, $prefix);

        $this->prefixes[] = $nextPrefix;
        $this->groupMiddlewares[] = [];
        $this->groupWithoutMiddlewares[] = [];
    }

    public function pop(): void
    {
        if (count($this->prefixes) <= 1)
        {
            return;
        }

        array_pop($this->prefixes);
        array_pop($this->groupMiddlewares);
        array_pop($this->groupWithoutMiddlewares);
    }

    public function isInsideGroup(): bool
    {
        return count($this->prefixes) > 1;
    }

    public function resolvePath(string $path): string
    {
        return $this->joinPaths($this->getCurrentPrefix(), $path);
    }

    public function addGroupMiddlewares(array $classes): void
    {
        $index = array_key_last($this->groupMiddlewares);
        if ($index === null)
        {
            return;
        }

        $this->groupMiddlewares[$index] = array_values(array_unique(array_merge(
            $this->groupMiddlewares[$index],
            $classes
        )));
    }

    public function addGroupWithoutMiddlewares(array $classes): void
    {
        $index = array_key_last($this->groupWithoutMiddlewares);
        if ($index === null)
        {
            return;
        }

        $this->groupWithoutMiddlewares[$index] = array_values(array_unique(array_merge(
            $this->groupWithoutMiddlewares[$index],
            $classes
        )));
    }

    public function getCurrentMiddlewares(): array
    {
        $merged = [];

        foreach ($this->groupMiddlewares as $middlewares)
        {
            $merged = array_merge($merged, $middlewares);
        }

        return array_values(array_unique($merged));
    }

    public function getCurrentWithoutMiddlewares(): array
    {
        $merged = [];

        foreach ($this->groupWithoutMiddlewares as $middlewares)
        {
            $merged = array_merge($merged, $middlewares);
        }

        return array_values(array_unique($merged));
    }

    private function getCurrentPrefix(): string
    {
        $last = end($this->prefixes);

        if (!is_string($last) || $last === '')
        {
            return '/';
        }

        return $last;
    }

    private function joinPaths(string $left, string $right): string
    {
        $left = trim($left, '/');
        $right = trim($right, '/');

        if ($left === '' && $right === '')
        {
            return '/';
        }

        if ($left === '')
        {
            return '/' . $right;
        }

        if ($right === '')
        {
            return '/' . $left;
        }

        return '/' . $left . '/' . $right;
    }
}
