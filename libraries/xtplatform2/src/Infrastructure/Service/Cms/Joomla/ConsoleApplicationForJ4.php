<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

/*
 * @package     Extly Infrastructure Support
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @see         https://www.extly.com
 */

namespace XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla;

use Joomla\CMS\Application\ConsoleApplication;

class ConsoleApplicationForJ4 extends ConsoleApplication
{
    public function getTemplate()
    {
        return (object) ['template' => 'cassiopeia', 'parent' => 'disabled-template'];
    }

    public function getUserState($key, $default = null)
    {
        return $default;
    }
}
