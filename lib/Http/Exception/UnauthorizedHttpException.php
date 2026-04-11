<?php
declare(strict_types=1);
namespace Oz\Router\Http\Exception;

use Oz\Router\Http\HttpStatusCode;

class UnauthorizedHttpException extends HttpException
{
    public function __construct(
        string $message = 'Unauthorized',
        ?\Throwable $previous = null,
        int $code = 0
    )
    {
        parent::__construct(HttpStatusCode::UNAUTHORIZED, $message, $previous, $code);
    }
}
