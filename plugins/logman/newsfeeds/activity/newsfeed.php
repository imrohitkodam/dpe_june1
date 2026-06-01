<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Newsfeed Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanNewsfeedsActivityNewsfeed extends ComLogmanModelEntityActivity
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'object_table'  => 'newsfeeds',
            'object_column' => 'id'
        ));

        parent::_initialize($config);
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $query = $this->getObject('lib:database.query.select')
             ->table(array('newsfeeds'))
             ->columns('*')
             ->where('id = :id')
             ->bind(array('id' => $this->row));

        if ($levels = $this->getViewLevels()) {
            $query->where('access IN :levels')->bind(array('levels' => $levels));
        }

        $newsfeed = $this->getTable()->getAdapter()->select($query, KDatabase::FETCH_OBJECT);

        if ($newsfeed && $newsfeed->published)
        {
            $table = JTable::getInstance('category');

            $table->load($newsfeed->catid);

            $parents = array();

            foreach ($table->getPath() as $parent) {
                $parents[]  = $parent->id == 1 ? '0' : $parent->id;
            }

            $template = sprintf('option=com_newsfeeds&view=newsfeed&id=%s-%s&catid=%s&Itemid=%%s', $this->row, $newsfeed->alias, $newsfeed->catid);

            $config->append(array(
                'pages' => array(
                    'template'   => $template,
                    'conditions' => array(
                        array(
                            'view' => 'newsfeed',
                            'id'   => $this->row
                        ),
                        array(
                            'view' => 'category',
                            'id'   => $parents
                        ),
                        array(
                            'view' => 'categories',
                            'id'   => $parents
                        )
                    )
                )
            ));
        }

        parent::_objectConfig($config);
    }
}