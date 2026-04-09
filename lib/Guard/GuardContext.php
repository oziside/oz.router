<?php
declare(strict_types=1);
namespace Oz\Router\Guard;

use Bitrix\Main\{
    HttpApplication,
    HttpContext,
    HttpRequest
};
use DI\Container;
use Oz\Router\Routing\Route;

final class GuardContext
{
    public function __construct(
        private readonly HttpApplication $application,
        private readonly HttpContext $httpContext,
        private readonly HttpRequest $request,
        private readonly Route $route,
        private readonly array $routeParams,
        private readonly Container $container,
    ){}

    public function getApplication(): HttpApplication
    {
        return $this->application;
    }

    public function getHttpContext(): HttpContext
    {
        return $this->httpContext;
    }

    public function getRequest(): HttpRequest
    {
        return $this->request;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getHandler(): mixed
    {
        return $this->route->getHandler();
    }
}
