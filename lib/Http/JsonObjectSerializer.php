<?php
declare(strict_types=1);
namespace Oz\Router\Http;

use Bitrix\Main\Type\Contract\Arrayable;
use Oz\Router\Attribute\JsonResource;

final class JsonObjectSerializer
{
    public function supports(object $object): bool
    {
        $reflection = new \ReflectionClass($object);

        return $reflection->getAttributes(JsonResource::class) !== [];
    }

    public function normalize(mixed $value): mixed
    {
        return $this->normalizeValue($value, false, []);
    }

    private function normalizeValue(
        mixed $value,
        bool $serializePlainObjects,
        array $processingIds
    ): mixed
    {
        if ($value === null || is_scalar($value))
        {
            return $value;
        }

        if ($value instanceof \BackedEnum)
        {
            return $value->value;
        }

        if ($value instanceof \DateTimeInterface)
        {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof \JsonSerializable)
        {
            return $this->normalizeValue(
                $value->jsonSerialize(),
                $serializePlainObjects,
                $processingIds
            );
        }

        if ($value instanceof Arrayable)
        {
            return $this->normalizeValue(
                $value->toArray(),
                $serializePlainObjects,
                $processingIds
            );
        }

        if ($value instanceof \Traversable)
        {
            $value = iterator_to_array($value);
        }

        if (is_array($value))
        {
            foreach ($value as $key => $item)
            {
                $value[$key] = $this->normalizeValue(
                    $item,
                    $serializePlainObjects,
                    $processingIds
                );
            }

            return $value;
        }

        if (!is_object($value))
        {
            return $value;
        }

        if (!$serializePlainObjects && !$this->supports($value))
        {
            return $value;
        }

        return $this->normalizeObject($value, $processingIds);
    }

    private function normalizeObject(object $object, array $processingIds): array
    {
        $objectId = spl_object_id($object);

        if (isset($processingIds[$objectId]))
        {
            throw new \LogicException(sprintf(
                'Circular reference detected while serializing "%s".',
                $object::class
            ));
        }

        $processingIds[$objectId] = true;

        $result = [];
        $reflection = new \ReflectionClass($object);

        do
        {
            foreach ($reflection->getProperties() as $property)
            {
                if ($property->isStatic())
                {
                    continue;
                }

                if ($property->getDeclaringClass()->getName() !== $reflection->getName())
                {
                    continue;
                }

                if (!$property->isInitialized($object))
                {
                    continue;
                }

                $result[$property->getName()] = $this->normalizeValue(
                    $property->getValue($object),
                    true,
                    $processingIds
                );
            }

            $reflection = $reflection->getParentClass();
        }
        while ($reflection !== false);

        return $result;
    }
}
