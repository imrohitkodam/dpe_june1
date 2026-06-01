<?php
/**
 * @package     TEXTman
 * @copyright   Copyright (C) 2017 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComLogmanViewImpressionsHtml extends ComKoowaViewHtml
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'auto_fetch' => false,
        ));

        parent::_initialize($config);
    }

    protected function _fetchData(KViewContext $context)
    {
        $state = clone $this->getModel()->getState();

        $state->setValues(array('package_name' => array('k2.item', 'content.article')));

        $model = $this->getObject('com://admin/logman.model.impressions');

        $context->data->articles = $model->initialize($state)
                                         ->group_by('url')
                                         ->sort('total')
                                         ->direction('DESC')
                                         ->limit(10)
                                         ->fetch();

        $callback = function(KModelContextInterface $context) {
            $context->query->where('tbl.referrer IS NOT NULL');
        };

        $model->addCommandCallback('before.fetch', $callback);

        $context->data->referrers = $model->initialize($state)
                                          ->internal(0)
                                          ->group_by('referrer')
                                          ->sort('total')
                                          ->direction('DESC')
                                          ->limit(10)
                                          ->fetch();

        $model->removeCommandCallback('before.fetch', $callback);

        $context->data->views = $model->initialize($state)->views();

        $context->data->visitors = $model->initialize($state)->visitors();

        $context->data->views_per_visit = number_format($model->initialize($state)->viewsPerVisit(), 2);

        $context->data->start_date = $state->start_date;
        $context->data->end_date   = $state->end_date;
        $context->data->package    = $state->package;

        parent::_fetchData($context);
    }
}
