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
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.controlleradmin');

/**
 * Courses list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerUserreport extends AdminController
{
	/**
	 * Function used to export data in csv format
	 *
	 * @return  jexit
	 *
	 * @since   1.0.0
	 */
	public function csvexport()
	{
		$input      = Jfactory::getApplication()->input;
		$post       = $input->post;
		$com_params = ComponentHelper::getParams('com_tjlms');
		$model      = $this->getModel('userreport');
		$data       = $model->getItems();

		// Create CSV headers
		$csvData       = null;
		$csvData_arr[] = Text::_('COM_TJLMS_ENROLMENT_USER_ID');
		$csvData_arr[] = Text::_('COM_TJLMS_REPORT_USERNAME');
		$csvData_arr[] = Text::_('COM_TJLMS_REPORT_USERUSERNAME');
		$csvData_arr[] = Text::_('COM_TJLMS_REPORT_USEREMAIL');
		$csvData_arr[] = Text::_('COM_TJLMS_ENROLMENT_TOTAL_COURSES_ENROLLED');
		$csvData_arr[] = Text::_('COM_TJLMS_ENROLMENT_TOTAL_COURSES_COMPLETED');
		$csvData_arr[] = Text::_('COM_TJLMS_ENROLMENT_TOTAL_COURSES_INCOMPLETED');
		$csvData_arr[] = Text::_('COM_TJLMS_ENROLMENT_TOTAL_PENDING_ENROLLED');
		$csvData_arr[] = Text::_('COM_TJLMS_ENROLMENT_GROUP_TITLE');
		$csvData_arr[] = Text::_('COM_TJLMS_ENROLMENT_USER_BLOCKED');

		$csvData .= implode(',', $csvData_arr);
		$csvData .= "\n";
		echo $csvData;

		$csvData = '';
		$filename = "lms_user_report_" . date("Y-m-d_H-i", time());

		// Set CSV headers
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=" . $filename . ".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		foreach ($data as $lessonData)
		{
			$csvData      = '';
			$csvData_arr1 = array();

			$csvData_arr1[] = $lessonData->id;
			$csvData_arr1[] = $lessonData->name;
			$csvData_arr1[] = $lessonData->username;
			$csvData_arr1[] = $lessonData->email;
			$csvData_arr1[] = $lessonData->enrolled_courses;
			$csvData_arr1[] = $lessonData->totalCompletedCourses;
			$csvData_arr1[] = $lessonData->inCompletedCourses;
			$csvData_arr1[] = $lessonData->pending_enrollment;
			$csvData_arr1[] = $lessonData->groups;

			$csvData_arr1['user_block'] = Text::_('JYES');

			if ($lessonData->block == 1)
			{
				$csvData_arr1['user_block'] = Text::_('JNO');
			}

			// TRIGGER After csv body add extra fields
			$csvData = implode(',', $csvData_arr1);
			echo $csvData . "\n";
		}

		jexit();
	}
}
