<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * CSV View
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanViewActivitiesCsv extends KViewCsv
{
    protected $_columns_map = array(
        'uuid'            => 'id',
        'application'     => 'application',
        'action'          => 'verb',
        'package'         => 'component',
        'name'            => 'object_type',
        'row'             => 'object_id',
        'title'           => 'object_name',
        'created_on'      => 'published',
        'created_by'      => 'actor_id',
        'created_by_name' => 'actor_name',
        '_author_email'   => 'actor_email',
        'ip'              => 'actor_ip'
    );

    /**
     * Return the views output
     *
     * @return string    The output of the view
     */
    protected function _actionRender(KViewContext $context)
    {
        $rows    = '';
        $columns = array_keys($this->_columns_map);

        //Create the rows
        foreach ($this->getModel()->fetch() as $entity)
        {
            $data = array();

            foreach ($columns as $column) {
                $data[$column] = $entity->{$column};
            }

            $rows .= $this->_arrayToString(array_values($data)) . $this->eol;
        }

        // Set the output
        $this->setContent($rows);
        return $this->_content;
    }

    public function getHeader()
    {
        return implode(',', array_values($this->_columns_map)) . $this->eol;
    }
}