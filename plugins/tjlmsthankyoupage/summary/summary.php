<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2020. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;

/**
 * Content builder plugin for Joomla Content
 *
 * @since  1.3.34
 */
class PlgTjlmsThankYouPageSummary extends CMSPlugin
{
	/**
	 * Load language file
	 **/
	protected $autoloadLanguage = true;

	/**
	 * Function to get Sub Format HTML when creating / editing lesson format
	 * the name of function should follow standard getSubFormat_<plugin_name>ContentHTML
	 *
	 * @param   OBJECT  $course     Course Data
	 * @param   OBJECT  $lesson     Lesson Data
	 * @param   OBJECT  $quiz       Quiz Data
	 * @param   OBJECT  $media      Media Data
	 * @param   OBJECT  $testState  Test State
	 * @param   OBJECT  $state      State Data
	 * @param   OBJECT  $timeTaken  Time Taken Data
	 * @param   OBJECT  $params     TJ-LMS Params
	 *
	 * @return  html
	 *
	 * @since 1.3.34
	 */
	public function getThankYouPageContent($course, $lesson, $quiz, $media, $testState, $state, $timeTaken, $params)
	{
		if ($quiz->gradingtype != 'quiz')
		{
			return false;
		}

		$summaryBase = $this->params->get('summary_base', 'section', 'STRING');

		$totalMarks = $lesson->total_marks;
		$scoredMarks = $quiz->attempt['score'];
		$passingMarks = $lesson->passing_marks;
		$passingPercentage = 0;
		$scoredPercentage = 0;
		$lessonTrackId = $state->get('test.lessonTrackId', '', 'INT');

		if (!empty($passingMarks) && !empty($totalMarks))
		{
			$passingPercentage = ($passingMarks / $totalMarks) * 100;
		}

		if (!empty($passingMarks) && !empty($totalMarks))
		{
			$scoredPercentage = ($scoredMarks / $totalMarks) * 100;
		}

		JLoader::import('components.com_tmt.models.test', JPATH_SITE);
		$testModel = BaseDatabaseModel::getInstance('Test', 'TmtModel');
		$passedIn = array();
		$failedIn = array();

		if ($summaryBase == 'section')
		{
			foreach ($testState['sectionsPerPage'] as $page)
			{
				foreach ($page as $sectionData)
				{
					$sectionQuestionsTestData = $testModel->getTestData($lessonTrackId, $sectionData->test_id, $quiz->attempt['user_id'], $sectionData->id);
					$sectionScore = 0;
					$sectionTotalScore = 0;

					foreach ($sectionQuestionsTestData->questions as $question)
					{
						$sectionScore += $question->userMarks;
						$sectionTotalScore += $question->marks;
					}

					$usersSectionPercentage = ($sectionScore / $sectionTotalScore) * 100;

					if ($usersSectionPercentage >= $passingPercentage)
					{
						$passedIn[] = $sectionData->title;
					}
					else
					{
						$failedIn[] = $sectionData->title;
					}
				}
			}
		}
		else
		{
			$categoryQuestions = array();
			$categoryTitles = array();

			foreach ($testState['sectionsPerPage'] as $page)
			{
				foreach ($page as $sectionData)
				{
					$sectionQuestionsTestData = $testModel->getTestData($lessonTrackId, $sectionData->test_id, $quiz->attempt['user_id'], $sectionData->id);

					foreach ($sectionQuestionsTestData->questions as $question)
					{
						$categoryTitles[$question->category_id] = $question->category;
						$categoryQuestions[$question->category_id][] = $question;
					}
				}
			}

			foreach ($categoryQuestions as $categoryId => $categoryQuestion)
			{
				$categoryScore = 0;
				$categoryTotalScore = 0;

				foreach ($categoryQuestions[$categoryId] as $question)
				{
					$categoryScore += $question->userMarks;
					$categoryTotalScore += $question->marks;
				}

				$usersCategoryPercentage = ($categoryScore / $categoryTotalScore) * 100;

				if ($usersCategoryPercentage >= $passingPercentage)
				{
					$passedIn[] = $categoryTitles[$categoryId];
				}
				else
				{
					$failedIn[] = $categoryTitles[$categoryId];
				}
			}
		}

		$html = "<h4><span class='font-bold'>" . Text::_("PLG_TJLMSTHANKYOUPAGE_LBL_SUMMARY") . "</span></h4>";

		if (!empty($passedIn))
		{
			$html .= "<h4>" . Text::_("PLG_TJLMSTHANKYOUPAGE_SUMMARY_DID_WELL") . implode(', ', $passedIn) . " </h4>";
		}

		if (!empty($failedIn))
		{
			$html .= "<h4>" . Text::_("PLG_TJLMSTHANKYOUPAGE_SUMMARY_CAN_IMPROVE") . implode(', ', $failedIn) . " </h4>";
		}

		return $html;
	}
}
