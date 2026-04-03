<?php
declare(strict_types=1);

namespace Oz\Router\Http\ArgumentResolver;

use Bitrix\Main\HttpRequest;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

final class HydratableTypeInspector
{
    public function isHydratableObject(string $className, array $visited = []): bool
    {
        if (!class_exists($className))
        {
            return false;
        }

        if (isset($visited[$className]))
        {
            return false;
        }

        $visited[$className] = true;

        $reflection = new ReflectionClass($className);

        if (!$reflection->isInstantiable())
        {
            return false;
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null)
        {
            return false;
        }

        foreach ($constructor->getParameters() as $parameter)
        {
            $type = $parameter->getType();

            if ($type === null)
            {
                return false;
            }

            if (!$this->isHydratableType($type, $visited))
            {
                return false;
            }
        }

        return $constructor->getParameters() !== [];
    }

    public function isHydratableType(ReflectionType $type, array $visited = []): bool
    {
        if ($type instanceof ReflectionUnionType)
        {
            foreach ($type->getTypes() as $namedType)
            {
                if ($namedType->getName() === 'null')
                {
                    continue;
                }

                if (!$this->isHydratableType($namedType, $visited))
                {
                    return false;
                }
            }

            return true;
        }

        if (!$type instanceof ReflectionNamedType)
        {
            return false;
        }

        if ($type->isBuiltin())
        {
            return in_array($type->getName(), ['string', 'int', 'float', 'bool', 'array'], true);
        }

        $typeName = $type->getName();

        if (is_a($typeName, HttpRequest::class, true))
        {
            return true;
        }

        if (enum_exists($typeName))
        {
            return true;
        }

        return $this->isHydratableObject($typeName, $visited);
    }

    public function shouldValidateObjectParameter(?ReflectionType $type): bool
    {
        if ($type instanceof ReflectionNamedType)
        {
            if ($type->isBuiltin())
            {
                return false;
            }

            return $this->isHydratableObject($type->getName());
        }

        if ($type instanceof ReflectionUnionType)
        {
            foreach ($type->getTypes() as $namedType)
            {
                if ($namedType->getName() === 'null' || $namedType->isBuiltin())
                {
                    continue;
                }

                if ($this->isHydratableObject($namedType->getName()))
                {
                    return true;
                }
            }
        }

        return false;
    }
}
