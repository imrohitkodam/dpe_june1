<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanModelBehaviorActivitiesSearchable extends KModelBehaviorSearchable
{
    /**
     * Overridden to dynamically add document id as searchable column when the search state value contains
     * the #document_id: prefix.
     */
    protected function _buildQuery(KModelContextInterface $context)
    {
        $state = $context->getState();
        $search = $state->search;

        if ($search && !$state->isUnique())
        {
            if (strpos($search, '#document_id:') === 0)
            {
                if (!in_array('row', $this->_columns))
                {
                    $this->_columns = array('row');

                    $context->query->where('name = :name')->bind(array('name' => 'document'));
                }

                $state->search = str_replace('#document_id:', '', $search); // cleanup for search
            }
        }

        parent::_buildQuery($context);

        if ($state->search != $search) {
            $state->search = $search; // reset search state value
        }
    }
}