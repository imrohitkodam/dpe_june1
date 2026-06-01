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
use Joomla\CMS\Factory;

jimport('joomla.application.component.controlleradmin');
jimport('techjoomla.common');

/**
 * Courses list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerLessonreport extends AdminController
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
		$lmsparams   = ComponentHelper::getParams('com_tjlms');
		$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		$input      = Jfactory::getApplication()->input;
		$post       = $input->post;
		$com_params = ComponentHelper::getParams('com_tjlms');
		$model      = $this->getModel('lessonreport');
		$data       = $model->getItems();

		// Create CSV headers
		$csvData       = null;
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONREPORT_ID');
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONREPORT_NAME');
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONREPORT_COURSENAME');
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONREPORT_STARTDATE');
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONREPORT_ENDDATE');
		$csvData_arr[] = Text::_('COM_TJLMS_REPORT_USERNAME');
		$csvData_arr[] = Text::_('COM_TJLMS_REPORT_USERUSERNAME');
		$csvData_arr[] = Text::_('COM_TJLMS_REPORT_USEREMAIL');
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONREPORT_ALLOWEDATTEMPTS');
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONREPORT_ATTEMPTSMADE');
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONREPORT_STATUS');
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONREPORT_TIMESPENT');
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONREPORT_SCORE');
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONREPORT_GRADINGMETHOD');
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONREPORT_COMSIDERMARKS');
		$csvData_arr[] = Text::_('COM_TJLMS_LESSONREPORT_FORMAT');

		$csvData .= implode(',', $csvData_arr);
		$csvData .= "\n";
		echo $csvData;

		$csvData = '';
		$filename = "lms_lesson_report_" . date("Y-m-d_H-i", time());

		// Set CSV headers
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=" . $filename . ".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		foreach ($data as $lessonData)
		{
			$csvData      = '';
			$csvData_arr1 = array();

			$csvData_arr1[] = $lessonData->lesson_id;
			$csvData_arr1[] = $lessonData->name;
			$csvData_arr1[] = $lessonData->courseTitle;

			if (!empty($lessonData->start_date))
			{
				if ($lessonData->start_date != '-')
				{
					$lessonData->start_date = $this->techjoomlacommon->getDateInLocal($lessonData->start_date, 0, $date_format_show);
				}

				if ($lessonData->start_date == '0000-00-00 00:00:00')
				{
					$lessonData->start_date = '-';
				}
			}

			if (!empty($lessonData->end_date))
			{
				if ($lessonData->end_date != '-')
				{
					$lessonData->end_date = $this->techjoomlacommon->getDateInLocal($lessonData->end_date, 0, $date_format_show);
				}

				if ($lessonData->end_date == '0000-00-00 00:00:00')
				{
					$lessonData->end_date = '-';
				}
			}

			$csvData_arr1[] = $lessonData->start_date;
			$csvData_arr1[] = $lessonData->end_date;
			$csvData_arr1[] = Factory::getUser($lessonData->user_id)->name;
			$csvData_arr1[] = Factory::getUser($lessonData->user_id)->username;
			$csvData_arr1[] = Factory::getUser($lessonData->user_id)->email;

			if ($lessonData->no_of_attempts == 0)
			{
				$csvData_arr1['no_of_attempts'] = Text::_('COM_TJLMS_UNLIMITED');
			}
			else
			{
				$csvData_arr1['no_of_attempts'] = $lessonData->no_of_attempts;
			}

			$csvData_arr1[] = $lessonData->attemptsDone;
			$csvData_arr1[] = $lessonData->status;
			$csvData_arr1[] = $lessonData->timeSpentOnLesson;
			$csvData_arr1[] = $lessonData->score;

			switch ($lessonData->attempts_grade)
			{
				case '0':
					$csvData_arr1['attempts_grade'] = Text::_('COM_TJLMS_NO_COMPLETED_OBJECT');
					break;
				case '1':
					$csvData_arr1['attempts_grade'] = Text::_('COM_TJLMS_NO_HIGHEST_SCORE_OBJECT');
					break;
				case '2':
					$csvData_arr1['attempts_grade'] = Text::_('COM_TJLMS_NO_AVERAGE_SUM_OBJECT');
					break;
				case '3':
					$csvData_arr1['attempts_grade'] = Text::_('COM_TJLMS_NO_SUM_OF_ALL_OBJECT');
					break;
			}

			$csvData_arr1['consider_marks'] = Text::_('JNO');

			if ($lessonData->consider_marks == 1)
			{
				$csvData_arr1['consider_marks'] = Text::_('JYES');
			}

			$csvData_arr1[] = $lessonData->format;

			// TRIGGER After csv body add extra fields
			$csvData = implode(',', $csvData_arr1);
			echo $csvData . "\n";
		}

		jexit();
	}
}
