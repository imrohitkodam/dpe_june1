<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Banners LOGPlugin.
 *
 * Provides event handlers for dealing with com_banners events.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
#[\AllowDynamicProperties]
class PlgLogmanBanners extends ComLogmanPluginJoomla
{
    protected function _getClientObjectData($data, $event)
    {
        return array('id' => $data->id, 'name' => $data->name);
    }

    protected function _getBannerObjectData($data, $event)
    {
        return $this->_getClientObjectData($data, $event);
    }

    protected function _getItems($ids, KObjectConfig $config)
    {
        $config->append(array('prefix' => 'BannersTable'));

        return parent::_getItems($ids, $config);
    }
}