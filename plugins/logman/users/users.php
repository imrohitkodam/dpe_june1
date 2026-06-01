<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Users LOGman Plugin.
 *
 * Provides handlers for dealing with com_users events.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanUsers extends ComLogmanPluginJoomla
{
    /**
     * @var JUser An instance of the current group object to be shared among events.
     */
    protected $_group = null;

    protected function _getNoteObjectData($data, $event)
    {
        return array('id' => $data->id, 'name' => $data->subject);
    }

    /**
     * User login event handler.
     *
     * @param $user
     * @param $options
     */
    public function onUserLogin($user, $options = array())
    {
        // Check if login events should be logged.
        if ($this->getParameter('logman_log_login_events') && (!isset($options['type']) || $options['type'] !== 'jwt'))
        {
            $user = $this->getObject('user.provider')->load(JUserHelper::getUserId($user['username']));

            if ($user->isEnabled())
            {
                $this->logActivity(array(
                    'object' => array(
                        'package' => 'users',
                        'type'    => 'user',
                        'id'      => $user->getId(),
                        'name'    => $user->getName()),
                    'verb'   => 'login',
                    'actor'  => $user->getId(),
                    'result' => 'logged in'
                ));
            }
        }
    }

    /**
     * User logout event handler.
     *
     * @param $user
     * @param $options
     */
    public function onUserLogout($user, $options = array())
    {
        if ($this->getParameter('logman_log_login_events') && (!isset($options['type']) || $options['type'] !== 'jwt'))
        {
            $user = $this->getObject('user.provider')->load($user['id']);

            $this->logActivity(array(
                'object' => array(
                    'package' => 'users',
                    'type'    => 'user',
                    'id'      => $user->getId(),
                    'name'    => $user->getName()),
                'verb'   => 'logout',
                'result' => 'logged out'
            ));
        }
    }

    /**
     * After user group save event handler.
     *
     * @param $context
     * @param $data
     * @param $isNew
     */
    public function onUserAfterSaveGroup($context, $data, $isNew)
    {
        $this->logActivity(array(
            'object' => array(
                'package' => 'users',
                'type'    => 'group',
                'id'      => $data->id,
                'name'    => $data->title),
            'verb'   => $isNew ? 'add' : 'edit'
        ));
    }

    /**
     * Before user group delete event handler.
     *
     * @param $group_properties
     */
    public function onUserBeforeDeleteGroup($group_properties)
    {
        // Store a copy of the group instance for future use.
        $group = JTable::getInstance('Usergroup', 'JTable');
        $group->load($group_properties['id']);
        $this->_group = $group;
    }

    /**
     * After user group delete event handler.
     *
     * @param $group_properties
     * @param $mysterious_arg
     * @param $error
     */
    public function onUserAfterDeleteGroup($group_properties, $mysterious_arg, $error)
    {
        if (!$error)
        {
            $this->logActivity(array(
                'object' => array(
                    'package' => 'users',
                    'type'    => 'group',
                    'id'      => $this->_group->id,
                    'name'    => $this->_group->title),
                'verb'   => 'delete'
            ));
        }
    }

    /**
     * After user save event handler.
     *
     * @param $user
     * @param $isNew
     * @param $success
     * @param $msg
     */
    public function onUserAfterSave($user, $isNew, $success, $msg)
    {
        if ($success)
        {
            $user = $this->getObject('user.provider')->load($user['id']);
            $actor = $this->getObject('user')->getId();

            if (!$actor && $isNew) {
                $actor = $user->getId(); // User is registering from frontend
            }

            // If there's no actor do not log an add/edit activity.
            if ($actor)
            {
                $this->logActivity(array(
                        'object' => array(
                            'package' => 'users',
                            'type'    => 'user',
                            'id'      => $user->getId(),
                            'name'    => $user->getName()),
                        'verb'   => $isNew ? 'add' : 'edit',
                        'actor'  => $actor,
                    )
                );
            }
        }
    }

    /**
     * After user delete event handler.
     *
     * @param $user
     * @param $success
     * @param $msg
     */
    public function onUserAfterDelete($user, $success, $msg)
    {
        if ($success)
        {
            $this->logActivity(array(
                'object' => array(
                    'package' => 'users',
                    'type'    => 'user',
                    'id'      => $user['id'],
                    'name'    => $user['name']),
                'verb'   => 'delete'
                )
            );
        }
    }

    protected function _getItems($ids, KObjectConfig $config)
    {
        if ($config->type == 'note') {
            $config->prefix = 'UsersTable';
        }

        return parent::_getItems($ids, $config);
    }
}