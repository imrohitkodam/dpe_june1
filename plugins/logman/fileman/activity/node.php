<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Node/FILEman Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
abstract class PlgLogmanFilemanActivityNode extends ComLogmanModelEntityActivity
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

        $parts = parse_url($this->row);

        if (isset($parts['scheme']))
        {
            $container = $this->_getContainer($parts['scheme']);

            if ($container)
            {
                $path   = $container->fullpath . '/' . trim($parts['path'], '/');
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
     * File container getter.
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

            if (class_exists('ComFilemanVersion') && version_compare(ComFilemanVersion::VERSION, '2.0.0', '>=')) {
                $result = true;
            }

            self::$__dependencies = $result;
        }

        return self::$__dependencies;
    }

    /**
     * Path page finder.
     *
     * @param $path string The node path.
     * @return object|null The page containing the path, null if the page wasn't found.
     */
    protected function _findPage($path)
    {
        $result = null;

        $levels = $this->getViewLevels() ? $this->getViewLevels() : array();

        $conditions = array(
            array('view' => 'folder', 'layout' => 'table'),
            array('view' => 'folder', 'layout' => 'gallery')
        );

        if ($this->name == 'file') {
            array_unshift($conditions, array('view' => 'file'));
        }

        $pages = $this->_findPages(array(
            'levels'     => $levels,
            'components' => 'com_fileman',
            'conditions' => $conditions
        ));

        if ($pages)
        {
            foreach ($pages as $page)
            {
                $link = parse_url($page->link);

                parse_str($link['query'], $query);

                $folder = isset($query['folder']) ? $query['folder'] : '';
                $name   = isset($query['name']) ? $query['name'] : '';

                $query_path = trim(sprintf('%s/%s', $folder, $name), '/');

                if (empty($query_path) || strpos($path, $query_path) === 0) $result = $page;

                if ($result) break;
            }
        }

        return $result;
    }
}
