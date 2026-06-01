<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

/**
 * Tjlms model.
 *
 * @since  1.6
 */
class TjlmsModelModuleform extends JModelAdmin
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_TJLMS';

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   type    $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return	JTable	A database object
	 *
	 * @since	1.6
	 */
	public function getTable($type = 'module', $prefix = 'TjlmsTable', $config = array())
	{
		JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

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
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = JFactory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjlms.tjmodule', 'tjmodule', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 *
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_tjlms.edit.tjmodule.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since	1.6
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
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   array  $table  post
	 *
	 * @return	boolean
	 *
	 * @since	1.6
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		try
		{
			if (empty($table->id))
			{
				// Set ordering to the last item if not set
				if (@$table->ordering === '')
				{
					$query = $this->_db->getQuery(true);

					$query->select('max(ordering) as total, l.no_of_attempts');
					$query->from($this->_db->qn('#__tjlms_modules'));

					$max = $this->_db->loadResult();
					$table->ordering = $max + 1;
				}
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return true;
	}

	/**
	 * Function used to delete the module of a course.
	 * Ordering of rest of the module is updated accordingly
	 *
	 * @param   int  $lessonId  Course id
	 * @param   int  $modId     module id
	 * @param   int  $courseId  Course id
	 *
	 * @return boolean
	 *
	 * @since  1.0
	 **/
	public function deleteLesson($lessonId, $modId, $courseId)
	{
		$query = $this->_db->getQuery(true);

		$query->select($this->_db->qn('ordering'));
		$query->from($this->_db->qn('#__tjlms_lessons'));
		$query->where($this->_db->qn('id') . ' = ' . $this->_db->q((int) $lessonId));

		$this->_db->setQuery($query);
		$currentOrder = $this->_db->loadResult();

		if (!$currentOrder)
		{
			$currentOrder = 0;
		}

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$lessonModel = JModelLegacy::getInstance('Lesson', 'TjlmsModel');

		if ($lessonModel->delete($lessonId))
		{
			// Update the order for rest of the lesson
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->qn('#__tjlms_lessons'))
					->set($this->_db->qn('ordering') . ' = `ordering`-1')
					->where($this->_db->qn('ordering') . ' > ' . $this->_db->q((int) $currentOrder))
					->where($this->_db->qn('course_id') . ' = ' . $this->_db->q((int) $courseId))
					->where($this->_db->qn('mod_id') . ' = ' . $this->_db->q((int) $modId));
			$this->_db->setQuery($query);

			$this->_db->execute();
		}

		return true;
	}

	/**
	 * Method to  save course module
	 *
	 * @param   ARRAY  $data  module data
	 *
	 * @return  mixed
	 *
	 * @since    1.3.0
	 */
	public function save($data)
	{
		$courseId = $data['course_id'];

		$canManageMaterial	= TjlmsHelper::canManageCourseMaterial($courseId);

		if ($canManageMaterial)
		{
			parent::save($data);
		}
		else
		{
			$errormsg = JText::_('COM_TJLMS_COURSE_INVALID_URL');
			$this->setError($errormsg);

			return false;
		}
	}
}
