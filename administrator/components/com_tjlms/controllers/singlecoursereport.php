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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.controlleradmin');
jimport('techjoomla.common');

/**
 * Courses list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerSinglecoursereport extends AdminController
{
	private $techjoomlacommon;
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
		$lmsparams              = ComponentHelper::getParams('com_tjlms');
		$date_format_show       = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');
		$filename = "lms_Student_single_course_report_" . date("Y-m-d_H-i", time());
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false);
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"$filename.csv\";");
		header("Content-Transfer-Encoding: binary");
		$input      = Factory::getApplication()->input;
		$post       = $input->post;
		$com_params = ComponentHelper::getParams('com_tjlms');
		$model      = $this->getModel('singlecoursereport');
		$data       = $model->getItems();

		// Create CSV headers
		$csvData       = null;
		$csvData_arr[] = Text::_('COM_TJLMS_EMP_NAME');
		$csvData_arr[] = Text::_('COM_TJLMS_EMP_CODE');
		$csvData_arr[] = Text::_('COM_TJLMS_EMP_EMAIL');
		$csvData_arr[] = Text::_('COM_TJLMS_EMP_GROUP');
		$csvData_arr[] = Text::_('COM_TJLMS_COURSE_NAME');
		$csvData_arr[] = Text::_('COM_TJLMS_USER_ENROLLED_ON');
		$csvData_arr[] = Text::_('Due Date');
		$csvData_arr[] = Text::_('COM_TJLMS_COURSE_COMPLETED_DATE');
		$csvData_arr[] = Text::_('COM_TJLMS_COMPLETION');
		$csvData_arr[] = Text::_('COM_TJLMS_REPORT_TIMESPENT');
		$count_lessons = $data['0']->totallessonsattempted;
		$less_headers2 = $less_headers = $data['0']->lessonheader;

		foreach ($less_headers AS $keyless1 => $lessonheader1)
		{
			$csvData_arr[] = '';
			$csvData_arr[] = $keyless1;
			$csvData_arr[] = '';
		}

		$csvData .= implode(',', $csvData_arr);
		$csvData .= "\n";
		echo $csvData;

		$csvData             = null;
		$csvData_arr_head2[] = '';
		$csvData_arr_head2[] = '';
		$csvData_arr_head2[] = '';
		$csvData_arr_head2[] = '';
		$csvData_arr_head2[] = '';
		$csvData_arr_head2[] = '';
		$csvData_arr_head2[] = '';
		$csvData_arr_head2[] = '';
		$csvData_arr_head2[] = '';
		$csvData_arr_head2[] = '';

		foreach ($less_headers2 AS $keyless2 => $lessonheader2)
		{
			$csvData_arr_head2[] = Text::_('COM_TJLMS_LESSON_SCORE_STUDENT');
			$csvData_arr_head2[] = Text::_('COM_TJLMS_LESSON_TIME_SPENT_STUDENT');
			$csvData_arr_head2[] = Text::_('COM_TJLMS_LESSON_STATUS_STUDENT');
		}

		$csvData .= implode(',', $csvData_arr_head2);
		$csvData .= "\n";
		echo $csvData;
		$csvData = '';

		foreach ($data as $courseData)
		{
			$csvData      = '';
			$csvData_arr1 = array();

			$csvData_arr1[] = $courseData->user_name;
			$csvData_arr1[] = $courseData->username;
			$csvData_arr1[] = $courseData->email;
			$temp            = preg_replace('#<br[/\s]*>#si', " ", $courseData->groups);
			$csvData_arr1[]  = trim($temp);
			$completion_date = '';
			$course_due_date = '';
			$csvData_arr1[]   = $courseData->title;
			$enrolled_on_time = '';

			if (!empty($courseData->enrolled_on_time))
			{
				if ($courseData->enrolled_on_time != '-')
				{
					$enrolled_on_time = $this->techjoomlacommon->getDateInLocal($courseData->enrolled_on_time, 0, $date_format_show);
					$csvData_arr1[]   = $enrolled_on_time;
				}
			}
			else
			{
				$csvData_arr1[] = "";
			}

			if (!empty($courseData->course_due_date))
			{
				if ($courseData->course_due_date != '-')
				{
					$course_due_date = $this->techjoomlacommon->getDateInLocal($courseData->course_due_date, 0, $date_format_show);
					$csvData_arr1[]  = $course_due_date;
				}
			}
			else
			{
				$csvData_arr1[] = "";
			}

			if (!empty($courseData->completion_date))
			{
				if ($courseData->completion_date != '-')
				{
					$completion_date = $this->techjoomlacommon->getDateInLocal($courseData->completion_date, 0, $date_format_show);
					$csvData_arr1[]  = $completion_date;
				}
			}
			else
			{
				$csvData_arr1[] = "";
			}

			$csvData_arr1[] = $courseData->completion . '%';
			$csvData_arr1[] = $courseData->totaltimespent;

			foreach ($data['0']->lessonheader AS $keyless => $lessonheader)
			{
				$courseData->lessondata[$keyless] = new stdClass;

				if (empty($courseData->lessondata[$keyless]->score))
				{
					$courseData->lessondata[$keyless]->score = new stdClass;
					$courseData->lessondata[$keyless]->score = 0;
				}

				if (!isset($courseData->lessondata[$keyless]->timeSpentOnLesson))
				{
					$courseData->lessondata[$keyless]->timeSpentOnLesson = '';
				}

				if (!isset($courseData->lessondata[$keyless]->lesson_status))
				{
					$courseData->lessondata[$keyless]->lesson_status = '';
				}

				$csvData_arr1[] = $courseData->lessondata[$keyless]->score;
				$csvData_arr1[] = $courseData->lessondata[$keyless]->timeSpentOnLesson;
				$csvData_arr1[] = $courseData->lessondata[$keyless]->lesson_status;
			}

			// TRIGGER After csv body add extra fields
			$csvData = implode(',', $csvData_arr1);
			echo $csvData . "\n";
		}

		jexit();
	}
}
