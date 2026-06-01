<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Template/Installer Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanInstallerActivityTemplate extends PlgLogmanInstallerActivityInstaller
{
    protected function _objectConfig(KObjectConfig $config)
    {
        $config->append(array(
            'url' => array('admin' => 'option=com_templates&view=template&id=' . $this->row)
        ));

        parent::_objectConfig($config);
    }
}


