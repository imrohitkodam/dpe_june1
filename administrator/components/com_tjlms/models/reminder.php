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
				$query = $this->_db->getQuery(true);
				$query->select('MAX(ordering)');
				$query->from($this->_db->qn('#__tjlms_reminders'));

				$this->_db->setQuery($query);

				$max             = $this->_db->loadResult();
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
					if (!$this->_db->insertObject('#__tjlms_reminders_xref', $obj, 'id'))
					{
						echo $this->_db->stderr();

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
					$query = $this->_db->getQuery(true);

					// Delete courses which are not selected and they are in xref
					$query->delete($this->_db->qn('#__tjlms_reminders_xref'));
					$query->where($this->_db->qn('course_id') . ' = ' . (int) $course_id);
					$query->where($this->_db->qn('reminder_id') . ' = ' . (int) $pk);
					$this->_db->setQuery($query);
					$result = $this->_db->execute();
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
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn('id'));
			$query->from($this->_db->qn('#__tjlms_reminders_xref', 'c'));
			$query->where($this->_db->qn('course_id') . ' = ' . (int) $course_id);
			$query->where($this->_db->qn('reminder_id') . ' = ' . (int) $reminder_id);
			$this->_db->setQuery($query);

			return $this->_db->loadResult();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
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
		$query = $this->_db->getQuery(true);
		$query->select($this->_db->qn('course_id'));
		$query->from($this->_db->qn('#__tjlms_reminders_xref', 'c'));
		$query->where($this->_db->qn('reminder_id') . ' = ' . (int) $reminder_id);
		$this->_db->setQuery($query);

		return $this->_db->loadColumn();
	}

	/**
	 * Get an reminders_xref_id
	 *
	 * @return Courses Array.
	 */

	public function getAllCourses()
	{
		$query = $this->_db->getQuery(true);
		$query->select('Distinct course_id');
		$query->from($this->_db->qn('#__tjlms_reminders_xref', 'c'));
		$query->where('1');
		$this->_db->setQuery($query);

		return $this->_db->loadColumn();
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
		try
		{
			if (isset($course_id))
			{
				$query = $this->_db->getQuery(true);

				$query->select($this->_db->qn('d.due_date'));
				$query->from($this->_db->qn('#__tjlms_reminders_xref', 'a'));
				$query->join('INNER', $this->_db->qn('#__tjlms_courses', 'b') . ' ON (' . $this->_db->qn('a.course_id') . ' = ' . $this->_db->qn('b.id') . ')');
				$query->join('INNER', $this->_db->qn('#__jlike_content', 'c') . ' ON (' . $this->_db->qn('b.id') . ' = ' . $this->_db->qn('c.element_id') . ')');
				$query->join('INNER', $this->_db->qn('#__jlike_todos', 'd') . ' ON (' . $this->_db->qn('d.content_id') . ' = ' . $this->_db->qn('c.id') . ')');
				$query->where($this->_db->qn('c.element') . ' LIKE ' . $this->_db->q('com_tjlms.course'));
				$query->where($this->_db->qn('b.state') . ' = 1 and ' . $this->_db->qn('a.course_id') . ' = ' . (int) $course_id);

				$this->_db->setQuery($query);

				return $this->_db->loadResult();
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
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
		try
		{
			if (isset($course_id))
			{
				$query = $this->_db->getQuery(true);
				$query->select($this->_db->qn(array('a.id','days','subject','email_template')));
				$query->from($this->_db->qn('#__tjlms_reminders', 'a'));
				$query->join(
				'INNER', $this->_db->qn('#__tjlms_reminders_xref', 'b') . '
				ON (' . $this->_db->qn('a.id') . ' = ' . $this->_db->qn('b.reminder_id') . ')');
				$query->where($this->_db->qn('course_id') . ' = ' . (int) $course_id);
				$query->where($this->_db->qn('state') . ' = 1');
				$this->_db->setQuery($query);

				return $this->_db->loadObjectList();
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * Get all Users assigned to the courses
	 *
	 * @param   INT  $course_id            course_id ID
	 * @param   INT  $reminder_batch_size  reminders batch size
	 *
	 * @return all Users Array.
	 */
	public function getAllUsers($course_id, $reminder_batch_size = 1)
	{
		if (isset($course_id))
		{
			$query = $this->_db->getQuery(true);

			$query->select($this->_db->qn('c.assigned_to'));
			$query->from($this->_db->qn('#__tjlms_courses', 'a'));
			$query->join('INNER', $this->_db->qn('#__jlike_content', 'b') . ' ON (' . $this->_db->qn('a.id') . ' = ' . $this->_db->qn('b.element_id') . ')');
			$query->join('INNER', $this->_db->qn('#__jlike_todos', 'c') . ' ON (' . $this->_db->qn('b.id') . ' = ' . $this->_db->qn('c.content_id') . ')');
			$query->where($this->_db->qn('b.element') . ' LIKE ' . $this->_db->q('com_tjlms.course'));
			$query->where($this->_db->qn('a.state') . ' = 1');
			$query->where($this->_db->qn('a.id') . ' = ' . $course_id);

			$query->setLimit($reminder_batch_size);
			$this->_db->setQuery($query);

			return $this->_db->loadColumn();
		}
	}
}
