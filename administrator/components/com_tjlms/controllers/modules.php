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

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Modules list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerModules extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   type    $name    The name of model
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return   model    A model object
	 *
	 * @since    1.6
	 */
	public function getModel($name = 'module', $prefix = 'TjlmsModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Method to get HTML of a sub format for a lesson format.
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	public function getSubFormatHTML()
	{
		$input      = JFactory::getApplication()->input;
		$format     = $input->get('lesson_format', '', 'STRING');
		$sub_format = $input->get('lesson_subformat', '', 'STRING');
		/*$mod_id     = $input->get('mod_id', '', 'INT');*/
		$lesson_id     = $input->get('lesson_id', '', 'INT');
		/*$media_id     = $input->get('media_id', '', 'INT');*/

		if ($lesson_id)
		{
			$model               = $this->getModel('modules');
			$subformat['result'] = 1;
			$subformat['html']   = $model->getallSubFormats_HTML($lesson_id, $format, $sub_format);
		}
		else
		{
			$subformat['result'] = 0;
			$subformat['html']   = '';
		}

		$result = json_encode($subformat);
		echo $result;
		jexit();
	}

	/**
	 * Method to get sub formats of a lesson format.
	 *
	 * @return  array
	 *
	 * @since   3.0
	 */
	public function getSubFormats()
	{
		$input  = JFactory::getApplication()->input;
		$format = $input->get('lesson_format', '', 'STRING');

		if ($format)
		{
			$model               = $this->getModel('modules');
			$subformat['result'] = 1;
			$subformat['html']   = $model->getallSubFormats($format);
		}
		else
		{
			$subformat['result'] = 0;
			$subformat['html']   = '';
		}

		$result = json_encode($subformat);
		echo $result;
		jexit();
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$input = JFactory::getApplication()->input;
		$pks   = $input->post->get('cid', array(), 'array');
		$order = $input->post->get('order', array(), 'array');

		// Sanitize the input
		JArrayHelper::toInteger($pks);
		JArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		JFactory::getApplication()->close();
	}

	/**
	 * Save ordering. Use when sorting is done using drap and drop
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function saveSortingForModules()
	{
		$input    = JFactory::getApplication()->input;
		$courseId = $input->get('course_id', 0, 'INT');
		$model    = $this->getModel('modules');

		// Get the order of all the modules present in that course.
		$data = $model->getModuleOrderList($courseId);

		foreach ($_POST as $key => $value)
		{
			if ($key != 'option')
			{
				$key_order   = explode('_', $key);
				$key_order   = $key_order[1];

				// Save current ordering in a variable.
				$newRank     = $value;

				// Order already saved in DB
				$currentRank = $data[$key_order];

				// If the order are not same then change the order according to new orders
				if ($currentRank != $newRank)
				{
					$model->switchOrder($key_order, $newRank, $courseId);
				}
			}
		}
	}

	/**
	 * Function used to delet the module of a particular course
	 *
	 * @return true/false
	 *
	 * @since  1.0.0
	 **/
	public function deleteModule()
	{
		$input    = JFactory::getApplication()->input;
		$courseId = $input->get('course_id', 0, 'INT');
		$moduleId = $input->get('mod_id', 0, 'INT');
		$model    = $this->getModel('modules');
		$deletMod = $model->deleteModule($courseId, $moduleId);
		$deletMod = json_encode($deletMod);
		echo $deletMod;
		jexit();
	}

	/**
	 * Function used to chage the state of the module
	 *
	 * @return true/false
	 *
	 * @since  1.0.0
	 **/
	public function changeState()
	{
		$input      = JFactory::getApplication()->input;
		$moduleId   = $input->get('mod_id', 0, 'INT');
		$state      = $input->get('state', 0, 'INT');
		$model      = $this->getModel('modules');
		$state_flag = $model->changeState($moduleId, $state);
		$state_flag = json_encode($state_flag);
		echo $state_flag;
		jexit();
	}
}
