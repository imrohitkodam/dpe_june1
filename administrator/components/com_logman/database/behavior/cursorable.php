<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Cursorable Database Behavior
 *
 * Provides support for cursor based pagination using an arbitrary offset column.
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanDatabaseBehaviorCursorable extends KDatabaseBehaviorAbstract
{
    /**
     * The offset column.
     *
     * @var string
     */
    protected $_offset_column;

    /**
     * The table alias as set by the model.
     *
     * @var string
     */
    protected $_table_alias;

    /**
     * The current offset value.
     *
     * @var mixed
     */
    protected $_offset;

    /**
     * The pagination limit.
     *
     * @var int
     */
    protected $_limit;

    /**
     * The next page offset value.
     *
     * @var mixed
     */
    protected $_next;

    /**
     * The previous page offset value.
     *
     * @var mixed
     */
    protected $_prev;

    /**
     * The pagination direction (ASC or DESC).
     *
     * @var string
     */
    protected $_direction;

    /**
     * The cursor condition.
     *
     * @var string|null
     */
    protected $_condition;

    /**
     * A copy of the cursor query.
     *
     * @var KDatabaseQueryInterface|null
     */
    protected $_query;

    /**
     * The paginated data.
     *
     * @var KDatabaseRowsetInterface|null
     */
    protected $_data;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        if (!$config->offset_column) {
            $config->offset_column = $this->getMixer()->getIdentityColumn();
        }

        $this->_table_alias   = $config->table_alias;
        $this->_limit         = $config->limit;
        $this->_offset        = $config->offset;
        $this->_offset_column = $config->offset_column;
        $this->_direction     = $config->direction;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array('table_alias' => 'tbl', 'order' => 'ASC'));
    }

    protected function _beforeSelect(KDatabaseContextInterface $context)
    {
        $query = $context->query;

        $this->_condition =  $this->_table_alias . '.' . $this->_offset_column . ' ' . ($this->_direction == 'ASC' ? '>' : '<') .
            ' :offset';

        if (isset($this->_offset) && !($this->_direction == 'DESC' && $this->_offset <= 0)) {
            $this->_setCondition($query);
        }

        if (!$query->isCountQuery())
        {
            $query->order = array();
            $query->order($this->_table_alias . '.' . $this->_offset_column, $this->_direction);
            $query->limit($this->_limit);
        }
    }

    /**
     * Sets the query offset condition.
     *
     * @param KDatabaseQuerySelect $query The query.
     */
    protected function _setCondition(KDatabaseQuerySelect $query)
    {
        $query->where($this->_condition)->bind(array('offset' => $this->_offset));
    }

    protected function _afterSelect(KDatabaseContextInterface $context)
    {
        $query = $context->query;

        if (!$query->isCountQuery())
        {
            $this->_data  = $context->data;
            $this->_query = $query;
        }
    }

    protected function _getOffset($next = true)
    {
        $offset = null;

        $data = $this->_data;

        if ($data && count($data) && $this->_query)
        {
            $data = $data->toArray();

            reset($data);

            $item = $next ? end($data) : current($data);

            $mixer = $this->getMixer();

            if ($mixer instanceof KDatabaseTableInterface) {
                $table = $mixer;
            }
            else $table = $mixer->getTable();

            $column = $table->mapColumns($this->_offset_column, true);

            $value = $item[$column];

            $query = clone $this->_query;

            // Set the condition if it wasn't already set on _beforeSelect.
            if (!$this->_offset) {
                $this->_setCondition($query);
            }

            $query->columns = array('COUNT(*)');

            $direction = $this->_direction;

            if (!$next)
            {
                $condition = $this->_table_alias . '.' . $this->_offset_column . ' ' . ($this->_direction == 'ASC' ? '<' : '>') . ' :offset';

                if ($this->_condition)
                {
                    foreach ($query->where as $key => $where)
                    {
                        if ($where['condition'] == $this->_condition) {
                            $query->where[$key]['condition'] = $condition;
                            break;
                        }
                    }
                }
                else $query->where($condition);

                $direction = $this->_direction == 'ASC' ? 'DESC' : 'ASC';
            }

            $query->bind(array('offset' => $value));

            $table->getCommandChain()->disable();

            if ($table->select($query, KDatabase::FETCH_FIELD)) {
                $offset = array($value => $direction);
            }

            $table->getCommandChain()->enable();
        }

        return $offset;
    }

    /**
     * Returns the next page offset value.
     *
     * @return mixed The offset value, null if there is no next page offset value.
     */
    public function getNext()
    {
        if (!$this->_next) {
            $this->_next = $this->_getOffset();
        }

        return $this->_next;
    }

    /**
     * Returns the previous page offset value.
     *
     * @return mixed The offset value, null if there is no previous page offset value.
     */
    public function getPrev()
    {
        if (!$this->_prev) {
            $this->_prev = $this->_getOffset(false);
        }

        return $this->_prev;
    }
}