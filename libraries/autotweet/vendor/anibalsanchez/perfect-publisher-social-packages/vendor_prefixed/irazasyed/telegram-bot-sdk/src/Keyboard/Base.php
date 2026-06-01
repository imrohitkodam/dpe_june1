<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Telegram\Bot\Keyboard;

use XTS_BUILD\Illuminate\Support\Collection;

/**
 * Class Base.
 */
class Base extends Collection
{
    /**
     * Dynamically build params.
     *
     * @param string $method
     * @param array  $args
     *
     * @return $this
     */
    public function __call($method, $args)
    {
        if (! XTS_starts_with($method, 'set')) {
            return parent::__call($method, $args);
        }
        $property = XTS_snake_case(substr($method, 3));
        $this->items[$property] = $args[0];

        return $this;
    }
}
