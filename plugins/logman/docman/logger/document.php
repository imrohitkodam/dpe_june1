<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Document DOCman Logger
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanDocmanLoggerDocument extends ComLogmanActivityLogger
{
    public function getActivityData(KModelEntityInterface $object, KObjectIdentifierInterface $subject, $action = null)
    {
        $data = parent::getActivityData($object, $subject, $action);

        if ($data['name'] == 'submit') {
            $data['name'] = 'document';
        }

        if ($action == 'after.add' && $object->created_by) {
            $data['created_by'] = $object->created_by;
        }

        return $data;
    }
}