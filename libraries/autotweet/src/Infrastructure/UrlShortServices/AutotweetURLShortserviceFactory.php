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

// Factory to create url short services.

// Include new services here only!!!

/**
 * AutotweetURLShortserviceFactory.
 *
 * @since       1.0
 */
class AutotweetURLShortserviceFactory
{
    /**
     * AutotweetURLShortserviceFactory.
     */
    private function __construct()
    {
        // Static class
    }

    /**
     * getInstance.
     *
     * @param array $data Param
     *
     * @return object
     */
    public static function getInstance($data)
    {
        $classname = 'Autotweet'.$data['type'].'Service';

        return new $classname($data);
    }
}
