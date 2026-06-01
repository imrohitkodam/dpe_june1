<?php

/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;

/**
 * Methods supporting Storages.
 *
 * @since  1.0.0
 */

class TjlmsTableStorages3 extends Table
{
	public $storageid = null;

	public $resource_path = null;

	/**
	 * Constructor.
	 *
	 * @param   object  &$db  DB object.
	 *
	 * @see     JController
	 * @since    1.6
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tjlms_storage_s3', 'storageid', $db);
	}

	/**
	 * store.
	 *
	 * @param   object  $updateNulls  updateNulls.
	 *
	 * @see     JController
	 *
	 * @return  boolean
	 *
	 * @since    1.6
	 */
	public function store($updateNulls = null)
	{
		$k = $this->_tbl_key;

		if (empty($this->$k))
		{
			return false;
		}

		$db = $this->getDBO();

		$query = 'SELECT count(*)'
				. ' FROM ' . $this->_tbl
				. ' WHERE ' . $this->_tbl_key . ' = ' . $db->Quote($this->storageid);
		$db->setQuery($query);
		$isExist = $db->loadResult();

		if (!$isExist)
		{
			$query = 'INSERT INTO ' . $this->_tbl
					. ' SET ' . $db->quoteName('storageid') . '=' . $db->Quote($this->storageid)
					. ' , ' . $db->quoteName('resource_path') . '= ' . $db->Quote($this->resource_path);
			$db->setQuery($query);
			
			try
			{
				$db->execute();
			}
			catch (\RuntimeException $e)
			{
				$this->setError($e->getMessage());

				return false;
			}
		}
		else
		{
			$query = 'UPDATE ' . $this->_tbl
					. ' SET ' . $db->quoteName('resource_path') . '= ' . $db->Quote($this->resource_path)
					. ' WHERE ' . $db->quoteName('storageid') . '=' . $db->Quote($this->storageid);
			$db->setQuery($query);
			
			try
			{
				$db->execute();
			}
			catch (\RuntimeException $e)
			{
				$this->setError($e->getMessage());

				return false;
			}
		}

		return true;
	}
}
