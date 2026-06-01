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
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Docman\Library\Dispatcher
 */
class ComDocmanDispatcherPermissionHttp extends ComKoowaDispatcherPermissionAbstract
{
    /**
     * Checks if there is an active menu item
     *
     * @throws RuntimeException
     * @return bool
     */
    public function canDispatch()
    {
        $menu = \Joomla\CMS\Factory::getApplication()->getMenu()->getActive();
        $default = \Joomla\CMS\Factory::getApplication()->getMenu()->getDefault();

        /* Joomla 4 always has a default menu as the active one, so here we check:
         * * The current menu item is the default menu item.
         * * The current menu item is NOT for Docman
         */
        $isNonDocmanDefault = ($menu && $default && $menu->id === $default->id && $menu->query['option'] !== 'com_docman');

        if (!$menu || $menu->id != $this->getRequest()->query->Itemid || $isNonDocmanDefault) {
            $result = in_array($this->getRequest()->query->view, array('doclink', 'documents', 'search'));
        } elseif ($this->getRequest()->query->view == 'search') {
            $result = true; // Joomla 4.x returns default menu item instead of null for non menu-item page
        } else {
            $result = $menu->query['option'] === 'com_docman';
        }

        if (!$result) {
            throw new RuntimeException($this->getObject('translator')->translate('Invalid menu item'));
        }

        return true;
    }
}