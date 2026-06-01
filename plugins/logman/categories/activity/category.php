<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Category/Categories Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanCategoriesActivityCategory extends ComLogmanModelEntityActivity
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'object_table'  => 'categories',
            'object_column' => 'id'
        ));

        if ($config->data->metadata) {
            $config->append(array('format' => '{actor} {action} {object.subtype} {object.type} title {object}'));
        }

        parent::_initialize($config);
    }

    protected function _objectConfig(KObjectConfig $config)
    {

        $query = $this->getObject('lib:database.query.select')
                      ->table(array('categories'))
                      ->columns('*')
                      ->where('id = :id')
                      ->bind(array('id' => $this->row));

        if ($levels = $this->getViewLevels()) {
            $query->where('access IN :levels')->bind(array('levels' => $levels));
        }

        $category = $this->getTable()->getAdapter()->select($query, KDatabase::FETCH_OBJECT);

        // Only link categories from known components.
        $components = array('com_content', 'com_contact', 'com_newsfeeds');

        if ($category && $category->published && in_array($category->extension, $components))
        {
            $table = JTable::getInstance('category');

            $table->load($this->row);

            $parents = array();

            foreach ($table->getPath() as $parent) {
                $parents[]  = $parent->id == 1 ? '0' : $parent->id;
            }

            $template = sprintf('option=%s&view=category&id=%s-%s&Itemid=%%s', $category->extension, $this->row, $category->alias);

            $config->append(array(
                'pages' => array(
                    'template'   => $template,
                    'components' => $components,
                    'conditions' => array(
                        array('view' => 'category', 'id' => $parents),
                        array('view' => 'categories', 'id' => $parents)
                    )
                )
            ));
        }

        parent::_objectConfig($config);

        if ($metadata = $this->getMetadata())
        {
            $config->url->admin = $config->url->admin .'&extension=' . $metadata->extension;

            // Set subtype.
            $config->subtype = array('objectName' => $this->_getSubtype(), 'object' => true);
        }
    }

    protected function _getSubtype()
    {
        $extension = $this->getMetadata()->extension;

        if (strpos($extension, '.') === false)
        {
            // Guess context based on provided extension.
            // J!2.5 only provides the extension name while v3 passes (ON SOME CASES) a context.
            switch ($extension)
            {
                case 'com_users':
                    $context = 'com_users.notes';
                    break;
                case 'com_content':
                    $context = 'com_content.articles';
                    break;
                case 'com_banners':
                    $context = 'com_banners.banners';
                    break;
                case 'com_contact':
                    $context = 'com_contact.contacts';
                    break;
                case 'com_newsfeeds':
                    $context = 'com_newsfeeds.newsfeeds';
                    break;
                case 'com_weblinks':
                    $context = 'com_weblinks.weblinks';
                    break;
                default:
                    $context = null;
                    break;
            }
        }
        else $context = $extension;

        // Translate context into readable type.
        switch ($context)
        {
            case 'com_users.notes':
                $subtype = 'user notes';
                break;
            case 'com_content.articles':
                $subtype = 'articles';
                break;
            case 'com_banners.banners':
                $subtype = 'banners';
                break;
            case 'com_contact.contacts':
                $subtype = 'contacts';
                break;
            case 'com_newsfeeds.newsfeeds':
                $subtype = 'newsfeeds';
                break;
            case 'com_weblinks.weblinks':
                $subtype = 'weblinks';
                break;
            default:
                $subtype = '';
                break;
        }

        if (!$subtype)
        {
            if (strpos($extension, '.') !== false) {
                $subtype = substr($extension, 0, strpos($extension, '.'));
            } else {
                $subtype = $extension;
            }

            // Load the component translations.
            ComLogmanActivityTranslator::loadSysIni($subtype);
        }

        return $subtype;
    }
}