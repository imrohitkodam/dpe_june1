<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComLogmanControllerToolbarMenubar extends ComKoowaControllerToolbarMenubar
{
    public function getCommands()
    {
        // Parent call adds commands from the component manifest
        parent::getCommands();

        $view = $this->getController()->getRequest()->getQuery()->view;

        $this->addCommand('Activities', array(
            'href'   => 'option=com_logman&view=activities',
            'active' => $view === 'activities' ? true : false
        ));

        if ($this->getController()->canAdmin())
        {
            $this->addCommand('Settings', array(
                'href'   => 'option=com_logman&view=config',
                'active' => $view === 'config' ? true : false
            ));

            $this->addCommand('Analytics', array(
                'href'   => 'option=com_logman&view=impressions',
                'active' => $view === 'impressions' ? true : false
            ));
        }

        return $this->_commands;
    }
}