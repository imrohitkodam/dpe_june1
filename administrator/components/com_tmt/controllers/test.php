<?php
/**
 * @package    Com_Tmt
 * @copyright  Copyright (C) 2009 -2015 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

/**
 * Test controller class.
 *
 * @since  1.0
 */
class TmtControllerTest extends FormController
{
	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->view_list = 'tests';
		parent::__construct();
	}

	/**
	 * Method to save posted item data and redirect to the edit form.
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function apply()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app = Factory::getApplication();
		$model = $this->getModel('Test', 'TmtModel');

		// Get all form data
		$data = $app->input->post;

		// *Important - get non-jform data for all non-jform fields
		$cid = $data->get('cid', '', 'array');
		$reviewers_hidden = $data->get('reviewers_hidden', '', 'array');

		// Get all jform data
		$data = $app->input->get('jform', array(), 'array');

		// * Important when checkboxes are unchecked.
		if (!isset($data['notify_candidate_passed']))
		{
			$data['notify_candidate_passed'] = 0;
		}

		if (!isset($data['notify_candidate_failed']))
		{
			$data['notify_candidate_failed'] = 0;
		}

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{
			$app->enqueueMessage($model->getError(), 'warning');
			$app->setHeader('status', 500, true);

			return false;
		}

		// Validate the posted data.
		$data = $model->validate($form, $data);

		// *Important - pass on non-jform data to model
		$data['cid'] = $cid;
		$data['reviewers_hidden'] = $reviewers_hidden;

		// Attempt to save the data.
		$test_id = $return = $model->save($data);

		$return = $model->storeRules($test_id, $data);

		// Check for errors.
		if ($return === false)
		{
			// Save the data in the session.
			$app->setUserState('com_tmt.edit.test.data', $data);

			// Redirect back to the edit screen.
			$id = (int) $app->getUserState('com_tmt.edit.test.id');
			$this->setMessage(Text::sprintf('Save failed', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_tmt&view=test&id=' . $id, false));

			return false;
		}

		// Check in the profile.
		if ($return)
		{
			$model->checkin($return);
		}

		// Clear the profile id from the session.
		$app->setUserState('com_tmt.edit.test.id', null);

		// Redirect to the edit screen.
		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=test');
		$redirect = Route::_('index.php?option=com_tmt&view=test&id=' . $return . '&Itemid=' . $itemid, false);
		$msg = Text::_('COM_TMT_MESSAGE_SAVE_TEST');
		$this->setRedirect($redirect, $msg);

		// Flush the data from the session.
		$app->setUserState('com_tmt.edit.test.data', null);
	}

	/**
	 * Method to save posted item data and redirect tests list
	 *
	 * @param   integer  $key     key
	 * @param   integer  $urlVar  url var
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app = Factory::getApplication();
		$model = $this->getModel('Test', 'TmtModel');

		// Get all form data
		$rules_data = $data = Factory::getApplication()->input->post;

		// *Important - get non-jform data for all non-jform fields
		$rule_id = $data->get('rule_id', '', 'array');
		$cid = $data->get('cid', '', 'array');
		$reviewers_hidden = $data->get('reviewers_hidden', '', 'array');
		$course_id = $data->get('course_id', '', 'INT');
		$mod_id = $data->get('mod_id', '', 'INT');
		$unique = $data->get('unique', '', 'INT');
		$addquiz = Factory::getApplication()->input->get('addquiz', '', 'INT');
		$qztype = Factory::getApplication()->input->get('qztype', '', 'STRING');

		// Get all jform data
		$data = Factory::getApplication()->input->get('jform', array(), 'array');

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{
			$app->enqueueMessage($model->getError(), 'warning');
			$app->setHeader('status', 500, true);

			return false;
		}

		// Validate the posted data.
		$data = $model->validate($form, $data);

		// *Important - pass on non-jform data to model
		$data['rule_id'] = $rule_id;
		$data['cid'] = $cid;
		$data['reviewers_hidden'] = $reviewers_hidden;
		$data['addquiz'] = $addquiz;
		$data['course_id'] = $course_id;
		$data['mod_id'] = $mod_id;
		$data['unique'] = $unique;

		// Attempt to save the data.
		$return = $model->save($data);

		// Check for errors.
		if ($return === false)
		{
			// Checking if called from LMS
			if (!empty($data['addquiz']))
			{
				$ret['OUTPUT'][0] = 1;
				$ret['OUTPUT'][1] = $return;
				$ret['OUTPUT'][2] = 'something went wrong while saving data into table';
				echo json_encode($ret);

				jexit();

				// $itemid=$tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=tests');
				// $redirect=JRoute::_('index.php?option=com_tjlms&view=modules&course_id='.$data['course_id'].'&mod_id='.$data['mod_id'],false);
			}
			else
			{
				// Save the data in the session.
				$app->setUserState('com_tmt.edit.test.data', $data);

				// Redirect back to the edit screen.
				$id = (int) $app->getUserState('com_tmt.edit.test.id');
				$this->setMessage(Text::sprintf('Save failed', $model->getError()), 'warning');
				$this->setRedirect(Route::_('index.php?option=com_tmt&view=test&id=' . $id, false));

				return false;
			}
		}

		// Check in the profile.
		if ($return)
		{
			// To store the rules into database $return is the test_id added by raviraj

			$return = $model->storeRules($return, $rules_data);
			$model->checkin($return);
		}

		// Clear the profile id from the session.
		$app->setUserState('com_tmt.edit.test.id', null);

		// Redirect to the list screen.
		$tmtFrontendHelper = new tmtFrontendHelper;

		// Durgesh Added for LMS
		$s_msg = Text::_('COM_TMT_QUIZ_SAVED_MSG');

		// Checking if called from LMS
		if (!empty($data['addquiz']))
		{
			$ret['OUTPUT'][0] = 1;
			$ret['OUTPUT'][1] = $return;
			$ret['OUTPUT'][2] = $s_msg;
			echo json_encode($ret);

			jexit();

			// $itemid=$tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=tests');
			// $redirect=JRoute::_('index.php?option=com_tjlms&view=modules&course_id='.$data['course_id'].'&mod_id='.$data['mod_id'],false);
		}
		else
		{
			$itemid = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=tests');
			$redirect = Route::_('index.php?option=com_tmt&view=tests&Itemid=' . $itemid, false);
		}

		$msg = Text::_('COM_TMT_MESSAGE_SAVE_TEST');
		$this->setRedirect($redirect, $msg);

		// Flush the data from the session.
		$app->setUserState('com_tmt.edit.test.data', null);
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key
	 *                           (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if access level check and checkout passes, false otherwise.
	 *
	 * @since   1.6
	 */
	public function edit($key = null, $urlVar = null)
	{
		$input = Factory::getApplication()->input;
		$cid = Factory::getApplication()->input->get('cid', '', 'INT');
		$mid = Factory::getApplication()->input->get('mid', '', 'INT');
		$gradingtype = Factory::getApplication()->input->get('gradingtype', '', 'WORD');

		$this->redirect = "index.php?option=com_tmt&view=test&layout=edit";
		$this->setRedirect(
				$this->redirect . "&gradingtype=" . $gradingtype . "&cid=" . $cid . "&mid=" . $mid
			);

		return true;
	}

	/**
	 * Method for redirecting to the list view
	 *
	 * @param   integer  $key  key
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	/*public function cancel($key = null)
	{
		$tmtFrontendHelper = new tmtFrontendHelper;
		$addquiz = Factory::getApplication()->input->get('addquiz', '', 'INT');

		if (!empty($addquiz))
		{
			$postdata = Factory::getApplication()->input->post;
			$data['course_id'] = $postdata->get('course_id', '', 'INT');
			$data['mod_id'] = $postdata->get('mod_id', '', 'INT');
			$addquiz = Factory::getApplication()->input->get('addquiz', '', 'INT');

			$redirect = Route::_('index.php?option=com_tjlms&view=modules&course_id=' . $data['course_id'] . '&mod_id=' . $data['mod_id'], false);
		}
		else
		{
			$itemid = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=tests');
			$redirect = Route::_('index.php?option=com_tmt&view=tests&Itemid=' . $itemid, false);
		}

		$msg = Text::_('COM_TMT_MESSAGE_CANCEL');
		$this->setRedirect($redirect, $msg);
	}*/

	/**
	 * Method to get questions based on rules posted using ajax
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function fetchQuestions()
	{
		$data = Factory::getApplication()->input->post;
		$model = $this->getModel('Test', 'TmtModel');
		$fetchQuestions = $model->fetchQuestions($data);

		// Output the response as JSON
		header('Content-type: application/json');
		echo json_encode($fetchQuestions);
		jexit();
	}

	/**
	 * Method to get a multiplication factor from the questions cnt provided.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since 1.0
	 */
	public function getMultiplicationFactor()
	{
		$data = Factory::getApplication()->input->post;

		$postdata = Factory::getApplication()->input->post;
		$question_count = $postdata->get('question_count', '', 'INT');

		$model = $this->getModel('Test', 'TmtModel');
		$res = $model->getMultiplicationFactor($question_count);

		// Output the response as JSON
		header('Content-type: application/json');
		echo json_encode($res);
		jexit();
	}

	/**
	 * Method to get updateTestQuestions
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function updateTestQuestions()
	{
		$input    = Factory::getApplication()->input;
		$section_id = $input->get('section_id', 0, 'INT');
		$question_id = $input->get('question_id', 0, 'INT');
		$is_compulsory = $input->get('required_value', 1, 'INT');
		$model    = $this->getModel('test');
		$updateTestQuestions = $model->updateTestQuestions($question_id, $section_id, $is_compulsory);
		$updateTestQuestions = json_encode($updateTestQuestions);
		echo $updateTestQuestions;
		jexit();
	}

	/**
	 * Save ordering. Use when sorting is done using drap and drop
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function saveSortingForSections()
	{
		$input    = Factory::getApplication()->input;
		$testId = $input->get('test_id', 0, 'INT');
		$model    = $this->getModel('test');

		// Get the order of all the modules present in that course.
		$data = $model->getSectionsOrderList($testId);
		$orderSorting = current(array_filter(unserialize($input->post->serialize())));

		foreach ($orderSorting as $key => $value)
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
					$model->switchOrder($key_order, $newRank, $testId);
				}
			}
		}

		echo 1;
		jexit();
	}

	/**
	 * Method to get information of is quiz quiz attempted already
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */
	public function deleteTestQuestion()
	{
		$input    = Factory::getApplication()->input;
		$section_id = $input->get('section_id', 0, 'INT');
		$question_id = $input->get('question_id', 0, 'INT');
		$model    = $this->getModel('test');
		$deletQuestions = $model->deleteTestQuestion($question_id, $section_id);
		$deletQuestions = json_encode($deletQuestions);
		echo $deletQuestions;
		jexit();
	}

	/**
	 * Method to get information of is quiz quiz attempted already
	 *
	 * @return  void
	 *
	 * @since 1.0
	 */

	public function isInUse()
	{
		$data = Factory::getApplication()->input->post;
		$model = $this->getModel('Test', 'TmtModel');

		$isInUse = $model->isInUse($data);

		// Output the response as JSON
		header('Content-type: application/json');
		echo json_encode($isInUse);
		jexit();
	}
}
