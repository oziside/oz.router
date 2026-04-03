<?php
declare(strict_types=1);

namespace Oz\Router\Http\ArgumentResolver;

use Oz\Router\Http\RequestMappingException;
use ReflectionEnum;

final class EnumValueCaster
{
    private ScalarValueCaster $scalarValueCaster;

    public function __construct(?ScalarValueCaster $scalarValueCaster = null)
    {
        $this->scalarValueCaster = $scalarValueCaster ?? new ScalarValueCaster();
    }

    public function cast(string $enumClass, mixed $rawValue, string $path): object
    {
        if (is_subclass_of($enumClass, \BackedEnum::class))
        {
            $reflection = new ReflectionEnum($enumClass);
            $backingType = $reflection->getBackingType()?->getName();

            if ($backingType === 'int')
            {
                $rawValue = $this->scalarValueCaster->cast('int', $rawValue, $path);
            }
            elseif ($backingType === 'string')
            {
                $rawValue = $this->scalarValueCaster->cast('string', $rawValue, $path);
            }

            $case = $enumClass::tryFrom($rawValue);

            if ($case !== null)
            {
                return $case;
            }

            throw new RequestMappingException(sprintf(
                'Invalid enum value for %s: %s.',
                $path,
                (string)$rawValue
            ));
        }

        if (!is_string($rawValue))
        {
            throw new RequestMappingException(sprintf(
                'Unit enum value for %s must be a string case name.',
                $path
            ));
        }

        foreach ($enumClass::cases() as $case)
        {
            if ($case->name === $rawValue)
            {
                return $case;
            }
        }

        throw new RequestMappingException(sprintf(
            'Invalid enum case for %s: %s.',
            $path,
            $rawValue
        ));
    }
}
