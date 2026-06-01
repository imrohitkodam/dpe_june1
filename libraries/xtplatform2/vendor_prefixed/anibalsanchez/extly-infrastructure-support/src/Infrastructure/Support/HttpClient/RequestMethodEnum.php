<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

/*
 * @package     Extly Infrastructure Support
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2021 Extly, CB. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @see         https://www.extly.com
 */

namespace XTP_BUILD\Extly\Infrastructure\Support\HttpClient;

use XTP_BUILD\Extly\Infrastructure\Creator\CreatorTrait;
use XTP_BUILD\MyCLabs\Enum\Enum;

/**
 * Class Request.
 */
final class RequestMethodEnum extends Enum
{
    use CreatorTrait;

    const DELETE = 'DELETE';
    const GET = 'GET';
    const OPTIONS = 'OPTIONS';
    const POST = 'POST';
    const PUT = 'PUT';
}
