<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,courseprerequisite
 *
 * @copyright   Copyright (C) 2005 - 2020. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
HTMLHelper::_('formbehavior.chosen', 'select.eligibilitycriteria');

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of courses
 *
 * @since  1.3.39
 */
class JFormFieldPrerequisitecourses extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.3.39
	 */
	protected $type = 'prerequisitecourses';

	/**
	 * Fiedd to decide if options are being loaded externally and from xml
	 *
	 * @var		integer
	 * @since	1.3.39
	 */
	protected $loadExternally = 0;

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of HTMLHelper options.
	 *
	 * @since   1.3.39
	 */
	protected function getInput()
	{
		$db    = Factory::getDbo();
		$app   = Factory::getApplication();
		$courseId = $app->input->get('id', 0, 'INT');
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('c.id, c.title');
		$query->from($db->qn('#__tjlms_courses', 'c'));
		$query->join('LEFT', $db->qn('#__categories', 'cat') . ' ON (' . $db->qn('cat.id') . ' = ' . $db->qn('c.catid') . ')');
		$query->where($db->qn('c.state') . '= 1');
		$query->where($db->qn('cat.published') . '= 1');

		if ($courseId)
		{
			$query->where($db->qn('c.id') . ' != ' . (int) $courseId);
		}

		$nullDate	= $db->quote($db->getNullDate());
		$nowDate	= $db->quote(Factory::getDate()->toSql());
		$query->where('(c.start_date = ' . $nullDate . ' OR c.start_date <= ' . $nowDate . ')');

		$query->order($db->escape('c.title ASC'));

		$db->setQuery($query);

		// Get all countries.
		$allcourses = $db->loadObjectList();

		$options   = array();
		$prop = " class='inputbox form-control eligibilitycriteria' multiple='true' ";

		foreach ($allcourses as $key => $obj)
		{
			$options[] = HTMLHelper::_('select.option', $obj->id, $obj->title);
		}

		if ($this->value)
		{
			$this->value = explode(',', $this->value);
		}

		$dropdown = HTMLHelper::_('select.genericlist', $options, $this->name, $prop, 'value', 'text', $this->value);

		return $dropdown;
	}
}
