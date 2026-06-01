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
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

jimport('joomla.application.component.modeladmin');

/**
 * Tjlms model.
 *
 * @since  1.6
 */
class TjlmsModelCoupon extends AdminModel
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 *
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
	public function getTable($type = 'Coupon', $prefix = 'TjlmsTable', $config = array())
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
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Get the form.
		$form = $this->loadForm('com_tjlms.coupon', 'coupon', array('control' => 'jform', 'load_data' => $loadData));

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
		$data = Factory::getApplication()->getUserState('com_tjlms.edit.coupon.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		if ($data->course_id)
		{
			$data->course_id = explode(',', $data->course_id);
		}

		if (isset($data->value))
		{
			$data->value = (int) $data->value;
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
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
	 * @param   integer  $table  table info.
	 *
	 * @return	mixed	Object on success, false on failure.
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
					$query->select('MAX(ordering)');
					$query->from($this->_db->qn('#__tjlms_coupons'));
					$this->_db->setquery($query);

					$max = $this->_db->loadResult();
					$table->ordering = $max + 1;

					// $this->_db->setQuery('SELECT MAX(ordering) FROM #__tjlms_coupons');
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
	 * Validate the code for the coupns
	 *
	 * @param   string  $code  coupon code.
	 *
	 * @return	value	1 or 0;
	 *
	 * @since	1.6
	 */
	public function validatecode($code)
	{
		try
		{
			$value = 0;

			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn('id'));
			$query->from($this->_db->qn('#__tjlms_coupons'));
			$query->where($this->_db->qn('code') . ' LIKE ' . $this->_db->q('%' . $code . '%'));
			$this->_db->setQuery($query);
			$result = $this->_db->loadobject();

			if (!empty($result))
			{
				$value = 1;
			}

			return $value;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}
}
