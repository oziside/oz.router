<?php
declare(strict_types=1);
namespace Oz\Router\Guard;

use DI\Container;
use InvalidArgumentException;
use Oz\Router\Interface\CanActivateInterface;

final class GuardResolver
{
    public function resolve(array $guardClasses, Container $container): array
    {
        $resolved = [];

        foreach ($guardClasses as $guardClass)
        {
            $guard = $container->get($guardClass);

            if (!$guard instanceof CanActivateInterface)
            {
                throw new InvalidArgumentException(
                    sprintf(
                        'Guard "%s" must implement %s',
                        $guardClass,
                        CanActivateInterface::class
                    )
                );
            }

            $resolved[] = $guard;
        }

        return $resolved;
    }
}
