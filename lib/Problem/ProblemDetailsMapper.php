<?php
declare(strict_types=1);

namespace Oz\Router\Problem;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\HttpRequest;
use Throwable;

final class ProblemDetailsMapper
{
    public function map(Throwable $exception, HttpRequest $request): ProblemDetails
    {
        if ($exception instanceof ProblemExceptionInterface)
        {
            return new ProblemDetails(
                type: $exception->getProblemType(),
                title: $exception->getProblemTitle(),
                status: $exception->getProblemStatus(),
                detail: $exception->getMessage(),
                instance: $this->resolveInstance($request),
                extensions: $exception->getProblemExtensions(),
                headers: $exception->getProblemHeaders()
            );
        }

        $detail = 'An internal server error occurred.';
        $extensions = [];

        if ($this->isDebugEnabled())
        {
            $detail = $exception->getMessage() !== ''
                ? $exception->getMessage()
                : $detail;

            $extensions = [
                'exception' => $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return new ProblemDetails(
            type: 'about:blank',
            title: 'Internal Server Error',
            status: 500,
            detail: $detail,
            instance: $this->resolveInstance($request),
            extensions: $extensions
        );
    }

    private function resolveInstance(HttpRequest $request): string
    {
        $requestUri = $request->getRequestUri();

        return is_string($requestUri) && $requestUri !== ''
            ? $requestUri
            : '/';
    }

    private function isDebugEnabled(): bool
    {
        $exceptionHandling = Configuration::getValue('exception_handling');

        return is_array($exceptionHandling)
            && ($exceptionHandling['debug'] ?? false) === true;
    }
}
