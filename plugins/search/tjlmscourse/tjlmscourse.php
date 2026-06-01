<?php
/**
 * @package     Techjoomla
 * @subpackage  Search.lesson
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


// No direct access
defined('_JEXEC') or die( 'Restricted access');
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

// Require the component's router file (Replace 'nameofcomponent' with the component your providing the search for
require_once JPATH_SITE . '/components/com_tjlms/helpers/route.php';

/**
 * Course Search plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Search.course
 * @since       1.0.0
 */
class PlgSearchTjlmscourse extends CMSPlugin
{
	/**
	 * Constructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @since       1.6
	 */
	public function __construct($subject, $config)
	{
			parent::__construct($subject, $config);
			$this->loadLanguage();
	}

	/**
	 * Content Search method
	 * Define a function to return an array of search areas. Replace 'nameofplugin' with the name of your plugin
	 *
	 * @return  JTable    A database object
	 */
	public function onContentSearchAreas()
	{
			static $areas = array(
					'tjlmscourse' => 'PLG_SEARCH_TJLMSCOURSE_TJLMSCOURSE'
			);

			return $areas;
	}

	/**
	 * Course Search method
	 * The sql must return the following fields that are used in a common display
	 * routine: href, title, section, created, text, browsernav
	 *
	 * @param   string  $text      Search String.
	 * @param   string  $phrase    Option exact|any|all
	 * @param   string  $ordering  Option newest|oldest|popular|alpha|category
	 * @param   mixed   $areas     An array if the search it to be restricted to areas, null if search all
	 *
	 * @return  JTable    A database object
	 */
	public function onContentSearch($text, $phrase = '', $ordering = '', $areas = null)
	{
		$db = Factory::getDbo();
		$app = Factory::getApplication();
		$user = Factory::getUser();
		$groups = implode(',', $user->getAuthorisedViewLevels());

		$tag = Factory::getLanguage()->getTag();

		/*require_once JPATH_SITE . '/components/com_tjlms/helpers/route.php';*/
		$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

		if (!class_exists('comtjlmsHelper'))
		{
			// Require_once $path;
			JLoader::register('comtjlmsHelper', $path);
			JLoader::load('comtjlmsHelper');
		}

		$comtjlmsHelper = new comtjlmsHelper;

		require_once JPATH_ADMINISTRATOR . '/components/com_search/helpers/search.php';

		$searchText = $text;

		if (is_array($areas))
		{
			if (!array_intersect($areas, array_keys($this->onContentSearchAreas())))
			{
				return array();
			}
		}

		$sCourse = $this->params->get('search_tjlmscourse', 1);
		$sArchived = $this->params->get('search_archived', 0);
		$limit = $this->params->def('search_limit', 50);
		$sLesson = $this->params->get('search_in_tjlmslesson', 1);

		$nullDate = $db->getNullDate();
		$date = Factory::getDate();
		$now = $date->toSql();

		$text = trim($text);

		if ($text == '')
		{
			return array();
		}

		switch ($phrase)
		{
			case 'exact':
				$text = $db->quote('%' . $db->escape($text, true) . '%', false);
				$wheres2 = array();
				$wheres2[] = 'a.title LIKE ' . $text;
				$wheres2[] = 'a.short_desc LIKE ' . $text;
				$wheres2[] = 'a.description LIKE ' . $text;
				$where = '(' . implode(') OR (', $wheres2) . ')';
				break;

			case 'all':
			case 'any':
			default:
				$words = explode(' ', $text);
				$wheres = array();

				foreach ($words as $word)
				{
					$word = $db->quote('%' . $db->escape($word, true) . '%', false);
					$wheres2 = array();
					$wheres2[] = 'a.title LIKE ' . $word;
					$wheres2[] = 'a.short_desc LIKE ' . $word;
					$wheres2[] = 'a.description LIKE ' . $word;

					if ($sLesson == '1')
					{
						$wheres2[] = 'l.title LIKE ' . $word;
					}

					$wheres[] = implode(' OR ', $wheres2);
				}

				$where = '(' . implode(($phrase == 'all' ? ') AND (' : ') OR ('), $wheres) . ')';

				break;
		}

		switch ($ordering)
		{
			case 'oldest':
				$order = 'a.start_date ASC';
				break;

			case 'alpha':
				$order = 'a.title ASC';
				break;

			case 'category':
				$order = 'c.title ASC, a.title ASC';
				break;

			case 'newest':
			default:
				$order = 'a.start_date DESC';
				break;
		}

		$rows = array();
		$query = $db->getQuery(true);

		// Search articles
		if ($sCourse && $limit > 0)
		{
			$query->clear();
			$case_when1 = ' CASE WHEN ';
			$case_when1 .= $query->charLength('c.alias', '!=', '0');
			$case_when1 .= ' THEN ';
			$c_id = $query->castAsChar('c.id');
			$case_when1 .= $query->concatenate(array($c_id, 'c.alias'), ':');
			$case_when1 .= ' ELSE ';
			$case_when1 .= $c_id . ' END as catslug';

			if ($sLesson != '1')
			{
				$query->select('a.id, a.title AS title, a.start_date AS created')
					->select($query->concatenate(array('a.short_desc', 'a.description')) . ' AS text')
					->select('c.title AS section,' . $case_when1 . ', ' . '\'2\' AS browsernav')

					->from('#__tjlms_courses AS a')
					->join('INNER', '#__categories AS c ON c.id=a.catid')
					->where(
						'(' . $where . ') AND a.state=1 AND c.published = 1 AND a.access IN (' . $groups . ') '
							. 'AND c.access IN (' . $groups . ') '
					)
					->group('a.id, a.title, a.start_date, c.title,  c.alias, c.id')
					->order($order);
			}
			else
			{
				$query->select('a.id, a.title AS title, a.start_date AS created')
					->select($query->concatenate(array('a.short_desc', 'a.description')) . ' AS text')
					->select('c.title AS section,' . $case_when1 . ', ' . '\'2\' AS browsernav')
					->from('#__tjlms_courses AS a')
					->join('INNER', '#__categories AS c ON c.id=a.catid')
					->join('LEFT', '#__tjlms_lessons AS l ON a.id = l.course_id')

					->where(
						'(' . $where . ') AND a.state=1 AND c.published = 1 AND a.access IN (' . $groups . ') '
							. 'AND c.access IN (' . $groups . ') '
					)
					->group('a.id, a.title, a.start_date, c.title,  c.alias, c.id')
					->order($order);
			}

			$db->setQuery($query, 0, $limit);
			$list = $db->loadObjectList();
			$limit -= count($list);

			if (isset($list))
			{
				foreach ($list as $key => $item)
				{
					$list[$key]->href = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $item->id, false);
				}
			}

			$rows[] = $list;
		}

		// Search archived content
		if ($sArchived && $limit > 0)
		{
			$query->clear();
			$case_when1 = ' CASE WHEN ';
			$case_when1 .= $query->charLength('c.alias', '!=', '0');
			$case_when1 .= ' THEN ';
			$c_id = $query->castAsChar('c.id');
			$case_when1 .= $query->concatenate(array($c_id, 'c.alias'), ':');
			$case_when1 .= ' ELSE ';
			$case_when1 .= $c_id . ' END as catslug';

			$query->select(
				'a.title AS title, a.start_date AS created, '
					. $query->concatenate(array("a.short_desc", "a.description")) . ' AS text,'
					. $case_when1 . ', '
					. 'c.title AS section, \'2\' AS browsernav'
			);

			$query->from('#__tjlms_courses AS a')
				->join('INNER', '#__categories AS c ON c.id=a.catid AND c.access IN (' . $groups . ')')
				->where(
					'(' . $where . ') AND a.state = 2 AND c.published = 1 AND a.access IN (' . $groups . ') AND c.access IN (' . $groups . ') '
				)
				->order($order);
			$db->setQuery($query, 0, $limit);
			$list3 = $db->loadObjectList();

			// Find an itemid for archived to use if there isn't another one
			$item = $app->getMenu()->getItems('link', 'index.php?option=com_tjlms&view=archive', true);
			$itemid = isset($item->id) ? '&Itemid=' . $item->id : '';

			if (isset($list3))
			{
				foreach ($list3 as $key => $item)
				{
					$date = Factory::getDate($item->created);

					$created_month = $date->format("n");
					$created_year = $date->format("Y");

					$list3[$key]->href = Route::_('index.php?option=com_tjlms&view=archive&year=' . $created_year . '&month=' . $created_month . $itemid);
				}
			}

			$rows[] = $list3;
		}

		$results = array();

		if (count($rows))
		{
			foreach ($rows as $row)
			{
				$new_row = array();

				foreach ($row as $article)
				{
					if ($sLesson != '1')
					{
						if (SearchHelper::checkNoHTML($article, $searchText, array('text', 'title', 'metadesc', 'metakey')))
						{
							$new_row[] = $article;
						}
					}
					else
					{
						$new_row[] = $article;
					}
				}

				$results = array_merge($results, (array) $new_row);
			}
		}

		return $results;
	}
}
