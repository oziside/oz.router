<?php
declare(strict_types=1);

namespace Oz\Router\Validation;

use Oz\Router\Problem\ProblemException;

final class RequestValidationException extends ProblemException
{
    public function __construct(
        private readonly array $errors,
        string $message = 'Request validation failed.'
    ) {
        parent::__construct(
            status: 422,
            title: 'Unprocessable Content',
            detail: $message,
            type: 'urn:oz-router:problem:validation-failed',
            extensions: [
                'errors' => $errors,
            ]
        );
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
