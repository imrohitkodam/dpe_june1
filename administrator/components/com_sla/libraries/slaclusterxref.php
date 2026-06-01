<?php
/**
 * @package    Sla
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die('Unauthorized Access');
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;

/**
 * Sla cluster xref class.  Handles all application interaction with a Sla cluster xref
 *
 * @since  1.0.0
 */
class SlaSlaClusterXref extends CMSObject
{
	public $id = null;

	public $sla_id = 0;

	public $cluster_id = 0;

	public $license_id = 0;

	public $lead_consultant_id = 0;

	public $notify_dpe_admin = 0;

	public $dpeadmins = '';

	protected static $slaClusterXref = array();

	/**
	 * Constructor activating the default information of the Sla cluster xref
	 *
	 * @param   int  $id  The unique event key to load.
	 *
	 * @since   1.0.0
	 */
	public function __construct($id = 0)
	{
		if (!empty($id))
		{
			$this->load($id);
		}

		$db = Factory::getDbo();
	}

	/**
	 * Returns the global Sla cluster xref object
	 *
	 * @param   integer  $id  The primary key of the sla to load (optional).
	 *
	 * @return  SlaSlaClusterXref  The SlaClusterXref object.
	 *
	 * @since   1.0.0
	 */
	public static function getInstance($id = 0)
	{
		if (!$id)
		{
			return new SlaSlaClusterXref;
		}

		if (empty(self::$slaClusterXref[$id]))
		{
			$sla = new SlaSlaClusterXref($id);
			self::$slaClusterXref[$id] = $sla;
		}

		return self::$slaClusterXref[$id];
	}

	/**
	 * Method to load a sla cluster xref object by sla cluster xref id
	 *
	 * @param   int  $id  The sla cluster xref id
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function load($id)
	{
		$table = SlaFactory::table("slaclusterxrefs");

		if (!$table->load($id))
		{
			return false;
		}

		$this->setProperties($table->getProperties());

		return true;
	}

	/**
	 * Method to save the Sla cluster xref object to the database
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0.0
	 * @throws  \RuntimeException
	 */
	public function save()
	{
		// Create the widget table object
		$table = SlaFactory::table("slaclusterxrefs");
		$table->bind($this->getProperties());

		// Allow an exception to be thrown.
		try
		{
			// Check and store the object.
			if (!$table->check())
			{
				$this->setError($table->getError());

				return false;
			}

			// Check if new record
			$isNew = empty($this->id);

			// Store the user data in the database
			if (!($table->store()))
			{
				$this->setError($table->getError());

				return false;
			}

			$this->id = $table->id;

			// Fire the onSlaAfterSave event.

			Factory::getApplication()->triggerEvent('onSlaClusterXrefAfterSave', array($isNew, $this));
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return true;
	}

	/**
	 * Method to bind an associative array of data to a sla cluster xref object
	 *
	 * @param   array  &$array  The associative array to bind to the object
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function bind(&$array)
	{
		if (empty ($array))
		{
			$this->setError(Text::_('COM_SLA_EMPTY_DATA'));

			return false;
		}

		// Bind the array
		if (!$this->setProperties($array))
		{
			$this->setError(\Text::_('COM_SLA_BINDING_ERROR'));

			return false;
		}

		// Make sure its an integer
		$this->id = (int) $this->id;

		return true;
	}
}
