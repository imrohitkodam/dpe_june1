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
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.view');

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsViewreports extends HtmlView
{
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since  1.0.0
	 */
	public function display($tpl = null)
	{
		$model                 = $this->getModel();
		$app             = Factory::getApplication();
		$input           = $app->input;

		$oluser_id             = Factory::getUser()->id;
		$this->tjlmsdbhelperObj = new tjlmsdbhelper;
		$this->comtjlmsHelper = new comtjlmsHelper;
		$this->showAnswerSheet = 1;

		$course_id       = $input->get('course_id', 0, 'INT');
		$this->lesson_id = $lesson_id = $input->get('lesson_id', 0, 'INT');
		$this->user_id   = $input->get('stuid', $oluser_id, 'INT');
		$this->course_id = $course_id;
		$this->attempts_report = '';
		$this->quizType = '';

		if (!$this->user_id)
		{
			$app->enqueueMessage(Text::_('COM_TJLMS_MESSAGE_LOGIN_FIRST'), 'error');
            $app->setHeader('status', 403, true);

			return false;
		}

		if ($this->user_id > 0)
		{
			if ($course_id > 0)
			{
				$results_for_report = $model->getReport($course_id, $this->user_id);
				$this->row          = $results_for_report;
			}

			if ($lesson_id > 0)
			{
				$results_for_attemptsreport = $model->getattemptsReport($lesson_id, $this->user_id);
				$this->attempts_report      = $results_for_attemptsreport;

				$this->format = $this->tjlmsdbhelperObj->get_records('format', 'tjlms_lessons', array(
					'id' => $this->lesson_id
				), '', 'loadResult');

				if ($this->format == 'quiz' || $this->format == 'exercise')
				{
					$this->media_id = $this->tjlmsdbhelperObj->get_records('media_id', 'tjlms_lessons', array(
						'id' => $this->lesson_id
					), '', 'loadResult');

					$this->test_id         = $this->tjlmsdbhelperObj->get_records('source', 'tjlms_media', array(
						'id' => $this->media_id
					), '', 'loadResult');

					$this->quizType = $this->tjlmsdbhelperObj->get_records('type', 'tmt_tests', array(
						'id' => $this->test_id
					), '', 'loadResult');

					$this->showAnswerSheet = $this->tjlmsdbhelperObj->get_records('answer_sheet', 'tmt_tests', array(
						'id' => $this->test_id
					), '', 'loadResult');
				}
			}
		}

		parent::display($tpl);
	}
}
