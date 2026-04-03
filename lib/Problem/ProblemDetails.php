<?php
declare(strict_types=1);

namespace Oz\Router\Problem;

final class ProblemDetails
{
    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly int $status,
        private readonly string $detail = '',
        private readonly string $instance = '',
        private readonly array $extensions = [],
        private readonly array $headers = []
    ) {
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function toArray(): array
    {
        $payload = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
        ];

        if ($this->detail !== '')
        {
            $payload['detail'] = $this->detail;
        }

        if ($this->instance !== '')
        {
            $payload['instance'] = $this->instance;
        }

        foreach ($this->extensions as $key => $value)
        {
            if (in_array($key, ['type', 'title', 'status', 'detail', 'instance'], true))
            {
                continue;
            }

            $payload[$key] = $value;
        }

        return $payload;
    }
}
