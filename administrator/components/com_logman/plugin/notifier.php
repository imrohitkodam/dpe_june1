<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Notifier LOGman plugin.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */

class ComLogmanPluginNotifier extends ComLogmanPluginAbstract
{
    public function __construct(&$dispatcher, $config = array())
    {
        parent::__construct($dispatcher, $config);

        if ($notifiers = $this->getConfig()->notifiers)
        {
            $behaviors = array('com://admin/logman.controller.behavior.notifiable' => array('notifiers' => $notifiers));

            $this->getIdentifier('com://admin/logman.controller.activity')
                 ->getConfig()
                 ->append(array('behaviors' => $behaviors));
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('notifiers' => array()));
        parent::_initialize($config);
    }
}