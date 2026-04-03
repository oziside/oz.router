<?php
declare(strict_types=1);

namespace Oz\Router\Http\ArgumentResolver;

use Bitrix\Main\HttpRequest;
use DI\Container;
use Oz\Router\Http\RequestMappingException;
use ReflectionClass;

final class ObjectHydrator
{
    public function hydrate(
        string $className,
        array $payload,
        HttpRequest $request,
        Container $container,
        callable $parameterResolver,
        string $path
    ): object
    {
        $reflection = new ReflectionClass($className);

        if (!$reflection->isInstantiable())
        {
            throw new RequestMappingException(sprintf(
                'Class %s is not instantiable for %s.',
                $className,
                $path
            ));
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null)
        {
            return $reflection->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter)
        {
            $arguments[] = $parameterResolver(
                $parameter,
                $request,
                $payload,
                $container,
                false,
                $path . '.' . $parameter->getName()
            );
        }

        return $reflection->newInstanceArgs($arguments);
    }

    public function resolvePayload(string $parameterName, array $input, string $path): array
    {
        if (array_key_exists($parameterName, $input))
        {
            $value = $input[$parameterName];

            if (!is_array($value))
            {
                throw new RequestMappingException(sprintf(
                    'Expected object payload for %s, got %s.',
                    $path,
                    get_debug_type($value)
                ));
            }

            return $value;
        }

        return $input;
    }
}
