<?php
declare(strict_types=1);
namespace Oz\Router\Routing;

final class RouteGroup
{
    private array $scopes;

    public function __construct()
    {
        $this->scopes = [
            [
                'prefix' => '/',
                'policy' => new RoutePolicy(),
            ],
        ];
    }

    public function push(string $prefix): void
    {
        $currentPrefix = $this->getCurrentPrefix();
        $nextPrefix = $this->joinPaths($currentPrefix, $prefix);

        $this->scopes[] = [
            'prefix' => $nextPrefix,
            'policy' => new RoutePolicy(),
        ];
    }

    public function pop(): void
    {
        if (count($this->scopes) <= 1)
        {
            return;
        }

        array_pop($this->scopes);
    }

    public function isInsideGroup(): bool
    {
        return count($this->scopes) > 1;
    }

    public function resolvePath(string $path): string
    {
        return $this->joinPaths($this->getCurrentPrefix(), $path);
    }

    public function currentPolicy(): RoutePolicy
    {
        $index = array_key_last($this->scopes);

        if ($index === null)
        {
            return new RoutePolicy();
        }

        $policy = $this->scopes[$index]['policy'] ?? null;

        if ($policy instanceof RoutePolicy)
        {
            return $policy;
        }

        return new RoutePolicy();
    }

    public function effectivePolicy(): RoutePolicy
    {
        $policy = new RoutePolicy();

        foreach ($this->scopes as $scope)
        {
            $scopePolicy = $scope['policy'] ?? null;

            if ($scopePolicy instanceof RoutePolicy)
            {
                $policy->merge($scopePolicy);
            }
        }

        return $policy;
    }

    private function getCurrentPrefix(): string
    {
        $lastScope = end($this->scopes);
        $last = is_array($lastScope)
            ? ($lastScope['prefix'] ?? null)
            : null;

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
