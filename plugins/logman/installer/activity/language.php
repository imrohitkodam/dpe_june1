<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Language/Installer Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanInstallerActivityLanguage extends PlgLogmanInstallerActivityInstaller
{
    public function getPropertyFormat()
    {
        if (!$this->_format)
        {
            $metadata = $this->getMetadata();

            $format   = '{actor} {action} {object} {object.subtype} {object.type}';

            if ($metadata->version) {
                $format .= ' {object.version}';
            }

            $this->_format = $format;
        }

        return parent::getPropertyFormat();
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $metadata = $this->getMetadata();

        if ($metadata)
        {
            $config->append(array(
                'subtype' => array('objectName' => $metadata->client, 'object' => true),
                'url'     => null
            ));
        }

        parent::_objectConfig($config);
    }
}