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
        return $this->isDispatched() ? $this->canManage() : parent::canRender();
    }

    public function canAdd()
    {
        $result = false;

        if ($this->pluginEnabled()) {
            $result = !$this->isDispatched();
        }

        return $result;
    }

    /**
     * Do not allow activities to be edited
     *
     * @return  boolean  Return TRUE if action is permitted. FALSE otherwise.
     */
    public function canEdit()
    {
        return false;
    }

    public function canPurge()
    {
        return !$this->isDispatched() || $this->canDelete();
    }
}
