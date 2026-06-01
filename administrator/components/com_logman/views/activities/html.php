<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Html View
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanViewActivitiesHtml extends ComKoowaViewHtml
{
    protected function _fetchData(KViewContext $context)
    {
        $method = '_prepare' . ucfirst($this->getLayout());
        $this->$method($context);

        parent::_fetchData($context);
    }

    protected function _preparePurge(KViewContext $context)
    {
        $date = $this->getObject('lib:date');
        $date->modify('-90 days');
        $context->data->end_date = $date->format('Y-m-d');
    }

    protected function _prepareExport(KViewContext $context)
    {
        $url = $this->getRoute('format=csv', false, false);

        $query = $url->getQuery(true);

        if (isset($query['offeset'])) unset($query['offset']);
        if (isset($query['limit'])) unset($query['limit']);

        $url->setQuery($query);

        $context->data->export_url = $url;
    }

    protected function _prepareList(KViewContext $context)
    {
        $context->data->view_all = $this->getObject('user')->authorise('core.manage', 'com_logman');
    }

    protected function _prepareDefault(KViewContext $context)
    {
        $this->_prepareExport($context);
        $this->_preparePurge($context);

        $query = $this->getObject('lib:database.query.select')
                      ->columns('package')
                      ->table('logman_activities')
                      ->distinct();

        $context->data->packages = $this->getModel()->getTable()->getAdapter()->select($query, KDatabase::FETCH_FIELD_LIST);

        // Determine if own activities should be greyed out or not.
        if ($this->getModel()->getState()->user == $this->getObject('user')->id)
        {
            // Filtering by current logged user => we do not grey out.
            $context->data->grey_self = false;
        }
        else
        {
            // We do grey out.
            $context->data->grey_self = true;
        }

        /*
         * You would think that Joomla menu already loads the necessary language files.
         * Well it does but after the component has been rendered so we need to do this ourselves
         */
        foreach ($context->data->packages as $package) {
            ComLogmanActivityTranslator::loadSysIni('com_' . $package);
        }
    }
}
