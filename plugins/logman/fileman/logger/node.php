<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Nodde FILEman Logger
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanFilemanLoggerNode extends ComLogmanActivityLogger
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'title_column' => 'name',
            'actions'      => array('after.move', 'after.copy')
        ));

        parent::_initialize($config);
    }

    public function log($action, KModelEntityInterface $object, KObjectIdentifierInterface $subject)
    {
        if ($subject->package == 'fileman') {
            parent::log($action, $object, $subject);
        }
    }

    public function getActivitySubject(KCommandInterface $context)
    {
        $identifier = $context->subject->getIdentifier()->toArray();

        $container = explode('-', $context->result->container);
        $container = $container[0];

        if ($container) {
            $identifier['package'] = $container;
        }

        return $this->getIdentifier($identifier);
    }

    public function getActivityData(KModelEntityInterface $object, KObjectIdentifierInterface $subject, $action = null)
    {
       $data = parent::getActivityData($object, $subject, $action);

        if (in_array($action, array('after.move', 'after.copy')) && isset($object->destination_folder))
        {
            $object                = clone $object;
            $object->source_folder = $object->folder;
            $object->folder        = $object->destination_folder;
        }

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

        if (isset($object->source_folder)) {
            $data['metadata']['source'] = array('folder' => $object->source_folder);
        }

        if ($object->getIdentifier()->name == 'file')
        {
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