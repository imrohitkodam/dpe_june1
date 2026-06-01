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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list
 *
 * @since  1.0.0
 */
class JFormFieldLessonlist extends \JFormFieldList

{
	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of HTMLHelper options.
	 *
	 * @since   11.4
	 */
	protected function getOptions()
	{
		$user      = Factory::getUser();
		$currentUserSuperUser = $user->authorise('core.admin');
		$canManageMaterial = $user->authorise('core.manage.material', 'com_tjlms');
		$db  = Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('jc.id', 'a.title')));
		$query->from($db->quoteName('#__tjlms_lessons', 'a'));
		$query->join('INNER', $db->qn('#__jlike_content', 'jc') .
		'ON(' . $db->qn('a.id') . '=' . $db->qn('jc.element_id') . ' AND ' . $db->qn('jc.element') . '= "com_tjlms.lesson")');
		$query->join('INNER', $db->qn('#__tjlms_media', 'tm') . 'ON(' . $db->qn('a.media_id') . '=' . $db->qn('tm.id') . ')');
		$query->where($db->quoteName('a.in_lib') . '= 1');

		if (!$currentUserSuperUser && !$canManageMaterial)
		{
			$query->where($db->quoteName('a.created_by') . '=' . $db->quote($user->id));
		}

		$query->order($db->qn('a.id') . ' DESC');
		$db->setQuery($query);

		$results = $db->loadObjectlist();

		$options = array();
		$options[] = JHTML::_('select.option', '', Text::_("COM_DPE_SELECT_DOCUMENT"));

		foreach ($results as $result)
		{
			$options[]   = HTMLHelper::_('select.option', $result->id, $result->title);
		}

		return $options;
	}
}
