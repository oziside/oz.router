<?php
declare(strict_types=1);
namespace Oz\Router\Http\Exception;

use Oz\Router\Http\HttpStatusCode;

class InternalServerErrorHttpException extends HttpException
{
    public function __construct(
        string $message = 'Internal Server Error',
        ?\Throwable $previous = null,
        int $code = 0
    )
    {
        parent::__construct(HttpStatusCode::INTERNAL_SERVER_ERROR, $message, $previous, $code);
    }
}
