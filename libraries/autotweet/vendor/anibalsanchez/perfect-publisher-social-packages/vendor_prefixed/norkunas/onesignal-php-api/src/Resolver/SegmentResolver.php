<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\OneSignal\Resolver;

use XTS_BUILD\Symfony\Component\OptionsResolver\Options;
use XTS_BUILD\Symfony\Component\OptionsResolver\OptionsResolver;

class SegmentResolver implements ResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(array $data)
    {
        return (new OptionsResolver())
            ->setDefined('id')
            ->setAllowedTypes('id', 'string')
            ->setRequired('name')
            ->setAllowedTypes('name', 'string')
            ->setDefined('filters')
            ->setAllowedTypes('filters', 'array')
            ->setNormalizer('filters', function (Options $options, array $values) {
                return $this->normalizeFilters($options, $values);
            })
            ->resolve($data);
    }

    private function normalizeFilters(Options $options, array $values)
    {
        $filters = [];

        foreach ($values as $filter) {
            if (isset($filter['field']) || isset($filter['operator'])) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }
}
