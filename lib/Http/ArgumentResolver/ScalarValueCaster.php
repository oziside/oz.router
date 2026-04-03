<?php
declare(strict_types=1);

namespace Oz\Router\Http\ArgumentResolver;

use Oz\Router\Http\RequestMappingException;

final class ScalarValueCaster
{
    public function cast(string $typeName, mixed $rawValue, string $path): mixed
    {
        return match ($typeName) {
            'string' => $this->castString($rawValue, $path),
            'int' => $this->castInt($rawValue, $path),
            'float' => $this->castFloat($rawValue, $path),
            'bool' => $this->castBool($rawValue, $path),
            'array' => $this->castArray($rawValue, $path),
            'mixed' => $rawValue,
            default => throw new RequestMappingException(sprintf(
                'Builtin type %s is not supported for %s.',
                $typeName,
                $path
            )),
        };
    }

    private function castString(mixed $rawValue, string $path): string
    {
        if (is_string($rawValue))
        {
            return $rawValue;
        }

        if (is_int($rawValue) || is_float($rawValue) || is_bool($rawValue))
        {
            return (string)$rawValue;
        }

        throw new RequestMappingException(sprintf(
            'Cannot cast %s to string for %s.',
            get_debug_type($rawValue),
            $path
        ));
    }

    private function castInt(mixed $rawValue, string $path): int
    {
        if (is_int($rawValue))
        {
            return $rawValue;
        }

        if (is_string($rawValue) && preg_match('/^-?\d+$/', $rawValue) === 1)
        {
            return (int)$rawValue;
        }

        throw new RequestMappingException(sprintf(
            'Cannot cast %s to int for %s.',
            get_debug_type($rawValue),
            $path
        ));
    }

    private function castFloat(mixed $rawValue, string $path): float
    {
        if (is_float($rawValue) || is_int($rawValue))
        {
            return (float)$rawValue;
        }

        if (is_string($rawValue) && is_numeric($rawValue))
        {
            return (float)$rawValue;
        }

        throw new RequestMappingException(sprintf(
            'Cannot cast %s to float for %s.',
            get_debug_type($rawValue),
            $path
        ));
    }

    private function castBool(mixed $rawValue, string $path): bool
    {
        if (is_bool($rawValue))
        {
            return $rawValue;
        }

        if (is_int($rawValue) && ($rawValue === 0 || $rawValue === 1))
        {
            return (bool)$rawValue;
        }

        if (is_string($rawValue))
        {
            return match (strtolower(trim($rawValue))) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off' => false,
                default => throw new RequestMappingException(sprintf(
                    'Cannot cast string value "%s" to bool for %s.',
                    $rawValue,
                    $path
                )),
            };
        }

        throw new RequestMappingException(sprintf(
            'Cannot cast %s to bool for %s.',
            get_debug_type($rawValue),
            $path
        ));
    }

    private function castArray(mixed $rawValue, string $path): array
    {
        if (is_array($rawValue))
        {
            return $rawValue;
        }

        throw new RequestMappingException(sprintf(
            'Cannot cast %s to array for %s.',
            get_debug_type($rawValue),
            $path
        ));
    }
}
