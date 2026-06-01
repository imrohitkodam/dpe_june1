<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Node/DOCman Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
abstract class PlgLogmanDocmanActivityNode extends ComLogmanModelEntityActivity
{
    /**
     * @var array Loaded containers.
     */
    static protected $_containers = array();

    /**
     * @var bool Whether required dependencies are installed or not.
     */
    static private $__dependencies;

    protected function _findActivityObject()
    {
        $result = false;

        $metadata  = $this->getMetadata();

        if ($metadata)
        {
            $container = $this->_getContainer($metadata->container->slug);

            if ($container)
            {
                $path   = $container->fullpath . '/' . $metadata->path;
                $result = (bool) file_exists($path);
            }
        }

        return $result;
    }

    public function getPropertyImage()
    {
        if ($this->verb == 'move') {
            $image = 'k-icon-move';
        }  else if ($this->verb == 'copy') {
            $image = 'k-icon-layers';
        }
        else
        {
            $image = parent::getPropertyImage();
        }

        return $image;
    }

    /**
     * Container getter.
     *
     * @param string $slug The container slug.
     *
     * @return mixed The container object, null if not found.
     */
    protected function _getContainer($slug)
    {
        if (!isset(self::$_containers[$slug]))
        {
            if ($this->_hasDependencies()) {
                $container = $this->getObject('com:files.model.containers')->slug($slug)->fetch();
            } else {
                $container = null;
            }

            self::$_containers[$slug] = $container;
        }
        return self::$_containers[$slug];
    }

    /**
     * Tells if dependencies are present and installed.
     *
     * @return bool True if it is, false otherwise.
     */
    protected function _hasDependencies()
    {
        if (!isset(self::$__dependencies))
        {
            $result = false;

            if (class_exists('ComDocmanVersion') && version_compare(ComDocmanVersion::VERSION, '2.0.0', '>=')) {
                $result = true;
            }

            self::$__dependencies = $result;
        }

        return self::$__dependencies;
    }
}
