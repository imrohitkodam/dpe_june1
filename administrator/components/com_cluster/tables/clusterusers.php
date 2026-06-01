<?php
/**
 * @package    Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\Data\DataObject;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

/**
 * Cluster users table class
 *
 * @since  1.0.0
 */
class ClusterTableClusterUsers extends Table
{
	/**
	 * Constructor
	 *
	 * @param   DataObjectbaseDriver  &$db  Database object
	 *
	 * @since  1.0.0
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tj_cluster_nodes', 'id', $db);
		$this->setColumnAlias('published', 'state');
	}

	/**
	 * Overrides Table::store to set modified data and user id.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0.0
	 */
	public function store($updateNulls = false)
	{
		$date = Factory::getDate();
		$user = Factory::getUser();

		if ($this->id)
		{
			$this->modified_by = $user->id;
			$this->modified_on = $date->toSql();
		}
		else
		{
			$this->created_by = $user->id;
			$this->created_on = $date->toSql();
		}

		return parent::store($updateNulls);
	}
}
