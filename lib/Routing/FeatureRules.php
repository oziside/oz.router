<?php
declare(strict_types=1);
namespace Oz\Router\Routing;

class FeatureRules
{
    private array $included;
    private array $excluded;

    public function __construct(
        array $included = [],
        array $excluded = []
    )
    {
        $this->included = [];
        $this->excluded = [];

        if ($included !== [])
        {
            $this->include($included);
        }

        if ($excluded !== [])
        {
            $this->exclude($excluded);
        }
    }

    public function add(string|array $items): self
    {
        $this->included = $this->mergeItems(
            $this->included,
            $items
        );

        return $this;
    }

    public function except(string|array $items): self
    {
        $this->excluded = $this->mergeItems(
            $this->excluded,
            $items
        );

        return $this;
    }

    public function merge(self $rules): self
    {
        if ($rules->included() !== [])
        {
            $this->add($rules->included());
        }

        if ($rules->excluded() !== [])
        {
            $this->except($rules->excluded());
        }

        return $this;
    }

    public function included(): array
    {
        return $this->included;
    }

    public function excluded(): array
    {
        return $this->excluded;
    }

    public function include(string|array $items): self
    {
        return $this->add($items);
    }

    public function exclude(string|array $items): self
    {
        return $this->except($items);
    }

    private function mergeItems(
        array $current,
        string|array $items
    ): array
    {
        $normalized = [];
        $itemList = is_array($items)
            ? $items
            : [$items];

        foreach ($itemList as $item)
        {
            $item = trim((string)$item);

            if ($item === '')
            {
                continue;
            }

            $normalized[] = $item;
        }

        return array_values(array_unique(array_merge($current, $normalized)));
    }
}
