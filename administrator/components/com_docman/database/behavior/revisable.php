<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanDatabaseBehaviorRevisable extends KDatabaseBehaviorAbstract
{
    /**
     * Saves a version of the file attached to te document
     *
     * @param KControllerContextInterface $context
     */
//    protected function _beforeUpdate(KDatabaseContext $context)
//    {
//        $config = $this->getObject('com://admin/docman.model.configs')->fetch();
//
//        if (!$config->file_versioning) {
//            return;
//        }
//
//        $file     = $this->storage;
//        $versions = $this->getFileVersions();
//
//        // Remove previous version of the same file
//        foreach ($versions as $time => $version)
//        {
//            if ($version['path'] == $file->path) {
//                unset($versions[$time]);
//            }
//        }
//
//        $versions[strtotime($this->modified_on)] = array(
//            'path' => $file->path,
//            'type' => $this->storage_type,
//            'date' => $this->modified_on,
//            'user' => $this->modified_by
//        );
//
//        $params = $this->getParameters();
//        $params->versions = $versions;
//        $this->parameters = $params;
//    }

    /**
     * Get file versions sorted from newest to oldest
     *
     * @return array
     */
    public function getFileVersions()
    {
        $path     = $this->storage_path;
        $params   = $this->getParameters();
        $versions = $params->has('versions') ? KObjectConfig::unbox($params->versions) : array();
        krsort($versions);

        return $versions;
    }

    /**
     * Get a file version
     *
     * @param string $identifier
     * @return array|null
     */
    public function getFileVersion($identifier)
    {
        $version = null;

        foreach ($this->getFileVersions() as $key => $value)
        {
            if ($identifier == $key)
            {
                $version = $value;
                break;
            }
        }

        return $version;
    }

    /**
     * Get active file version
     *
     * @return array|null
     */
    public function getActiveVersion()
    {
        $identifier = $this->getParameters()->has('current_version') ? $this->getParameters()->current_version : null;
        return $this->getFileVersion($identifier);
    }

    /**
     * Checks for version with existing path
     *
     * @param $path
     * @return boolean
     */
    public function hasVersion($path)
    {
        $result = false;

        foreach ($this->getFileVersions() as $version)
        {
            if ($path == $version['path']) {
                $result = true;
                break;
            }
        }

        return $result;
    }
}