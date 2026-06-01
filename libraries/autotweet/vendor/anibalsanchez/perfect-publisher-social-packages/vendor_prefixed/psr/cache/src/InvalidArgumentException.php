<?php /* This file has been prefixed by <PHP-Prefixer> for "XT Social Libraries" */

namespace XTS_BUILD\Psr\Cache;

/**
 * Exception interface for invalid cache arguments.
 *
 * Any time an invalid argument is passed into a method it must throw an
 * exception class which implements Psr\Cache\InvalidArgumentException.
 */
interface InvalidArgumentException extends CacheException
{
}
