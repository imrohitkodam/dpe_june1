<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * DOCman LOGman plugin.
 *
 * Wires DOCman loggers to Files and DOCman components controllers.
 *
 * Also provides event handlers for DOCman 1.x.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanDocman extends ComLogmanPluginKoowa
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
                'com:files.controller.node'              => 'plg:logman.docman.logger.node',
                'com://site/docman.controller.download'  => 'plg:logman.docman.logger.download',
                'com://site/docman.controller.document'  => 'plg:logman.docman.logger.document',
                'com://site/docman.controller.submit'    => 'plg:logman.docman.logger.document',
                'com://admin/docman.controller.document' => 'plg:logman.docman.logger.document',
                'com://admin/docman.controller.category' => 'plg:logman.docman.logger.category',
                'com://site/docman.controller.category'  => 'plg:logman.docman.logger.category',
                'com:files.controller.file'              => 'plg:logman.docman.logger.node',
                'com:files.controller.folder'            => 'plg:logman.docman.logger.node'
            )
        ));

        parent::_initialize($config);
    }
}