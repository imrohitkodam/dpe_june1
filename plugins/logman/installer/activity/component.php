<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Component/Installer Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanInstallerActivityComponent extends PlgLogmanInstallerActivityInstaller
{
    protected function _objectConfig(KObjectConfig $config)
    {
        $metadata = $this->getMetadata();
        $element  = $metadata->element;

        $url  = null;
        $file = JPATH_ADMINISTRATOR . '/components/' . $element . '/' . str_replace('com_', '', $element) . '.php';

        // Only components with entry point files are reachable.
        if (file_exists($file)) {
            $url = 'option=' . $element;
        }

        parent::_objectConfig($config->append(array('url' => array('admin' => $url))));
    }
}
