<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;

jimport('joomla.event.dispatcher');

use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\ItemModel;
/**
 * Timelog model.
 *
 * @since  1.0.0
 */
class TimelogModelActivity extends ItemModel
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return void
	 *
	 * @since    1.0.0
	 *
	 */
	protected function populateState()
	{
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		// Check published state
		if ((!$user->authorise('core.edit.state', 'com_timelog')) && (!$user->authorise('core.edit', 'com_timelog')))
		{
			$this->setState('filter.published', 1);
			$this->setState('filter.archived', 2);
		}

		// Load state from the request userState on edit or from the passed variable on default
		if ($app->input->get('layout') == 'edit')
		{
			$id = $app->getUserState('com_timelog.edit.activity.id');
		}
		else
		{
			$id = $app->input->get('id');
			$app->setUserState('com_timelog.edit.activity.id', $id);
		}

		$this->setState('activity.id', $id);

		// Load the parameters.
		$app  = Factory::getApplication('com_timelog');

		$params       = $app->getParams();
		$params_array = $params->toArray();

		if (isset($params_array['item_id']))
		{
			$this->setState('activity.id', $params_array['item_id']);
		}

		$this->setState('params', $params);
	}

	/**
	 * Method to get an object.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @throws Exception
	 */
	public function getItem($id = null)
	{
		if ($this->_item === null)
		{
			$this->_item = false;

			if (empty($id))
			{
				$id = $this->getState('activity.id');
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
						throw new Exception(Text::_('COM_TIMELOG_ITEM_NOT_LOADED'), 403);
					}
				}

				// Convert the JTable to a clean JObject.
				$properties  = $table->getProperties(1);
				$this->_item = ArrayHelper::toObject($properties, 'JObject');
			}
		}

		if (!empty($this->_item->activity_type_id))
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_timelog/models');
			$activityTypeModel = BaseDatabaseModel::getInstance('Activitytype', 'TimelogModel', array('ignore_request' => true));
			$activityTypes = $activityTypeModel->getItem($this->_item->activity_type_id);
			$this->_item->activity_type_id = $activityTypes->title;
		}

		if (!empty($this->_item->client_id))
		{
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jlike/models');
			$jlikeTodoModel = BaseDatabaseModel::getInstance('Todo', 'JLikeModel', array('ignore_request' => true));
			$jlikeTodo = $jlikeTodoModel->getContent($this->_item->client_id);
			$this->_item->client_id = $jlikeTodo->content_title;
		}

		if (isset($this->_item->created_by))
		{
			$this->_item->created_by_name = Factory::getUser($this->_item->created_by)->name;
		}

		if (isset($this->_item->modified_by))
		{
			$this->_item->modified_by_name = Factory::getUser($this->_item->modified_by)->name;
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
	public function getTable($type = 'Activity', $prefix = 'TimelogTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_timelog/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Function to get activity cluster
	 *
	 * @param   int  $recordId  record id.
	 * @param   int  $mediaId   media id.
	 *
	 * @return  object.
	 */
	public function getActivityCluster($recordId, $mediaId)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('c.*');
		$query->from($db->quoteName('#__tj_media_files_xref', 'a'));
		$query->join('LEFT', $db->quoteName('#__timelog_activities', 'b') . ' ON ' . $db->quoteName('a.client_id') . ' = ' . $db->quoteName('b.id'));
		$query->join('LEFT', $db->quoteName('#__tj_sla_activities', 'c') . ' ON ' . $db->quoteName('b.client_id') . ' = ' . $db->quoteName('c.id'));
		$query->where($db->quoteName('a.client_id') . ' = ' . $db->quote($recordId));
		$query->where($db->quoteName('a.id') . ' = ' . $db->quote($mediaId));
		$db->setQuery($query);

		return  $db->loadObject();
	}
}
