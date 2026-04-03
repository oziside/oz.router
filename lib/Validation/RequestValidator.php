<?php
declare(strict_types=1);

namespace Oz\Router\Validation;

use Bitrix\Main\Error;
use Bitrix\Main\Validation\ValidationService;
use ReflectionParameter;

final class RequestValidator
{
    private ValidationService $validationService;

    public function __construct(?ValidationService $validationService = null)
    {
        $this->validationService = $validationService ?? new ValidationService();
    }

    public function validateHandlerArgument(
        ReflectionParameter $parameter,
        mixed $value,
        bool $validateObject = false
    ): void
    {
        $errors = $this->normalizeErrors(
            $this->validationService->validateParameter($parameter, $value)->getErrors()
        );

        if ($validateObject && is_object($value))
        {
            $errors = array_merge(
                $errors,
                $this->normalizeErrors(
                    $this->validationService->validate($value)->getErrors(),
                    $parameter->getName()
                )
            );
        }

        if ($errors !== [])
        {
            throw new RequestValidationException($errors);
        }
    }

    public function validate(
        ReflectionParameter $parameter,
        mixed $value,
        bool $validateObject = false
    ): void
    {
        $this->validateHandlerArgument($parameter, $value, $validateObject);
    }

    private function normalizeErrors(iterable $errors, ?string $prefix = null): array
    {
        $normalized = [];

        foreach ($errors as $error)
        {
            if (!$error instanceof Error)
            {
                continue;
            }

            $code = $this->normalizeCode($prefix, $error->getCode());
            $item = [
                'message' => $error->getMessage(),
                'code' => $code,
            ];

            if ($code !== null)
            {
                $item['field'] = $code;
            }

            $customData = $error->getCustomData();
            if ($customData !== null)
            {
                $item['customData'] = $customData;
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    private function normalizeCode(?string $prefix, int|string $code): ?string
    {
        $normalizedCode = (string)$code;
        if ($normalizedCode === '' || $normalizedCode === '0')
        {
            $normalizedCode = null;
        }

        if ($prefix === null || $prefix === '')
        {
            return $normalizedCode;
        }

        return $normalizedCode === null
            ? $prefix
            : $prefix . '.' . $normalizedCode;
    }
}
