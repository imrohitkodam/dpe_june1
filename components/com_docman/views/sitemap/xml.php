<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanViewSitemapXml extends KViewTemplate
{
    /**
     * @var array Views
     */
    protected $_docman_views = array();

    /**
     * Constructor
     *
     * @param   KObjectConfig $config Configuration options
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_docman_views = KObjectConfig::unbox($config->docman_views);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'behaviors'    => array('pageable'),
            'docman_views' => array('list', 'flat', 'tree')
        ));

        parent::_initialize($config);
    }

    public function isCollection()
    {
        return true;
    }

    protected function _fetchData(KViewContext $context)
    {
        $context->data->event_context = 'com_docman.documents';
        $pages = $this->getObject('com:docman.model.pages')->view($this->_docman_views)->fetch();
        $documents = array();

        foreach ($pages as $page)
        {
            // Parameters of each menu item
            $this->setParameters($page->params);

            // Passed-in the itemid for correct menu routing, see prepareDocument()
            $params = $this->getParameters();
            $params->set('itemid', $page->id);

            $documents = array_merge($documents, $this->_getDocuments($page->query, $params, $context));
        }

        $context->data->documents = $documents;
    }

    protected function _actionRender(KViewContext $context)
    {
        // Prepend the xml prolog
        $result  = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
        $result .=  parent::_actionRender($context);

        return $result;
    }

    /**
     * Fetch documents
     *
     * @param array $query
     * @param KObjectDecoratorInterface $params
     * @param KViewContextInterface $context
     * @return array
     */
    protected function _getDocuments(array $query, KObjectDecoratorInterface $params, KViewContextInterface $context)
    {
        $request           = $this->getObject('request');
        $category_children = isset($query['category_children']) ? $query['category_children'] : null;
        $slug              = isset($query['slug']) ? $query['slug'] : null;
        $access            = $request->query->access;
        $category          = isset($query['category']) ? $query['category'] : null;

        switch ($query['view'])
        {
            case 'flat':
                $controller = $this->getObject('com://site/docman.controller.flat')
                                   ->access($access)
                                   ->category_children($category_children)
                                   ->category($category)
                                   ->format('json');

                $entities = $controller->render()->entities;
                break;

            case 'list':
                $controller = $this->getObject('com://site/docman.controller.list')
                                   ->access($access)
                                   ->format('json');

                if ($slug)
                {
                    $controller->slug($slug);
                    $entities = $controller->render()->linked->documents;
                }
                else $entities = $this->_getDocumentsByCategories($controller);

                break;

            case 'tree':
                $controller = $this->getObject('com://site/docman.controller.tree')
                                   ->access($access)
                                   ->format('json');

                if ($slug)
                {
                    $controller->slug($slug);
                    $entities = $controller->render()->linked->documents;
                }
                else $entities = $this->_getDocumentsByCategories($controller);

                break;
        }

        $documents = array();

        if ($entities) {
            $documents = $this->_buildDocuments($entities, $params, $context->data->event_context);
        }

        return $documents;
    }

    /**
     * Get documents by each categories
     *
     * @param KControllerInterface $controller
     * @return array|KObjectConfigInterface
     */
    protected function _getDocumentsByCategories(KControllerInterface $controller)
    {
        $document_controller = clone $controller;
        $categories = $controller->render()->entities;
        $entities = array();

        foreach ($categories as $category)
        {
            $controller->slug($category->slug);
            $entities = array_merge($entities, KObjectConfig::unbox($document_controller->render()->linked->documents));
        }

        return $entities;
    }

    /**
     * Build list of documents
     *
     * @param $entities
     * @param $params
     * @param $event_context
     * @return array
     */
    protected function _buildDocuments($entities, $params, $event_context)
    {
        $documents = array();

        foreach($entities as $entity)
        {
            $document = $this->getModel()->create(KObjectConfig::unbox($entity));
            $this->prepareDocument($document, $params, $event_context);
            $documents[] = $document;
        }

        return $documents;
    }

    public function prepareDocument(&$document, $params, $event_context = 'com_docman.document')
    {
        if (empty($this->_route_helper)) {
            $this->_route_helper = $this->getObject('com://site/docman.template.helper.route');
        }

        $this->_route_helper->setRouter(array($this, 'getRoute'));

        $fqr = true;

        $document->document_link = $this->_route_helper->document(array('entity'=> $document, 'layout' => 'default', 'Itemid' => $params->itemid), $fqr);
        $document->download_link = $this->_route_helper->document(array('entity'=> $document, 'view'   => 'download', 'Itemid' => $params->itemid), $fqr);

        // Copy link
        $document->copy_link = $this->_route_helper->document(array('entity'=> $document, 'view'   => 'download'), true);

        $link_to = $params->document_title_link;

        switch ($link_to)
        {
            case 'download':
                $document->title_link = $document->download_link;
                break;

            case 'details':
                $document->title_link = $document->document_link;
                break;

            default:
                $document->title_link = $document->download_link;
        }

        if ($document->image) {
            $document->image_download_path = $document->image_path;
        }

        if ($document->isImage() && $document->canPerform('download')) {
            $document->image_download_path = $document->download_link;
        }

        $this->getObject('com://site/docman.template.helper.event')->trigger(array(
            'name'       => 'onDocmanContentPrepare',
            'attributes' => array($event_context, &$document, &$params)
        ));
    }

    /**
     * Remove the format query parameter in the sitemap links
     *
     * {@inheritdoc}
     */
    public function getRoute($route = '', $fqr = false, $escape = true)
    {
        if (is_string($route)) {
            parse_str(trim($route), $parts);
        } else {
            $parts = $route;
        }

        if (!isset($parts['Itemid'])) {
            $parts['Itemid'] = $this->getActiveMenu()->id;
        }

        $route = parent::getRoute($parts, $fqr, $escape);
        $query = $route->getQuery(true);
        unset($query['format']);
        $route->setQuery($query);

        return $route;
    }
}
