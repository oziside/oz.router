<?php
declare(strict_types=1);
namespace Oz\Router;


use Bitrix\Main\{
    HttpApplication,
    HttpContext,
    HttpRequest,
    HttpResponse
};
use Oz\Router\Http\Exception;

use Oz\Router\Http\HandlerInvoker;
use Oz\Router\Http\RequestContainerFactory;
use Oz\Router\Http\ResponseNormalizer;
use Oz\Router\Middleware\MiddlewareNormalizer;
use Oz\Router\Middleware\MiddlewareRegistry;
use Oz\Router\Middleware\MiddlewareResolver;
use Oz\Router\Middleware\MiddlewareRunner;
use Oz\Router\Middleware\MiddlewareSelector;
use Oz\Router\Routing\Path;
use Oz\Router\Routing\Route;
use Oz\Router\Routing\RouteCollection;
use Oz\Router\Routing\RouteRegistrar;
use Oz\Router\Routing\RouteGroup;


final class Router
{
    private RouteCollection $routes;
    private array $definitions;

    private MiddlewareNormalizer $middlewareNormalizer;

    private RouteGroup $routeGroup;
    private RouteRegistrar $routeRegistrar;

    private MiddlewareRegistry $middlewareRegistry;
    private MiddlewareSelector $middlewareSelector;
    private MiddlewareResolver $middlewareResolver;
    private MiddlewareRunner $middlewareRunner;

    private RequestContainerFactory $containerFactory;
    private HandlerInvoker $handlerInvoker;
    private ResponseNormalizer $responseNormalizer;
    private Path $pathHelper;

    public function __construct(
        array $definitions = [],
        array $config = []
    )
    {
        $this->routes      = new RouteCollection();
        $this->definitions = $definitions;

        $this->middlewareNormalizer = new MiddlewareNormalizer();

        $this->routeGroup = new RouteGroup();
        $this->routeRegistrar = new RouteRegistrar(
            $this->routes,
            $this->routeGroup
        );

        $this->middlewareRegistry = new MiddlewareRegistry();
        $this->middlewareSelector = new MiddlewareSelector($this->middlewareRegistry);
        $this->responseNormalizer = new ResponseNormalizer();
        $this->middlewareResolver = new MiddlewareResolver();
        $this->middlewareRunner = new MiddlewareRunner($this->responseNormalizer);

        $this->containerFactory = new RequestContainerFactory($this->definitions);
        $this->handlerInvoker = new HandlerInvoker();
        $this->pathHelper = new Path();
    }


    /**
     * Adds a GET-method route
     * 
     * @param string $path
     * @param callable|array|string $handler
     * 
     * @return Route
    */
    public function get(string $path, callable|array|string $handler): Route
    {
        return $this->add('GET', $path, $handler);
    }


    /**
     * Adds a POST-method route
     * 
     * @param string $path
     * @param callable|array|string $handler
     * 
     * @return Route
    */
    public function post(string $path, callable|array|string $handler): Route
    {
        return $this->add('POST', $path, $handler);
    }


    /**
     * Adds a PUT-method route
     * 
     * @param string $path
     * @param callable|array|string $handler
     * 
     * @return Route
    */
    public function put(string $path, callable|array|string $handler): Route
    {
        return $this->add('PUT', $path, $handler);
    }


    /**
     * Adds a PATCH-method route
     * 
     * @param string $path
     * @param callable|array|string $handler
     * 
     * @return Route
    */
    public function patch(string $path, callable|array|string $handler): Route
    {
        return $this->add('PATCH', $path, $handler);
    }


    /**
     * Adds a DELETE-method route
     * 
     * @param string $path
     * @param callable|array|string $handler
     * 
     * @return Route
    */
    public function delete(string $path, callable|array|string $handler): Route
    {
        return $this->add('DELETE', $path, $handler);
    }


    /**
     * Adds an OPTIONS-method route
     * 
     * @param string $path
     * @param callable|array|string $handler
     * 
     * @return Route
    */
    public function option(string $path, callable|array|string $handler): Route
    {
        return $this->add('OPTIONS', $path, $handler);
    }


    /**
     * Adds a route for any HTTP method
     * 
     * @param string $path
     * @param callable|array|string $handler
     * 
     * @return Route
    */
    public function any(string $path, callable|array|string $handler): Route
    {
        return $this->add('ANY', $path, $handler);
    }


    /**
     * Adds a single route
     * with an arbitrary request method
     * 
     * @param string|array $methods, 
     * @param string $path
     * @param callable|array|string $handler
     * 
     * @return Route
    */
    public function add(
        string|array $methods,
        string $path,
        callable|array|string $handler
    ): Route
    {
        return $this->routeRegistrar->add($methods, $path, $handler);
    }


    /**
     * Dispatches a request to the appropriate route
     * 
     * @param HttpRequest $request
     * @param HttpContext $context
     * @param HttpApplication $application
     * 
     * @return HttpResponse
    */
    public function dispatch(
        HttpRequest $request,
        HttpContext $context,
        HttpApplication $application
    ): HttpResponse
    {
        $method  = $this->extractMethod($request);
        $path    = $this->extractPath($request);

        if ($method === null)
        {
            throw new Exception\BadRequestHttpException(
                'HTTP request method is missing or invalid.',
            );
        }

        $matched = $this->routes->match($method, $path);

        if ($matched === null)
        {
            $allowedMethods = $this->routes->getAllowedMethods($path, $method);

            if ($allowedMethods !== [])
            {
                throw new Exception\MethodNotAllowedHttpException(
                    sprintf('The %s method is not supported for route %s.', $method, $path),
                );
            }

            throw new Exception\NotFoundHttpException(
                sprintf('Cannot %s %s', $method, $path),
            );
        }

        /** @var Route $route */
        [$route, $routeParams] = $matched;

        $container = $this->containerFactory->build(
            $application,
            $context,
            $request,
            $routeParams
        );

        $effectiveMiddlewares = $this->middlewareSelector->select($route);
        $resolvedMiddlewares = $this->middlewareResolver->resolve($effectiveMiddlewares, $container);

        $destination = function (HttpRequest $request) use ($route, $container, $routeParams): HttpResponse
        {
            $result = $this->handlerInvoker->invoke($route->getHandler(), $container, $request, $routeParams);

            return $this->responseNormalizer->normalize($result);
        };

        return $this->middlewareRunner->run(
            $resolvedMiddlewares,
            $request,
            $destination
        );
    }


    /**
     * Extracts the path from the HTTP request
     * 
     * @param HttpRequest $request
     * 
     * @return string
    */
    private function extractPath(
        HttpRequest $request
    ): string
    {
        $uri  = $request->getRequestUri() ?: '/';
        $path = parse_url($uri, PHP_URL_PATH);

        if (!is_string($path))
        {
            $path = '/';
        }

        return $this->pathHelper->normalize($path);
    }


    /**
     * Extracts and validates the HTTP method from the request.
     *
     * Bitrix passes REQUEST_METHOD through as-is, so we normalize only
     * the case/whitespace here and treat an empty or missing method as invalid.
     *
     * @param HttpRequest $request
     *
     * @return ?string
    */
    private function extractMethod(
        HttpRequest $request
    ): ?string
    {
        $method = $request->getRequestMethod();

        if (!is_string($method))
        {
            return null;
        }

        $method = strtoupper(trim($method));

        return $method !== ''
            ? $method
            : null;
    }


    public function group(string $prefix, callable $callback): self
    {
        $this->routeGroup->push($prefix);

        try
        {
            $callback($this);
        }
        finally
        {
            $this->routeGroup->pop();
        }

        return $this;
    }

    public function withMiddleware(string|array $middleware): self
    {
        $classes = $this->middlewareNormalizer->normalizeClasses($middleware);

        if ($this->routeGroup->isInsideGroup())
        {
            $this->routeGroup->addGroupMiddlewares($classes);
        }
        else
        {
            $this->middlewareRegistry->add($classes);
        }

        return $this;
    }

    public function withoutMiddleware(string|array $middleware): self
    {
        if (!$this->routeGroup->isInsideGroup())
        {
            return $this;
        }

        $classes = $this->middlewareNormalizer->normalizeClasses($middleware);
        $this->routeGroup->addGroupWithoutMiddlewares($classes);

        return $this;
    }

    public function loadRoutesFromFile(string $filePath): self
    {
        RoutesFileLoader::load($filePath, $this);

        return $this;
    }
}
