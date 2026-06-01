<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Folder/FILEman Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanFilemanActivityFolder extends PlgLogmanFilemanActivityNode
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('format' => '{actor} {action} {object.type} name {object}'));

        parent::_initialize($config);
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $metadata = $this->getMetadata();
        $path     = $metadata->name;

        if ($metadata->folder) $path = $metadata->folder . '/' . $path;

        $url = $this->getObject('lib:http.url', array(
            'url' => '/administrator/index.php?option=com_fileman&view=files&folder=' .
                     rawurlencode($path) . '&container=' . $metadata->container->slug
        ));

        $config->append(array('url' => array('admin' => $url)));

        if ($page = $this->_findPage($path))
        {
            $template = 'option=com_fileman&view=folder&folder=%s&Itemid=%s';
            $config->append(array('url' => array('site' => sprintf($template, rawurlencode($path), $page->id))));
        }

        parent::_objectConfig($config);
    }
}