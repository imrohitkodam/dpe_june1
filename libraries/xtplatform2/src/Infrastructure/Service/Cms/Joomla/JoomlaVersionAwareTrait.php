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

trait JoomlaVersionAwareTrait
{
    protected $isJ4 = false;

    private function detectJoomlaVersion()
    {
        if (defined('JOOMLA_SITE_PATH')) {
            $this->isJ4 = is_file(JOOMLA_SITE_PATH.'/libraries/bootstrap.php');

            return;
        }

        if (defined('JPATH_ROOT')) {
            $this->isJ4 = is_file(JPATH_ROOT.'/libraries/bootstrap.php');

            return;
        }

        throw new \Exception('JoomlaVersionAware: Unable to detect Joomla version');
    }

    public function factoryForJoomlaVersion(callable $forJ3Callback, callable $forJ4Callback): callable
    {
        return $this->isJ4 ? $forJ4Callback : $forJ3Callback;
    }
}
