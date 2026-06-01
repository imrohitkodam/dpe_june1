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
	 * Publish the element
	 *
	 * @param   int  &$id    Item id
	 * @param   int  $state  Publish state
	 *
	 * @return  boolean
	 */
	public function publishes(&$id, $state)
	{
		$table = $this->getTable();
		$data = new stdClass;
		$data->load((int) $id);
		$data->state = $state;

		return $table->save($data);
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
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  mixed  The user id on success, false on failure.
	 *
	 * @since  1.0
	 */
	public function save($data)
	{
		if (!empty($data['course_id']))
		{
			$courseId = (count($data['course_id']) > 1) ? implode(',', $data['course_id']) :$data['course_id'][0];
		}

		$data['course_id'] = trim($courseId, ",");

		$table = $this->getTable();

		if ($table->save($data) == true)
		{
			return $table->id;
		}
		else
		{
			return false;
		}
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

		if (empty($table->id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering === '')
			{
				$db = Factory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__tjlms_coupons');
				$max = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
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
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->qn('id'));
			$query->from($db->qn('#__tjlms_coupons'));
			$query->where($db->qn('code') . ' LIKE ' . $db->quote('%' . $code . '%'));
			$db->setQuery($query);
			$result = $db->loadobject();

			if (!empty($result))
			{
				return 1;
			}

			return 0;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}
}
