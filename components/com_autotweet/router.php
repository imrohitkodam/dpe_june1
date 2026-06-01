<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

/**
 * Perfect Publisher  router functions.
 *
 * These functions are proxys for the new router interface
 * for old SEF extensions.
 *
 * @param array &$query An array of URL arguments
 *
 * @return array the URL arguments to use to assemble the subsequent URL
 *
 * @deprecated  4.0  Use Class based routers instead
 */
function autotweetBuildRoute(&$query)
{
    $param = [];

    if (isset($query['view'])) {
        $param[] = $query['view'];

        unset($query['view']);
    }

    return $param;
}

/**
 * Perfect Publisher  router functions.
 *
 * These functions are proxys for the new router interface
 * for old SEF extensions.
 *
 * @param array $segments the segments of the URL to parse
 *
 * @return array the URL attributes to be used by the application
 *
 * @deprecated  4.0  Use Class based routers instead
 */
function autotweetParseRoute($segments)
{
    $view = 'notauths';

    if (1 === count($segments)) {
        $view = $segments[0];
    }

    return ['view' => $view];
}
