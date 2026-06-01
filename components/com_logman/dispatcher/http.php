<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Http Dispatcher
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanDispatcherHttp extends ComKoowaDispatcherHttp
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('controller' => 'activity', 'behaviors' => array('permissible', 'docman')));

        parent::_initialize($config);
    }

    /**
     * Loads the admin translations instead of site
     *
     * @param KControllerContextInterface $context
     */
    protected function _loadTranslations(KControllerContextInterface $context)
    {
        $this->getObject('translator')->load('com://admin/logman');
    }

    public function getRequest()
    {
        if (!$this->_request instanceof KDispatcherRequestInterface)
        {
            $request = parent::getRequest();

            $query = $request->getQuery();

            if ($query->view != 'linker')
            {
                $query->context = 'site';

                $query->levels = $this->getUser()->getRoles();

                $query->direction = 'DESC';

                $user = $this->getObject('user');

                // Never display read actions in frontend streams
                $query->read = 0;

                // Only show frontend activities for frontend users.
                if (!$user->authorise('core.login.admin')) {
                    $query->application = 'site';
                }

                $menu = JFactory::getApplication()->getMenu()->getActive();

                if ($menu)
                {
                    $params = $menu->getParams();

                    // Force limit.
                    $query->limit = $params->get('limit');

                    // Filter by user groups.
                    $query->usergroup = $params->get('usergroup');

                    if ($params->get('show_own_activities'))
                    {
                        if ($user->isAuthentic()) {
                            $query->user = $user->getId();
                        }
                        else $query->user = -1; // Use invalid user for showing an empty list.
                    }

                    if ($packages_actions = $params->get('packages_actions'))
                    {
                        if (isset($packages_actions->packages)) {
                            $query->package = $packages_actions->packages;
                        }

                        if (isset($packages_actions->actions)) {
                            $query->resource_action = $packages_actions->actions;
                        }
                    }
                }
            }
        }

        return $this->_request;
    }

    public function getController()
    {
        if (!($this->_controller instanceof KControllerInterface))
        {
            $controller = parent::getController();

            if ($controller->getIdentifier()->getName() == 'activity')
            {
                $internals = array('application', 'sort', 'limit', 'offset');

                $model = $controller->getModel();
                $state = $model->getState();

                foreach ($internals as $internal) {
                    $state->setProperty($internal, 'internal', true);
                }

                $request = $this->getRequest();

                if ($request->isAjax()) {
                    $model->fixOffset(false);
                    $state->setValues($request->getQuery()->toArray());
                }
            }
        }

        return $this->_controller;
    }
}