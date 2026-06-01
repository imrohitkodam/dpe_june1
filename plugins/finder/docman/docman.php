<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

defined('_JEXEC') or die;

JLoader::register('PlgKoowaFinder', JPATH_ROOT.'/libraries/joomlatools/plugins/koowa/finder.php');

/**
 * Joomla smart search plugin for DOCman
 */
class plgFinderDocman extends PlgKoowaFinder
{
    /**
     * Initializes the default configuration for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   KObjectConfig $config Configuration options
     * @return  void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'context'  => 'DOCman',
            'entity'   => 'document',
            'instructions' => array(
                //FinderIndexer::TEXT_CONTEXT => array('contents'),
                FinderIndexer::META_CONTEXT => array('storage_path')
            )
        ));

        parent::_initialize($config);
    }

    public function onFinderAfterSave($context, $entity, $isNew)
    {
        if (!class_exists('Koowa')) return;

        if ($context === $this->extension.'.category') {
            return $this->reindexCategory($entity);
        }

        return parent::onFinderAfterSave($context, $entity, $isNew);
    }


    /**
     * Method to remove outdated index entries
     * 
     * Copied from https://github.com/Hackwar/joomla-cms/blob/a23099cdd84d647840a06a364887e3d508c8aacf/administrator/components/com_finder/src/Indexer/Adapter.php#L272 
     * to fix the ID column
     *
     * @return  integer
     *
     * @since   4.2.0
     */
    public function onFinderGarbageCollection()
    {
        $db = $this->db;
        $type_id = $this->getTypeId();

        $query = $db->getQuery(true);
        $subquery = $db->getQuery(true);
        $subquery->select('CONCAT(' . $db->quote($this->getUrl('', $this->extension, $this->layout)) . ', docman_document_id)')
            ->from($db->quoteName($this->table));
        $query->select($db->quoteName('l.link_id'))
            ->from($db->quoteName('#__finder_links', 'l'))
            ->where($db->quoteName('l.type_id') . ' = ' . $type_id)
            ->where($db->quoteName('l.url') . ' LIKE ' . $db->quote($this->getUrl('%', $this->extension, $this->layout)))
            ->where($db->quoteName('l.url') . ' NOT IN (' . $subquery . ')');
        $db->setQuery($query);

        $items = $db->loadColumn();

        foreach ($items as $item) {
            $this->indexer->remove($item);
        }

        return count($items);
    }

    /**
     * See PlgKoowaFinder::index()
     *
     * @param FinderIndexerResult $item
     * @return bool|void
     */
    protected function index(FinderIndexerResult $item)
    {
        if (version_compare(JVERSION, '5', '>=')) {
            FinderIndexerHelper::addCustomFields($item, 'com_docman.document'); // Index custom fields
        }

        parent::index($item);
    }

    protected function reindexCategory($category)
    {
        $db    = JFactory::getDbo();
        $query = 'SELECT docman_document_id, access, enabled FROM #__docman_documents WHERE docman_category_id = %d';

        $db->setQuery(sprintf($query, $category->id));
        $items = $db->loadObjectList();

        $access_changed = $category->old_access !== $category->access;
        $state_changed  = $category->old_enabled !== $category->enabled;

        $limit = ini_get('memory_limit');
        if ($limit != '-1') {
            $limit = $this->convertToBytes($limit);
        }

        foreach ($items as $item)
        {
            // Leave at least 5 MBs of memory
            if ($limit != '-1' && ($limit - memory_get_usage() < 5*1048576)) {
                break;
            }

            if ($access_changed) {
                $this->change($item->docman_document_id, 'access', max($item->access, $category->access));
            }

            if ($state_changed) {
                $this->change($item->docman_document_id, 'state', min($item->enabled, $category->enabled));
            }
        }

        return true;
    }

    /**
     * Returns the model
     *
     * @return KModelAbstract
     */
    protected function getModel()
    {
        $model = parent::getModel();

        $model->page('finder');

        return $model;
    }

    /**
     * Turns an entity into a finder item
     *
     * @param KModelEntityInterface $entity
     * @return object
     */
    protected function getFinderItem(KModelEntityInterface $entity)
    {
        if (!$entity->isNew())
        {
            if (!$entity->itemid) {
                $this->getModel()->setPage($entity);
            }
    
            $item = parent::getFinderItem($entity);
    
            // Add language
            if ($entity->itemid)
            {
                $menu = KObjectManager::getInstance()->getObject('com://admin/docman.model.pages')
                    ->language('all')->id($entity->itemid)->fetch();
    
                if ($menu->language) {
                    $item->language = $menu->language;
                }
            }

            $show_category = (boolean) $this->params->def('show_category', false);
    
            // Add the category taxonomy data.
            if ($show_category && !empty($item->category_title))
            {
                $category_state  = isset($item->category_enabled) ? $item->category_enabled : 1;
                $category_access = isset($item->category_access) ? $item->category_access   : 1;
    
                if ($category_access < 1) {
                    $category_access = 1;
                }
    
                $item->state  = min($item->enabled, $category_state);
                $item->access = max($item->access,  $category_access);

                $url  = $this->getCategoryLink($entity);
                $link = "<a href=\"{$url}\">{$entity->category_title}</a>";
                $item->addTaxonomy('Category', $link, $category_state, $category_access);
            }
    
            if ($item->access < 1) {
                $item->access = 1;
            }
    
            if (!$item->summary) {
                $item->summary = '';
            }
    
            // Tokenizing 40000 characters seem to take around 10 seconds in Finder
            if (strlen($entity->contents ?: '') > 40000) {
                $contents = substr($entity->contents, 0, 40000);
            } else {
                $contents = $entity->contents;
            }
    
            $item->summary .= ' '.$contents;
    
            $tags = $entity->getTags();
            
            if (count($tags)) {
                $tag_titles = [];
    
                foreach ($tags as $tag) {
                    $tag_titles[] = $tag->title;
                }
    
                $item->summary .= ' '.implode(' ', $tag_titles);
            }
        }
        else $item = parent::getFinderItem($entity);

        // Add the author taxonomy term.
        $show_author = (boolean) $this->params->def('show_author', false);

        if ($show_author && $entity->isCreatable()) {
            $item->addTaxonomy('Author', $entity->getAuthor()->getName());
        }

        return $item;
	}

    /**
     * Returns a link to a row
     *
     * @param KModelEntityInterface $entity
     * @return string
     */
    protected function getLink(KModelEntityInterface $entity)
    {
        $template = 'index.php?option=com_docman&view=document&alias=%s&category_slug=%s&Itemid=%d';

        return sprintf($template, $entity->alias, $entity->category_slug, $entity->itemid);
    }
    
    /**
     * Returns a link to the category
     *
     * @param KModelEntityInterface $entity
     * @return string
     */
    protected function getCategoryLink(KModelEntityInterface $entity)
    {
        $template = 'index.php?option=com_docman&view=category&slug=%s&Itemid=%d';

        return sprintf($template, $entity->category_slug, $entity->itemid);
    }

    protected function convertToBytes($value)
    {
        $keys = array('k', 'm', 'g');
        $last_char = strtolower(substr($value, -1));
        $value = (int) $value;

        if (in_array($last_char, $keys)) {
            $value *= pow(1024, array_search($last_char, $keys)+1);
        }

        return $value;
    }
}
