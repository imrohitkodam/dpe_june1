<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * LEADman Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanLeadmanActivityLeadman extends ComLogmanModelEntityActivity
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'format' => '{actor} {action} {object.type} {object}'
        ));

        parent::_initialize($config);
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $config->append(array('url' => array('admin' => sprintf('option=com_leadman&view=contact&id=%s', $this->row))));

        $user = $this->getObject('user');

        if ($user->authorise('core.add','com_leadman') || $user->authorise('core.edit', 'com_leadman')) {
            $config->append(array('url' => array('site' => sprintf('option=com_leadman&view=contact&id=%s', $this->row))));
        }

        parent::_objectConfig($config);
    }
}