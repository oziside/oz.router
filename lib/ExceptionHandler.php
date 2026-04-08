<?php
declare(strict_types=1);
namespace Oz\Router;

use Throwable;
use Bitrix\Main\{
    Web\Json,
    HttpRequest,
    HttpResponse,
    Engine\Response,
    Config\Configuration
};

use Oz\Router\Http\Exception\HttpException;
use Oz\Router\Http\Exception\InternalServerErrorHttpException;
use Oz\Router\Validation\RequestValidationException;


final class ExceptionHandler
{
    /**
     * Handles the given exception and returns 
     * an appropriate HTTP response
     * 
     * @param Throwable $exception
     * @param HttpRequest $request
     * 
     * @return HttpResponse
    */
    public function handle(
        Throwable $exception, 
        HttpRequest $request
    ): HttpResponse
    {
        $httpException = $this->toHttpException($exception);

        return $this->resolveFormat($request) === 'json'
            ? $this->createJsonResponse($httpException)
            : $this->createHtmlResponse($httpException);
    }


    /**
     * Resolves the response format based on the request's headers
     * 
     * @param HttpRequest $request
     * 
     * @return string
    */
    private function resolveFormat(
        HttpRequest $request
    ): string
    {
        $accept = strtolower((string)$request->getHeader('Accept'));

        if (str_contains($accept, 'application/json'))
        {
            return 'json';
        }

        if (str_contains($accept, 'text/html'))
        {
            return 'html';
        }

        return 'html';
    }


    /**
     * Creates a JSON response based on the given HttpException
     * 
     * @param HttpException $exception
     * 
     * @return Response\Json
    */
    private function createJsonResponse(
        HttpException $exception
    ): Response\Json
    {
        $response = new Response\Json(options: Json::DEFAULT_OPTIONS);
        $data = [
            'statusCode' => $exception->getStatusCode(),
            'message'    => $exception->getMessage(),
        ];

        if ($exception instanceof RequestValidationException)
        {
            $data['errors'] = $exception->getErrors();
        }

        $response->setData($data);
        $response->setStatus($exception->getStatusCode());

        return $response;
    }


    /**
     * Creates an HTML response based on the given HttpException
     * 
     * @param HttpException $exception
     * 
     * @return HttpResponse
    */
    private function createHtmlResponse(
        HttpException $exception
    ): HttpResponse
    {
        $response = new HttpResponse();
        $response->setStatus($exception->getStatusCode());
        $response->addHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->setContent($exception->getMessage());

        return $response;
    }


    /**
     * Converts a generic exception to an HTTP exception
     * 
     * @param Throwable $exception
     * 
     * @return HttpException
    */
    private function toHttpException(
        Throwable $exception
    ): HttpException
    {
        $exceptionHandling = Configuration::getValue('exception_handling');
        $isDebug = is_array($exceptionHandling)
            && ($exceptionHandling['debug'] ?? false) === true;

    
        if (!$exception instanceof HttpException)
        {
            return new InternalServerErrorHttpException(
                message: $isDebug
                    ? $exception->getMessage()
                    : 'Internal Server Error',
                previous: $exception,
            );
        }

        if (!$isDebug && $exception->getStatusCode() >= 500)
        {
            return new InternalServerErrorHttpException(
                message: 'Internal Server Error',
                previous: $exception,
            );
        }

        return $exception;
    }
}
