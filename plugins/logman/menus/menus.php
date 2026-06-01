<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Menus LOGman Plugin.
 *
 * Provides event handlers for dealing with com_menus events.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanMenus extends ComLogmanPluginJoomla
{
    protected function _getItems($ids, KObjectConfig $config)
    {
        if ($config->type == 'item')
        {
            if (version_compare(JVERSION, '4', '<')) {
                $config->prefix = 'MenusTable';
            }

            $config->type = 'Menu';
        }

        return parent::_getItems($ids, $config);
    }

    public function onAfterDispatch()
    {
        parent::onAfterDispatch();

        $vars = JFactory::getApplication()->getRouter()->getVars();

        if (count($vars) == 2 && isset($vars['Itemid']) && isset($vars['option']) && $vars['option'] != 'com_logman')
        {
            $item = JFactory::getApplication()->getMenu()->getItem($vars['Itemid']);

            if ($item)
            {
                $data = new stdClass();

                $data->id = $item->id;
                $data->title = $item->title;

                $this->logActivity(array(
                    'context' => 'com_menus.item',
                    'data'    => $data,
                    'verb'    => 'read',
                    'result'  => 'read',
                    'event'   => 'onAfterDispatch'
                ));
            }
        }
    }
}