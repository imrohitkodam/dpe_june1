<?php
/**
 * @package    Sla
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.

defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;

use Joomla\CMS\Factory;
use \Joomla\CMS\MVC\Model\AdminModel;

/**
 * Item Model for an Sla activity.
 *
 * @since  1.0.0
 */
class SlaModelSlaActivity extends AdminModel
{
	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_sla.slaactivity', 'slaactivity', array('control' => 'jform', 'load_data' => $loadData));

		return empty($form) ? false : $form;
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 */
	public function getTable($type = 'SlaActivities', $prefix = 'SlaTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sla/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	$data  The data for the form.
	 *
	 * @since	1.0.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_sla.edit.slaactivity.data', array());

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
		$pk   = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('slaactivity.id');
		$slaActivity = SlaSlaActivity::getInstance($pk);

		// Bind the data.
		if (!$slaActivity->bind($data))
		{
			$this->setError($slaActivity->getError());

			return false;
		}

		$result = $slaActivity->save();

		// Store the data.
		if (!$result)
		{
			$this->setError($slaActivity->getError());

			return false;
		}

		$this->setState('slaactivity.id', $slaActivity->id);

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
		$this->setState('slaactivity.id', $id);
	}
}
