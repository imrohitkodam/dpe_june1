<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activities Database Table
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanDatabaseTableActivities extends ComActivitiesDatabaseTableActivities
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('name' => 'logman_activities'));
        parent::_initialize($config);
    }
}