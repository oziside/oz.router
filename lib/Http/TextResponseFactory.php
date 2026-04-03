<?php
declare(strict_types=1);
namespace Oz\Router\Http;

use Bitrix\Main\HttpResponse;

final class TextResponseFactory
{
    public function create(string $content, int $status): HttpResponse
    {
        $response = new HttpResponse();
        $response->setStatus($status);
        $response->addHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->setContent($content);

        return $response;
    }
}
