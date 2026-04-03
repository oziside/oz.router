<?php
declare(strict_types=1);
namespace Oz\Router\Http;

use Bitrix\Main\HttpRequest;
use DI\Container;
use InvalidArgumentException;

final class HandlerInvoker
{
    private HandlerArgumentsResolver $argumentsResolver;

    public function __construct(?HandlerArgumentsResolver $argumentsResolver = null)
    {
        $this->argumentsResolver = $argumentsResolver ?? new HandlerArgumentsResolver();
    }

    public function invoke(
        callable|array|string $handler,
        Container $container,
        HttpRequest $request,
        array $routeParams
    ): mixed
    {
        return $this->invokeCallable($handler, $container, $request, $routeParams);
    }

    private function invokeCallable(
        callable|array|string $handler,
        Container $container,
        HttpRequest $request,
        array $routeParams
    ): mixed
    {
        if (is_string($handler))
        {
            if (!str_contains($handler, '@'))
            {
                throw new InvalidArgumentException('String handler must be in format Class@method');
            }

            [$controllerClass, $method] = explode('@', $handler, 2);
            $controller = $container->get($controllerClass);

            return $this->invokeMethod($controller, $method, $container, $request, $routeParams);
        }

        if (is_array($handler) && isset($handler[0], $handler[1]))
        {
            [$controller, $method] = $handler;

            if (is_string($controller))
            {
                $controller = $container->get($controller);
            }

            return $this->invokeMethod($controller, $method, $container, $request, $routeParams);
        }

        $callable = \Closure::fromCallable($handler);
        $reflection = new \ReflectionFunction($callable);
        $arguments = $this->argumentsResolver->resolve($reflection, $container, $request, $routeParams);

        return $reflection->invokeArgs($arguments);
    }

    private function invokeMethod(
        object $controller,
        string $method,
        Container $container,
        HttpRequest $request,
        array $routeParams
    ): mixed
    {
        $reflection = new \ReflectionMethod($controller, $method);
        $arguments = $this->argumentsResolver->resolve($reflection, $container, $request, $routeParams);

        return $reflection->invokeArgs($controller, $arguments);
    }
}
