<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\OneSignal\Resolver;

use XTS_BUILD\Symfony\Component\OptionsResolver\OptionsResolver;

class DeviceFocusResolver implements ResolverInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolve(array $data)
    {
        return (new OptionsResolver())
            ->setDefault('state', 'ping')
            ->setAllowedTypes('state', 'string')
            ->setRequired('active_time')
            ->setAllowedTypes('active_time', 'int')
            ->resolve($data);
    }
}
