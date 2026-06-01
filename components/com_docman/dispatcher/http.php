<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

class ComDocmanDispatcherHttp extends ComKoowaDispatcherHttp
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->addCommandCallback('before.get', '_setLimit');

        $this->getObject('event.publisher')->addListener('onException', array($this, 'onExceptionNotFound'));
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'controller'     => 'list',
            'authenticators' => array('jwt'),
            'behaviors'      => array(
                'connectable',
                'com://admin/docman.dispatcher.behavior.routable'
            )
        ));

        parent::_initialize($config);
    }

    /**
     * Redirects guest users to login form on 404 errors if the entity can be accessed without the viewlevel filter
     *
     * @param KEventInterface $event
     */
    public function onExceptionNotFound(KEventInterface $event)
    {
        if ($event->exception->getCode() === KHttpResponse::NOT_FOUND && $event->exception->getMessage() !== 'File not found')
        {
            $redirect = false;
            $document = $this->getController()->getModel()
                                           ->setState(array(
                                               'access' => null,
                                               'status' => null,
                                               'enabled' => null,
                                               'category' => null
                                           ))
                                           ->fetch();

            if (!$this->getObject('user')->isAuthentic() && !$document->isNew())
            {
                // DPE HACK: check if it's a marketing document to avoid redirect
                $plugin = PluginHelper::getPlugin('system', 'dpe');
                $params = new Registry($plugin->params);
                $overrideTagTitle = strtolower(trim($params->get('marketing_tags', '')));
                $isMarketing = false;

                if ($overrideTagTitle && $document->tag_list) {
                    $tagList = array_map('trim', explode(',', $document->tag_list));
                    foreach($tagList as $tagTitle) {
                        if (strtolower($tagTitle) === $overrideTagTitle) {
                            $isMarketing = true;
                            break;
                        }
                    }
                }

                if (!$isMarketing) {
                    $message = $this->getObject('translator')->translate('You are not authorized to access this resource. Please login and try again.');
                    $url = Route::_('index.php?option=com_users&view=login&return='.base64_encode((string) $this->getRequest()->getUrl()), false);
                    $redirect = true;
                }
            }
            elseif ($this->getObject('user')->isAuthentic() && !$document->isNew())
            {
                // If the user is logged-in and the document exists
                $message = $this->getObject('translator')->translate('You do not have access to this resource.');
                $url = Route::_('/', false);
                $redirect = true;
            }

            if ($redirect)
            {
                $this->getResponse()->setRedirect($url, $message, 'error');
                $this->getResponse()->send();
                $event->stopPropagation();
            }
        }
    }

    /**
     * Sets and override default limit based on page settings parameters.
     *
     * @param KDispatcherContextInterface $context
     * @return KModelEntityInterface
     */
    protected function _setLimit(KDispatcherContextInterface $context)
    {
        $controller = $this->getController();

        if (in_array($controller->getIdentifier()->name, array('tree', 'list', 'flat')))
        {
            $params = Factory::getApplication()->getMenu()->getActive()->getParams();

            if ($limit = $params->get('limit')) {
                $this->getConfig()->limit->default = $limit;
            }

            if (!$params->get('show_document_sort_limit'))
            {
                $this->getRequest()->getQuery()->limit = (int) $this->getConfig()->limit->default;
                $controller->getModel()->getState()->setProperty('limit', 'internal', true);
            }
        }
    }

    public function getRequest()
    {
        $request = parent::getRequest();

        $menu = Factory::getApplication()->getMenu()->getActive();

        $query = $request->query;

        if ($query->alias && !$query->slug)
        {
            $parts       = explode('-', $query->alias, 2);
            $query->slug = array_pop($parts);
        }

        if ($menu && !in_array($query->view, array('doclink', 'documents', 'search'))) {
            $query->Itemid = $menu->id;
        }

        // Can't use executable behavior here as it calls getController which in turn calls this method
        if ($this->getObject('user')->authorise('core.manage', 'com_docman') !== true)
        {
            $query->enabled = 1;
            $query->status  = 'published';
        }

        // Set default format for sitemap view
        if ($query->view == 'sitemap') {
            $query->set('format', 'xml');
        }

        $query->access          = $this->getObject('user')->getRoles();
        $query->category_access = true;
        $query->page            = $query->Itemid;
        $query->current_user    = $this->getObject('user')->getId();

        // DPE HACK start
        // Trigger custom event for ACL override
        PluginHelper::importPlugin('system');
		Factory::getApplication()->triggerEvent('onDocmanBeforeRequest', array(&$query));
        // DPE HACK end

        // This cannot come from the query string on frontend
        unset($query->group);

        return $request;
    }
}
