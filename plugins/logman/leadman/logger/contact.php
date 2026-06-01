<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * LEADman Contact Logger
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanLeadmanLoggerContact extends ComLogmanActivityLogger
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'actions' => ['after.trash', 'after.restore']
        ]);

        parent::_initialize($config);
    }
    
    public function getActivityData(KModelEntityInterface $object, KObjectIdentifierInterface $subject, $action = null)
    {
        $data = parent::getActivityData($object, $subject, $action);

        $data['title'] = sprintf('%s', $object->email);
        $data['name']  = 'contact';

        if ($subject->getName() == 'form') {
            $data['metadata'] = array('form' => true);
        }

        return $data;
    }

    public function getActivityStatus(KModelEntityInterface $object, $action = null)
    {
        switch($action) {
            case 'after.trash':
                $status = 'trashed';
                break;
            case 'after.restore':
                $status = 'restored';
                break;
            default:
                $status = parent::getActivityStatus($object, $action);
                break;
        }

        return $status;
    }

    protected function _getController($config = array())
    {
        if (JComponentHelper::isInstalled('com_leadman') && JComponentHelper::getComponent('com_leadman', true)->enabled) {
            $config['log_guests_activities'] = true;
        }

        return parent::_getController($config);
    }
}