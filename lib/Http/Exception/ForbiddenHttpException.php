<?php
declare(strict_types=1);
namespace Oz\Router\Http\Exception;

use Oz\Router\Http\HttpStatusCode;

class ForbiddenHttpException extends HttpException
{
    public function __construct(
        string $message = 'Forbidden',
        ?\Throwable $previous = null,
        int $code = 0
    )
    {
        parent::__construct(HttpStatusCode::FORBIDDEN, $message, $previous, $code);
    }
}
