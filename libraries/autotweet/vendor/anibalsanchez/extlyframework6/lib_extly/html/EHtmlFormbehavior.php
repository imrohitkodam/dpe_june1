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
 * Utility class for form related behaviors.
 *
 * @since       3.0
 */
abstract class EHtmlFormbehavior
{
    /**
     * @var array Array containing information for loaded files
     *
     * @since  3.0
     */
    protected static $loaded = [];

    /**
     * Method to load the Chosen JavaScript framework and supporting CSS into the document head.
     *
     * If debugging mode is on an uncompressed version of Chosen is included for easier debugging.
     *
     * @param string $selector class for Chosen elements
     * @param mixed  $debug    Is debugging mode on? [optional]
     *
     * @since   3.0
     */
    public static function chosen($selector = '.advandedSelect', $debug = null)
    {
        if (isset(self::$loaded[__METHOD__][$selector])) {
            return;
        }

        // Add chosen.jquery.js language strings
        JText::script('JGLOBAL_SELECT_SOME_OPTIONS');
        JText::script('JGLOBAL_SELECT_AN_OPTION');
        JText::script('JGLOBAL_SELECT_NO_RESULTS_MATCH');

        $document = JFactory::getDocument();
        $url = JUri::root();
        $document->addStyleSheet($url.'libraries/extly/css/chosen.css');

        self::$loaded[__METHOD__][$selector] = true;
    }
}
