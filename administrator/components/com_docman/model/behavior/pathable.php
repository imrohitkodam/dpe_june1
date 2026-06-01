<?php
/**
 * @package    DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */


class ComDocmanModelBehaviorPathable extends KModelBehaviorAbstract
{
    protected $_id_column;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_id_column = $config->id_column;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'id_column' => 'id',
        ));

        parent::_initialize($config);
    }
    
    public function onMixin(KObjectMixable $mixer)
    {
        parent::onMixin($mixer);

        $state = $mixer->getState();

        // @todo Make category path optional
        // Insert the path model state
        //$state->insert('category_path', 'string');
    }

    protected function _afterFetch(KModelContextInterface $context)
    {
        $entities = $context->entity;

        if (count($entities))
        {
            $ids = array();

            foreach ($entities as $entity) {
                $ids[] = $entity->{$this->_id_column};
            }

            $query = $this->getObject('lib:database.query.select');
            $query
                ->columns(array('tbl.*'))
                ->columns(array('SUBSTRING_INDEX(GROUP_CONCAT(tbl.`docman_category_id` ORDER BY relation.level ASC), ",", 1 ) AS id'))
                ->columns(array('GROUP_CONCAT(tbl.title ORDER BY relation.level DESC SEPARATOR " » ") AS category_path'))
                ->table(array('tbl' => 'docman_categories'))
                ->join(array('relation' => 'docman_category_relations'), 'relation.ancestor_id = tbl.`docman_category_id`', 'LEFT')
                ->having('FIND_IN_SET(0, GROUP_CONCAT(tbl.enabled ORDER BY relation.level DESC)) = 0')
                ->group('relation.descendant_id')
                ->order('tbl.title')
            ;

            if ($ids)
            {
                $query->where('relation.descendant_id IN :id');
                $query->bind(array('id' => $ids));
            }

            $map = $this->getTable()->getAdapter()->select($query, KDatabase::FETCH_OBJECT_LIST, 'id');

            foreach ($entities as $entity) {
                if (isset($map[$entity->{$this->_id_column}])) {
                    $entity->category_path = $map[$entity->{$this->_id_column}]->category_path;
                }
            }
        }
    }
}