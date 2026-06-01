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

// No direct access
defined('_JEXEC') || exit('Restricted access');

/**
 * This is the base class for the Extly framework.
 *
 * @since       1.0
 */
class Scheduler
{
    /**
     * getParser.
     *
     * @param string $unix_mhdmd Param
     */
    public static function getParser($unix_mhdmd = null)
    {
        return \XTP_BUILD\Cron\CronExpression::factory($unix_mhdmd);
    }
}
