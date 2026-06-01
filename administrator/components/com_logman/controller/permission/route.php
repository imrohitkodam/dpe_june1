<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Route Controller Permission
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanControllerPermissionRoute extends ComKoowaControllerPermissionAbstract
{
    public function canAdd()
    {
        return !$this->isDispatched();
    }

    public function canEdit()
    {
        return $this->canAdd();
    }

    public function canDelete()
    {
        return $this->canAdd();
    }
}