<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Activities Model
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanModelActivities extends ComActivitiesModelActivities
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()
             ->insert('usergroup', 'int')
             ->insert('resource_action', 'cmd')
             ->insert('context', 'cmd', null, false, array(), true)
             ->insert('levels', 'int', null, false, array(), true)
             ->insert('read', 'int');
    }

    protected function _actionCreate(KModelContext $context)
    {
        $context->entity->append(array('context' => $context->state->context));

        return parent::_actionCreate($context);
    }

    /**
     * Overridden to push state data into options.
     */
    protected function _actionFetch(KModelContext $context)
    {
        $state   = $context->state;
        $table   = $this->getTable();

        //Entity options
        $options = array(
            'identity_column' => $context->getIdentityKey(),
            'context'         => $state->context,
            'levels'          => $state->levels
        );

        //Select the rows
        if (!$state->isEmpty())
        {
            $context->query->columns('tbl.*');
            $context->query->table(array('tbl' => $table->getName()));

            $this->_buildQueryColumns($context->query);
            $this->_buildQueryJoins($context->query);
            $this->_buildQueryWhere($context->query);
            $this->_buildQueryGroup($context->query);

            $data = $table->select($context->query, KDatabase::FETCH_ROWSET, $options);
        }
        else $data = $table->createRowset($options);

        return $data;
    }

    protected function _buildQueryJoins(KDatabaseQueryInterface $query)
    {
        parent::_buildQueryJoins($query);

        $state = $this->getState();

        if ($usergroup = $state->usergroup)
        {
            $guest_usergroup = JComponentHelper::getParams('com_users')->get('guest_usergroup');

            if (in_array($guest_usergroup, $usergroup)) {
                $query->join('user_usergroup_map AS users_groups', 'tbl.created_by = users_groups.user_id', 'LEFT');
            } else {
                $query->join('user_usergroup_map AS users_groups', 'tbl.created_by = users_groups.user_id', 'INNER');
            }
        }
    }

    protected function _buildQueryWhere(KDatabaseQueryInterface $query)
    {
        parent::_buildQueryWhere($query);

        $state = $this->getState();

        // Avoid system activities from being displayed
        if (!is_numeric($state->user)) {
            $query->where('tbl.created_by >= :created_by')->bind(array('created_by' => 0));
        }

        if ($usergroup = $state->usergroup)
        {
            $guest_usergroup = JComponentHelper::getParams('com_users')->get('guest_usergroup');

            if (in_array($guest_usergroup, $usergroup)) {
                $query->where('(tbl.created_by = :guest OR users_groups.group_id IN :usergroup)')
                      ->bind(array('guest' => 0));
            } else {
                $query->where('users_groups.group_id IN :usergroup');
            }

            $query->bind(array('usergroup' => $usergroup));
        }

        if ($resource_action = $state->resource_action)
        {
            $resources_actions = (array) $resource_action;

            $conditions = array();

            $i = 0;

            foreach ($resources_actions as $resource_action)
            {
                $conditions[] = "(tbl.name = :name{$i} AND tbl.action = :action{$i})";

                list($resource, $action) = explode('.', $resource_action);

                $query->bind(array("name{$i}" => $resource, "action{$i}" => $action));

                $i++;
            }

            $query->where('(' . implode(' OR ', $conditions) . ')');
        }

        if (is_numeric($state->read))
        {
            // When the read state is specifically set, show all or just the read activities
            if ($state->read) {
                $query->where('tbl.action = :read');
            } else {
                $query->where('tbl.action <> :read');
            }

            $query->bind(array('read' => 'read'));
        }
    }
}