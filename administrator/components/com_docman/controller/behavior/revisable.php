<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanControllerBehaviorRevisable extends KControllerBehaviorAbstract
{
    /**
     * Populated in before.edit to hold a list of files being replaced
     *
     * @var array
     */
    protected $_path_cache;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->addCommandCallback('before.add', '_cache');
        $this->addCommandCallback('after.add', '_create');
        $this->addCommandCallback('before.edit', '_cache');
        $this->addCommandCallback('after.edit', '_create');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority'  => self::PRIORITY_LOWEST // Making sure organizable runs first
        ));

        parent::_initialize($config);
    }

    /**
     * Revert the file to a chosen version
     *
     * @param  KControllerContextInterface $context
     * @return void
     */
    protected function _actionRevert(KControllerContextInterface $context)
    {
        $document = $this->getModel()->fetch();
        $identifier = $context->request->data->version_id;
        $version = $document->getFileVersion($identifier);

        // Update the document file
        $document->storage_path = $version['path'];
        $document->storage_type = $version['type'];

        // Set active file version of the document
        $params = $document->getParameters();
        $params->current_version = $version['id'];

        $document->parameters = $params;
    }

    /**
     * Caches current storage path
     *
     * @param KControllerContextInterface $context
     */
    protected function _cache(KControllerContextInterface $context)
    {
        if ($context->request->query->has('id') && $context->request->query->id) {
            $entity = $this->getModel()->fetch();
        } else {
            $entity = $this->getModel()->create($context->request->data->toArray());  
        }

        $path = $entity->storage_path;

        // Mark the path of the overwriting file to add a new version and remove the old one
        if ($entity->isNew() || $entity->hasVersion($entity->storage_path)) {
            $this->_path_cache = $path . '_overwrite';
        } else {
            $this->_path_cache = $path;
        }
    }

    /**
     * Saves a version of the file attached to the document
     *
     * @param KControllerContextInterface $context
     */
    protected function _create(KControllerContextInterface $context)
    {
        $config = $this->getObject('com://admin/docman.model.configs')->fetch();

        if (!$config->file_versioning) {
            return;
        }

        $document = $context->result;

        // Only add a new version when the file is updated
        if ($this->_path_cache != $document->storage_path)
        {
            $identifier = uniqid();

            $versions = $this->_deleteOldVersion($document);
            $versions[$identifier] = array(
                'id'     => $identifier,
                'path'   => $document->storage_path,
                'type'   => $document->storage_type,
                'date'   => $document->modified_on == '0000-00-00 00:00:00' ? $document->created_on : $document->modified_on,
                'user'   => $document->modified_by ? $document->modified_by : $document->created_by
            );

            $params = $document->getParameters();
            $params->versions = $versions;
            $params->current_version = $identifier; // Set the current file version

            $document->parameters = $params;
            $document->save();
        }
    }

    /**
     * Deletes duplicate file and return the list of versions

     * @param $file
     * @return array
     */
    protected function _deleteOldVersion($document)
    {
        $file = $document->storage;
        $versions = $document->getFileVersions();

        // Remove overwritten version of the file
        foreach ($versions as $id => $version)
        {
            if ($version['path'] == $file->path) {
                unset($versions[$id]);
            }
        }

        return $versions;
    }
}
