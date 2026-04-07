<?php
declare(strict_types=1);
namespace Oz\Router\Http\Exception;

use Oz\Router\Http\HttpStatusCode;

class BadRequestHttpException extends HttpException
{
    public function __construct(
        string $message = 'Bad Request',
        ?\Throwable $previous = null,
        int $code = 0
    )
    {
        parent::__construct(HttpStatusCode::BAD_REQUEST, $message, $previous, $code);
    }
}
