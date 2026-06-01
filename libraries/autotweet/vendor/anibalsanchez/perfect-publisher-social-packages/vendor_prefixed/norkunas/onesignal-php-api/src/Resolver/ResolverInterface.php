<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\OneSignal\Resolver;

interface ResolverInterface
{
    /**
     * Resolve option array.
     *
     * @param array $data
     *
     * @return array
     */
    public function resolve(array $data);
}
