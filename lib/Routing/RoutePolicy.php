<?php
declare(strict_types=1);
namespace Oz\Router\Routing;

final class RoutePolicy
{
    private FeatureRules $middlewares;
    private FeatureRules $guards;

    public function __construct()
    {
        $this->middlewares = new FeatureRules();
        $this->guards = new FeatureRules();
    }

    public function middlewares(): FeatureRules
    {
        return $this->middlewares;
    }

    public function guards(): FeatureRules
    {
        return $this->guards;
    }

    public function merge(self $policy): self
    {
        $this->middlewares->merge($policy->middlewares());
        $this->guards->merge($policy->guards());

        return $this;
    }
}
