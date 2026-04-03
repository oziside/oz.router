<?php
declare(strict_types=1);

namespace Oz\Router\Problem;

use RuntimeException;
use Throwable;

class ProblemException extends RuntimeException implements ProblemExceptionInterface
{
    public function __construct(
        private readonly int $status,
        private readonly string $title,
        string $detail = '',
        private readonly string $type = 'about:blank',
        private readonly array $extensions = [],
        private readonly array $headers = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($detail !== '' ? $detail : $title, 0, $previous);
    }

    public function getProblemType(): string
    {
        return $this->type;
    }

    public function getProblemTitle(): string
    {
        return $this->title;
    }

    public function getProblemStatus(): int
    {
        return $this->status;
    }

    public function getProblemExtensions(): array
    {
        return $this->extensions;
    }

    public function getProblemHeaders(): array
    {
        return $this->headers;
    }
}
