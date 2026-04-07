<?php
declare(strict_types=1);

namespace Oz\Router\Validation;

use Oz\Router\Http\Exception\UnprocessableContentHttpException;

final class RequestValidationException extends UnprocessableContentHttpException
{
    public function __construct(
        private readonly array $errors,
        string $message = 'Request validation failed.'
    ) {
        parent::__construct(
            message: $message,
        );
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
