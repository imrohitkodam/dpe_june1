<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Folder/DOCman Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanDocmanActivityFolder extends PlgLogmanDocmanActivityNode
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'format' => '{actor} {action} {object.type} name {object}'
        ));

        parent::_initialize($config);
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $metadata = $this->getMetadata();
        $path     = $metadata->name;

        if ($metadata->folder) {
            $path = $metadata->folder . '/' . $path;
        }

        if ($this->row != 'docman-files:tmp')
        {
            $url = $this->getObject('lib:http.url', array(
                'url' => '/administrator/index.php?option=com_docman&view=files&folder=' .
                         rawurlencode($path) . '&container=' . $metadata->container->slug
            ));

            $config->append(array('url' => array('admin' => $url)));
        }
        else $config->url = false;

        parent::_objectConfig($config);
    }
}