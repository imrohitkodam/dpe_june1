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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;


/**
 * Lesson controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerAssessment extends FormController
{
	/**
	 * function to Save Assessments Data
	 *
	 * @return JSON
	 *
	 * @since 1.0.0
	 * */
	public function submit()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$data = Factory::getApplication()->input->post;
		$newdata = array();
		$newdata['ltId'] = $data->get('ltId', '', 'INT');
		$newdata['reviewId'] = $data->get('reviewId', '', 'INT');
		$newdata['gradingtype'] = $data->get('gradingtype', '', 'STRING');
		$newdata['reviewerMarks'] = $data->get('marks', array(), 'ARRAY');
		$newdata['marks'] = $data->get('marks', 0, 'INT');
		$newdata['reviewStatus'] = $data->get('review_status', 0, 'INT');
		$newdata['assessmentParams'] = $data->get('assessmentParams', array(), 'ARRAY');
		$newdata['feedback'] = $data->get('feedback', 0, 'STRING');

		try
		{
			require_once JPATH_SITE . '/components/com_tjlms/models/assessment.php';
			$model = $this->getModel('Assessment', 'TjlmsModel');
			$result = $model->save($newdata);

			/*$model = $this->getModel('Test', 'TmtModel');
			$model->save($data);*/
			$errrors = $model->getErrors();

			if (empty($errrors))
			{
				if ($newdata['reviewStatus'] == 1)
				{
					$success = Text::_('COM_TJLMS_ASSESSMENTS_SAVED_SUCCESSFULLY');
				}
				else
				{
					$success = Text::_('COM_TJLMS_ASSESSMENTS_DRAFTED_SUCCESSFULLY');
				}

				echo new JsonResponse($result, $success);
			}
			else
			{
				$this->processErrors($errrors);
				echo new JResponseJson(0, Text::_('COM_TJLMS_ASSESSMENTS_SAVE_ERROR'), true);
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Method to procees errors
	 *
	 * @param   ARRAY  $errors  ERRORS
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function processErrors($errors)
	{
		$app = Factory::getApplication();

		if (!empty($errors))
		{
			$code = 500;
			$msg  = array();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$code  = $errors[$i]->getCode();
					$msg[] = $errors[$i]->getMessage();
				}
				else
				{
					$msg[] = $errors[$i];
				}
			}

			$app->enqueueMessage(implode("\n", $msg), 'error');
		}
	}
}
