<?php
declare(strict_types=1);

namespace Oz\Router\Http;

use Bitrix\Main\HttpRequest;
use DI\Container;
use Oz\Router\Http\ArgumentResolver\ArgumentInputBuilder;
use Oz\Router\Http\ArgumentResolver\EnumValueCaster;
use Oz\Router\Http\ArgumentResolver\HydratableTypeInspector;
use Oz\Router\Http\ArgumentResolver\ObjectHydrator;
use Oz\Router\Http\ArgumentResolver\ParameterResolver;
use Oz\Router\Http\ArgumentResolver\ScalarValueCaster;
use Oz\Router\Http\ArgumentResolver\TypeResolver;
use Oz\Router\Validation\RequestValidator;
use ReflectionFunctionAbstract;

final class HandlerArgumentsResolver
{
    private RequestValidator $requestValidator;
    private ArgumentInputBuilder $argumentInputBuilder;
    private ParameterResolver $parameterResolver;
    private HydratableTypeInspector $hydratableTypeInspector;

    public function __construct(?RequestValidator $requestValidator = null)
    {
        $this->requestValidator = $requestValidator ?? new RequestValidator();
        $scalarValueCaster = new ScalarValueCaster();
        $this->hydratableTypeInspector = new HydratableTypeInspector();
        $this->argumentInputBuilder = new ArgumentInputBuilder();
        $this->parameterResolver = new ParameterResolver(
            new TypeResolver(
                $scalarValueCaster,
                new EnumValueCaster($scalarValueCaster),
                new ObjectHydrator(),
                $this->hydratableTypeInspector
            )
        );
    }

    public function resolve(
        ReflectionFunctionAbstract $reflection,
        Container $container,
        HttpRequest $request,
        array $routeParams
    ): array
    {
        $input = $this->argumentInputBuilder->build($request, $routeParams);
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter)
        {
            $value = $this->parameterResolver->resolve(
                $parameter,
                $request,
                $input,
                $container,
                true,
                '$' . $parameter->getName()
            );

            $this->requestValidator->validate(
                $parameter,
                $value,
                $this->hydratableTypeInspector->shouldValidateObjectParameter($parameter->getType())
            );
            $arguments[] = $value;
        }

        return $arguments;
    }
}
