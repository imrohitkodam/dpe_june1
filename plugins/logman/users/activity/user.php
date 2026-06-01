<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * User/Users Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanUsersActivityUser extends PlgLogmanUsersActivityUsers
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'objects'      => array('application'),
            'format'       => null,
            'object_table' => 'users'
        ));

        parent::_initialize($config);
    }

    public function getActivityFormat()
    {
        if (!$this->_format)
        {
            $format = '{actor} {action} ';

            if ($this->_isEditOwn()) {
                $format .= 'own {object.subtype} {object.type}';
            } elseif ($this->_isRegistration() || $this->_isSelfLog()) {
                $format .= '{application}';
            } else {
                $format .= '{object.type} name {object}';
            }

            $this->_format = $format;
        }

        return parent::getActivityFormat();
    }

    public function getActivityApplication()
    {
        $application = null;

        if ($this->_isRegistration() || $this->_isSelfLog()) {
            $application = $this->_getObject(array('objectName' => $this->application));
        }

        return $application;
    }

    public function getPropertyImage()
    {
        $images = array('login' => 'k-icon-person', 'logout' => 'k-icon-power-standby');

        if (in_array($this->verb, array_keys($images))) {
            $image = $images[$this->verb];
        } else {
            $image = parent::getPropertyImage();
        }

        return $image;
    }

    public function getPropertyObject()
    {
        $object = null;

        // Self login and logout activities do not have an object.
        if (!$this->_isSelfLog()) {
            $object = parent::getPropertyObject();
        }

        return $object;
    }

    protected function _actionConfig(KObjectConfig $config)
    {
        if ($this->_isRegistration()) {
            $config->append(array('objectName' => 'registered'));
        }

        parent::_actionConfig($config);
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        if ($this->_isEditOwn()) {
            $config->append(array('type' => array('objectName' => 'profile', 'object' => true)));
        } else {
            $config->append(array('subtype' => null));
        }

        parent::_objectConfig($config);
    }

    /**
     * Tells if the current activity is a self login/logout.
     *
     * @return bool True if it is, false otherwise.
     */
    protected function _isSelfLog()
    {
        return (bool) ($this->verb == 'login' || ($this->verb == 'logout' && ($this->created_by == $this->row)));
    }

    /**
     * Tells is the current activity is a user registration.
     *
     * @return bool True if it is, false otherwise.
     */
    protected function _isRegistration()
    {
        return (bool) ($this->verb == 'add' && $this->application == 'site');
    }

    /**
     * Tells if the current activity is an own edit.
     *
     * @return bool True if it is, false otherwise.
     */
    protected function _isEditOwn()
    {
        return (bool) ($this->verb == 'edit' && $this->row == $this->created_by);
    }
}