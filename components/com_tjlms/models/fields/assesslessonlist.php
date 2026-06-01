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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list
 *
 * @since  1.0.0
 */
class FormFieldAssesslessonlist extends ListField
{
	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		$options[] = JHTML::_('select.option', '', Text::_("TJLMS_SELECT_LESSON"));
		$asessLessonFormat = array('exercise','quiz');

		$db  = Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select('l.id,l.title');
		$query->from($db->quoteName('#__tjlms_lessons', 'l'));

		// If course report only list those course lessons
		$input = Factory::getApplication()->input;
		$requestVars = $input->get('filter', '0', 'Array');

		if (isset($requestVars['course']) && (int) $requestVars['course'])
		{
			$query->where($db->quoteName('l.course_id') . ' = ' . $db->quote($requestVars['course']));
		}

		$db->setQuery($query);

		$results = $db->loadObjectlist();

		if (!empty($results))
		{
			foreach ($results as $result)
			{
				$options[]   = HTMLHelper::_('select.option', $result->id, $result->title);
			}
		}

		return $options;
	}
}
