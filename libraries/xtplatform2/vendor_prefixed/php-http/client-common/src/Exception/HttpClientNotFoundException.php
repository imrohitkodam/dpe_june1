<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Client\Common\Exception;

use XTP_BUILD\Http\Client\Exception\TransferException;

/**
 * Thrown when a http client cannot be chosen in a pool.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class HttpClientNotFoundException extends TransferException
{
}
