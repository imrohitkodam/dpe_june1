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
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.view');

/**
 * View class for a list of Tjlms.
 *
 * @since  1.0.0
 */
class TjlmsViewTeacher_Report extends HtmlView
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$app  = Factory::getApplication();
		$input = $app->input;
		$course_id = $input->get('courseid', '0', 'INT');
		$this->course_id = $course_id;

		$canManageReport = TjlmsHelper::canManageCourseReport($course_id);

		if (!$this->course_id || !$canManageReport)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
            $app->setHeader('status', 403, true);

			return false;
		}

		$model = $this->getModel('teacher_report');

		$tjlmscoursehelper = new tjlmsCoursesHelper;

		$this->CourseName = $tjlmscoursehelper->courseName($course_id);

		$this->CompleteStudent = $model->coursecompletedusers($course_id);

		$this->EnrollStudent = $model->courseTotalEnrolled($course_id);

		$this->pendingenrolStudent = $model->coursePendingEnrollments($course_id);

		$this->IncompleteStudent = ($this->EnrollStudent + $this->pendingenrolStudent) - $this->CompleteStudent;

		$this->TopScorer = $model->getTopScorer($course_id, 10);

		$this->courseActivities = $model->getactivity($course_id);

		$this->courseInfo = $tjlmscoursehelper->getcourseInfo($course_id);

		if ($this->courseInfo->type == 1)
		{
			$this->orderReport = $model->getrevenueData($course_id);
		}

		$this->StudentwhoLiked = $model->getStudentwhoLiked($course_id);

		parent::display($tpl);
	}
}
