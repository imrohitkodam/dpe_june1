<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activity Controller
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
#[\AllowDynamicProperties]
class ComLogmanControllerActivity extends ComActivitiesControllerActivity
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        // Need to load component translations for HMVC calls
        $this->getObject('translator')->load('com://admin/logman');

        $this->addCommandCallback('before.render', '_setPluginWarning');
        $this->addCommandCallback('before.render', '_cleanup');

        $this->addCommandCallback('before.add', '_checkGroups');
        $this->addCommandCallback('before.add', '_checkActor');

        $this->addCommandCallback('after.add', '_handleErrors');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'model'                 => 'activities',
            'toolbars'              => array('menubar', 'activity'),
            'formats'               => array('csv', 'rss'),
            'ignored_groups'        => (array) JComponentHelper::getParams('com_logman')->get('ignored_groups'),
            'log_guests_activities' => (bool) JComponentHelper::getParams('com_logman')->get('log_guest_actions'),
            'behaviors'             => array(
                'editable',
                'singleable',
                'persistable',
                'restrictable' => array('redirect_url' => 'index.php?option=com_logman&view=config'),
                'com://admin/logman.controller.behavior.exportable.csv',
                'com:activities.controller.behavior.resourceable' => array('actions' => array('uninstall'))
            )
        ));

        parent::_initialize($config);
    }

    /**
     * Bypass parent getRequest call to avoid package model state being forced.
     */
    public function getRequest()
    {
        return KControllerAbstract::getRequest();
    }

    /**
     * Performs a check so that activities from users that belong to the ignored groups are not logged.
     *
     * @param KControllerContextInterface $context The command context
     * @return bool True if activity can be logged, false otherwise.
     */
    protected function _checkGroups(KControllerContextInterface $context)
    {
        $result = true;
        $data   = $context->request->data;

        if($data->created_by) {
            $user = $this->getObject('user.provider')->load($data->created_by);
        } else {
            $user = $this->getObject('user');
        }

        $user_groups    = array_values(KObjectConfig::unbox($user->getGroups()));
        $ignored_groups = KObjectConfig::unbox($this->getConfig()->ignored_groups);

        // Do not log actions from users on ignored user groups.
        if (array_intersect($user_groups, $ignored_groups)) {
            $result = false;
        }

        return $result;
    }

    /**
     * Performs a check so that only activities from registered users are logged.
     *
     * @param KControllerContextInterface $context The command context.
     * @return bool True if activity can be logged, false otherwise.
     */
    protected function _checkActor(KControllerContextInterface $context)
    {
        $result = $this->getConfig()->log_guests_activities;

        if (!$result && ($context->user->isAuthentic() || $context->request->data->created_by)) {
            $result = true;
        }

        return $result;
    }

    /**
     * Error handler.
     *
     * @param KControllerContextInterface $context
     */
    protected function _handleErrors(KControllerContextInterface $context)
    {
        $result = $context->result;

        if ($result->getStatus() !== KDatabase::STATUS_CREATED)
        {
            if (JFactory::getApplication()->getCfg('debug'))
            {
                // Notify user about error.
                $translator = $this->getObject('com://admin/logman.translator');
                $message    = $result->getStatusMessage();
                $context->getResponse()->addMessage($translator->translate('Error while adding Activity',
                    array('message' => $message)), 'notice');
            }
        }
    }

    protected function _setPluginWarning(KControllerContextInterface $context)
    {
        if ($this->isDispatched() && !$this->pluginEnabled())
        {
            if ($this->getRequest()->query->tmpl !== 'koowa')
            {
                $message = $this->getObject('translator')->translate('Please note that LOGman is disabled right now.');
                $context->getResponse()->addMessage($message, KControllerResponse::FLASH_NOTICE);
            }
        }
    }

    protected function _cleanup()
    {
        $params = JComponentHelper::getParams('com_logman');

        if ($max_age = (int) $params->get('maximum_age'))
        {
            // Get a clone without the current request
            $controller = $this->getObject((string) $this->getIdentifier());

            $end_date = $this->getObject('lib:date');
            $end_date->modify(sprintf('-%d days', $max_age));
            $controller->end_date($end_date->format('Y-m-d'))->purge();
        }
    }

    protected function _actionEditPlugin(KControllerContextInterface $context)
    {
        $value = $context->request->data->enabled;
        $id    = $this->_getPluginId();

        $query = 'UPDATE #__extensions SET enabled = %d WHERE extension_id = %d';

        $db = JFactory::getDBO();
        $db->setQuery(sprintf($query, $value, $id));

        return $db->execute();
    }

    protected function _getPluginId()
    {
        $db = JFactory::getDbo();
        $query = /** @lang text */"SELECT extension_id FROM #__extensions
            WHERE type = 'plugin' AND element = 'logman' AND folder = 'koowa'
            LIMIT 1
        ";

        $db->setQuery($query);

        return $db->loadResult();
    }

    public function pluginEnabled()
    {
        $query = 'SELECT enabled FROM #__extensions WHERE extension_id = %d';

        $db = JFactory::getDBO();
        $db->setQuery(sprintf($query, $this->_getPluginId()));

        return !!$db->loadResult();
    }

    protected function _actionLog(KControllerContextInterface $context)
    {
        $param = $context->param;

        $param->append(array(
            'extension'   => 'com',
            'application' => JFactory::getApplication()->isClient('administrator') ? 'admin' : 'site',
            'object'      => array(
                'metadata' => array(),
                'table'    => KStringInflector::pluralize($param->object->type),
                'column'   => 'id'
            )
        ));

        // Store table, identity column and activity format as metadata
        $param->object->metadata->append(array(
            '_logman' => array(
                'object_table'  => $param->object->table,
                'object_column' => $param->object->column,
                'format'        => $param->format,
                'url'           => $param->object->url
            )
        ));

        $data = array();

        $data['type']        = $param->extension;
        $data['package']     = $param->object->package;
        $data['name']        = $param->object->type;
        $data['row']         = $param->object->id;
        $data['title']       = $param->object->name;
        $data['metadata']    = KObjectConfig::unbox($param->object->metadata);
        $data['status']      = $param->result;
        $data['action']      = $param->verb;
        $data['application'] = $param->application;

        if ($actor = $param->actor) {
            $data['created_by'] = $actor;
        }

        return $this->add($data);
    }

    protected function _afterLog(KControllerContextInterface $context)
    {
        $activity = $context->result;

        $config = $this->getObject('com://admin/logman.model.configs')->fetch();

        if (!isset($config['_logman'])) {
            $config['_logman'] = array('api_actions' => array());
        }

        $logman = $config['_logman'];

        $package  = $activity->package;
        $resource = $activity->name;
        $action   = $activity->verb;

        if(!isset($logman['api_actions'][$package])) {
            $logman['api_actions'][$package] = array();
        }

        if (!isset($logman['api_actions'][$package][$resource])) {
            $logman['api_actions'][$package][$resource] = array();
        }

        if (!in_array($action, $logman['api_actions'][$package][$resource]))
        {
            $logman['api_actions'][$package][$resource][] = $action;
            $config['_logman']                            = $logman;

            $config->save();
        }
    }
}