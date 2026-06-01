<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Contact LOGman Plugin.
 *
 * Provides event handlers for dealing with com_contact events.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanContact extends ComLogmanPluginJoomla
{
    protected function _getContactObjectData($data, $event)
    {
        return array('id' => $data->id, 'name' => $data->name);
    }

    public function onSubmitContact($contact, $data)
    {
        $form_name  = @$data['contact_name'];
        $form_email = @$data['contact_email'];

        $this->logActivity(array(
            'object'   => array(
                'package' => 'contact',
                'type'    => 'contact',
                'name'    => $contact->name,
                'id'      => $contact->id,
                'metadata' => array(
                    'sender' => array(
                        'name'  => $form_name,
                        'email' => $form_email
                    )
                )),
            'result'   => 'contacted',
            'verb'     => 'contact'
        ));
    }

    protected function _getItems($ids, KObjectConfig $config)
    {
        $config->append(array('prefix' => 'ContactTable'));

        return parent::_getItems($ids, $config);
    }
}