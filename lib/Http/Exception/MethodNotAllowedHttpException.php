<?php
declare(strict_types=1);
namespace Oz\Router\Http\Exception;

use Oz\Router\Http\HttpStatusCode;

class MethodNotAllowedHttpException extends HttpException
{
    public function __construct(
        string $message = 'Method Not Allowed',
        ?\Throwable $previous = null, 
        int $code = 0
    )
    {
        parent::__construct(HttpStatusCode::METHOD_NOT_ALLOWED, $message, $previous, $code);
    }
}
