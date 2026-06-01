<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanViewBehaviorPageable extends KViewBehaviorAbstract
{
    /**
     * A reference to the menu or module parameters
     */
    protected $_parameters;

    /**
     * @var ComDocmanTemplateHelperRoute
     */
    protected $_route_helper;

    protected function _beforeRender(KViewContext $context)
    {
        if ($this->getMixer() instanceof KViewTemplate)
        {
            $this->getMixer()->getTemplate()->registerFunction('isRecent', array($this, 'isRecent'));
            $this->getMixer()->getTemplate()->registerFunction('prepareText', array($this, 'prepareText'));
        }

        $params = $this->getParameters();
        $menu   = $this->getActiveMenu();

        if (isset($menu->query['layout']) && $menu->query['layout'] === 'table' && $this->getMixer()->getName() !== 'document') {
            $params->show_document_title = true;
        }

        $context->data->menu   = $menu;
        $context->data->params = $params;
        $context->data->config = $this->getObject('com://admin/docman.model.configs')->fetch();
    }

    /**
     * Get menu parameters
     */
    public function getParameters()
    {
        if (!isset($this->_parameters)) {
            $this->setParameters($this->getActiveMenu()->getParams());
        }

        return $this->_parameters;
    }

    public function setParameters($parameters)
    {
        if (!($parameters instanceof ComKoowaDecoratorParameter) && !($parameters instanceof KObjectConfigInterface)) {
            $parameters = new ComKoowaDecoratorParameter(new KObjectConfig(array('delegate' => $parameters)));
        }

        $this->_parameters = $parameters;

        return $this;
    }

    /**
     * Returns currently active menu item
     *
     * Default menu item for the site will be returned if there is no active menu items
     *
     * @return object
     */
    public function getActiveMenu()
    {
        $menu = \Joomla\CMS\Factory::getApplication()->getMenu()->getActive();
        if (is_null($menu)) {
            $menu = \Joomla\CMS\Factory::getApplication()->getMenu()->getDefault();
        }

        return $menu;
    }

    /**
     * Runs a text through content plugins
     *
     * @param string $text The text to process
     * @param KModelRowInterface|null $entity An optional entity for providing context while handling content events
     *
     * @return string
     */
    public function prepareText($text, $entity = null)
    {
        if ($entity instanceof KModelEntityInterface &&  Joomla\CMS\Plugin\PluginHelper::isEnabled('content', 'fields'))
        {
            if (version_compare(JVERSION, '4', '>=')) {
                /* @var Joomla\Plugin\Content\Fields\Extension\Fields $plugin */
                $plugin = Joomla\CMS\Factory::getApplication()->bootPlugin('fields', 'content');
            } else {

                $plugin     = Joomla\CMS\Plugin\PluginHelper::getPlugin('content', 'fields');
                $dispatcher = \JEventDispatcher::getInstance();

                $plugin = new PlgContentFields($dispatcher, (array) $plugin);
            }
            
            if ($entity instanceof KModelEntityRowset) {
                $entity = $entity->getIterator()->current();
            }

            $context = sprintf('com_docman.%s', $entity->getIdentifier()->getName());

            $item       = (object) $entity->toArray();
            $item->text = $text;

            try {
                if (class_exists("ReflectionMethod")) {
                    $reflection = new ReflectionMethod($plugin, 'onContentPrepare');
                    $params = $reflection->getParameters();
        
                    if (count($params) === 1 && $params[0]->getType() && $params[0]->getType()->getName() === 'Joomla\CMS\Event\Content\ContentPrepareEvent') {
                        $event = new \Joomla\CMS\Event\Content\ContentPrepareEvent(
                            'onContentPrepare',
                            ['context' => $context, 'subject' => $item, 'params' => new JRegistry]
                        );
                        $plugin->onContentPrepare($event);
                    } else {
                        $params = new JRegistry;
                        $plugin->onContentPrepare($context, $item, $params);
                    }
                }
            }
            catch (Exception $e) {}
            

            $text = $item->text;
        }

        $result = JHtml::_('content.prepare', $text);

        // Make sure our script filter does not screw up email cloaking
        if (strpos($result ?: '', '<script') !== false) {
            $result = str_replace('<script', '<script data-inline', $result);
        }

        return $result;
    }

    /**
     * Returns true if the document should have a badge marking it as new
     *
     * @param KModelEntityInterface $document
     *
     * @return bool
     */
    public function isRecent(KModelEntityInterface $document)
    {
        $result = false;

        $days_for_new = $this->getParameters()->get('days_for_new');

        if (!empty($days_for_new))
        {
            $post = strtotime($document->created_on);
            $new = time() - ($days_for_new*24*3600);
            if ($post >= $new) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Adds some information to the document row like download links and thumbnails
     *
     * @param $document KModelEntityInterface      Document row
     * @param $params   ComKoowaDecoratorParameter Page parameters
     * @param $event_context string                Event context
     */
    public function prepareDocument(&$document, $params, $event_context = 'com_docman.document')
    {
        if (empty($this->_route_helper)) {
            $this->_route_helper = $this->getObject('com://site/docman.template.helper.route');
        }

        if ($this->getMixer() instanceof KViewTemplate) {
            $this->_route_helper->setRouter(array($this->getMixer(), 'getRoute'));
        } else {
            $this->_route_helper->setRouter(array($this, 'getRoute'));
        }

        $fqr = $this->getMixer() instanceof KViewHtml ? false : true;

        // Document link
        $document->document_link = $this->_route_helper->document(array('entity'=> $document, 'layout' => 'default'), $fqr);

        // Download link
        $download_url_query = array('entity'=> $document, 'view'   => 'download');

        if ($token = $this->getObject('request')->query->token) {
            $download_url_query['token'] = $token;
        }

        $document->download_link = $this->_route_helper->document($download_url_query, $fqr);
        
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
            $document->image_download_path = $document->canPerform('download') ? $document->download_link : $document->image_path;
        }

        if ($document->isImage() && $document->canPerform('download')) {
            $document->image_download_path = $document->download_link;
        }

        $this->getObject('com://site/docman.template.helper.event')->trigger(array(
            'name'       => 'onDocmanContentPrepare',
            'attributes' => array($event_context, &$document, &$params)
        ));
    }
}