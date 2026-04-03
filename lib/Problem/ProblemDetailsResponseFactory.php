<?php
declare(strict_types=1);

namespace Oz\Router\Problem;

use Bitrix\Main\HttpRequest;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Web\Json;
use Oz\Router\Http\TextResponseFactory;
use Throwable;

final class ProblemDetailsResponseFactory
{
    private ProblemDetailsMapper $mapper;
    private RequestFormatResolver $requestFormatResolver;
    private TextResponseFactory $textResponseFactory;

    public function __construct(
        ?ProblemDetailsMapper $mapper = null,
        ?RequestFormatResolver $requestFormatResolver = null,
        ?TextResponseFactory $textResponseFactory = null
    ) {
        $this->mapper = $mapper ?? new ProblemDetailsMapper();
        $this->requestFormatResolver = $requestFormatResolver ?? new RequestFormatResolver();
        $this->textResponseFactory = $textResponseFactory ?? new TextResponseFactory();
    }

    public function create(Throwable $exception, HttpRequest $request): HttpResponse
    {
        $problem = $this->mapper->map($exception, $request);

        if ($this->requestFormatResolver->prefersProblemJson($request))
        {
            return $this->createJsonResponse($problem);
        }

        return $this->createTextResponse($problem);
    }

    public function createJsonResponse(ProblemDetails $problem): HttpResponse
    {
        $response = new HttpResponse();
        $response->setStatus($problem->getStatus());
        $response->addHeader('Content-Type', 'application/problem+json; charset=UTF-8');

        foreach ($problem->getHeaders() as $name => $value)
        {
            $response->addHeader((string)$name, (string)$value);
        }

        $response->setContent(Json::encode($problem->toArray(), Json::DEFAULT_OPTIONS));

        return $response;
    }

    private function createTextResponse(ProblemDetails $problem): HttpResponse
    {
        $content = $problem->getDetail();

        if ($content === '')
        {
            $content = $problem->getTitle();
        }

        $response = $this->textResponseFactory->create($content, $problem->getStatus());

        foreach ($problem->getHeaders() as $name => $value)
        {
            $response->addHeader((string)$name, (string)$value);
        }

        return $response;
    }
}
