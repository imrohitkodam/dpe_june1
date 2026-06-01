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
class FormFieldAssessnamelist extends ListField
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

		$query->select('distinct u.id user_id, u.name as user_name');
		$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
		$query->join('left', $db->quoteName('#__tjlms_lessons', 'l') . 'ON ' . $db->quoteName('lt.lesson_id') . '=' . $db->quoteName('l.id'));
		$query->join('left', $db->quoteName('#__tjlms_assessment_reviews', 'ar') .
						'ON(' . $db->quoteName('lt.id') . '=' . $db->quoteName('ar.lesson_track_id') . ')');
		$query->join('left', $db->quoteName('#__users', 'u') . 'ON ' . $db->quoteName('lt.user_id') . '=' . $db->quoteName('u.id'));
		$query->where($db->quoteName('lt.lesson_status') . ' IN(' . implode(',', $db->quote(array('AP', 'completed','passed','failed'))) . ')');

		$db->setQuery($query);

		$results = $db->loadObjectlist();

		$options[] = JHTML::_('select.option', '', Text::_("TJLMS_SELECT_NAME"));

		foreach ($results as $result)
		{
			$user = Factory::getUser($result->user_id);
			$options[]   = HTMLHelper::_('select.option', $user->id, $user->name);
		}

		return $options;
	}
}
