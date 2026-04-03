<?php
declare(strict_types=1);

namespace Oz\Router\Http\ArgumentResolver;

use Bitrix\Main\HttpRequest;
use DI\Container;
use Oz\Router\Http\RequestMappingException;
use ReflectionParameter;

final class ParameterResolver
{
    private TypeResolver $typeResolver;

    public function __construct(?TypeResolver $typeResolver = null)
    {
        $this->typeResolver = $typeResolver ?? new TypeResolver();
    }

    public function resolve(
        ReflectionParameter $parameter,
        HttpRequest $request,
        array $input,
        Container $container,
        bool $allowContainerFallback,
        string $path
    ): mixed
    {
        $type = $parameter->getType();

        if ($type === null)
        {
            if ($this->hasInputValue($input, $parameter->getName()))
            {
                return $input[$parameter->getName()];
            }

            if ($parameter->isDefaultValueAvailable())
            {
                return $parameter->getDefaultValue();
            }

            throw new RequestMappingException(sprintf(
                'Cannot resolve parameter %s: no type and no input value.',
                $path
            ));
        }

        return $this->typeResolver->resolve(
            $type,
            $parameter,
            $request,
            $input,
            $container,
            $allowContainerFallback,
            $path,
            [$this, 'resolve']
        );
    }

    private function hasInputValue(array $input, string $name): bool
    {
        return array_key_exists($name, $input);
    }
}
