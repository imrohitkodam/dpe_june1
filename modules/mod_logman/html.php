<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * LOGman Module
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Module\LOGman
 */
class ModLogmanHtml extends ModKoowaHtml
{
    protected function _actionRender(KViewContext $context)
    {
        $result = '';

        $translator = $this->getObject('translator');

        // Load translations.
        $translator->load('com:activities');
        $translator->load('com//admin/logman');

        $params = $this->module->params;
        $params->append(array('limit' => 10));

        $user = $this->getObject('user');

        if ($params->user_filter)
        {
            if ($user->isAuthentic()) {
                $params->user = $user->getId();
            }
            else $params->user = -1;
        }

        switch ($params->impression_filter)
        {
            case 'impressions':
                $params->read = 1;
                break;

            case 'ignore_impressions':
                $params->read = 0;
        }

        // Only show frontend activities for frontend users.
        if (!$user->authorise('core.admin.login')) {
            $params->application = 'site';
        }

        if (isset($params['package']) && isset($params['package']['packages']))
        {
            if (isset($params['package']['actions'])) {
                $params->resource_action = $params['package']['actions'];
            }

            $params->package = $params['package']['packages'];
        }

        $params->levels = $this->getObject('user')->getRoles();

        $model = $this->getObject('com://admin/logman.model.activities')->setState(KObjectConfig::unbox($params));

        $context->data->params      = $params;
        $context->parameters->total = $model->count();
        $context->data->activities  = $model->fetch();
        $context->data->links       = $user->authorise('core.admin.login');


        return parent::_actionRender($context);
    }
}