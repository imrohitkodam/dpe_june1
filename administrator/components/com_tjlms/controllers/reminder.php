<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Tjlms
 * @author     TechJoomla <contact@techjoomla.com>
 * @copyright  Copyright (C) 2014 - 2016. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

jimport('joomla.application.component.modeladmin');

/**
 * Tjlms model.
 *
 * @since  1.6
 */
class TjlmsModelReminder extends AdminModel
{
	/**
	 * @var      string    The prefix to use with controller messages.
	 * @since    1.6
	 */
	protected $text_prefix = 'COM_TJLMS';

	/**
	 * @var   	string  	Alias to manage history control
	 * @since   3.2
	 */
	public $typeAlias = 'com_tjlms.reminder';

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
	 * @return    JTable    A database object
	 *
	 * @since    1.6
	 */
	public function getTable($type = 'Reminder', $prefix = 'TjlmsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
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
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjlms.reminder', 'reminder', array('control' => 'jform','load_data' => $loadData));

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
		$data = Factory::getApplication()->getUserState('com_tjlms.edit.reminder.data', array());

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
	 * @return   mixed    Object on success, false on failure.
	 *
	 * @since    1.6
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Do any procesing on fields here if needed
		}

		return $item;
	}

	/**
	 * Method to duplicate an Reminder
	 *
	 * @param   array  &$pks  An array of primary key IDs.
	 *
	 * @return  boolean  True if successful.
	 *
	 * @throws  Exception
	 */
	public function duplicate(&$pks)
	{
		$user = Factory::getUser();

		// Access checks.
		if (!$user->authorise('core.create', 'com_tjlms'))
		{
			throw new Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
		}

		$context    = $this->option . '.' . $this->name;

		// Include the plugins for the save events.
		PluginHelper::importPlugin($this->events_map['save']);

		$table = $this->getTable();

		foreach ($pks as $pk)
		{
			if ($table->load($pk, true))
			{
				// Reset the id to create a new record.
				$table->id = 0;

				if (!$table->check())
				{
					throw new Exception($table->getError());
				}

				// Trigger the before save event.
				$result = Factory::getApplication()->triggerEvent($this->event_before_save, array($context,&$table,true));

				if (in_array(false, $result, true) || !$table->store())
				{
					throw new Exception($table->getError());
				}

				// Trigger the after save event.
				Factory::getApplication()->triggerEvent($this->event_after_save, array($context,&$table,true));
			}
			else
			{
				throw new Exception($table->getError());
			}
		}

		// Clean cache
		$this->cleanCache();

		return true;
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

		if (empty($table->id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering === '')
			{
				$db = Factory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__tjlms_reminders');
				$max             = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

	/**
	 * Method to save form
	 *
	 * @param   Array  $data  data of the form
	 *
	 * @return noting
	 *
	 * @since   1.0
	 */
	public function save($data)
	{
		parent::save($data);

		$db   = Factory::getDBO();
		$post = Factory::getApplication()->input->post;

		$table = $this->getTable();

		$key = $table->getKeyName();

		$pk = (!empty($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');

		$select_course = $data['select_course'];

			// Save Course id and reminder id into reminder_xref table.

			foreach ($select_course as $course_id)
			{
				$obj              = new stdclass;
				$obj->id          = '';
				$obj->reminder_id = $pk;
				$obj->course_id   = $course_id;

				$XrefId = $this->getReminderId($pk, $course_id);

				if (!$XrefId)
				{
					if (!$db->insertObject('#__tjlms_reminders_xref', $obj, 'id'))
					{
						echo $db->stderr();

						return false;
					}
				}
			}

			$courses_xref = $this->getCoursesId($pk);

			$result = array_diff($courses_xref, $select_course);

		if ($result)
			{
			foreach ($result as $course_id)
			{
					$query = $db->getQuery(true);

					// Delete courses which are not selected and they are in xref
					$query->delete($db->quoteName('#__tjlms_reminders_xref'));
					$query->where('course_id = ' . $course_id . ' and reminder_id = ' . $pk);
					$db->setQuery($query);
					$result = $db->execute();
			}
		}

		if ($pk)
		{
			// Trigger to save notify
			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onAfterSaveCoursereminder', array(
														$pk,
														$data['notify_cc']
													)
								);
		}

	return true;
	}

	/**
	 * Get an reminders_xref_id
	 *
	 * @param   INT  $reminder_id  Reminder_id ID
	 *
	 * @param   INT  $course_id    Course_id ID
	 *
	 * @return reminders_xref_id.
	 */
	public function getReminderId($reminder_id, $course_id)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__tjlms_reminders_xref') . 'as c');
		$query->where('course_id = ' . $course_id . ' and reminder_id = ' . $reminder_id);
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Get an reminders_xref_id
	 *
	 * @param   INT  $reminder_id  Reminder_id ID
	 *
	 * @return Courses Array.
	 */
	public function getCoursesId($reminder_id)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('course_id');
		$query->from($db->quoteName('#__tjlms_reminders_xref') . 'as c');
		$query->where('reminder_id = ' . $reminder_id);
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Get an reminders_xref_id
	 *
	 * @return Courses Array.
	 */

	public function getAllCourses()
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('Distinct course_id');
		$query->from($db->quoteName('#__tjlms_reminders_xref') . 'as c');
		$query->where('1');
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Get Due date of course
	 *
	 * @param   INT  $course_id  course_id ID
	 *
	 * @return due_dates Array.
	 */
	public function getCoursesDuedate($course_id)
	{
		if (isset($course_id))
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select('d.due_date');
			$query->from($db->quoteName('#__tjlms_reminders_xref', 'a'));
			$query->join('INNER', $db->quoteName('#__tjlms_courses', 'b') . ' ON (' . $db->quoteName('a.course_id') . ' = ' . $db->quoteName('b.id') . ')');
			$query->join('INNER', $db->quoteName('#__jlike_content', 'c') . ' ON (' . $db->quoteName('b.id') . ' = ' . $db->quoteName('c.element_id') . ')');
			$query->join('INNER', $db->quoteName('#__jlike_todos', 'd') . ' ON (' . $db->quoteName('d.content_id') . ' = ' . $db->quoteName('c.id') . ')');
			$query->where(
			$db->quoteName('c.element') . ' LIKE ' . $db->quote('com_tjlms.course') . ' and
		' . $db->quoteName('b.state') . ' = 1 and ' . $db->quoteName('a.course_id') . ' = ' . $course_id
			);
			$query;
			$db->setQuery($query);

		return $db->loadResult();
		}

		return false;
	}

	/**
	 * Get an reminders_id,days,subject,email_template
	 *
	 * @param   INT  $course_id  course_id ID
	 *
	 * @return Courses Array.
	 */

	public function getAllReminders($course_id)
	{
		if (isset($course_id))
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('a.id,days,subject,email_template');
			$query->from($db->quoteName('#__tjlms_reminders') . 'as a');
			$query->join(
			'INNER', $db->quoteName('#__tjlms_reminders_xref', 'b') . '
			ON (' . $db->quoteName('a.id') . ' = ' . $db->quoteName('b.reminder_id') . ')');
			$query->where('course_id = ' . $course_id . ' and state = 1');
			$db->setQuery($query);

			return $db->loadObjectList();
		}

		return false;
	}

	/**
	 * Get all Users assigned to the courses
	 *
	 * @param   INT  $course_id  course_id ID
	 *
	 * @return all Users Array.
	 */
	public function getAllUsers($course_id)
	{
		if (isset($course_id))
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);

			$query->select('c.assigned_to');
			$query->from($db->quoteName('#__tjlms_courses', 'a'));
			$query->join('INNER', $db->quoteName('#__jlike_content', 'b') . ' ON (' . $db->quoteName('a.id') . ' = ' . $db->quoteName('b.element_id') . ')');
			$query->join('INNER', $db->quoteName('#__jlike_todos', 'c') . ' ON (' . $db->quoteName('b.id') . ' = ' . $db->quoteName('c.content_id') . ')');
			$query->where(
			$db->quoteName('b.element') . ' LIKE ' . $db->quote('com_tjlms.course') . ' and
		' . $db->quoteName('a.state') . ' = 1 and ' . $db->quoteName('a.id') . ' = ' . $course_id
			);
			$db->setQuery($query);

		return $db->loadColumn();
		}

		return false;
	}
}
