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
        
        $params = $this->module->params;
        $params->append(array('limit' => 20));

        if ($params->user_filter) {
            $params->user = $this->getObject('user')->getId();
        }

        switch ($params->impression_filter)
        {
            case 'impressions':
                $params->read = 1;
                break;

            case 'ignore_impressions':
                $params->read = 0;
        }

        $controller = $this->getObject('com://admin/logman.controller.activity');
        $controller->getRequest()->setQuery($params->toArray());
        $this->module->content = $controller->layout('list')->render();
        
        if (!empty($this->module->content)) {
            $result = parent::_actionRender($context);
        } 
        
        return $result;
    }
}