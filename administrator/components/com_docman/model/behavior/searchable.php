<?php
/**
 * Joomlatools Framework - https://www.joomlatools.com/developer/framework/
 *
 * @copyright   Copyright (C) 2007 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework for the canonical source repository
 */

/**
 * Searchable Model Behavior
 *
 * @see  KModelBehaviorSearchable
 */
class ComDocmanModelBehaviorSearchable extends KModelBehaviorSearchable
{
    /**
     * Add search query
     *
     * @todo   Support mysql fulltext search
     *
     * @param  KModelContextInterface $context A model context object
     * @return void
     */
    protected function _buildQuery(KModelContextInterface $context)
    {
        $model = $context->getSubject();

        if ($model instanceof KModelDatabase && !$context->state->isUnique())
        {
            $state = $context->state;

            $search = urldecode($context->state->search ?? ''); // Decodes symbol ('+') to space character
            $context->state->search = $search;

            $mixer = $this->getMixer();   
            
            if ($mixer->getIdentifier()->getName() == 'documents')
            {
                $ignore = [];

                // Join contents table when required
                if ($state->search_contents || strpos($search, 'contents:') !== false) {
                    $context->query->join(array('contents' => 'docman_document_contents'), 'contents.docman_document_id = tbl.docman_document_id');
                } else {
                    $ignore[] = 'contents';
                }

                // Join custom fields table when required
                if ($state->search_fields || strpos($search, 'custom-fields:') !== false)
                {
                    $context->query->join(array('cf_values' => 'fields_values'), 'cf_values.item_id = tbl.docman_document_id');
                    $context->query->join(array('cf' => 'fields'), 'cf.id = cf_values.field_id');
                    $context->query->where('cf.context = :context')->bind(['context' => 'com_docman.document']);
                }
                else $ignore[] = 'custom-fields';

                $context->_ignore_columns = $ignore;
            }

            /*
             * Parse search operators (:and, :or)
             */
            $matches = preg_split('#:\b(and|or)\b#i', $search, -1, PREG_SPLIT_DELIM_CAPTURE);

            if ($search && (in_array('and', array_map('strtolower', $matches)) || in_array('or', array_map('strtolower', $matches))))
            {
                array_unshift($matches, 'and');
                $matches = array_chunk($matches, 2);

                $i = 0;

                foreach($matches as $match)
                {
                    $context->_combination = strtoupper($match[0]);
                    $context->_prefix      = $i;

                    $context->state->search = trim($match[1]);

                    parent::_buildQuery($context);

                    $i++;
                }

                $context->state->search = $search;  // Reinstate the search state
            }
            else parent::_buildQuery($context);
        }
    }

    /**
     * Override parent method to handle special query for searching in custom field values
     * Rationale: We can search against a specific table column by using a column prefix in the config.
     *      However, to search for custom field value, the search keyword will be `value:` which isn't self-explanatory.
     *      Hence, we map the designated column (for the purpose of search keyword) to the actual column.
     * 
     * @param $search
     * @param KModelContextInterface $context
     * @return array
     */
    protected function _getConditions($search, KModelContextInterface $context)
    {
        list($conditions, $binds) = parent::_getConditions($search, $context);

        foreach ($conditions as $key => $condition)
        {
            // Map the specified CF search keyword to actual alias and column
            if (strpos($condition, 'cf.custom-fields') !== false) {
                $conditions[$key] = str_replace('cf.custom-fields', 'cf_values.value', $condition);
            }
        }

        return [$conditions, $binds];
    }
}