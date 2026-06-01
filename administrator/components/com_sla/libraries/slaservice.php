<?php
/**
 * @package    Sla
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die('Unauthorized Access');
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

use Joomla\CMS\Factory;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;

/**
 * Sla service class.  Handles all application interaction with a Sla Service
 *
 * @since  1.0.0
 */
class SlaSlaService extends CMSObject
{
	public $id = null;

	public $sla_id = 0;

	public $sla_activity_type_id = 0;

	public $title = '';

	public $description = '';

	public $params = '';

	public $ideal_time = 0;

	public $ordering = 0;

	public $state = 1;

	public $checked_out = null;

	public $checked_out_time = null;

	public $created_on = null;

	public $created_by = 0;

	public $modified_on = null;

	public $modified_by = 0;

	protected static $slaServiceObj = array();

	/**
	 * Constructor activating the default information of the Sla service
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
	 * Returns the global sla service object
	 *
	 * @param   integer  $id  The primary key of the sla service id to load (optional).
	 *
	 * @return  Object  Sla service object.
	 *
	 * @since   1.0.0
	 */
	public static function getInstance($id = 0)
	{
		// @Todo- Check the comments for this function
		if (!$id)
		{
			return new SlaSlaService;
		}

		if (empty(self::$slaServiceObj[$id]))
		{
			$slaService = new SlaSlaService($id);
			self::$slaServiceObj[$id] = $slaService;
		}

		return self::$slaServiceObj[$id];
	}

	/**
	 * Method to load a sla service object by sla service id
	 *
	 * @param   int  $id  The sla service id
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 */
	public function load($id)
	{
		$table = SlaFactory::table("slaservices");

		if (!$table->load($id))
		{
			return false;
		}

		$this->setProperties($table->getProperties());

		return true;
	}

	/**
	 * Method to save the Sla service object to the database
	 *
	 * @return  boolean  True on success
	 *
	 * @since 1.0.0
	 * @throws  \RuntimeException
	 */
	public function save()
	{
		// Create the sla service table object
		$table = SlaFactory::table("slaservices");
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

			// Fire the onSlaServiceAfterSave event.
			Factory::getApplication()->triggerEvent('onSlaServiceAfterSave', array($isNew, $this));
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return true;
	}

	/**
	 * Method to bind an associative array of data to a sla service object
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
			$this->setError(Text::_('COM_SLA_BINDING_ERROR'));

			return false;
		}

		// Make sure its an integer
		$this->id = (int) $this->id;

		return true;
	}

	/**
	 * Method to get Sla details
	 *
	 * @return  object  Sla details
	 *
	 * @since 1.0.0
	 */
	public function getSlaDetails()
	{
		return SlaSla::getInstance($this->sla_id);
	}

	/**
	 * Method to add Sla service Jlike Todos
	 *
	 * @param   array  $data  Todo data.
	 *
	 * @return  object  Jlike Todo Table object
	 *
	 * @since 1.0.0
	 */
	public function saveTodo($data)
	{
		$user            = Factory::getUser();
		$currentDateTime = Factory::getDate()->toSql();

		// Initial Todo status is Incomplete
		$initialTodoStatus = 'I';

		// Todo type is 'assign'
		$todoType = 'assign';

		JLoader::import('components.com_jlike.models.recommendationform', JPATH_SITE);
		$recommendationFormModel = BaseDatabaseModel::getInstance('RecommendationForm', 'JlikeModel', array('ignore_request' => true));

		$todoData = array();

		$todoData['id']           = $data['id'];
		$todoData['state']        = 1;
		$todoData['created_by']   = $user->id;
		$todoData['assigned_by']  = $user->id;
		$todoData['assigned_to']  = $data['assigned_to'];
		$todoData['created_date'] = $currentDateTime;
		$todoData['start_date']   = (!empty($data['start_date'])) ? $data['start_date'] : $currentDateTime;
		$todoData['due_date']     = (!empty($data['due_date'])) ? $data['due_date'] : $currentDateTime;
		$todoData['status']       = $initialTodoStatus;
		$todoData['title']        = (!empty($data['title'])) ? $data['title'] : $this->title;
		$todoData['type']         = $todoType;
		$todoData['ideal_time']   = (!empty($data['ideal_time'])) ? $data['ideal_time'] : $this->ideal_time;
		$todoData['sender_msg']   = $data['sender_msg'];
		$todoData['content_id']   = $data['content_id'] ? $data['content_id'] : 0;
		$todoData['parent_id']    = $data['parent_id'] ? $data['parent_id'] : 0;
		$todoData['notifyClient'] = "com_sla";
		$todoData['cc_users']     = ($data['cc_users'])?$data['sender_msg']:'0';

		// This code will execute when activities creating by cron
		if ($data['key'])
		{
			$todoData['key'] = $data['key'];
		}

		return $recommendationFormModel->save($todoData);
	}

	/**
	 * Method to update Sla service Jlike Todo's assignee
	 *
	 * @param   int  $assignedTo  Todo Assigned to user.
	 *
	 * @param   int  $todoId      Jlike Todo Id.
	 *
	 * @return  object  Jlike Todo Table object
	 *
	 * @since 1.0.0
	 */
	public function updateTodo($assignedTo, $todoId)
	{
		$user            = Factory::getUser();
		$currentDateTime = Factory::getDate()->toSql();

		JLoader::import('components.com_jlike.models.recommendationform', JPATH_SITE);
		$recommendationFormModel = BaseDatabaseModel::getInstance('RecommendationForm', 'JlikeModel', array('ignore_request' => true));

		$todoData = array();

		$todoData['id']            = $todoId;
		$todoData['assigned_by']   = $user->id;
		$todoData['assigned_to']   = $assignedTo;
		$todoData['modified_date'] = $currentDateTime;
		$todoData['created_by']    = $user->id;

		return $recommendationFormModel->save($todoData);
	}
}
