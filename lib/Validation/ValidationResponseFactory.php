<?php
declare(strict_types=1);

namespace Oz\Router\Validation;

use Bitrix\Main\HttpResponse;
use Oz\Router\Problem\ProblemDetails;
use Oz\Router\Problem\ProblemDetailsResponseFactory;

final class ValidationResponseFactory
{
    public function create(RequestValidationException $exception): HttpResponse
    {
        return (new ProblemDetailsResponseFactory())->createJsonResponse(
            new ProblemDetails(
                type: $exception->getProblemType(),
                title: $exception->getProblemTitle(),
                status: $exception->getProblemStatus(),
                detail: $exception->getMessage(),
                extensions: [
                    'errors' => $exception->getErrors(),
                ]
            )
        );
    }
}
