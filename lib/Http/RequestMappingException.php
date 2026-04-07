<?php
declare(strict_types=1);

namespace Oz\Router\Http;

use Oz\Router\Http\Exception\BadRequestHttpException;
use Throwable;

final class RequestMappingException extends BadRequestHttpException
{
    public function __construct(
        string $detail,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            message: $detail,
            previous: $previous
        );
    }
}
