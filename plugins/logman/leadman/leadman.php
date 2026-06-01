<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * LEADman LOGman plugin.
 *
 * Wires loggers to LEADman component controllers.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanLeadman extends ComLogmanPluginKoowa
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'controllers' => array(
                'com://site/leadman.controller.comment' => 'plg:logman.leadman.logger.comment',
                'com://site/leadman.controller.form'    => 'plg:logman.leadman.logger.contact',
                'com://site/leadman.controller.contact' => 'plg:logman.leadman.logger.contact',
                'com://site/leadman.controller.profile' => 'plg:logman.leadman.logger.contact',
            )
        ));

        parent::_initialize($config);
    }
}