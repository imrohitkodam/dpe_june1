<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Module/Installer Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanInstallerActivityModule extends PlgLogmanInstallerActivityInstaller
{
    protected function _objectConfig(KObjectConfig $config)
    {
        // Modules aren't accessible through installer activities.
        $config->append(
            array('url' => null
        ));

        parent::_objectConfig($config);
    }
}