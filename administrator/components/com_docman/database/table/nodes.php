<?php
/**
 * @package     DOCman
 * @copyright   Copyright (C) 2011 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://www.joomlatools.com
 */

class ComDocmanDatabaseTableNodes extends KDatabaseTableAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'base' => 'docman_files',
            'name' => 'docman_nodes'
        ));

        parent::_initialize($config);
    }

    public function getSchema()
    {
        $result = parent::getSchema();

        // Unset the primary key from the base table as the view doesn't have any
        if (is_object($result)){
            unset($result->columns['docman_file_id']);
        }

        return $result;
    }
}
