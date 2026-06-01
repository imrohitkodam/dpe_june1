<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Redirect LOGman Plugin.
 *
 * Provides event handlers for dealing with com_redirect events.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanRedirect extends ComLogmanPluginJoomla
{
    protected function _getLinkObjectData($data, $event)
    {
        return array('id' => $data->id, 'name' => 'redirect');
    }

    protected function _getItems($ids, KObjectConfig $config)
    {
        $config->append(array('prefix' => 'RedirectTable'));

        return parent::_getItems($ids, $config);
    }
}