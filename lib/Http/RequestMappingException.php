<?php
declare(strict_types=1);

namespace Oz\Router\Http;

use Oz\Router\Problem\ProblemException;
use Throwable;

final class RequestMappingException extends ProblemException
{
    public function __construct(
        string $detail,
        array $extensions = [],
        ?Throwable $previous = null
    ) {
        parent::__construct(
            status: 400,
            title: 'Bad Request',
            detail: $detail,
            type: 'urn:oz-router:problem:request-mapping',
            extensions: $extensions,
            previous: $previous
        );
    }
}
