<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JLoader::import('components.com_tjlms.libraries.suggestcourses', JPATH_SITE);

use Joomla\CMS\Factory;

/**
 * Class to suggest course categories to a user
 *
 * @since  1.3.22
 */
class TjSuggestCategories
{
	/**
	 *  Filter categories based on custom logic
	 *
	 * @return  object|boolean
	 *
	 * @since   1.3.22
	 */
	public static function suggestCategories()
	{
		$suggestedCourses = TjSuggestCourses::getSuggestedCourses();

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('DISTINCT(c.catid), cat.title as category');
		$query->from($db->qn('#__tjlms_courses', 'c'));
		$query->join('INNER', $db->qn('#__categories', 'cat') . ' ON (' . $db->qn('cat.id') . ' = ' . $db->qn('c.catid') . ')');

		// Exclude already enrolled courses
		if ($suggestedCourses['suggested_from_enrolled_categegories'])
		{
			$query->where($db->qn('c.id') . ' NOT IN (' . implode(',', $suggestedCourses['courses']) . ')');
		}
		else
		{
			$query->where($db->qn('c.id') . ' IN (' . implode(',', $suggestedCourses['courses']) . ')');
		}

		$db->setQuery($query);

		return $db->loadObjectList();
	}
}
