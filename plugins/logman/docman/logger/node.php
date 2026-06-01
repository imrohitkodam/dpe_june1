<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Node DOCman Logger
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanDocmanLoggerNode extends ComLogmanActivityLogger
{
    protected $_uploaded_files = array();

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'title_column' => 'name',
            'actions'      => array('after.move', 'after.copy', 'before.move')
        ));

        parent::_initialize($config);
    }

    public function log($action, KModelEntityInterface $object, KObjectIdentifierInterface $subject)
    {
        $can_log = true;

        // Check if a move action should be logged as an upload action (moved from tmp folder)
        if ($action == 'before.move')
        {
            if ($object->folder == 'tmp' && $object->destination_folder)
            {
                $object->folder = $object->destination_folder;

                $this->_uploaded_files[$object->fullpath] = true;

                $object->folder = 'tmp';
            }

            $can_log = false;
        }

        // Do not log uploads to tmp folder
        if ($action == 'after.add' && ($object->getIdentifier()->name == 'file') && $object->folder == 'tmp') {
            $can_log = false;
        }

        if ($can_log && $subject->package == 'docman' && !in_array($object->container, array('docman-icons', 'docman-images'))) {
            parent::log($action, $object, $subject);
        }
    }

    public function getActivitySubject(KCommandInterface $context)
    {
        $identifier = $context->subject->getIdentifier()->toArray();

        if ($context->result)
        {
            $container = explode('-', $context->result->container);
            $container = $container[0];

            if ($container) {
                $identifier['package'] = $container;
            }
        }

        return $this->getIdentifier($identifier);
    }

    public function getActivityData(KModelEntityInterface $object, KObjectIdentifierInterface $subject, $action = null)
    {
        $data = parent::getActivityData($object, $subject, $action);

        $container = $object->getContainer();

        // Use container:path as row identifier.
        $data['row'] = $object->container . ':' . $object->path;

        $data['name'] = $object->getIdentifier()->name;

        // Get the application name from Joomla!.
        $data['application'] = JFactory::getApplication()->getName() == 'site' ? 'site' : 'admin';

        $data['metadata'] = array(
            'name'      => $object->name,
            'folder'    => $object->folder,
            'path'      => $object->path,
            'container' => array(
                'id'    => $container->id,
                'slug'  => $container->slug,
                'title' => $container->title
            )
        );

        // Log tmp folder create actions as an internal activity
        if ($action == 'after.add' && ($object->container == 'docman-files') && ($object->getIdentifier()->name == 'folder') && $object->name == 'tmp') {
            $data['created_by'] = -1;
        }

        if ($object->getIdentifier()->name == 'file')
        {
            if (isset($this->_uploaded_files[$object->fullpath]))
            {
                $data['status'] = 'uploaded';
                $data['action'] = 'upload';
            }

            if ($size = $object->size) {
                $data['metadata']['size'] = $size;
            }

            if ($object->isImage())
            {
                $data['metadata']['image']  = true;
                $data['metadata']['width']  = $object->width;
                $data['metadata']['height'] = $object->height;
            }
            else $data['metadata']['image'] = false;
        }

        return $data;
    }

    public function getActivityStatus(KModelEntityInterface $object, $action = null)
    {
        switch($action) {
            case 'after.move':
                $status = 'moved';
                break;
            case 'after.copy':
                $status = 'copied';
                break;
            default:
                $status = parent::getActivityStatus($object, $action);
                break;
        }

        return $status;
    }
}