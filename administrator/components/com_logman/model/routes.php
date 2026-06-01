<?php
/**
 * @package     LOGman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

/**
 * Routes Model
 *
 * @author  Arunas Mazeika <https://github.com/amazeika>
 * @package Joomlatools\Component\LOGman
 */
class ComLogmanModelRoutes extends KModelDatabase
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()->insert('page', 'int')
             ->insert('package', 'string')
             ->insert('name', 'string')
             ->insert('row', 'string')
             ->insert('path', 'string');
    }

    protected function _buildQueryWhere(KDatabaseQueryInterface $query)
    {
        parent::_buildQueryWhere($query);

        $state = $this->getState();

        if ($page = $state->page) {
            $query->where('tbl.page IN :page')->bind(array('page' => (array) $page));
        }

        if ($package = $state->package) {
            $query->where('tbl.package IN :package')->bind(array('package' => (array) $package));
        }

        if ($name = $state->name) {
            $query->where('tbl.name IN :name')->bind(array('name' => (array) $name));
        }


        if ($row = $state->row) {
            $query->where('tbl.row IN :row')->bind(array('row' => (array) $row));
        }


        if ($path = $state->path) {
            $query->where('path IN :path')->bind(array('path' => (array) $path));
        }
    }
}