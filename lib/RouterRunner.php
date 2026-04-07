<?php
declare(strict_types=1);
namespace Oz\Router;

use Bitrix\Main\{
    HttpApplication, 
    HttpContext,
    HttpResponse,

    SystemException
};

final class RouterRunner
{
    public function __construct(
        private readonly Router $router
    ){}
    

    /**
     * Runs the router and sends the response.
     * 
     * @param HttpApplication|null $application
     * @return HttpResponse
     * @throws SystemException
    */
    public function run(
        ?HttpApplication $application = null
    ): HttpResponse
    {
        $application ??= HttpApplication::getInstance();
        $context     = $application->getContext();

        if (!$context instanceof HttpContext)
        {
            throw new SystemException('HttpContext is not initialized');
        }

        $request = $context->getRequest();

        try
        {
            $response = $this->router->dispatch(
                $request, 
                $context, 
                $application
            );
        }
        catch (\Throwable $exception)
        {
            $exceptionHandler = new ExceptionHandler;

            $response = $exceptionHandler->handle($exception, $request);
        }

        $context->setResponse($response);
        
        return $response;
    }
}
