<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Tmt category helper class
 *
 * @since       1.0.0
 *
 * @deprecated  1.4.0  This class will be removed and some replacements will be provided in utilities library
 *
 */
class TmtCategoryHelper
{
	/**
	 * Method to validate if logged in user is author if id is not null
	 *
	 * @param   int    $id            Category id
	 *
	 * @param   array  $companyUsers  Array of company users
	 *
	 * @return	boolean true/false
	 *
	 * @since	1.0
	 */
	public function checkCreator($id = null, $companyUsers = null)
	{
		$db = Factory::getDBO();

		if ($id)
		{
			$query = "SELECT c.created_user_id FROM `#__categories` AS c WHERE c.id=" . $id . "
			AND c.extension='com_tmt.questions'";
			$db->setQuery($query);
			$creator = $db->loadResult();

			// @TODO remove the company reference
			if (is_array($companyUsers))
			{
				if (in_array($creator, $companyUsers))
				{
					return true;
				}
			}
			else
			{
				if ($creator == Factory::getUser()->id)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Method to check if a category can be deleted.
	 * If category contains 1 or more than questions, it can't be deleted.
	 *
	 * @param   int  $id  Category id
	 *
	 * @return	boolean	true/false
	 *
	 * @since	1.0
	 */
	public function canBeDeleted($id=null)
	{
		$db = Factory::getDBO();

		if ($id)
		{
			$query = "SELECT q.id FROM `#__tmt_questions` AS q WHERE q.category_id = " . $id;
			$db->setQuery($query);
			$questions = $db->loadObjectlist();

			if (count($questions))
			{
				// Don't allow delete, if questions are present.
				return false;
			}
			else
			{
				// Allow deleting.
				return true;
			}
		}

		return false;
	}

	/**
	 * Method to get options for category list filter/dropdown (as per ACL)
	 *
	 * @return	array   $options  id and title of all categories (as per ACL)
	 *
	 * @since	1.0
	 */
	public function getCategories()
	{
		$user = Factory::getUser();
		$db   = Factory::getDbo();

		$query = $db->getQuery(true);
		$query->select('a.id, a.title');
		$query->from('`#__categories` AS a');

		// Join users table
		// $query->join('LEFT', '#__users AS u ON u.id = a.created_user_id');
		$query->where('a.extension="com_tmt.questions"');

		// Get only published categories.
		$query->where('a.published=1');

		// $query->where('a.created_user_id='.JFactory::getUser()->id);

		$db->setQuery($query);
		$cats = $db->loadObjectList();

		$options = array();
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TMT_SELECT_CATEGORY'));

		foreach ($cats as $cat)
		{
			$options[] = HTMLHelper::_('select.option', $cat->id, $cat->title);
		}

		return $options;
	}

	/**
	 * Method to get count of categories created & published.
	 *
	 * @return	int	$count	number of categories
	 *
	 * @since	1.0
	 */
	public function getCategoriesCount()
	{
		$user = Factory::getUser();
		$db   = Factory::getDbo();

		$query = $db->getQuery(true);
		$query->select('COUNT(c.id) AS count');
		$query->from('`#__categories` AS c');

		// Join users table
		// $query->join('LEFT', '#__users AS u ON u.id = c.created_user_id');
		$query->where('c.extension="com_tmt.questions"');

		// Get only published categories.
		$query->where('c.published=1');

		// $query->where('c.created_user_id=' . JFactory::getUser()->id);

		$db->setQuery($query);
		$count = $db->loadResult();

		return $count;
	}
}
