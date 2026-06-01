<?php
/**
 * @package     Techjoomla
 * @subpackage  Search.lesson
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;

// Require the component's router file (Replace 'nameofcomponent' with the component your providing the search for
require_once JPATH_SITE . '/components/com_tjlms/helpers/route.php';

/**
 * Lesson Search plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Search.lesson
 * @since       1.0.0
 */
class PlgSearchTjlmslesson extends CMSPlugin
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
	 *
	 * @return  JTable    A database object
	 */
	public function onContentSearchAreas()
	{
			static $areas = array(
					'tjlmslesson' => 'PLG_SEARCH_TJLMSLESSONS_TJLMSLESSONS'
			);

			return $areas;
	}

	/**
	 * Lesson Search method
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

		$limit = $this->params->def('search_limit', 50);

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

			case 'newest':
			default:
				$order = 'a.start_date DESC';
				break;
		}

		$rows = array();
		$query = $db->getQuery(true);

		/*if ($sLesson && $limit > 0)*/
		{
			$query->clear();

			$query->select('a.id, a.title, a.start_date AS created, a.image')
				->select($query->concatenate(array('a.short_desc', 'a.description')) . ' AS text, ' . '\'2\' AS browsernav')

				->from('#__tjlms_lessons AS a')
				->where(
					'(' . $where . ') AND a.state=1 '
				)
				->group('a.id, a.title, a.start_date')
				->order($order);

			$db->setQuery($query, 0, $limit);
			$list = $db->loadObjectList();
			$limit -= count($list);

			if (isset($list))
			{
				foreach ($list as $key => $item)
				{
					$list[$key]->href = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=lesson&tmpl=component&lesson_id=' . $item->id, false);
					$list[$key]->section = '';
				}
			}

			$rows[] = $list;
		}

		$results = array();

		if (count($rows))
		{
			foreach ($rows as $row)
			{
				$new_row = array();

				foreach ($row as $article)
				{
					if (SearchHelper::checkNoHTML($article, $searchText, array('text', 'title', 'metadesc', 'metakey')))
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
