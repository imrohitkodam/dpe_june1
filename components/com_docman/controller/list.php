<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanControllerList extends ComKoowaControllerModel
{
    /**
     * Model object or identifier (com://APP/COMPONENT.model.NAME)
     *
     * @var	string|object
     */
    protected $_model;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        // Set the model identifier
        $this->_model = $config->model;

        if ($this->isDispatched())
        {
            $this->addBehavior('com://site/docman.controller.behavior.filterable', array(
                'vars' => array(
                    'sort' => 'sort_documents',
                    'sort_categories' => 'sort_categories'
                )
            ));
        }

        $this->addCommandCallback('before.delete', '_checkDocumentCount');
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'toolbars'  => ['list'],
            'formats'   => array('json', 'rss'),
            'model'     => 'com://site/docman.model.categories',
            'behaviors' => array(
                'restrictable' => array('actions' => array('edit', 'delete', 'add'), 'tables' => array('documents', 'categories'), 'notify' => false),
                'ownable',
                'persistable',
                'findable',
                'organizable',
                'sluggable'
            )
        ));

        parent::_initialize($config);
    }

    /**
     * Add the toolbar for non-authentic users too
     *
     * @param KControllerContextInterface $context
     */
    protected function _addToolbars(KControllerContextInterface $context)
    {
        if($this->getView() instanceof KViewHtml)
        {
            if($this->isDispatched())
            {
                foreach($context->toolbars as $toolbar) {
                    $this->addToolbar($toolbar);
                }

                if($toolbars = $this->getToolbars())
                {
                    $this->getView()
                        ->getTemplate()
                        ->addFilter('toolbar', array('toolbars' => $toolbars));
                };
            }
        }
    }

    /**
     * If the user is searching through multiple categories or a category other than the one in the URL redirect to root
     *
     * @param KControllerContextInterface $context
     * @return bool
     */
    protected function _beforeRender(KControllerContextInterface $context)
    {
        $query    = $context->request->query;
        $filter   = $query->filter;

        // searching for something
        if (!empty($filter) &&
            (!empty($filter['reset']) || !empty($filter['category']) || !empty($filter['search']) || !empty($filter['created_on_from']) || !empty($filter['created_on_to']))
        ) {
            $categories = !empty($filter['category']) ? $filter['category'] : array();
            $categories = is_array($categories) ? $categories : array($categories);
            $route      = array();

            // TODO: Temporary fix, see #1947 for the details
            $model = clone $this->getModel();
            $model->reset()->own(null);

            if (count($categories) === 1 && (!$this->getModel()->getState()->isUnique() || $categories[0] !== $model->fetch()->id))
            {
                $model = clone $this->getModel();
                $slug = $model->reset()->slug(null)->id($categories[0])->fetch()->slug;

                $this->getModel()->getState()->id = null;

                $route = array('slug' => $slug, 'filter'=> $query->filter);
            }
            else if (count($categories) > 1 || (count($categories) === 0 && $query->slug))
            {
                $menu      = \Joomla\CMS\Factory::getApplication()->getMenu()->getActive();
                $menu_slug = isset($menu->query['slug']) ? $menu->query['slug'] : null;

                if ($menu_slug != $query->slug) {
                    $query->slug = $menu_slug;

                    $route = array('slug' => $menu_slug, 'filter'=> $query->filter);
                }
            }

            if ($route)
            {

                if (isset($route['filter']['search'])) {
                    $route['filter']['search'] = urlencode($route['filter']['search']); // Make sure to re-encode the search terms
                }

                $route = $this->getView()->getRoute($route, true, false);
                \Joomla\CMS\Factory::getApplication()->redirect($route);

                return false;
            }
        }
    }

    /**
     * The created_by query parameter coming from the menu item is meant for documents.
     *
     * Temporarily unset it here until afterBrowse
     *
     * @param KControllerContextInterface $context
     */
    protected function _beforeBrowse(KControllerContextInterface $context)
    {
        $query = $context->request->query;

        if ($query->created_by || $query->own)
        {
            $menu = \Joomla\CMS\Factory::getApplication()->getMenu()->getActive();

            if ($menu)
            {
                if (isset($menu->query['created_by']))
                {
                    $context->_query_created_by = $query->created_by;
                    $this->created_by(null);
                }
    

                if (!empty($menu->query['own']))
                {
                    $context->_query_own = true;
                    $this->own(null);
                }
            }
        }
    }

    protected function _actionBrowse(KControllerContextInterface $context)
    {
        $entity = parent::_actionBrowse($context);

        $menu  = \Joomla\CMS\Factory::getApplication()->getMenu()->getActive();
        $model = $this->getModel();
        $view  = $this->getView();

        $menu_link = parse_url($menu->link);

        parse_str($menu_link['query'], $menu_query);

        if (!empty($menu_query['slug']) && $model->getState()->slug == $menu_query['slug'])
        {
            // Menu item entry point, pass it to the view
        
            $view->setParentCategory($this->getObject($this->getConfig()->model)->slug($model->getState()->slug)->fetch());
        }
    
        return $entity;
    }

    /**
     * Restores created_by parameter in the request
     *
     * @param KControllerContextInterface $context
     */
    protected function _afterBrowse(KControllerContextInterface $context)
    {
        if ($context->_query_created_by) {
            $context->request->query->created_by = $context->_query_created_by;
        }

        if ($context->_query_own) {
            $context->request->query->own = 1;
        }
    }

    public function getRequest()
    {
        $request = parent::getRequest();
        $query   = $request->query;

        if (!isset($query->slug) && isset($query->path)) {
            $query->slug = array_pop(explode('/', $query->path));
        }

        return $request;
    }

    public function getView()
    {
        if(!$this->_view instanceof KViewInterface)
        {
            //Get the view
            $view = parent::getView();

            //Set the model in the view
            $view->setModel($this->getModel());

            $view->can_delete = $this->canDelete();
            $view->can_add    = $this->canAdd();

            $view->multi_download = $this->getObject('com://site/docman.controller.behavior.compressible')->isSupported();
        }

        return parent::getView();
    }

    /**
     * Halts the delete if the category has documents attached to it.
     *
     * Also makes sure subcategories are deleted correctly when both
     * they and their parents are in the rowset to be deleted.
     *
     * @param KDispatcherContextInterface $context
     * @throws KControllerExceptionActionFailed
     */
    protected function _checkDocumentCount(KDispatcherContextInterface $context)
    {
        $data = $this->getModel()->fetch();

        if ($count = $data->countDocuments())
        {
            $message = $this->getObject('translator')->choose(array(
                'This category or its children has a document attached. You first need to delete or move it before deleting this category.',
                'This category or its children has {count} documents attached. You first need to delete or move them before deleting this category.'
               ), $count, array('count' => $count));

            if ($context->getRequest()->getFormat() === 'html') {
                $context->getResponse()->addMessage($message, KControllerResponse::FLASH_ERROR);
                $context->response->setRedirect($this->getRequest()->getReferrer());

                return false;
            } else {
                throw new KControllerExceptionActionFailed($message);
            }
        }

        /*
         * Removes the child categories from the rowset since they will be deleted by their parent.
         * Otherwise rowset gets confused when it tries to delete a non-existant row.
         */
        if ($data instanceof KModelEntityInterface)
        {
            $to_be_deleted = array();

            // PHP gets confused if you extract a row and then continue iterating on the rowset
            $iterator = clone $data;
            foreach ($iterator as $entity)
            {
                if (in_array($entity->id, $to_be_deleted)) {
                    $data->remove($entity);
                }

                foreach ($entity->getDescendants() as $descendant) {
                    $to_be_deleted[] = $descendant->id;
                }
            }
        }
    }
}
