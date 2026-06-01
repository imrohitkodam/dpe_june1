<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * LEADman Comment Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanLeadmanActivityComment extends ComLogmanModelEntityActivity
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'format'        => '{actor} {action} {object.type} {object}',
            'object_table'  => 'comments',
            'object_column' => 'comments_comment_id'
        ));

        parent::_initialize($config);
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $contact = $this->getMetadata()->contact_id;

        $url = sprintf('option=com_leadman&view=contact&id=%s#comment_%s', $contact, $this->row);

        $config->append(array(
            'objectName' => 'comment',
            'translate'  => true,
            'url'        => array('admin' => $url),
            'type'       => array('objectName' => 'contact', 'object' => true)
        ));

        $user = $this->getObject('user');

        if ($user->authorise('core.add','com_leadman') || $user->authorise('core.edit', 'com_leadman')){
            $config->append(array('url' => array('site' => $url)));
        }

        parent::_objectConfig($config);
    }
}