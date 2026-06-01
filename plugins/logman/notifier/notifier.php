<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activities Notifier LOGman plugin.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */

class PlgLogmanNotifier extends ComLogmanPluginNotifier
{
    protected function _initialize(KObjectConfig $config)
    {
        $recipients = $config->params->get('recipients', array());

        if (is_string($recipients))
        {
            $recipients = trim($recipients);

            if (!empty($recipients)) {
                $recipients = explode(',', $recipients);
            } else {
                $recipients = array();
            }
        }

        $config->append(array(
            'notifiers' => array(
                'plg:logman.notifier.email' => array(
                    'html'                   => $config->params->get('html', true),
                    'bcc'                    => $recipients,
                    'recipients'             => $recipients,
                    'usergroups'             => $config->params->get('usergroups', array()),
                    'packages_actions'       => $config->params->get('packages_actions', null),
                    'notify_userlog_actions' => $config->params->get('notify_userlog_actions', array()),
                    'notify_page_views'      => $config->params->get('notify_page_views', 0)
                )
            )
        ));

        parent::_initialize($config);
    }
}