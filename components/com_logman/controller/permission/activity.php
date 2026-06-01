<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activity Controller Permission
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanControllerPermissionActivity extends ComKoowaControllerPermissionAbstract
{
    public function canRender()
    {
        $result = true;

        $menu = JFactory::getApplication()->getMenu()->getActive();

        if ($menu)
        {
            $params = $menu->getParams();

            if ($params->get('show_own_activities') && !$this->getUser()->isAuthentic()) {
                $result = false;
            }
        }

        return $result;
    }
}