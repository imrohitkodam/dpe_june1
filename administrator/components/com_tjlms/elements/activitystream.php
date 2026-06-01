<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('formbehavior.chosen', 'select.eligibilitycriteria');

jimport('joomla.form.formfield');

/**
 * Element for activity stream
 *
 * @since  1.0.0
 */
class JFormFieldActivitystream extends JFormField
{
	public $type = 'activitystream';

	/**
	 * Function to get the input
	 *
	 * @return  Filter
	 *
	 * @since  1.0.0
	 */
	public function getInput()
	{
		return self::fetchElement($this->name, $this->value, $this->element, $this->options['control']);
	}

	/**
	 * Function to get the activity stream filter
	 *
	 * @param   STRING  $name          name of the field
	 * @param   STRING  $value         value of the field
	 * @param   STRING  &$node         name of the field
	 * @param   STRING  $control_name  name of the field
	 *
	 * @return  Filter
	 *
	 * @since  1.0.0
	 */
	public function fetchElement($name, $value, &$node, $control_name)
	{
		$options[] = JHTML::_('select.option', 'onafterCourseCreate', Text::_('COM_TJLMS_ACTIVITY_AFTER_COURSE_CREATE'));
		$options[] = JHTML::_('select.option', 'onafterCourseEnroll', Text::_('COM_TJLMS_ACTIVITY_AFTER_COURSE_ENROLL'));
		$options[] = JHTML::_('select.option', 'onafterCourseRecommend', Text::_('COM_TJLMS_ACTIVITY_AFTER_COURSE_RECOMMEND'));
		$options[] = JHTML::_('select.option', 'onafterLessonAttemptStart', Text::_('COM_TJLMS_ACTIVITY_AFTER_LESSON_START'));
		$options[] = JHTML::_('select.option', 'onafterLessonAttemptEnd', Text::_('COM_TJLMS_ACTIVITY_AFTER_LESSON_END'));
		$options[] = JHTML::_('select.option', 'onAfterCourseCompletion', Text::_('COM_TJLMS_ACTIVITY_AFTER_COURSE_COMPLITE'));
		$options[] = JHTML::_('select.option', 'none', Text::_('COM_TJLMS_ACTIVITY_NONE'));
		$fieldName = $name;

		$default = array();
		$default[] = 'onafterCourseCreate';
		$default[] = 'onafterCourseEnroll';
		$default[] = 'onafterCourseRecommend';
		$default[] = 'onAfterCourseCompletion';
		$default[] = 'onafterLessonAttemptStart';
		$default[] = 'onafterLessonAttemptEnd';

		if (empty($value))
		{
			$value = $default;
		}

		$optionalField = "class='inputbox form-control eligibilitycriteria' multiple='true'";

		return JHTML::_('select.genericlist', $options, $fieldName, $optionalField, 'value', 'text', $value, $control_name . $name);
	}
}
