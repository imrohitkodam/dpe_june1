<?php
/**
 * @package    TJ-Fields
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Table\Table;

/**
 * Migration file for TJ-Fields
 *
 * @since  1.4.6
 */
class TjHouseKeepingTjVendorsColumn extends TjModelHouseKeeping
{
	public $title = "Country, Region, and City table fix for com_tjvendors";

	public $description = "Add com_tjvendors column in Country, Region, and City table";

	/**
	 * Add com_tjvendors column in Country, Region, and City table if not exists
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function migrate()
	{
		$result = array();

		try
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query = "SHOW COLUMNS FROM `#__tj_city`";
			$db->setQuery($query);
			$columns = $db->loadAssoclist();

			$columns = array_column($columns, "Field");

			if (!in_array('com_tjvendors', $columns))
			{
				$query = "ALTER TABLE `#__tj_city` ADD COLUMN `com_tjvendors` tinyint(1) NOT NULL DEFAULT '1'";
				$db->setQuery($query);

				if (!$db->execute())
				{
					$result['status']   = false;
					$result['message']  = $db->getErrorMsg();

					return $result;
				}
			}

			$query = $db->getQuery(true);
			$query = "SHOW COLUMNS FROM `#__tj_region`";
			$db->setQuery($query);
			$columns = $db->loadobjectlist();

			$columns = array_column($columns, "Field");

			if (!in_array('com_tjvendors', $columns))
			{
				$query = "ALTER TABLE `#__tj_region` ADD COLUMN `com_tjvendors` tinyint(1) NOT NULL DEFAULT '1'";
				$db->setQuery($query);

				if (!$db->execute())
				{
					$result['status']   = false;
					$result['message']  = $db->getErrorMsg();

					return $result;
				}
			}

			$query = $db->getQuery(true);
			$query = "SHOW COLUMNS FROM `#__tj_country`";
			$db->setQuery($query);
			$columns = $db->loadobjectlist();

			$columns = array_column($columns, "Field");

			if (!in_array('com_tjvendors', $columns))
			{
				$query = "ALTER TABLE `#__tj_country` ADD COLUMN `com_tjvendors` tinyint(1) NOT NULL DEFAULT '1'";
				$db->setQuery($query);

				if (!$db->execute())
				{
					$result['status']   = false;
					$result['message']  = $db->getErrorMsg();

					return $result;
				}
			}

			$result['status']   = true;
			$result['message']  = "Migration successful";
		}
		catch (Exception $e)
		{
			$result['err_code'] = '';
			$result['status']   = false;
			$result['message']  = $e->getMessage();
		}

		return $result;
	}
}
