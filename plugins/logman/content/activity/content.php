<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Content Activity Entity
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Plugin\LOGman
 */
class PlgLogmanContentActivityContent extends ComLogmanModelEntityActivity
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'object_table'  => 'content',
            'object_column' => 'id'
        ));

        if ($config->data->action == 'read')
        {
            if (isset($config->data->metadata['url'])) {
                $config->append(array('format' => '{actor} {action} {object.type} {object} {object.impression}'));
            } else {
                $config->append(array('format' => '{actor} {action} {object.type} {object}'));
            }
        }

        parent::_initialize($config);
    }

    protected function _objectConfig(KObjectConfig $config)
    {
        $query = $this->getObject('lib:database.query.select')
                      ->table(array('content'))
                      ->columns('*')
                      ->where('id = :id')
                      ->bind(array('id' => $this->row));

        if ($levels = $this->getViewLevels()) {
            $query->where('access IN :levels')->bind(array('levels' => $levels));
        }

        $article = $this->getTable()->getAdapter()->select($query, KDatabase::FETCH_OBJECT);

        if ($article)
        {
            $table = JTable::getInstance('category');

            $table->load($article->catid);

            $parents = array();

            foreach ($table->getPath() as $parent) {
                $parents[]  = $parent->id == 1 ? '0' : $parent->id;
            }

            $template = sprintf('option=com_content&view=article&id=%s&catid=%s&Itemid=%%s', $this->row, $article->catid);

            if ($article->state != 0)
            {
                $config->append(array(
                    'pages' => array(
                        'template'   => $template,
                        'conditions' => array()
                    )
                ));

                switch($article->state) {
                    case 1: // Published
                        if ($article->featured) {
                            $config->pages->conditions->append(array(array('view' => 'featured')));
                        }

                        $config->pages->conditions->append(array(
                            array(
                                'view' => 'article',
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
                        ));

                        break;
                    case 2: // Archive
                        //$config->pages->conditions->append(array(array('view' => 'archive')));
                        break;
                    default:
                        break;
                }
            }
        }

        if ($this->getActivityVerb() == 'read')
        {
            $filter   = $this->getObject('lib:filter.path');
            $helper   = $this->getObject('com://admin/logman.template.helper.impression');
            $metadata = $this->getMetadata();

            // The check against path is necessary since LOGman TEXTman plugin used to log
            // articles titles for read activities

            if ($filter->validate($this->title))
            {
                $config->append(array(
                    'url' => array(
                        'admin' => $this->getObject('lib:http.url',
                            array('url' => $helper->route(array('url' => $this->title))))
                    )
                ));
            }
            elseif ($url = $metadata->url)
            {
                $config->append(array(
                    'impression' => array(
                        'object'     => true,
                        'objectName' => $url,
                        'url'        =>
                            array(
                                'admin' => $this->getObject('lib:http.url',
                                    array('url' => $helper->route(array('url' => $url))))
                            )
                    )
                ));
            }
        }

        parent::_objectConfig($config);
    }

    /**
     * LEADman-related activity log
     *
     * @param KObjectConfig $config
     * @return KObjectConfig|void
     */
    protected function _actorConfig(KObjectConfig $config)
    {
        if ($this->created_by != -1 && $this->lead_id)
        {
            $url = new KObjectConfig([
                'admin' => 'option=com_leadman&view=contact&id=' . $this->lead_id
            ]);

            $config->append(array(
                'objectName' => $this->lead_email,
                'url'        => $url,
                'translate'  => false
            ));
        }
        else parent::_actorConfig($config);
    }
}