<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

/**
 * Model for Competency skillcontentusermap.
 *
 * @since  1.0.0
 */
class TjCompetencyModelSkillContentUserMap extends AdminModel
{
	public $notify = 1;

	/**
	 * Method to get a skillcontentusermap.
	 *
	 * @param   integer  $pk  An optional id of the object to get, otherwise the id from the model state is used.
	 *
	 * @return  mixed    SkillContentUserMap data object on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItem($pk = null)
	{
		$result = parent::getItem($pk);

		return $result;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm|boolean  A JForm object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_tjcompetency.skillcontentusermap', 'skillcontentusermap', array('control' => 'jform', 'load_data' => $loadData));

		return empty($form) ? false : $form;
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable    A database object
	 */
	public function getTable($type = 'SkillContentUserMaps', $prefix = 'TjCompetencyTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjcompetency/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	$data  The data for the form.
	 *
	 * @since	1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_tjcompetency.edit.skillcontentusermap.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function save($data)
	{
		$pk   = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('skillcontentusermap.id');
		$skillContentUserMap = TjCompetency::SkillContentUserMap($pk);

		$skillContentUserMap->notify = $this->notify;

		$previousState = $skillContentUserMap->state;

		// Bind the data.
		if (!$skillContentUserMap->bind($data))
		{
			$this->setError($skillContentUserMap->getError());

			return false;
		}

		$result = $skillContentUserMap->save();

		// Store the data.
		if (!$result)
		{
			$this->setError($skillContentUserMap->getError());

			return false;
		}

		// State changed from Pending Approval to Published
		if ($previousState == 3 && $skillContentUserMap->state == 1)
		{
			TjCompetency::Mails()->onSkillAfterAwarded($skillContentUserMap);
		}

		$this->setState('skillcontentusermap.id', $skillContentUserMap->id);

		return true;
	}

	/**
	 * Publish the element
	 *
	 * @param   array  $ids    Item id
	 *
	 * @param   array  $value  value
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function publish(&$ids, $value = 1)
	{
		$table = $this->getTable();

		foreach ($ids as $id)
		{
			$table->load($id);
			$previousState = $table->state;
			$table->state  = $value;

			if ($table->store())
			{
				// State changed from Pending Approval to Published
				if ($previousState == 3 && $table->state == 1)
				{
					TjCompetency::Mails()->onSkillAfterAwarded($table);
				}
			}
		}

		return true;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return   void
	 *
	 * @since    1.0.0
	 */

	protected function populateState()
	{
		$jinput = Factory::getApplication()->input;
		$id = ($jinput->get('id'))?$jinput->get('id'):$jinput->get('id');
		$this->setState('skillcontentusermap.id', $id);
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   JForm  $form  The form to validate against.
	 * @param   Array   $data  The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @since   12.2
	 */
	public function validate($form, $data, $group = null)
	{
		if ($data['skill_id'] == 0)
		{
			$data['skill_id'] = '';
		}

		if ($data['scale_id'] == 0)
		{
			$data['scale_id'] = '';
		}

		if ($data['user_id'] == 0)
		{
			$data['user_id'] = '';
		}

		$clientIdField = new SimpleXMLElement('<field type="text" name="client_id" />');
		$form->setField($clientIdField);

		$return = parent::validate($form, $data, $group);

		return $return;
	}

	/**
	 * Allows preprocessing of the JForm object.
	 *
	 * @param   JForm   $form   The form object
	 * @param   array   $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	protected function preprocessForm(JForm $form, $data, $group = 'tjcompetencycontenttype')
	{
		parent::preprocessForm($form, $data, $group);
	}
}
