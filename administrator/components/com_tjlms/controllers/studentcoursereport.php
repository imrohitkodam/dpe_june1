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

jimport('techjoomla.common');

/**
 * Courses list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerStudentcoursereport extends AdminController
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
		$this->techjoomlacommon = new TechjoomlaCommon;
		$lmsparams   = ComponentHelper::getParams('com_tjlms');
		$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		$input      = Jfactory::getApplication()->input;
		$post       = $input->post;
		$com_params = ComponentHelper::getParams('com_tjlms');
		$model      = $this->getModel('studentcoursereport');
		$data       = $model->getItems();

		// Create CSV headers
		$csvData       = null;
		$csvData_arr[] = Text::_('COM_TJLMS_COURSE_NAME');
		$csvData_arr[] = Text::_('COM_TJLMS_COURSE_CAT');
		$csvData_arr[] = Text::_('COM_TJLMS_ACL_GROUP');
		$csvData_arr[] = Text::_('COM_TJLMS_FORM_LBL_COURSE_CERTIFICATE_TERM');
		$csvData_arr[] = Text::_('COM_TJLMS_REPORT_USERNAME');
		$csvData_arr[] = Text::_('COM_TJLMS_REPORT_USERUSERNAME');
		$csvData_arr[] = Text::_('COM_TJLMS_REPORT_USEREMAIL');
		$csvData_arr[] = Text::_('COM_TJLMS_USER_ENROLLED_ON');
		$csvData_arr[] = Text::_('COM_TJLMS_COMPLETION');
		$csvData_arr[] = Text::_('COM_TJLMS_REPORT_TIMESPENT');
		$csvData_arr[] = Text::_('COM_TJLMS_COURSE_ID');

		$csvData .= implode(',', $csvData_arr);
		$csvData .= "\n";
		echo $csvData;

		$csvData = '';
		$filename = "lms_Student_course_report_" . date("Y-m-d_H-i", time());

		// Set CSV headers
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=" . $filename . ".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		foreach ($data as $courseData)
		{
			$csvData      = '';
			$csvData_arr1 = array();

			$csvData_arr1[] = $courseData->title;
			$csvData_arr1[] = $courseData->cat_title;
			$csvData_arr1[] = $courseData->access_level_title;
			$csvData_arr1[] = $courseData->certificate_term;
			$csvData_arr1[] = $courseData->user_name;
			$csvData_arr1[] = $courseData->user_username;
			$csvData_arr1[] = $courseData->useremail;

			if (!empty($lessonData->last_accessed_on))
			{
				if ($courseData->enrolled_on_time != '-')
				{
					$courseData->enrolled_on_time = $this->techjoomlacommon->getDateInLocal($courseData->enrolled_on_time, 0, $date_format_show);
				}

				if ($courseData->enrolled_on_time == '0000-00-00 00:00:00')
				{
					$courseData->enrolled_on_time = '-';
				}
			}

			$csvData_arr1[] = $courseData->enrolled_on_time;
			$csvData_arr1[] = $courseData->completion . '%';
			$csvData_arr1[] = $courseData->totaltimespent;
			$csvData_arr1[] = $courseData->course_id;

			// TRIGGER After csv body add extra fields
			$csvData = implode(',', $csvData_arr1);
			echo $csvData . "\n";
		}

		jexit();
	}
}
