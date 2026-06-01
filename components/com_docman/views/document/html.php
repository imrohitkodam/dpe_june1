<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanViewDocumentHtml extends ComDocmanViewHtml
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'decorator'  => $config->layout === 'form' ? 'koowa' : 'joomla'
        ]);

        parent::_initialize($config);
    }

    protected function _fetchData(KViewContext $context)
    {
        $document = $this->getModel()->fetch();

        if ($this->getLayout() !== 'form')
        {
            $params                       = $this->getParameters();
            $context->data->event_context = 'com_docman.document';

            $this->prepareDocument($document, $params, $context->data->event_context);

            //Settings
            $query = $this->getActiveMenu()->query;
            if ($query['view'] === 'document' && $query['slug'] === $document->slug) {
                $context->data->show_delete = false;
            }

            // Force download should be disabled when embed document is in use
            if ($params->embed_document) {
                $params->force_download = false;
            }
        }
        else
        {
            $this->getObject('translator')->load('com:files');

            $menu = $this->getActiveMenu();

            $view = $menu->query['view'];

            if (!empty($menu->query['own']) || ($document->isPermissible() && !$document->canPerform('manage'))) {
                $context->data->hide_owner_field = true;
            }

            if (in_array($view, array('list', 'tree')) && isset($menu->query['slug']))
            {
                $category = $this->getObject('com://admin/docman.model.categories')->slug($menu->query['slug'])->fetch();

                if (!$category->isNew()) {
                    $context->data->root_category = $category->id;
                }
            }

            $context->data->tag_count      = $this->getObject('com://admin/docman.model.tags')->count();
            $context->data->can_create_tag = $this->getObject('com://admin/docman.model.configs')->fetch()->can_create_tag;
            $context->data->hide_tag_field = $context->data->tag_count == 0 && !$context->data->can_create_tag;

            $document->setProperty('automatic_humanized_titles', $this->getObject('com://admin/docman.model.entity.config')->automatic_humanized_titles);
        }

        $category_filter = array(
            'page' => isset($menu) ? $menu->id : null
        );
        
        if (!$context->data->admin && !$context->data->can_manage)
        {
            $category_filter = array_merge($category_filter, [
                'access'       => $this->getObject('user')->getRoles(),
                'current_user' => $this->getObject('user')->getId(),
                'enabled'      => 1,
                'own'          => isset($this->getActiveMenu()->query['own_categories']) && (bool) $this->getActiveMenu()->query['own_categories'] ? $this->getActiveMenu()->query['own_categories'] : 0
            ]);
        }

        $context->data->category_filter = $category_filter;

        parent::_fetchData($context);
    }

    protected function _generatePathway($category = null, $document = null)
    {
        $document = $this->getModel()->fetch();

        if ($this->getLayout() === 'form')
        {
            $translator = $this->getObject('translator');

            if($document->isNew()) {
                $text = $translator->translate('Add document');
            } else {
                $text = $translator->translate('Edit document {title}', array('title' => $document->title));
            }

            $this->getPathway()->addItem($text, '');
        }
        else
        {
            $menu = $this->getActiveMenu();

            if (isset($menu->query['combined']) && (bool) $menu->query['combined']) {
                $category = null;
            } else {
                $category = $this->getObject('com://site/docman.model.categories')->id($document->docman_category_id)->fetch();
            }
            
            parent::_generatePathway($category, $document);
        }
    }

    /**
     * If the current page is not to a document menu item, use the current document title
     */
    protected function _setPageTitle()
    {
        if ($this->getName() !== $this->getActiveMenu()->query['view'])
        {
            $document = $this->getModel()->fetch();

            $this->getParameters()->set('page_heading', $document->title);
            $this->getParameters()->set('page_title',   $document->title);
        }

        parent::_setPageTitle();
    }

    /**
     * If the current page is not to a document menu item, set metadata
     */
    protected function _preparePage()
    {
        $document = $this->getModel()->fetch();

        if ($this->getName() !== $this->getActiveMenu()->query['view'])
        {
            $helper   = $this->getTemplate()->createHelper('string');
            $this->getParameters()->{'menu-meta_description'} = $helper->truncate(array(
                'text'   => $document->description,
                'length' => 140
            ));
        }

        $this->_addSocialMetatags($document);

        parent::_preparePage();
    }

    /**
     * Generate social meta tags from a document
     * @param  KModelEntityInterface $document
     */
    protected function _addSocialMetatags(KModelEntityInterface $document)
    {
        $page = JFactory::getDocument();

        $page->setMetaData('og:url', JURI::current(), 'property');
        $page->setMetaData('og:title', $document->title, 'property');
        $page->setMetaData('og:description', strip_tags($document->description ?: ''));

        // Open graph object type
        if ($document->isVideo()) {
            $page->setMetaData('og:type', 'video.movie', 'property');
        }
        else $page->setMetaData('og:type', 'website', 'property');

        // Generates fully qualified url for the document
        $url = (string) $this->getObject('com://site/docman.template.helper.route')
                             ->setRouter(array($this, 'getRoute'))
                             ->document(array('entity'=> $document, 'view'   => 'download'), true);

        $type = $document->getParameters()->icon;

        switch ($type)
        {
            case ($type == 'image' || $document->image_download_path):
                $url = $type != 'image' ? $document->image_download_path : $url;
                $page->setMetaData('og:image', $url, 'property');
                $page->setMetaData('twitter:image', $url);
                $page->setMetaData('twitter:image:alt', $document->title);
                break;

            case 'video':
                $page->setMetaData('og:video', $url, 'property');
                $page->setMetaData('og:video:tag', $document->tag_list);
                $page->setMetaData('twitter:card', 'player');
                $page->setMetaData('twitter:player', $url);
                break;

            case 'audio':
                $page->setMetaData('og:audio', $url, 'property');
                break;

            default:
                $page->setMetaData('twitter:card', 'summary');
                break;
        }
    }
}
