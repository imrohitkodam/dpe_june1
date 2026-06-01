<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

require_once JPATH_COMPONENT . '/controller.php';

/**
 * Answersheet controller class.
 *
 * @since  1.0.0
 */
class TmtControllerAnswersheet extends TmtController
{
	/**
	 * To get data of each page
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function getStepPageData()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$data = Factory::getApplication()->input->post;

		// Get encoded redirect url
		$rUrl = $data->get('rUrl', '', 'STRING');
		$this->setRedirect(Route::_(base64_decode($rUrl), false));
	}

	/**
	 * To return back to reviewed papers
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function backToReviewedpapers()
	{
		$app               = Factory::getApplication();
		$id                = $app->input->get('id');
		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid            = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=reviewpaper');
		$redirect          = Route::_('index.php?option=com_tmt&view=reviewpaper&test_id=' . $id . '&Itemid=' . $itemid, false);
		$this->setRedirect($redirect, '');
	}

	/**
	 * To return back to candidate history
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function backToCandihistory()
	{
		$app               = Factory::getApplication();
		$id                = $app->input->get('candid_id');
		$tmtFrontendHelper = new tmtFrontendHelper;
		$itemid            = $tmtFrontendHelper->getItemId('index.php?option=com_tmt&view=candidateshistory');
		$redirect          = Route::_('index.php?option=com_tmt&view=candidateshistory&user_id=' . $id . '&Itemid=' . $itemid, false);
		$this->setRedirect($redirect, '');
	}

	/**
	 * To submit review and update lesson and test data
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function submitreview()
	{
		$app            = Factory::getApplication();
		$id             = $app->input->get('candid_id');
		$data           = $app->input->post;
		$course_id      = $data->get('course_id', '', 'INT');
		$marks          = $data->get('marks', '', 'ARRAY');
		$qztype         = $data->get('gradingtype', '', 'STRING');
		$invId          = $data->get('invite_id', '', 'INT');
		$user_id        = $data->get('candid_id', '', 'INT');
		$from_course    = $data->get('from_course', '', 'INT');
		$model          = $this->getModel('answersheet', 'TmtModel');
		$tjlmshelperObj = new comtjlmsHelper;

		if ($qztype == 'quiz')
		{
			$attendStep = $model->update_lesson_test_quiz($data, $isfinal = 1);
		}

		if ($qztype == 'exercise')
		{
			$attendStep = $model->update_lesson_test_exercise($data, $isfinal = 1);
		}

		$redirect = $tjlmshelperObj->tjlmsRoute('index.php?option=com_tjlms&view=assessments', false);

		$this->setRedirect($redirect, '');
	}

	/**
	 * To save data as a draft while reviewing
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */

	public function savereview()
	{
		$app         = Factory::getApplication();
		$id          = $app->input->get('candid_id');
		$data        = $app->input->post;
		$marks       = $data->get('marks', '', 'ARRAY');
		$invId       = $data->get('invite_id', '', 'INT');
		$user_id     = $data->get('candid_id', '', 'INT');
		$test_id     = $data->get('id', '', 'INT');
		$course_id   = $data->get('course_id', '', 'INT');
		$qztype      = $data->get('gradingtype', '', 'STRING');
		$model       = $this->getModel('answersheet', 'TmtModel');
		$from_course = $data->get('from_course', '', 'INT');

		$tjlmshelperObj = new comtjlmsHelper;

		if ($qztype == 'quiz')
		{
			$attendStep = $model->update_lesson_test_quiz($data, $isfinal = 0);
		}

		if ($qztype == 'exercise')
		{
			$attendStep = $model->update_lesson_test_exercise($data, $isfinal = 0);
		}

		$redirect = $tjlmshelperObj->tjlmsRoute('index.php?option=com_tjlms&view=assessments', false);

		$this->setRedirect($redirect, '');
	}
}
