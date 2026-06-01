<?php
/**
 * @version    SVN: <svn_id>
 * @package    LMS
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of courses
 *
 * @since  1.6
 */
class JFormFieldSelectcourses extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'selectcourses';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getInput()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('c.id, c.title');
		$query->from('`#__tjlms_courses` AS c');
		$query->where('c.state = 1');
		$query->order($db->escape('c.ordering ASC'));

		$db->setQuery($query);

		// Get all countries.
		$allcourses = $db->loadObjectList();

		$options = array();

		$input = Factory::getApplication()->input;
		$reminder_id = $input->get('id');

		foreach ($allcourses as $c)
		{
			$options[] = HTMLHelper::_('select.option', $c->id, $c->title, 'value', 'text', "", "selected");
		}

		$options = array_merge(parent::getOptions(), $options);

		if (isset($reminder_id))
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('course_id');
			$query->from($db->quoteName('#__tjlms_reminders_xref') . 'as c');
			$query->where('reminder_id = ' . $reminder_id);
			$db->setQuery($query);
			$selected_courses = $db->loadcolumn();

			if ($selected_courses)
			{
				return $html = HTMLHelper::_(
				'select.genericlist', $options, $this->name, 'class="inputbox required" required="required"
				multiple="multiple" size="5"', 'value', 'text', array_values($selected_courses), $this->id
				);
			}
		}
		else
		{
			return $html = HTMLHelper::_(
			'select.genericlist', $options, $this->name, 'class="inputbox required invalid"
			multiple="multiple" size="5"', 'value', 'text', '', $this->id
			);
		}
	}
}
