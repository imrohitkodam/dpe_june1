<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Group/Users Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanUsersActivityGroup extends PlgLogmanUsersActivityUsers
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'object_table'  => 'usergroups'
        ));

        parent::_initialize($config);
    }
}