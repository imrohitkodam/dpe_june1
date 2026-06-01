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
use Joomla\Registry\Registry;

/**
 * Sla class.  Handles all application interaction with a Sla
 *
 * @since  1.0.0
 */
class SlaSla extends CMSObject
{
	public $id = null;

	public $title = "";

	public $description = "";

	public $params = '';

	public $ordering = 0;

	public $state = 1;

	public $checked_out = null;

	public $checked_out_time = null;

	public $created_on = null;

	public $created_by = 0;

	public $modified_on = null;

	public $modified_by = 0;

	protected static $slaObj = array();

	/**
	 * Constructor activating the default information of the Sla
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

		$this->checked_out_time = $this->created_on = $this->modified_on = $db->getNullDate();
	}

	/**
	 * Returns the global Sla object
	 *
	 * @param   integer  $id  The primary key of the sla to load (optional).
	 *
	 * @return  SlaSla  The Sla object.
	 *
	 * @since   1.0.0
	 */
	public static function getInstance($id = 0)
	{
		if (!$id)
		{
			return new SlaSla;
		}

		if (empty(self::$slaObj[$id]))
		{
			$sla = new SlaSla($id);
			self::$slaObj[$id] = $sla;
		}

		return self::$slaObj[$id];
	}

	/**
	 * Method to load a sla object by sla id
	 *
	 * @param   int  $id  The sla id
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function load($id)
	{
		$table = SlaFactory::table("slas");

		if (!$table->load($id))
		{
			return false;
		}

		$this->setProperties($table->getProperties());

		return true;
	}

	/**
	 * Method to save the Sla object to the database
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0.0
	 * @throws  \RuntimeException
	 */
	public function save()
	{
		// Create the widget table object
		$table = SlaFactory::table("slas");
		$table->bind($this->getProperties());

		$currentDateTime = Factory::getDate()->toSql();

		$user = Factory::getUser();

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

			if ($isNew)
			{
				$table->created_on = $currentDateTime;
				$table->created_by = $user->id;
			}
			else
			{
				$table->modified_on = $currentDateTime;
				$table->modified_by = $user->id;
			}

			// Store the user data in the database
			if (!($table->store()))
			{
				$this->setError($table->getError());

				return false;
			}

			$this->id = $table->id;

			// Fire the onSlaAfterSave event. joomla 4 changes.
			Factory::getApplication()->triggerEvent('onSlaAfterSave', array($isNew, $this));
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return true;
	}

	/**
	 * Method to bind an associative array of data to a sla object
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

	/**
	 * Method to get Sla services
	 *
	 * @return  object  SlaService details
	 *
	 * @since 1.0.0
	 */
	public function getSlaServices()
	{
		$slaServicesModel = SlaFactory::model('SlaServices');
		$slaServicesModel->setState('filter.sla_id', $this->id);

		return $slaServicesModel->getItems();
	}

	/**
	 * Method to get sla activity types only if configured from backend
	 *
	 * @return  object  sla activity types
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getSlaActivityTypes()
	{
		$activityTypes         = new Registry($this->params);
		$slaActivityTypesModel = SlaFactory::model('SlaActivityTypes', array('ignore_request' => true));
		$slaActivityTypesModel->setState('filter.id', explode(',', /** @scrutinizer ignore-type */ $activityTypes['activityTypes']));
		$slaActivityTypesModel->setState('filter.state', 1);

		return $slaActivityTypesModel->getItems();
	}

	/**
	 * Method to get sla tools only if configured from backend
	 *
	 * @return  Array  sla Tools
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getSlaTools()
	{
		$tools         = new Registry($this->params);

		return $tools->get('tools');
	}
}
