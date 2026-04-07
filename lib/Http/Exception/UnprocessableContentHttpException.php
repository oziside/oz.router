<?php
declare(strict_types=1);
namespace Oz\Router\Http\Exception;

use Oz\Router\Http\HttpStatusCode;

class UnprocessableContentHttpException extends HttpException
{
    public function __construct(
        string $message = 'Unprocessable Content',
        ?\Throwable $previous = null,
        int $code = 0
    )
    {
        parent::__construct(HttpStatusCode::UNPROCESSABLE_ENTITY, $message, $previous, $code);
    }
}
