<?php
declare(strict_types=1);

namespace Oz\Router\Http\ArgumentResolver;

use Bitrix\Main\HttpRequest;
use DI\Container;
use Oz\Router\Http\RequestMappingException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

final class TypeResolver
{
    private ScalarValueCaster $scalarValueCaster;
    private EnumValueCaster $enumValueCaster;
    private ObjectHydrator $objectHydrator;
    private HydratableTypeInspector $hydratableTypeInspector;

    public function __construct(
        ?ScalarValueCaster $scalarValueCaster = null,
        ?EnumValueCaster $enumValueCaster = null,
        ?ObjectHydrator $objectHydrator = null,
        ?HydratableTypeInspector $hydratableTypeInspector = null
    ) {
        $this->scalarValueCaster = $scalarValueCaster ?? new ScalarValueCaster();
        $this->enumValueCaster = $enumValueCaster ?? new EnumValueCaster($this->scalarValueCaster);
        $this->objectHydrator = $objectHydrator ?? new ObjectHydrator();
        $this->hydratableTypeInspector = $hydratableTypeInspector ?? new HydratableTypeInspector();
    }

    public function resolve(
        ReflectionType $type,
        ReflectionParameter $parameter,
        HttpRequest $request,
        array $input,
        Container $container,
        bool $allowContainerFallback,
        string $path,
        callable $parameterResolver
    ): mixed
    {
        if ($type instanceof ReflectionNamedType)
        {
            return $this->resolveNamedType(
                $type,
                $parameter,
                $request,
                $input,
                $container,
                $allowContainerFallback,
                $path,
                $parameterResolver
            );
        }

        if ($type instanceof ReflectionUnionType)
        {
            return $this->resolveUnionType(
                $type,
                $parameter,
                $request,
                $input,
                $container,
                $allowContainerFallback,
                $path,
                $parameterResolver
            );
        }

        if ($type instanceof ReflectionIntersectionType)
        {
            throw new RequestMappingException(sprintf(
                'Intersection types are not supported for parameter %s.',
                $path
            ));
        }

        throw new RequestMappingException(sprintf(
            'Unsupported parameter type for %s.',
            $path
        ));
    }

    private function resolveNamedType(
        ReflectionNamedType $type,
        ReflectionParameter $parameter,
        HttpRequest $request,
        array $input,
        Container $container,
        bool $allowContainerFallback,
        string $path,
        callable $parameterResolver
    ): mixed
    {
        if (
            $this->hasInputValue($input, $parameter->getName())
            && $input[$parameter->getName()] === null
            && $parameter->allowsNull()
        )
        {
            return null;
        }

        $typeName = $type->getName();

        if ($type->isBuiltin())
        {
            return $this->resolveBuiltinType($typeName, $parameter, $input, $path);
        }

        if (is_a($typeName, HttpRequest::class, true))
        {
            return $request;
        }

        if (enum_exists($typeName))
        {
            return $this->resolveEnumType($typeName, $parameter, $input, $path);
        }

        if ($this->hydratableTypeInspector->isHydratableObject($typeName))
        {
            $payload = $this->objectHydrator->resolvePayload($parameter->getName(), $input, $path);

            return $this->objectHydrator->hydrate(
                $typeName,
                $payload,
                $request,
                $container,
                $parameterResolver,
                $path
            );
        }

        if ($allowContainerFallback)
        {
            return $container->get($typeName);
        }

        throw new RequestMappingException(sprintf(
            'Cannot resolve object parameter %s of type %s.',
            $path,
            $typeName
        ));
    }

    private function resolveUnionType(
        ReflectionUnionType $type,
        ReflectionParameter $parameter,
        HttpRequest $request,
        array $input,
        Container $container,
        bool $allowContainerFallback,
        string $path,
        callable $parameterResolver
    ): mixed
    {
        $allowsNull = false;
        $lastError = null;

        if (
            $this->hasInputValue($input, $parameter->getName())
            && $input[$parameter->getName()] === null
        )
        {
            foreach ($type->getTypes() as $namedType)
            {
                if ($namedType->getName() === 'null')
                {
                    return null;
                }
            }
        }

        foreach ($type->getTypes() as $namedType)
        {
            if ($namedType->getName() === 'null')
            {
                $allowsNull = true;
                continue;
            }

            try
            {
                return $this->resolveNamedType(
                    $namedType,
                    $parameter,
                    $request,
                    $input,
                    $container,
                    $allowContainerFallback,
                    $path,
                    $parameterResolver
                );
            }
            catch (RequestMappingException $exception)
            {
                $lastError = $exception;
            }
        }

        if ($allowsNull && !$this->hasInputValue($input, $parameter->getName()))
        {
            return null;
        }

        if ($parameter->isDefaultValueAvailable())
        {
            return $parameter->getDefaultValue();
        }

        throw new RequestMappingException(
            $lastError?->getMessage()
            ?? sprintf('Cannot resolve union parameter %s.', $path)
        );
    }

    private function resolveBuiltinType(
        string $typeName,
        ReflectionParameter $parameter,
        array $input,
        string $path
    ): mixed
    {
        if (!$this->hasInputValue($input, $parameter->getName()))
        {
            if ($parameter->isDefaultValueAvailable())
            {
                return $parameter->getDefaultValue();
            }

            if ($parameter->allowsNull())
            {
                return null;
            }

            throw new RequestMappingException(sprintf(
                'Missing value for parameter %s.',
                $path
            ));
        }

        return $this->scalarValueCaster->cast(
            $typeName,
            $input[$parameter->getName()],
            $path
        );
    }

    private function resolveEnumType(
        string $enumClass,
        ReflectionParameter $parameter,
        array $input,
        string $path
    ): mixed
    {
        if (!$this->hasInputValue($input, $parameter->getName()))
        {
            if ($parameter->isDefaultValueAvailable())
            {
                return $parameter->getDefaultValue();
            }

            if ($parameter->allowsNull())
            {
                return null;
            }

            throw new RequestMappingException(sprintf(
                'Missing value for enum parameter %s.',
                $path
            ));
        }

        return $this->enumValueCaster->cast($enumClass, $input[$parameter->getName()], $path);
    }

    private function hasInputValue(array $input, string $name): bool
    {
        return array_key_exists($name, $input);
    }
}
