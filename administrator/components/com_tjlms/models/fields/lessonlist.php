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
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list
 *
 * @since  1.0.0
 */
class JFormFieldLessonlist extends JFormFieldList
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
		$db  = Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select($db->quoteName('title'));
		$query->select($db->quoteName('id'));
		$query->from($db->quoteName('#__tjlms_lessons'));

		if ($this->element['listFilter'] == 'lib')
		{
			$query->where($db->qn('in_lib') . " = 1");
		}

		$query->where($db->qn('state') . " = 1");

		$db->setQuery($query);

		$results = $db->loadObjectlist();

		$options[] = JHTML::_('select.option', '', Text::_("TJLMS_SELECT_LESSON"));

		foreach ($results as $result)
		{
			$options[]   = HTMLHelper::_('select.option', $result->id, $result->title);
		}

		return $options;
	}
}
