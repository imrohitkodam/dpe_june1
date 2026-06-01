<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Joomla Update Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanJoomlaupdateActivityJoomlaupdate extends ComLogmanModelEntityActivity
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('format' => null));
        parent::_initialize($config);

    }

    public function getPropertyFormat()
    {
        if (!$this->_format)
        {
            $metadata = $this->getMetadata();

            $format   = '{actor} {action} {object}';

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
        $version  = $metadata->version;

        $config->append(array('type' => array('object' => true, 'objectName' => $this->name)));

        if ($version) {
            $config->append(array('version' => array('object' => true, 'objectName' => $version)));
        }

        parent::_objectConfig($config);
    }

    public function getPropertyImage()
    {
        return 'icon-wrench';
    }
}
