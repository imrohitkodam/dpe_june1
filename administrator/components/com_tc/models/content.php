<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tc
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2016-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
Use Joomla\CMS\MVC\Model\AdminModel;

jimport('joomla.application.component.modeladmin');

/**
 * Tc model.
 *
 * @since  1.6
 */
class TcModelContent extends AdminModel
{
	/**
	 * @var      string    The prefix to use with controller messages.
	 * @since    1.6
	 */
	protected $text_prefix = 'COM_TC';

	/**
	 * @var   	string  	Alias to manage history control
	 * @since   3.2
	 */
	public $typeAlias = 'com_tc.content';

	/**
	 * @var null  Item data
	 * @since  1.6
	 */
	protected $item = null;

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return    Joomla\CMS\Table\Table    A database object
	 *
	 * @since    1.6
	 */
	public function getTable($type = 'Content', $prefix = 'TcTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm  A JForm object on success, false on failure
	 *
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.

		// Get the form.
		$form = $this->loadForm(
			'com_tc.content', 'content',
			array('control' => 'jform',
				'load_data' => $loadData
			)
		);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return   mixed  The data for the form.
	 *
	 * @since    1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_tc.edit.content.data', array());

		if (empty($data))
		{
			if ($this->item === null)
			{
				$this->item = $this->getItem();
			}

			$data = $this->item;
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since    1.6
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Do any procesing on fields here if needed

			if ($item->tc_id)
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_tc/models/urlpatterns.php';

				$urlPatternsModel = JModelLegacy::getInstance('urlpatterns', 'TcModel');

				$item->url_pattern = $urlPatternsModel->getItems();
			}

			// Exploding user saved user groups.
			$item->groups = explode(',', $item->groups);
		}

		return $item;
	}

	/**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   JTable  $table  Table Object
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if (empty($table->tc_id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering === '')
			{
				$db = Factory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__tc_content');
				$max             = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * save a record (and redirect to main page)
	 *
	 * @param   array  $data  TC form data
	 *
	 * @return boolean|void
	 */
	public function save($data)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_tc/models/urlpattern.php';
		parent::save($data);
		$tcId   = (int) $this->getState($this->getName() . '.id');
		$client = $data['client'];
		$url_pattern = $data['url_pattern'];
		$isNew = true;

		// Delete existing url patterns
		$urlPatternModel = JModelLegacy::getInstance('urlpattern', 'TcModel');

		$urlPatternIds = $urlPatternModel->getURLPatternIdList($tcId);

		if (count($urlPatternIds))
		{
			foreach ($urlPatternIds as $patternId)
			{
				$deletePatternData = $urlPatternModel->delete($patternId->id);

				if ($deletePatternData != 1)
				{
					return false;
				}
			}
		}

		// Save / Update URL patterns
		$tcURLObj = array();

		foreach ($url_pattern as $pattern)
		{
			$tcURLObj['id'] = '';
			$tcURLObj['tc_id'] = $tcId;
			$tcURLObj['client'] = $client;
			$tcURLObj['option'] = $pattern['option'];
			$tcURLObj['view'] = $pattern['view'];
			$tcURLObj['params'] = '';

			$patternData = $urlPatternModel->save($tcURLObj);

			if ($patternData != 1)
			{
				return false;
			}
		}

		$this->setState('com_tc.edit.content.id', $tcId);
		$this->setState('com_tc.edit.content.new', $isNew);

		return true;
	}

	/**
	 * Method to check valid TC based on client & version values via AJAX.
	 *
	 * @param   STRING  $tcVersion  TC version
	 * @param   STRING  $tcClient   TC client
	 *
	 * @return  boolean|string
	 *
	 * @since   3.0
	 */
	public function checkDuplicateAndLatestVersionTC($tcVersion,$tcClient)
	{
		if ($tcVersion && $tcClient)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('version');
			$query->from($db->quoteName('#__tc_content'));
			$query->where($db->quoteName('client') . " = " . $db->quote($tcClient));

			$db->setQuery($query);
			$getTCVersions = $db->loadObjectList();

			$maxVersion = array();

			foreach ($getTCVersions as $TCVersions)
			{
				array_push($maxVersion, $TCVersions->version);
			}

			if ($getTCVersions)
			{
				if ($tcVersion <= max($maxVersion))
				{
					return max($maxVersion);
				}
				else
				{
					return true;
				}
			}
			else
			{
				return 'newVersion';
			}
		}
	}

	/**
	 * Method to get latest matching TC id's based option and view parameters
	 *
	 * @param   STRING  $option  component  option name
	 * @param   STRING  $view    component view name
	 *
	 * @return array|array<mixed,mixed>
	 *
	 * @since  1.6
	 */
	public function getMatchingTCs($option, $view)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('p.tc_id, c.version, c.client');
		$query->from($db->quoteName('#__tc_patterns', 'p'));
		$query->join('LEFT', $db->quoteName('#__tc_content', 'c') . ' ON (' . $db->quoteName('c.tc_id') . ' = ' . $db->quoteName('p.tc_id') . ')');
		$query->where($db->quoteName('p.option') . " = " . $db->quote($option));
		$query->where($db->quoteName('p.view') . " = " . $db->quote($view));
		$query->where($db->quoteName('c.start_date') . " <= UTC_TIMESTAMP()");
		$query->where($db->quoteName('c.state') . " = " . $db->quote(1));

		// Order in ascending, so get latest version of T&C
		$orderCol  = 'c.version';
		$orderDirn = 'asc';
		$query->order($db->escape($orderCol . ' ' . $orderDirn));
		$db->setQuery($query);

		// Returns an associated array, it will give result with highest version number first for every client
		$getMatchingTCIdList = $db->loadObjectList('client');

		return $getMatchingTCIdList;
	}

	/**
	 * Method to check accepeted TC ids & return not accepted TC ids to accept user
	 *
	 * @param   INT  $loggedInUserId  logged in used id
	 * @param   INT  $tcId            TC id
	 *
	 * @return array
	 *
	 * @since  1.6
	 */
	public function hasUserAcceptedTC($loggedInUserId, $tcId)
	{
		// Get user accepted TC ids
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('c.tc_id');
		$query->from($db->quoteName('#__tc_content', 'c'));

		$query->join('LEFT', $db->quoteName('#__tc_acceptance', 'a') . ' ON (' . $db->quoteName('c.tc_id') . ' = ' . $db->quoteName('a.tc_id') . ')');

		$query->where($db->quoteName('a.user_id') . ' = ' . $loggedInUserId);

		$db->setQuery($query);
		$userAcceptedTCs = $db->loadObjectList();

		$tcIdList = array();

		foreach ($tcId as $key => $value)
		{
			unset($value->version);
			unset($value->client);

			array_push($tcIdList, $value);
		}

		// Remove accepted TC ids from pattern matching TC ids
		if (count($userAcceptedTCs))
		{
			foreach ($userAcceptedTCs as $key => $value)
			{
				if (($key = array_search($value, $tcIdList)) !== false)
				{
					unset($tcIdList[$key]);
				}
			}

			$tcAcceptIdList = array_values($tcIdList);

			return $tcAcceptIdList;
		}
		else
		{
			return $tcIdList;
		}
	}

	/**
	 * Method to get TC User group, Global, Is Blacklist(user group behaviour) values
	 *
	 * @param   INT  $tcId  TC id
	 *
	 * @return boolean
	 *
	 * @since  1.6
	 */
	public function getTCValidationStatus($tcId)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('global,groups,is_blacklist');
		$query->from($db->quoteName('#__tc_content'));
		$query->where($db->quoteName('tc_id') . " = " . $db->quote($tcId));
		$query->where($db->quoteName('state') . " = " . $db->quote(1));

		$db->setQuery($query);
		$TCValidationInfo = $db->loadObjectList();

		if (!empty($TCValidationInfo))
		{
			// Check TC user groups access
			$checkTCGroupAccess = $this->checkUserGroupAccess($TCValidationInfo[0]->groups, $TCValidationInfo[0]->is_blacklist);

			if ($checkTCGroupAccess == 1)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to check TC user group access
	 *
	 * @param   array  $tcUserGroup  TC groups
	 * @param   INT    $isBlacklist  TC groups
	 *
	 * @return void
	 *
	 * @since  1.6
	 */
	public function checkUserGroupAccess($tcUserGroup,$isBlacklist)
	{
		$user = Factory::getUser();
		$loggedInUserId = $user->get('id');
		$userGroups = Factory::getUser($loggedInUserId);

		if ($loggedInUserId)
		{
			if (!empty($tcUserGroup))
			{
				if ($tcUserGroup && $isBlacklist == 1)
				{
					$explodeSavedTCGroup = explode(",", $tcUserGroup);

					for ($j = 0; $j < count($explodeSavedTCGroup); $j++)
					{
						if (in_array($explodeSavedTCGroup[$j], $userGroups->groups))
						{
							return true;
						}
					}
				}
				elseif ($tcUserGroup && $isBlacklist == 0)
				{
					$explodeSavedTCGroup = explode(",", $tcUserGroup);

					for ($j = 0; $j < count($explodeSavedTCGroup); $j++)
					{
						if (in_array($explodeSavedTCGroup[$j], $userGroups->groups))
						{
							// Avoid to show TC if stored groups is available in user groups[because is_blacklist is set to 0]
						}
						else
						{
							return true;
						}
					}
				}
			}
			else
			{
				// If user group is empty then show TC to all users
				return true;
			}
		}
	}

	/**
	 * Method to get all global TC id's
	 *
	 * @return INT global TC id
	 *
	 * @since  1.6
	 */
	public function getGlobalTCIdList()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('p.tc_id, c.version, c.client');
		$query->from($db->quoteName('#__tc_patterns', 'p'));
		$query->join('LEFT', $db->quoteName('#__tc_content', 'c') . ' ON (' . $db->quoteName('c.tc_id') . ' = ' . $db->quoteName('p.tc_id') . ')');
		$query->where($db->quoteName('c.global') . " = " . $db->quote(1));
		$query->where($db->quoteName('c.start_date') . " <= UTC_TIMESTAMP()");
		$query->where($db->quoteName('c.state') . " = " . $db->quote(1));

		// Order in ascending, so get latest version of T&C
		$orderCol  = 'c.version';
		$orderDirn = 'asc';
		$query->order($db->escape($orderCol . ' ' . $orderDirn));
		$db->setQuery($query);

		// Returns an associated array, it will give result with highest version number first for every client
		$globalTCIdList = $db->loadObjectList('client');

		return $globalTCIdList;
	}

	/**
	 * Method to get global TC validation status
	 *
	 * @param   INT  $tcId  TC id's
	 *
	 * @return INT global TC id
	 *
	 * @since  1.6
	 */
	public function getGlobalTCValidationStatus($tcId)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('groups,is_blacklist');
		$query->from($db->quoteName('#__tc_content'));
		$query->where($db->quoteName('tc_id') . " = " . $db->quote($tcId));

		$db->setQuery($query);
		$GlobalTCValidationStatus = $db->loadObjectList();

		// Check TC user groups access
		$checkGlobalTCGroupAccess = $this->checkUserGroupAccess($GlobalTCValidationStatus[0]->groups, $GlobalTCValidationStatus[0]->is_blacklist);

		if ($checkGlobalTCGroupAccess == 1)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to get T&C client name based on T&C id
	 *
	 * @param   INT  $tcId  tc id.
	 *
	 * @return  client name
	 *
	 * @since    1.6
	 */
	public function getTCClient($tcId)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('client');
		$query->from($db->quoteName('#__tc_content'));
		$query->where($db->quoteName('tc_id') . " = " . $db->quote($tcId));

		$db->setQuery($query);
		$TCClient = $db->loadResult();

		return $TCClient;
	}

	/**
	 * Method to check user has already T&C accepted or not. If accepted then redirect to Home page.
	 *
	 * @param   INT  $loggedInUserId  logged in used id
	 * @param   INT  $tcId            TC id
	 *
	 * @return true/false
	 *
	 * @since  1.6
	 */
	public function isUserAcceptedTC($loggedInUserId, $tcId)
	{
		if ($loggedInUserId && $tcId)
		{
			$table = $this->getTable('usertcs');
			$table->load(['user_id' => $loggedInUserId, 'tc_id' => $tcId]);

			// Return true if user has already accepted T&C else return false
			if ($table->tc_id)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Loads ContentHelper for filters before validating data.
	 *
	 * @param   object  $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the group(defaults to null).
	 *
	 * @return  mixed  array of filtered data if valid, false otherwise.
	 *
	 * @since   1.1
	 */
	public function validate($form, $data, $group=null)
	{
		$url_pattern = $data['url_pattern'];
		$global = $data['global'];

		foreach ($url_pattern as $pattern)
		{
			$option = $pattern['option'];
			$view = $pattern['view'];

			// Don't allow to add T&C for same component.
			if (($option == 'com_tc') && ($view == 'content'))
			{
				$this->setError(JText::_('COM_TC_INVALID_URL_PATTERN'));

				return false;
			}

			if ($global == 0 && (empty($option) || empty($view)))
			{
				$this->setError(JText::_('COM_TC_ENTER_URL_PATTERN'));

				return false;
			}
		}

		return parent::validate($form, $data, $group);
	}
}
