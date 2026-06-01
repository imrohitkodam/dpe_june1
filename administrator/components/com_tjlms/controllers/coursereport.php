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
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.controlleradmin');

/**
 * Courses list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerCoursereport extends AdminController
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
		$model      = $this->getModel('coursereport');
		$data       = $model->getItems();

		// Create CSV headers
		$csvData       = null;
		$csvData_arr = array();
		$csvData_arr[] = Text::_('COM_TJLMS_COURSE_NAME');
		$csvData_arr[] = Text::_('COM_TJLMS_COURSE_CAT');
		$csvData_arr[] = Text::_('COM_TJLMS_ACL_GROUP');
		$csvData_arr[] = Text::_('COM_TJLMS_COURSE_TYPE');
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONS_CNT');
		$csvData_arr[] = Text::_('COM_TJLMS_ENROLLED_USERS_CNT');
		$csvData_arr[] = Text::_('COM_TJLMS_PENDING_ENROLLED_USERS_CNT');
		$csvData_arr[] = Text::_('COM_TJLMS_COMPLETED_USERS_CNT');
		$csvData_arr[] = Text::_('COM_TJLMS_LIKES_CNT');
		$csvData_arr[] = Text::_('COM_TJLMS_DISLIKES_CNT');
		$csvData_arr[] = Text::_('COM_TJLMS_COMMENTS_CNT');
		$csvData_arr[] = Text::_('COM_TJLMS_RECO_CNT');
		$csvData_arr[] = Text::_('COM_TJLMS_ASSIGN_CNT');
		$csvData_arr[] = Text::_('COM_TJLMS_COURSE_ID');

		$csvData .= implode(',', $csvData_arr);
		$csvData .= "\n";
		echo $csvData;

		$filename = "lms_course_report_" . date("Y-m-d_H-i", time());

		// Set CSV headers
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=" . $filename . ".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		foreach ($data as $courseData)
		{
			$csvData      = '';
			$csvData_arr1 = array();

			$csvData_arr1[] = $courseData->course_title;
			$csvData_arr1[] = $courseData->cat_title;
			$csvData_arr1[] = $courseData->access_level_title;
			$csvData_arr1[] = $courseData->type;
			$csvData_arr1[] = $courseData->lessons_cnt;
			$csvData_arr1[] = $courseData->enrolled_users;
			$csvData_arr1[] = $courseData->pending_enrollment;
			$csvData_arr1[] = $courseData->totalCompletedUsers;
			$csvData_arr1[] = $courseData->likeCnt;
			$csvData_arr1[] = $courseData->dislikeCnt;
			$csvData_arr1[] = $courseData->commnetsCnt;
			$csvData_arr1[] = $courseData->recommendCnt;
			$csvData_arr1[] = $courseData->assignCnt;
			$csvData_arr1[] = $courseData->course_id;

			// TRIGGER After csv body add extra fields
			$csvData = implode(',', $csvData_arr1);
			echo $csvData . "\n";
		}

		jexit();
	}
}
