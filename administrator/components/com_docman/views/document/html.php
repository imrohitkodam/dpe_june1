<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanViewDocumentHtml extends ComDocmanViewHtml
{
    /**
     * Number of activities
     */
    protected $_activities_limit = 10;

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'decorator' => 'koowa'
        ]);

        parent::_initialize($config);
    }

    protected function _fetchData(KViewContext $context)
    {
        $context->data->tag_count      = $this->getObject('com://admin/docman.model.tags')->count();
        $context->data->can_create_tag = $this->getObject('com://admin/docman.model.configs')->fetch()->can_create_tag;
        $context->data->hide_tag_field = $context->data->tag_count == 0 && !$context->data->can_create_tag;

        $config = $this->getObject('com://admin/docman.model.configs')->fetch();
        $context->data->file_versioning = $config->file_versioning;

        parent::_fetchData($context);

        $this->_activities($context);
      
        $context->data->document->setProperty('automatic_humanized_titles', $this->getObject('com://admin/docman.model.entity.config')->automatic_humanized_titles);

        // Check for pending auto-thumbnail featured image
        $document = $context->data->document;

        if (!$document->isNew())
        {
            $scan = $this->getObject('com://admin/docman.model.scans')->identifier($document->uuid)->fetch();
            $pending_scan = !$scan->isNew() && $scan->thumbnail == 1 && $scan->status == \ComDocmanControllerBehaviorScannable::STATUS_PENDING;
        }
        else $pending_scan = false;

        $document->setProperty('pending_scan', $pending_scan);
    }

    /**
     * Render activities
     * 
     * @param KViewContext $context
     * @return void
     */
    protected function _activities(KViewContext $context)
    {
        $document = $context->data->document;

        if (!$document->isNew() && JComponentHelper::isInstalled('com_logman'))
        {
            $activities = new stdClass();

            $model = $this->getObject('com://admin/logman.model.activities');

            $activities->count = $model->package('docman')
                ->name('document')
                ->row($document->id)
                ->count();

            if ($activities->count) {
                $activities->entities = $model->sort('created_on')->direction('desc')->limit($this->_activities_limit)->fetch();
            } else {
                $activities->entities = array();
            }

            $context->data->show_view_more = $activities->count > $this->_activities_limit;
            $context->data->activities     = $activities;
        }
    }
}