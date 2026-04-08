<?php
declare(strict_types=1);
namespace Oz\Router\Validation;

use ReflectionParameter;
use Bitrix\Main\Error;
use Bitrix\Main\Validation\ValidationService;


final class RequestValidator
{
    private ValidationService $validationService;

    public function __construct()
    {
        $this->validationService = new ValidationService;
    }

    /**
     * Validates a request parameter against its defined validation rules.
     * 
     * @param ReflectionParameter $parameter
     * @param mixed $value
     * @param bool $validateObject
     * 
     * @return void
    */
    public function validate(
        ReflectionParameter $parameter,
        mixed $value,
        bool $validateObject = false
    ): void
    {
        $errors = $this->mapErrors(
            $this->validationService->validateParameter($parameter, $value)->getErrors()
        );

        if ($validateObject && is_object($value))
        {
            $errors = array_merge(
                $errors,
                $this->mapErrors(
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


    /**
     * Normalizes a list of validation errors.
     * 
     * @param iterable $errors
     * @param string|null $prefix
     * 
     * @return array
    */
    private function mapErrors(
        iterable $errors, 
        ?string $prefix = null
    ): array
    {
        $normalized = [];

        foreach ($errors as $error)
        {
            if (!$error instanceof Error)
            {
                continue;
            }

            $field = $this->buildErrorField($prefix, $error->getCode());
            $item = [
                'message' => $error->getMessage(),
                'field' => $field,
            ];

            $normalized[] = $item;
        }

        return $normalized;
    }


    /**
     * Normalizes an error code by combining it with an optional prefix.
     * 
     * @param string|null $prefix
     * @param int|string $code
     * 
     * @return string|null
    */
    private function buildErrorField(
        ?string $prefix, 
        int|string $code
    ): ?string
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
