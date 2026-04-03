<?php
declare(strict_types=1);
namespace Oz\Router\Http;

use Bitrix\Main\HttpApplication;
use Bitrix\Main\HttpContext;
use Bitrix\Main\HttpRequest;
use DI\Container;
use DI\ContainerBuilder;

final class RequestContainerFactory
{
    private array $definitions;

    public function __construct(array $definitions = [])
    {
        $this->definitions = $definitions;
    }

    public function build(
        HttpApplication $application,
        HttpContext $context,
        HttpRequest $request,
        array $routeParams
    ): Container
    {
        $builder = new ContainerBuilder;
        $builder->useAutowiring(true);

        if ($this->definitions !== [])
        {
            $builder->addDefinitions($this->definitions);
        }

        $builder->addDefinitions([
            HttpApplication::class => $application,
            HttpContext::class     => $context,
            HttpRequest::class     => $request,
            'route_params'         => $routeParams,
        ]);

        return $builder->build();
    }
}
