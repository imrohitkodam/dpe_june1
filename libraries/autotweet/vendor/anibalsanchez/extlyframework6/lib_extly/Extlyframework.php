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

if (!defined('XTF0F_INCLUDED')) {
    JFactory::getApplication()->enqueueMessage(
        'Your Extly Framework installation is broken; please re-install. Alternatively, extract the installation archive and copy the f0f directory inside your site\'s libraries directory.',
        'error'
    );
}

if (!defined('EXTLY_VERSION')) {
    // @name EXTLY_VERSION
    define('EXTLY_VERSION', 'EXTLY_BUILD_VERSION');

    // CSS Styling
    define('EXTLY_BASE', '6_0_0');

    defined('DS') || define('DS', \DIRECTORY_SEPARATOR);
    defined('EPATH_LIBRARY') || define('EPATH_LIBRARY', JPATH_LIBRARIES.'/extly');
    defined('EJSON_START') || define('EJSON_START', '@EXTLYSTART@');
    defined('EJSON_END') || define('EJSON_END', '@EXTLYEND@');

    $isJ3 = version_compare(JVERSION, '3.0', 'gt') && version_compare(JVERSION, '4.0', 'lt');
    defined('EXTLY_J3') || define('EXTLY_J3', ($isJ3));

    $isJ4 = version_compare(JVERSION, '4.0', 'gt');
    defined('EXTLY_J4') || define('EXTLY_J4', $isJ4);
}

/**
 * This is the base class for the Extlyframework.
 *
 * @since       1.0
 */
class Extlyframework
{
    public static function initialize()
    {
    }
}
