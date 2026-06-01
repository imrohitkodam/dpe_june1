<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.controller');

/**
 * Lesson controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerAssessments extends TjlmsController
{
	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JModelLegacy  The model.
	 *
	 * @since   12.2
	 */
	public function getModel($name = 'Assessments', $prefix = 'TjlmsModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * function to Save Assessments Data
	 *
	 * @return JSON
	 *
	 * @since 1.0.0
	 * */
	public function save()
	{
		$error = $success = '';
		$app 	   		= Factory::getApplication();
		$input     		= Factory::getApplication()->input;
		$jform			= $input->get('jform', ARRAY(), 'ARRAY');

		$model  = $this->getModel();
		$result = $model->save($jform);

		if (empty($model->getErrors()))
		{
			if ($jform['status'] == 'save' || $jform['status'] == 'savenclose')
			{
				$success = Text::_('COM_TJLMS_ASSESSMENTS_SAVED_SUCCESSFULLY');
			}
			else
			{
				$success = Text::_('COM_TJLMS_ASSESSMENTS_DRAFTED_SUCCESSFULLY');
			}
		}
		else
		{
			$error = Text::_('COM_TJLMS_ASSESSMENTS_SAVE_ERROR') . "\n" . implode("\n", $model->getErrors());
		}

		$this->_outputJson($error, $success);
	}

	/**
	 * function to output json data
	 *
	 * @param   ARRAY  $error    Errors Message
	 * @param   ARRAT  $success  Success Message
	 *
	 * @return JSON
	 *
	 * @since 1.0.0
	 *
	 * */
	private function _outputJson($error = '', $success = '')
	{
		@ob_end_clean();
		header('Content-type: application/json');
		echo json_encode(array('error' => $error, 'success' => $success));
		jexit();
	}
}
