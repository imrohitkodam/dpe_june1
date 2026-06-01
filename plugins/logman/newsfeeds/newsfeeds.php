<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Newsfeeds LOGman Plugin.
 *
 * Provides event handlers for dealing with com_newsfeeds events.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanNewsfeeds extends ComLogmanPluginJoomla
{
    protected function _getNewsfeedObjectData($data, $event)
    {
        return array('id' => $data->id, 'name' => $data->name);
    }

    protected function _getItems($ids, KObjectConfig $config)
    {
        $config->append(array('prefix' => 'NewsfeedsTable'));

        return parent::_getItems($ids, $config);
    }
}