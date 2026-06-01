<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * LEADman Contact Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanLeadmanActivityContact extends PlgLogmanLeadmanActivityLeadman
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'object_table'  => 'leadman_contacts',
            'object_column' => 'leadman_contact_id'
        ));

        $data = $config->data;

        if ($metadata = $data->metadata)
        {
            if (isset($metadata->form) && $metadata->form == true && $data->action == 'add') {
                $config->append(array('format' => '{actor} filled form {object}'));
            }
        }

        if ($data->created_by && $data->action != 'add')
        {
            $user = $this->getObject('user.provider')->load($data->created_by);

            if ($user->getEmail() == $data->title) {
                $config->append(array('format' => '{actor} {action} its own {object}'));
            }
        }

        parent::_initialize($config);
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        if ($this->_format == '{actor} {action} its own {object}') {
            $config->append(array('objectName' => 'contact'));
        }

        parent::_objectConfig($config);
    }

}