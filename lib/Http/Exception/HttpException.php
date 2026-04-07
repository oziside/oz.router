<?php
declare(strict_types=1);
namespace Oz\Router\Http\Exception;

use Oz\Router\Http\HttpStatusCode;

class HttpException extends \RuntimeException
{
    public function __construct(
        private readonly HttpStatusCode $statusCode,
        string $message = '',
        ?\Throwable $previous = null,
        int $code = 0,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns the HTTP status code 
     * associated with this exception.
     * 
     * @return int
    */
    public function getStatusCode(): int
    {
        return $this->statusCode->value;
    }
}
