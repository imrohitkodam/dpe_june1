<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activity Notifier Interface.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
interface ComLogmanActivityNotifierInterface
{
    /**
     * Sends a notification about an activity.
     *
     * @param ComActivitiesActivityInterface $activity The activity to notify about.
     *
     * @return bool True if the notification was successfully sent, false otherwise.
     */
    public function notify(ComActivitiesActivityInterface $activity);
}