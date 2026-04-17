<?php
declare(strict_types=1);
namespace Oz\Router;

use InvalidArgumentException;


final class RoutesFileLoader
{
    /**
     * Подгружает маршруты, описанные в
     * файле
     * 
     * @param string $filePath
     * @param Router $router
     * 
     * @return void
    */
    public static function load(
        string $filePath, 
        Router $router
    ): void
    {
        $filePath = trim($filePath);

        if ($filePath === '')
        {
            throw new InvalidArgumentException('Routes file path must not be empty');
        }

        if (!is_file($filePath) || !is_readable($filePath))
        {
            throw new InvalidArgumentException('Routes file not found or not readable: ' . $filePath);
        }

        $loaded = require $filePath;

        if (is_callable($loaded))
        {
            $loaded($router);
            return;
        }

        if (is_array($loaded))
        {
            foreach ($loaded as $callback)
            {
                if (!is_callable($callback))
                {
                    throw new InvalidArgumentException('Each route loader in array must be callable');
                }

                $callback($router);
            }
        }
    }
}
