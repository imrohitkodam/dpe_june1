<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Categories LOGman Plugin.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
#[\AllowDynamicProperties]
class PlgLogmanCategories extends ComLogmanPluginJoomla
{
    protected function _getCategoryObjectData($data, $event)
    {
        return array('id' => $data->id, 'name' => $data->title, 'metadata' => array('extension' => $data->extension));
    }
}