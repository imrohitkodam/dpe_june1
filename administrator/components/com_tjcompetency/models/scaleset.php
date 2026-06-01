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
 * Model for Competency scaleset.
 *
 * @since  1.0.0
 */
class TjCompetencyModelScaleset extends AdminModel
{
	/**
	 * Method to get a scaleset.
	 *
	 * @param   integer  $pk  An optional id of the object to get, otherwise the id from the model state is used.
	 *
	 * @return  mixed    Scaleset data object on success, false on failure.
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
		$form = $this->loadForm('com_tjcompetency.scaleset', 'scaleset', array('control' => 'jform', 'load_data' => $loadData));

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
	public function getTable($type = 'Scalesets', $prefix = 'TjCompetencyTable', $config = array())
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
		$data = Factory::getApplication()->getUserState('com_tjcompetency.edit.scaleset.data', array());

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
		$pk   = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('scaleset.id');
		$scaleset = TjCompetency::Scaleset($pk);

		if (isset($data['params']) && is_array($data['params']))
		{
			$registry = new JRegistry;
			$registry->loadArray($data['params']);
			$data['params'] = (string) $registry;
		}

		// Bind the data.
		if (!$scaleset->bind($data))
		{
			$this->setError($scaleset->getError());

			return false;
		}

		$result = $scaleset->save();

		// Store the data.
		if (!$result)
		{
			$this->setError($scaleset->getError());

			return false;
		}

		$this->setState('scaleset.id', $scaleset->id);

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
		$this->setState('scaleset.id', $id);
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
}
