<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
jimport('joomla.html.html');
jimport('joomla.html.parameter');
jimport('joomla.utilities.date');

/**
 * Tjdbhelper helper
 *
 * @since  1.0.0
 */
class Tjlmsdbhelper
{
	/**
	 * Method to get a record.
	 *
	 * @param   array  	 $col        column name
	 * 
	 * @param   integer  $table      table info
	 * 
	 * @param   array  	 $wherearr   data info
	 * 
	 * @param   array  	 $ordering   order 
	 * 
	 * @param   array  	 $operation  operation
	 *
	 * @return  Object   $statusDetails
	 *
	 * @since  1.0.0
	 */
	public function get_records($col, $table, $wherearr = '', $ordering = '', $operation = '')
	{
		$db = Factory::getDBO();

		$where = array();
		$wherestr = '';

		$query = "SELECT $col FROM #__$table";

		if (!empty($wherearr))
		{
			foreach ($wherearr as $column => $val)
			{
				$where[] = $column . "= '" . $val . "' ";
			}

			$wherestr = 'where ' . implode(' AND ', $where);
			$query .=	' ' . $wherestr . ' ';
		}

		if ($ordering)
		{
			$query .=	' ORDER BY ' . $ordering . ' ';
		}

			$query;
		$db->setQuery($query);

		if ($operation == 'loadResult')
		{
			return $db->loadResult();
		}
		elseif($operation == 'loadObject')
		{
			return $db->loadObject();
		}
		elseif($operation == 'loadObjectList')
		{
			return $db->loadObjectList();
		}
		else
		{
			return $db->loadColumn();
		}
	}

	/**
	 * Method to remove a record.
	 * 
	 * @param   integer  $table     table info
	 * 
	 * @param   array  	 $wherearr  data info
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 */
	public function delete_records($table, $wherearr = '')
	{
		$db = Factory::getDBO();
		$where = array();
		$wherestr = '';

		$query = "DELETE FROM #__$table";

		if (!empty($wherearr))
		{
			foreach ($wherearr as $col => $val)
			{
				$where[] = $col . "= '" . $val . "' ";
			}

			$wherestr = 'where ' . implode(' AND ', $where);
			$query .=	' ' . $wherestr . ' ';
		}

		$db->setQuery($query);
		$db->execute();
	}
}
