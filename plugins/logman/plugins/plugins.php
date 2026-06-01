<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Plugins LOGman Plugin.
 *
 * Provides event handlers for dealing with com_plugins events.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanPlugins extends ComLogmanPluginJoomla
{
    protected function _getPluginObjectData($data)
    {
        return array('id' => $data->extension_id, 'name' => 'plg_' . $data->folder . '_' . $data->element);
    }

    protected function _getItems($ids, KObjectConfig $config)
    {
        $config->type = 'extension';

        return parent::_getItems($ids, $config);
    }
}