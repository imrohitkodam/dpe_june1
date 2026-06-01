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

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list
 *
 * @since  1.0.0
 */
class FormFieldNamelist extends FormFieldList
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

		$query->select('DISTINCT lt.user_id');
		$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
		$query->join('left', $db->quoteName('#__tjlms_lessons', 'l') . 'ON(' . $db->quoteName('lt.lesson_id') . '=' . $db->quoteName('l.id') . ') ');

		$query->join('left', $db->quoteName('#__tjlms_tmtquiz', 'ttq') . 'ON(' . $db->quoteName('l.id') . '=' . $db->quoteName('ttq.lesson_id') . ') ');

		$query->where(
			'l.id IN ' . '(SELECT tjt.lesson_id FROM #__tjlms_tmtquiz AS tjt LEFT JOIN #__tmt_tests AS tt ON(tt.id=tjt.test_id)
			WHERE tt.isObjective="0" OR tt.gradingtype ="exercise" OR tt.gradingtype="feedback")');

		$query->where($db->quoteName('l.created_by') . '=' . $db->quote(Factory::getUser()->id));
		$query->where($db->quoteName('lt.lesson_status') . '<>' . $db->quote('started'));

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
