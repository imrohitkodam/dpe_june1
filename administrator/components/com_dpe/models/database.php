<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die();
use Joomla\CMS\Schema\ChangeSet;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

require_once JPATH_ADMINISTRATOR . '/components/com_installer/models/database.php';

/**
 * Database model class.
 *
 * @package  DPE
 * @since    __DEPLOY_VERSION
 */
class DpeModelDatabase extends InstallerModelDatabase
{
	/**
	 * Gets the changeset object.
	 *
	 * @return  ChangeSet|boolean
	 */
	public function getItems()
	{
		$folder = JPATH_ADMINISTRATOR . '/components/com_dpe/sql/updates/';

		try
		{
			$changeSet = ChangeSet::getInstance($this->getDbo(), $folder);
		}
		catch (RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');

			return false;
		}

		return $changeSet;
	}

	/**
	 * + Techjoomla - Dummy override
	 * Fix schema version if wrong.
	 *
	 * @param   JSchemaChangeSet  $changeSet  Schema change set.
	 *
	 * @return   mixed  string schema version if success, false if fail.
	 */
	public function fixSchemaVersion($changeSet)
	{
		// We don't want to update anything related to core Joomla after db upgrade fix
		$schema = $this->getSchemaVersion();

		return $schema;
	}

	/**
	 * Fix Joomla version in #__extensions table if wrong
	 *
	 * @return   mixed  string update version if success, false if fail.
	 */
	public function fixUpdateVersion()
	{
		$table = Table::getInstance('Extension');
		$table->load(array('type' => 'component', 'element' => 'com_dpe'));
		$cache = new Registry($table->manifest_cache);
	}
}
