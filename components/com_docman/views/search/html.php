<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanViewSearchHtml extends ComDocmanViewHtml
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'auto_fetch' => false
        ));

        parent::_initialize($config);
    }

    public function isCollection()
    {
        return true;
    }

    /**
     * Override Pageable behavior to use permalink in search results
     * Permalink is a standalone link and not dependent to any DOCman page (menu item)
     *
     * @param $document
     * @param $params
     * @param $event_context
     * @return void
     */
    public function prepareDocument(&$document, $params, $event_context = 'com_docman.document')
    {
        parent::prepareDocument($document, $params, $event_context);

        if ($this->_isStandalone())
        {
             // Override download and title link for each document in the search result

            $document->download_link = $document->getPermalink();
            $document->title_link    = $document->getPermalink();
        }
    }

    protected function _fetchData(KViewContext $context)
    {
        $params = $this->_getParameters();

        $context->data->event_context = 'com_docman.documents';
        $context->data->documents = $this->getModel()->fetch();
        $context->data->total     = $this->getModel()->count();

        foreach ($context->data->documents as $document) {
            $this->prepareDocument($document, $params, $context->data->event_context);
        }

        parent::_fetchData($context);

        $context->parameters->total = $this->getModel()->count();

        // Standalone search page
        $query = $this->getActiveMenu()->query;
        $context->data->is_standalone = $this->_isStandalone();

        $this->_setSearchFilterData($context);
    }

    protected function _isStandalone()
    {
        return $this->getModel()->getState()->page ? false : true;
    }

    protected function _setSearchFilterData(KViewContext $context)
    {
        $context->data->filter = $this->getModel()->getState();

        $menu       = $this->getActiveMenu();
        $filter     = $context->data->filter;
        $tags       = !empty($menu->query['tag']) ? $menu->query['tag'] : array();
        $categories = !empty($menu->query['category']) ? $menu->query['category'] : array();
        $children   = isset($menu->query['category_children']) ? $menu->query['category_children'] : true;

        if (count($tags) === 1) {
            $menu->getParams()->set('show_tag_filter', false);
            $menu->getParams()->set('show_document_tags', false);
        }

        if (!$this->getObject('com://admin/docman.model.entity.config')->connectAvailable()) {
            $menu->getParams()->set('show_content_filter', false);
        }
        elseif ($filter->search_contents === null) {
            $filter->search_contents = true;
        }

        $category_filter = array(
            'access'       => $this->getObject('user')->getRoles(),
            'current_user' => $this->getObject('user')->getId(),
            'enabled'      => true
        );

        if ($categories)
        {
            if ($children) {
                $category_filter['parent_id'] = $categories;
                $category_filter['include_self'] = true;
            } else {
                $category_filter['id'] = $categories;
            }
        }

        $context->data->filter_toggled = ($context->parameters->total > $this->getModel()->getState()->limit)
            || (!empty($filter->search)
                || (!empty($filter->category) && $filter->category != $categories)
                || (!empty($filter->tag) && $filter->tag != $tags)
                || (!empty($filter->created_by)));

        $context->data->category_filter = $category_filter;

        $context->data->tag_model  = $this->getObject('com://admin/docman.model.pagetags');
        $context->data->tag_filter = [
            'access'       => $this->getObject('user')->getRoles(),
            'current_user' => $this->getObject('user')->getId(),
            'enabled'      => $this->getModel()->getState()->enabled,
            'status'       => $this->getModel()->getState()->status
        ];

        if (!$context->data->tag_model->setState($context->data->tag_filter->toArray())->count()) {
            $menu->getParams()->set('show_tag_filter', false);
        }

        // Hide owner filter to non-managers
        $menu->getParams()->set('show_owner_filter', $this->getObject('user')->authorise('core.manage', 'com_docman'));
    }

    /**
     * Get page parameters or return a default one for standalone search page
     *
     * @return mixed
     */
    protected function _getParameters()
    {
        $query = $this->getActiveMenu()->query;

        if ($query['option'] != 'com_docman' && $query['view'] != 'search')
        {
            // Default page parameters for standalone search page
            $this->setParameters(new JRegistry([
                'show_pagination' => 1,
                'show_document_title' => 1,
                'document_title_link' => 'download',
                'show_document_tags' => 1,
                'show_document_description' => 1,
                'show_document_icon' => 1,
                'show_document_image' => 1,
                'show_document_recent' => 1,
                'show_document_popular' => 1,
                'show_document_category' => 1,
                'show_document_created' => 1,
                'show_document_created_by' => 1,
                'show_document_modified' => 1,
                'show_document_filename' => 1,
                'show_document_size' => 1,
                'show_document_hits' => 1,
                'show_document_extension' => 1,
                'track_downloads' => 1,
                'allow_multi_download' => 1,
                'show_player' => 1
            ]));
            $params = $this->getParameters();
        }
        else $params = $this->getParameters();

        return $params;
    }
}
