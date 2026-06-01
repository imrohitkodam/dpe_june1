<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

/**
 * Tmt helper.
 *
 * @since  1.0.0
 */
class TmtHelper extends TjlmsHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   STRING  $vName  View name
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public static function addSubmenu($vName = '')
	{
		$lang = Factory::getLanguage();
		$lang->load('com_tjlms', JPATH_ADMINISTRATOR);

		parent::addSubmenu($vName);
	}

	/**
	 * Get all jtext for javascript
	 *
	 * @return   void
	 *
	 * @since   1.0
	 *
	 * @deprecated  1.4.0  This function will be removed and replacements will be provided in language library
	 */
	public static function getLanguageConstant()
	{
		// For date valiation
		Text::script('COM_TJLMS_MAX_ATTEMPT_VALIDATION_MSG');
		Text::script('COM_TJLMS_EMPTY_TITLE_ISSUE');
		Text::script('COM_TJLMS_COURSE_DURATION_VALIDATION');
		Text::script('COM_TJLMS_LESSON_UPDATED_SUCCESSFULLY');
		Text::script('COM_TJLMS_MODULE_PUBLISHED_SUCCESSFULLY');
		Text::script('COM_TJLMS_MODULE_UNPUBLISHED_SUCCESSFULLY');
		Text::script('COM_TMT_QUIZ_DUPLICATE_QUESTIONS');

		Text::script('COM_TMT_DATE_VALIDATION_MONTH_INCORRECT');
		Text::script('COM_TMT_DATE_VALIDATION_MONTH_INCORRECT');
		Text::script('COM_TMT_DATE_VALIDATION_TIME_INCORRECT');
		Text::script('COM_TMT_DATE_VALIDATION_DATE_RANGE');
		Text::script('COM_TMT_DATE_TIME_VALIDATION');
		Text::script('COM_TMT_DATE_RANGE_VALIDATION');
		Text::script('COM_TMT_DATE_VALIDATION');
		Text::script('COM_TMT_DATE_VALIDATION_DATE_RANGE');
		Text::script('COM_TMT_Q_FORM_NON_ZERO_MIN_FOR_QUIZ');

		Text::script('COM_TMT_QUESTIONS_VALIDATION');
		Text::script('COM_TMT_QUESTIONS_ANSWER_VALIDATION');
		Text::script('COM_TMT_QUESTIONS_COMMENT_VALIDATION');
		Text::script('COM_TMT_Q_FORM_NON_ZERO_MARKS');
		Text::script('COM_TMT_TEST_FORM_NON_ZERO_VALUE_TIME');
		Text::script('COM_TMT_HIDE_ALERT_TIME');
		Text::script('COM_TMT_TEST_FORM_NON_ZERO_VALUE_MARKS');
		Text::script('COM_TMT_TEST_FORM_MSG_MIN_MARKS_HIGHER');
		Text::script('COM_TMT_TEST_FORM_MSG_FIX_DUPLI');
		Text::script('COM_TMT_TEST_FORM_MSG_ADD_Q');
		Text::script('COM_TMT_TEST_FORM_MSG_MARKS_MISMATCH');
		Text::script('COM_TMT_TEST_FORM_MSG_MARKS_MIN_MISMATCH');
		Text::script('COM_TMT_DATE_ISSUE');
		Text::script('COM_TMT_QUIZ_TITLE_VALIDATION_MSG');
		Text::script('COM_TMT_QUIZ_ASGN_NAME');
		Text::script('COM_TMT_QUIZ_DUPLICATE_QUESTIONS');
		Text::script('COM_TMT_SHORT_DESC_QUIZ');
		Text::script('COM_TMT_QUIZ_TOTAL_MARK');
		Text::script('COM_TMT_QUIZ_MIN_MARK');
		Text::script('COM_TMT_TEST_FORM_MSG_TIME_FINISHED_DURATION_HIGHER');
		Text::script('COM_TMT_TEST_FORM_MSG_FIX_DUPLI');
		Text::script('COM_TMT_TEST_FORM_INALID_RULES');
		Text::script('COM_TMT_TEST_FORM_MSG_NO_Q_FOUND');
		Text::script('COM_TMT_TEST_FORM_MSG_NO_Q_FOUND_SET');
		Text::script('COM_TMT_SURE_DELETE_SECTION');

		Text::script('COM_TMT_TEST_FORM_MSG_QUESTION_COUNT_SHOULD_NUMBER');
		Text::script('COM_TMT_TEST_FORM_MSG_QUESTION_MARKS_SHOULD_NUMBER');
		Text::script('COM_TMT_TEST_FORM_MSG_QUESTION_MARKS_FIELD_MANDATORY');
		Text::script('COM_TMT_TEST_FORM_MSG_QUESTION_COUNT_FIELD_MANDATORY');
		Text::script('COM_TMT_TEST_FORM_MSG_SET_QUIZ_ADD_QUESTION_NOTICE');
		Text::script('COM_TMT_FORM_TEST_TOTAL_MARKS_FOR_QUIZ');
		Text::script('COM_TMT_TEST_FORM_ADD_ATLEAST_ONE_QUIZ_RULE');
		Text::script('COM_TMT_TEST_FORM_MSG_INSUFFICIENT_Q_FOUND');
		Text::script('COM_TMT_NO_SECTION_QUESTION');

		Text::script('COM_TMT_TEST_FORM_TIME_FINISHED_ALERT_MSG_1');
		Text::script('COM_TMT_TEST_FORM_TIME_FINISHED_ALERT_MSG_2');
		Text::script('COM_TMT_END_DATE_CANTBE_GRT_TODAY');

		Text::script('COM_TMT_FORM_LBL_ALLOW_QUIZ');

		/* Question form validation */
		Text::script('COM_TMT_QUESTION_TITLE_FIELD_VALIDATION');
		Text::script('COM_TMT_QUESTION_ANSWER_FIELD_VALIDATION');
		Text::script('COM_TMT_QUESTION_COMMENT_FIELD_VALIDATION');
		Text::script('COM_TMT_Q_DUPLICATE_ANS');
		Text::script('COM_TMT_Q_FORM_ENTER_VALID_NUMBER');
		Text::script('COM_TMT_Q_FORM_ENTER_VALID_MARK');
		Text::script('COM_TMT_Q_FORM_MARKS_MISMATCH');
		Text::script('COM_TMT_Q_FORM_NO_CORRECT_ANSWER');
		Text::script('COM_TMT_QUESTION_ANSWER_OPTION_DELETE_CONFIRMATION_MSG');
		Text::script('COM_TMT_QUESTION_ANSWER_OPTION_DELETE_SUCCESSFULLY_MSG');
		Text::script('COM_TMT_Q_FORM_NO_MARK_FOR_CORRECT_ANSWER');
		Text::script('COM_TMT_Q_DELETE_ALERT');
		Text::script('COM_TMT_Q_RATING_UPPER_LOWER_RANGE');
		Text::script('COM_TMT_QUESTION_NO_CORRECT_ANS_MSG');
		Text::script('COM_TMT_QUESTION_MCQ_MRQ_ATLEAST_TWO_ANSWERS');
		/* Question form validation */

		Text::script('COM_TMT_NO_MARKS_ANSWER_MSG1');
		Text::script('COM_TMT_NO_MARKS_ANSWER_MSG2');
		Text::script('COM_TMT_NO_ANSWER_MSG1');
		Text::script('COM_TMT_Q_FORM_MESSAGE_SAVE_QUESTION');
		Text::script('COM_TMT_SAVE_SECTION_ERROR');

		/* Dynamic Questions*/
		Text::script('COM_TMT_TEST_DYNAMIC_RULE_INSUFFICIENT');
		Text::script('COM_TMT_TEST_DYNAMIC_RULE_SUFFICIENT_FOR_SET');
		Text::script('COM_TMT_TEST_DYNAMIC_MISMATCH_SET_MARKS');
		Text::script('COM_TMT_TEST_FORM_MSG_RULES_MIN_QUESTIONS');

		/*1.3*/
		Text::script('COM_TJLMS_ASSESSMENT_MARKS_MISMATCH');
		Text::script('COM_TMT_TEST_CONFIRM_QUESTION_DELETE');
		Text::script('COM_TMT_TEST_CONFIRM_SECTION_DELETE');
		Text::script('COM_TMT_TEST_ASSESSMENT_TOTAL_MARKS_NONZERO');
		Text::script('COM_TMT_TEST_MSG_NO_QUESTIONS');
		Text::script('COM_TJLMS_ASSESSMENT_MSG_NO_PARAMS');
		Text::script('COM_TMT_TEST_MSG_INVALID_RULE');
		Text::script('COM_TMT_MESSAGE_SELECT_ITEMS');
		Text::script('COM_TJLMS_QUIZ_CONFIRM_BOX');
		Text::script('COM_TMT_VALID_MARKS');
		Text::script('COM_TMT_TEST_UNPUBLISH_SECTION');
		Text::script('COM_TMT_TEST_PUBLISH_SECTION');
		Text::script('COM_TMT_QUESTION_RATING_TYPE_VALIDATION');
		Text::script('COM_TMT_TEST_MSG_TIME_FINISHED_INVALID');
		Text::script('COM_TMT_QUESTION_MARKS_FOR_NOTCORRECT_ANSWER');
		Text::script('COM_TMT_Q_FORM_MARKS_NOTMATCH_FOR_MCQ');
		Text::script('COM_TMT_TEST_CONFIRM_QUESTION_MEDIA_DELETE');
		Text::script('COM_TMT_QUESTION_TEXTAREA_TYPE_VALIDATION');
		Text::script('COM_TMT_Q_FORM_PARAMS_FILE_SIZE_MSG');
		Text::script('COM_TJLMS_MIN_NO_OF_ASSESSMENT_VALIDATION_MSG');
		Text::script('COM_TMT_Q_FROM_CATEGORY_CHANGE');
		Text::script('COM_TMT_QUESTION_RATING_LABEL_ERROR');
	}
}
