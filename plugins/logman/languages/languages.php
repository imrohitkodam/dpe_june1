<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Languages LOGman Plugin.
 *
 * Provides event handlers for dealing with com_languages events.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanLanguages extends ComLogmanPluginJoomla
{
    protected function _getLanguageObjectData($data, $event)
    {
        return array('id' => $data->lang_id, 'name' => $data->title);
    }
}