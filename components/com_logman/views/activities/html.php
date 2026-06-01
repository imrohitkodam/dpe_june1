<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Html Activities View
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanViewActivitiesHtml extends ComKoowaViewHtml
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('links' => false));

        parent::_initialize($config);
    }

    protected function _fetchData(KViewContext $context)
    {
        $params = JFactory::getApplication()->getMenu()->getActive()->getParams();

        $context->data->append(array(
            'params'     => $params,
            'show_date'  => $params->get('show_date'),
            'show_time'  => $params->get('show_time'),
            'show_icons' => $params->get('show_icons'),
        ));

        if ($next = $this->getModel()->getTable()->getNext())
        {
            $url = $this->getRoute(sprintf('tmpl=component&offset=%s&direction=%s', key($next), current($next)), false, false);
            $context->data->append(array('next' => $url));
        }

        $method = '_prepare' . ucfirst($this->getLayout());
        $this->$method($context);

        return parent::_fetchData($context);
    }

    public function getModel()
    {
        $model  = parent::getModel();
        $params = JFactory::getApplication()->getMenu()->getActive()->getParams();

        switch ($params->get('impression_filter'))
        {
            case 'impressions':
                $params->read = 1;
                break;

            case 'ignore_impressions':
                $params->read = 0;
        }

        if (isset($params->read)) {
            $model->getState()->set('read', $params->read);
        }

        return $model;
    }

    protected function _prepareDefault(KViewContext $context)
    {
        $this->_prepareExport($context);
    }

    protected function _prepareExport(KViewContext $context)
    {
        $url = $this->getRoute('format=csv', false, false);

        $query = $url->getQuery(true);

        if (isset($query['offset'])) unset($query['offset']);
        if (isset($query['limit'])) unset($query['limit']);

        $url->setQuery($query);

        $context->data->export_url = $url;
    }
}