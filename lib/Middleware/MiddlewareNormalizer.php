<?php
declare(strict_types=1);
namespace Oz\Router\Middleware;


final class MiddlewareNormalizer
{
    /**
     * Normalizes middleware classes
     * 
     * @param string|string[] $middleware
     * 
     * @return string[]
    */
    public function normalizeClasses(
        string|array $middleware
    ): array
    {
        $normalized = [];
        $classList  = is_array($middleware) 
            ? $middleware 
            : [$middleware];


        foreach ($classList as $className)
        {
            $className = trim($className);

            if($className === '')
                continue;

            $normalized[] = $className;
        }

        return array_values(
            array_unique($normalized)
        );
    }
}
