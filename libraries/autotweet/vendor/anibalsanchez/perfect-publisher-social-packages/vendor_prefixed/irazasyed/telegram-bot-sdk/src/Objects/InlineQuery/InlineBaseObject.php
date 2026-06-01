<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Telegram\Bot\Objects\InlineQuery;

use XTS_BUILD\Illuminate\Support\Collection;

/**
 * Class InlineBaseObject.
 */
abstract class InlineBaseObject extends Collection
{
    /** @var string Type */
    protected $type;

    /**
     * InlineBaseObject constructor.
     *
     * @param array $params
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->put('type', $this->type);
    }

    /**
     * Magic method to set properties dynamically.
     *
     * @param $name
     * @param $arguments
     *
     * @return $this|mixed
     */
    public function __call($name, $arguments)
    {
        if (! XTS_starts_with($name, 'set')) {
            throw new \BadMethodCallException("Method {$name} does not exist.");
        }
        $property = XTS_snake_case(substr($name, 3));
        $this->put($property, $arguments[0]);

        return $this;
    }
}
