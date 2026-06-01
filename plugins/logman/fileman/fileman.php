<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * FILEman LOGman Plugin.
 *
 * Wires FILEman loggers to com_files controllers.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanFileman extends ComLogmanPluginKoowa
{
    public function __construct($subject, $config = array())
    {
        parent::__construct($subject, $config);

        $identifiers = array('com:files.controller.node', 'com:files.controller.file', 'com:files.controller.folder');

        foreach ($identifiers as $identifier)
        {
            $this->getIdentifier($identifier)
                 ->getConfig()
                 ->append(array('behaviors' => array('com://admin/logman.controller.behavior.removable')));
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'controllers' => array(
                'com:files.controller.node'            => 'plg:logman.fileman.logger.node',
                'com:files.controller.file'            => 'plg:logman.fileman.logger.node',
                'com:files.controller.folder'          => 'plg:logman.fileman.logger.node',
                'com://site/fileman.controller.folder' => 'plg:logman.fileman.logger.node',
                'com://site/fileman.controller.file'   => 'plg:logman.fileman.logger.download'
            )
        ));

        parent::_initialize($config);
    }
}