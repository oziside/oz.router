<?php
declare(strict_types=1);
namespace Oz\Router\Http\Exception;

use Oz\Router\Http\HttpStatusCode;

class NotFoundHttpException extends HttpException
{
    public function __construct(
        string $message = 'Not Found',
        ?\Throwable $previous = null, 
        int $code = 0
    )
    {
        parent::__construct(HttpStatusCode::NOT_FOUND, $message, $previous, $code);
    }
}
