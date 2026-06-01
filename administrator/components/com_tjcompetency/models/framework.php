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
use Joomla\String\StringHelper;
use \Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

/**
 * Model for Competency framework.
 *
 * @since  1.0.0
 */
class TjCompetencyModelFramework extends AdminModel
{
	/**
	 * Method to get a framework.
	 *
	 * @param   integer  $pk  An optional id of the object to get, otherwise the id from the model state is used.
	 *
	 * @return  mixed    Framework data object on success, false on failure.
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
		$form = $this->loadForm('com_tjcompetency.framework', 'framework', array('control' => 'jform', 'load_data' => $loadData));

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
	public function getTable($type = 'Frameworks', $prefix = 'TjCompetencyTable', $config = array())
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
		$data = Factory::getApplication()->getUserState('com_tjcompetency.edit.framework.data', array());

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
		$pk   = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('framework.id');
		$framework = TjCompetency::Framework($pk);

		if (isset($data['params']) && is_array($data['params']))
		{
			$registry = new JRegistry;
			$registry->loadArray($data['params']);
			$data['params'] = (string) $registry;
		}

		$input = Factory::getApplication()->input;

		// Alter the uniue code
		if ($input->get('task') == 'save2copy')
		{
			$origTable = clone $this->getTable();
			$origTable->load($input->getInt('id'));

			if ($data['unique_code'] == $origTable->unique_code)
			{
				$data['unique_code'] = $this->generateNewUniqueCode($data['unique_code']);
			}

			$data['state'] = 0;
		}

		// Bind the data.
		if (!$framework->bind($data))
		{
			$this->setError($framework->getError());

			return false;
		}

		$result = $framework->save();

		// Store the data.
		if (!$result)
		{
			$this->setError($framework->getError());

			return false;
		}

		$this->setState('framework.id', $framework->id);

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
		$this->setState('framework.id', $id);
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
		$return = parent::validate($form, $data);

		return $return;
	}

	/**
	 * Method to unique code.
	 *
	 * @param   string   $alias     The Unique code.
	 *
	 * @return  array    Contains the modified Unique code.
	 *
	 * @since   1.7
	 */
	protected function generateNewUniqueCode($uniqueCode)
	{
		// Alter the title & alias
		$table = $this->getTable();

		while ($table->load(array('unique_code' => $uniqueCode)))
		{
			$uniqueCode = StringHelper::increment($uniqueCode, 'dash');
		}

		return $uniqueCode;
	}
}
