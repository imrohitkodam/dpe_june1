<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

namespace XTP_BUILD\Http\Client\Common\Exception;

use XTP_BUILD\Http\Client\Exception\HttpException;

/**
 * Thrown when circular redirection is detected.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class CircularRedirectionException extends HttpException
{
}
