<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Installer Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanInstallerActivityInstaller extends ComLogmanModelEntityActivity
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $client = 'admin';

        if (($metadata = $this->getMetadata()) && $metadata->client) {
            $client = $metadata->client;
        }

        ComLogmanActivityTranslator::loadSysIni($this->title, $client);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'format'           => null,
            'object_table'     => 'extensions',
            'object_column'    => 'extension_id'
        ));

        parent::_initialize($config);
    }

    public function getPropertyFormat()
    {
        if (!$this->_format)
        {
            $metadata = $this->getMetadata();

            $format   = '{actor} {action} {object} {object.type}';

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
                'version' => array('objectName' => $metadata->version, 'object' => true)
            ));
        }

        parent::_objectConfig($config);
    }

    public function getPropertyImage()
    {
        $images = array(
            'install' => 'k-icon-hard-drive',
            'uninstall' => 'k-icon-trash',
            'update' => 'k-icon-data-transfer-download'
        );

        $verb   = $this->verb;

        if (in_array($verb, array_keys($images))){
            $image = $images[$verb];
        } else {
            $image = parent::getPropertyImage();
        }

        return $image;
    }
}