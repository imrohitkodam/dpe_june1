<?php
/**
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2020 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Multiagency model.
 *
 * @since  __DEPLOY__VERSION__
 */
class MultiagencyModelMultiagency extends ItemModel
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return void
	 *
	 * @since    __DEPLOY__VERSION__
	 *
	 */
	protected function populateState()
	{
		$app  = Factory::getApplication('com_multiagency');
		$user = Factory::getUser();

		// Check published state
		if ((!$user->authorise('core.edit.state', 'com_multiagency')) && (!$user->authorise('core.edit', 'com_multiagency')))
		{
			$this->setState('filter.published', 1);
			$this->setState('fileter.archived', 2);
		}

		// Load state from the request userState on edit or from the passed variable on default
		if (Factory::getApplication()->input->get('layout') == 'edit')
		{
			$id = Factory::getApplication()->getUserState('com_multiagency.edit.multiagency.id');
		}
		else
		{
			$id = Factory::getApplication()->input->get('id');
			Factory::getApplication()->setUserState('com_multiagency.edit.multiagency.id', $id);
		}

		$this->setState('multiagency.id', $id);

		// Load the parameters.
		$params       = $app->getParams();
		$params_array = $params->toArray();

		if (isset($params_array['item_id']))
		{
			$this->setState('multiagency.id', $params_array['item_id']);
		}

		$this->setState('params', $params);
	}

/**
     * Method to get an item.
     *
     * @param   integer  $pk  The id of the item
     *
     * @return  object
     *
     * @since 4.0.0
     * @throws \Exception
     */
    public function getItem($pk = null)
    {

    }
    
	/**
	 * Method to get an object.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return  mixed    Object on success, false on failure.
	 */
	public function &getData($id = null)
	{
		if ($this->_item === null)
		{
			$this->_item = false;

			if (empty($id))
			{
				$id = $this->getState('multiagency.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			// Attempt to load the row.
			if ($table->load($id))
			{
				// Check published state.
				if ($published = $this->getState('filter.published'))
				{
					if (isset($table->state) && $table->state != $published)
					{
						throw new Exception(Text::_('COM_MULTIAGENCY_ITEM_NOT_LOADED'), 403);
					}
				}

				// Convert the JTable to a clean JObject.
				$properties  = $table->getProperties(1);
				$this->_item = ArrayHelper::toObject($properties, 'JObject');
			}
		}

		if (isset($this->_item->created_by) )
		{
			$this->_item->created_by_name = Factory::getUser($this->_item->created_by)->name;
		}

		if (isset($this->_item->modified_by) )
		{
			$this->_item->modified_by_name = Factory::getUser($this->_item->modified_by)->name;
		}

		if (isset($this->_item->manager_id) )
		{
			$this->_item->manager_id_name = Factory::getUser($this->_item->manager_id)->name;
		}

		return $this->_item;
	}

	/**
	 * Get an instance of Table class
	 *
	 * @param   string  $type    Name of the Table class to get an instance of.
	 * @param   string  $prefix  Prefix for the table class name. Optional.
	 * @param   array   $config  Array of configuration values for the Table object. Optional.
	 *
	 * @return  Table|bool Table if success, false on failure.
	 */
	public function getTable($type = 'Multiagency', $prefix = 'MultiagencyTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Get the id of an item by alias
	 *
	 * @param   string  $alias  Item alias
	 *
	 * @return  mixed
	 */
	public function getItemIdByAlias($alias)
	{
		$table = $this->getTable();

		$table->load(array('alias' => $alias));

		return $table->id;
	}

	/**
	 * Method to check in an item.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	public function checkin($id = null)
	{
		// Get the id.
		$id = (!empty($id)) ? $id : (int) $this->getState('multiagency.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Attempt to check the row in.
			if (method_exists($table, 'checkin'))
			{
				if (!$table->checkin($id))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to check out an item for editing.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	public function checkout($id = null)
	{
		// Get the user id.
		$id = (!empty($id)) ? $id : (int) $this->getState('multiagency.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Get the current user object.
			$user = Factory::getUser();

			// Attempt to check the row out.
			if (method_exists($table, 'checkout'))
			{
				if (!$table->checkout($user->get('id'), $id))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get the name of a category by id
	 *
	 * @param   int  $id  Category id
	 *
	 * @return  Object|null	Object if success, null in case of failure
	 */
	public function getCategoryName($id)
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select('title')
			->from('#__categories')
			->where('id = ' . $id);
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Publish the element
	 *
	 * @param   int  $id     Item id
	 * @param   int  $state  Publish state
	 *
	 * @return  boolean
	 */
	public function publish($id, $state)
	{
		$table = $this->getTable();
		$table->load($id);
		$table->state = $state;

		return $table->store();
	}

	/**
	 * Method to delete an item
	 *
	 * @param   int  $id  Element id
	 *
	 * @return  bool
	 */
	public function delete($id)
	{
		$table = $this->getTable();

		return $table->delete($id);
	}

	/**
	 * Method to get an allocated agencies of users
	 *
	 * @param   int    $userId   Retrive record based on User Id
	 * @param   array  $notRole  not allowed agency roles array
	 * @param   int    $options  the possible options to query change
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getAllocatedAgencies($userId = null, $notRole = null, $options = array())
	{
		$user = Factory::getUser();
		$params = ComponentHelper::getParams('com_multiagency');
		$adminRole = $params->get('multyagency_admin_role_id', '0', 'INT');

		// Check if current user is super user
		$currentUserSuperUser = $user->authorise('core.admin');

		JLoader::import("components.com_subusers.includes.rbacl", JPATH_ADMINISTRATOR);

		if ($userId == null)
		{
			$userId = $user->id;
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$memberRole = RBACL::getRoleByUser($userId, 'com_multiagency', 0);

		$query->select(' DISTINCT ml.id, ml.title');
		$query->from($db->quoteName('#__tjmultiagency_multiagency') . ' AS ml');

		// Show all schools to dpeadmin or where the all schools needed
		if (!$options['schools_without_license'])
		{
			$query->select('su.role_id');
			$query->join('INNER', $db->quoteName('#__tjsu_users', 'su') . ' ON (' . $db->quoteName('su.client_id') . ' = ' . $db->quoteName('ml.id') . ')');
			$query->join('INNER', $db->quoteName('#__tjsu_roles', 'tr') . ' ON (' . $db->quoteName('tr.id') . ' = ' . $db->quoteName('su.role_id') . ')');
			$query->where($db->quoteName('tr.state') . ' = ' . 1);

			// Check admin role

			if (!in_array($adminRole, $memberRole) && !$currentUserSuperUser)
			{
				$query->where($db->quoteName('su.user_id') . ' = ' . $db->quote((int) $userId));
			}

			$query->where($db->quoteName('su.client') . " = 'com_multiagency'");

			if (!empty($notRole))
			{
				$query->where($db->quoteName('su.role_id') . 'NOT IN ( ' . implode(',', $notRole) . ')');
			}
		}

		$query->where($db->quoteName('ml.state') . ' =  1');
		$query->order($db->quoteName('ml.title') . ' ASC');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Function to get the agency filter
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getAgencyFilter()
	{
		$loggedInUser = Factory::getUser()->id;

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$multiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel');
		$params = ComponentHelper::getParams('com_multiagency');
		$memberRole = $params->get('member_role_id', '0', 'INT');
		$managerAgencies = $multiagencyModel->getAllocatedAgencies($loggedInUser, array($memberRole));

		$agencyFilter[] = HTMLHelper::_('select.option', '', Text::sprintf('COM_TJLMS_FILTER_SELECT_AGENCY', Text::_('COM_MULTIAGENCY_ORGANISATION')));

		if (!empty($managerAgencies))
		{
			foreach ($managerAgencies as $agency)
			{
				$agencyFilter[] = HTMLHelper::_('select.option', $agency->id, $agency->title);
			}
		}

		return $agencyFilter;
	}
}
