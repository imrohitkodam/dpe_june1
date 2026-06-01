<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Joomla Update LOGman Plugin.
 *
 * Provides handlers for logging Joomla Core Updates.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanJoomlaupdate extends ComLogmanPluginLogger
{
    public function onAfterRoute()
    {
        $query = $this->getObject('request')->getQuery();

        if ($query->option == 'com_joomlaupdate' && $query->layout == 'complete')
        {
            $this->logActivity(array(
                'object' => array(
                    'package'  => 'joomlaupdate',
                    'type'     => 'site',
                    'name'     => 'Joomla',
                    'metadata' => array('version' => JVERSION)
                ),
                'result' => 'upgraded',
                'verb'   => 'upgrade'
            ));
        }
    }
}