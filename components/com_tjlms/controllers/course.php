<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');
use Joomla\CMS\Factory;

/**
 * Tjmodules list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerCourse extends TjlmsController
{
	/**
	 * function to get course filters result
	 *
	 * @return redirect
	 *
	 * @since  1.0.0
	 */
	public function setCategoryForCourses()
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$course_cat = $input->get('course_cat', '0', 'INT');
		$app->setUserState('com_tjlms' . '.filter.category_filter', $course_cat);
		$comtjlmsHelper = new ComtjlmsHelper;
		$link = $comtjlmsHelper->tjlmsroute('index.php?option=com_tjlms&view=courses', false);
		$app->redirect($link);
	}

	/**
	 * function to get course filters result
	 *
	 * @return redirect
	 *
	 * @since  1.0.0
	 */
	public function userEnrollAction()
	{
		$app = Factory::getApplication();
		$input = $app->input;
		$comtjlmsHelper = new ComtjlmsHelper;
		$courseId = $input->get('cId', '0', 'INT');
		$userId = Factory::getUser()->id;
		$userEnrollment = TjLms::Enrollment($userId, $courseId);

		$courseLink = 'index.php?option=com_tjlms&view=course&id=' . $courseId;
		$itemId = $comtjlmsHelper->getitemid($courseLink);
		$rUrl = $courseLink . '&Itemid=' . $itemId;

		if ($userEnrollment->id && !$userEnrollment->expired)
		{
			$app->redirect($comtjlmsHelper->tjlmsRoute($rUrl, false));
		}

		require_once JPATH_SITE . "/components/com_tjlms/controllers/enrolment.php";
		$controller = new TjlmsControllerEnrolment;

		$controller->userEnrollment(array($courseId), array($userId), $comtjlmsHelper->tjlmsRoute($courseLink, false));
	}
}
