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
 * AutotweetLogger.
 *
 * @since       1.0
 */
class AutotweetLogger
{
    private static $instance = null;

    /**
     * getInstance.
     *
     * @return object
     */
    public static function &getInstance()
    {
        if (!self::$instance) {
            $log_level = EParameter::getComponentParam(CAUTOTWEETNG, 'log_level', \Joomla\CMS\Log\Log::ERROR);
            $log_mode = EParameter::getComponentParam(CAUTOTWEETNG, 'log_mode', ELog::LOG_MODE_SCREEN);

            self::$instance = new ELog($log_level, $log_mode);
        }

        return self::$instance;
    }
}
