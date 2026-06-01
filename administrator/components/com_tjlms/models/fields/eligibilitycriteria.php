<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;

use Joomla\CMS\HTML\HTMLHelper;
HTMLHelper::_('formbehavior.chosen', 'select.eligibilitycriteria');

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list
 *
 * @since  1.0.0
 */
class JFormFieldEligibilitycriteria extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'Eligibilitycriteria';

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
		$input = Factory::getApplication()->input;
		$query = $db->getQuery(true);
		$query->select('a.id, a.title');
		$query->from('`#__tjlms_lessons` AS a');
		$query->where('a.course_id=' . $input->get('cid', '0', 'INT'));
		$query->where('(a.media_id <> 0 OR a.format="tmtQuiz")');

		$lessonId = $this->form->getValue('id');

		if ($this->form->getValue('id') && 'test' == $input->get('view', '', 'STRING'))
		{
			$lessonId = $input->get('lid', 0, 'INT');
		}

		if ($lessonId)
		{
			$query->where('a.id !=' . $lessonId);
			$query->where("a.eligibility_criteria NOT LIKE '%," . $lessonId . ",%'");
		}

		$query->order('a.ordering', 'ASC');
		$db->setQuery($query);
		$res = $db->loadObjectList();

		$options   = array();
		$prop = " class='inputbox form-control eligibilitycriteria' multiple='true' ";

		foreach ($res as $key => $obj)
		{
			$options[] = JHTML::_('select.option', $obj->id, $obj->title);
		}

		if ($this->value)
		{
			$this->value = explode(',', $this->value);
		}

		$dropdown = JHTML::_('select.genericlist', $options, $this->name, $prop, 'value', 'text', $this->value);

		return $dropdown;
	}
}
