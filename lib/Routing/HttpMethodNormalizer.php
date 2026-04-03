<?php
declare(strict_types=1);
namespace Oz\Router\Routing;


final class HttpMethodNormalizer
{
    /**
     * ---
     * 
     * @param array $methods
     * @param string $default
     * 
     * @return string[]
     */
    public function normalizeList(
        array $methods, 
        string $default = 'GET'
    ): array
    {
        $normalized = [];

        foreach ($methods as $method)
        {
            $normalized[] = $this->normalizeOne((string)$method);
        }

        $normalized = array_values(array_unique($normalized));

        if ($normalized === [])
        {
            return [$this->normalizeOne($default)];
        }

        return $normalized;
    }


    /**
     * --
     * 
     * @param string $method
     * @param string $default
     * 
     * @return string
    */
    public function normalizeOne(
        string $method, 
        string $default = 'GET'
    ): string
    {
        $method = strtoupper(trim($method));

        return $method !== '' 
            ? $method 
            : strtoupper(trim($default));
    }
}
