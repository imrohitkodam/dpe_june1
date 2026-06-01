<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Illuminate\Support\Facades;

use XTP_BUILD\Psr\Log\LoggerInterface;

/**
 * @see \Illuminate\Log\Writer
 */
class Log extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return LoggerInterface::class;
    }
}
