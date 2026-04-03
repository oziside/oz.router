<?php
declare(strict_types=1);

namespace Oz\Router\Problem;

interface ProblemExceptionInterface
{
    public function getProblemType(): string;

    public function getProblemTitle(): string;

    public function getProblemStatus(): int;

    public function getProblemExtensions(): array;

    public function getProblemHeaders(): array;
}
