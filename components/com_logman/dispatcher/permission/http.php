<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Http Dispatcher Permission
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Logman\Library\Dispatcher
 */
class ComLogmanDispatcherPermissionHttp extends ComKoowaDispatcherPermissionAbstract
{
    /**
     * Checks if there is an active menu item
     *
     * @throws RuntimeException
     * @return bool
     */
    public function canDispatch()
    {
        $menu    = \Joomla\CMS\Factory::getApplication()->getMenu()->getActive();
        $default = \Joomla\CMS\Factory::getApplication()->getMenu()->getDefault();

        /* Joomla 4 always has a default menu as the active one, so here we check:
         * * The current menu item is the default menu item.
         * * The current menu item is NOT for Logman
         */
        $isNonLogmanDefault = ($menu && $default && $menu->id === $default->id && $menu->query['option'] !== 'com_logman');

        if (!$menu || $menu->id != $this->getRequest()->query->Itemid || $isNonLogmanDefault) {
            $result = in_array($this->getRequest()->query->view, array('linker'));
        } else {
            $result = $menu->query['option'] === 'com_logman';
        }

        if (!$result) {
            throw new RuntimeException($this->getObject('translator')->translate('Invalid menu item'));
        }

        return true;
    }
}
