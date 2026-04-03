<?php
declare(strict_types=1);
namespace Oz\Router\Routing;


final class Path
{
    public function normalize(string $path): string
    {
        $path = trim($path);
        
        if ($path === '')
        {
            return '/';
        }

        return '/' . trim($path, '/');
    }


    /**
     * @return array{0:string,1:array,2:bool}
     */
    public function compile(string $path): array
    {
        if (strpos($path, '{') === false)
        {
            return ['#^' . preg_quote($path, '#') . '$#', [], false];
        }

        $paramNames = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}#',
            static function (array $matches) use (&$paramNames): string {
                $name = $matches[1];
                $pattern = isset($matches[2]) && $matches[2] !== '' ? $matches[2] : '[^/]+';
                $paramNames[] = $name;

                return '(?P<' . $name . '>' . $pattern . ')';
            },
            $path
        );

        if (!is_string($regex))
        {
            $regex = preg_quote($path, '#');
        }

        return [
            '#^' . $regex . '$#',
            $paramNames,
            true
        ];
    }
}
