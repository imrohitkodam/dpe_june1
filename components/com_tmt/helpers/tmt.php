<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

/**
 * Helper Class to for tests
 *
 * @since       1.0
 *
 * @deprecated  1.4.0  This class will be removed and replacements will be provided in language library
 */
class TmtHelper
{
	/**
	 * myfunction
	 *
	 * @return   string
	 *
	 * @since   1.0
	 */
	public static function myFunction()
	{
		$result = 'Something';

		return $result;
	}

	/**
	 * Get all jtext for javascript
	 *
	 * @return   void
	 *
	 * @since   1.0
	 */
	public static function getLanguageConstant()
	{
		Text::script('COM_TJLMS_ONAFTERUPLOAD_PROCESS_START');
		Text::script('COM_TJLMS_ADDTABLEENTRIES_PROCESS_START');
		Text::script('COM_TJLMS_QUIZ_CONFIRM_BOX');
		Text::script('COM_TJLMS_QUIZ_THANK_YOU_CONFIRM_BOX');
		Text::script('COM_TJLMS_QUIZ_DRAFT_CONFIRM_BOX');
		Text::script('COM_TMT_REVIEW_CONFIRM_BOX');
		Text::script('COM_TMT_REVIEW_SAVE_CONFIRM_BOX');
		Text::script('COM_TMT_TEST_APPEAR_ATTEMPTED_OF');
		Text::script('COM_TMT_TEST_MAX_OPTION_ATTEMPT_VALIDATION');
		Text::script('COM_TMT_TEST_APPEAR_FINISH_EXERCISE');
		Text::script('COM_TMT_TEST_APPEAR_FINISH_FEEDBACK');
		Text::script('COM_TMT_TEST_APPEAR_FINISH_QUIZ');
		Text::script('COM_TMT_DELETE_ITEM');
		Text::script('COM_TMT_TEST_DRAFT_CONFIRM_BOX');
		Text::script('COM_TJLMS_ALLOWED_FILE_EXTENSION_ERROR_MSG');
		Text::script('COM_TJLMS_ALLOWED_FILE_SIZE_ERROR_MSG');
		Text::script('COM_TJLMS_MAX_NUMBER_OF_FILE_UPLOAD_ERROR_MSG');
		Text::script('COM_TMT_Q_FORM_PARAMS_TEXTAREA_COUNTER_TEXT_MAX');
		Text::script('COM_TJLMS_SUCCESS_UPLOAD');
		Text::script('COM_TMT_TEST_SUBMIT_VALIDATION_FOR_COMPULSORY_MSG');
		Text::script('COM_TMT_TEST_NOT_ATTEMPTED_QUE');
	}
}
