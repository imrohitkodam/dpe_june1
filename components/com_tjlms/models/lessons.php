<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Model for lessons
 *
 * @since  1.3.8
 */

class TjlmsModelLessons extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since    1.3.3
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'l.id',
				'title', 'l.title',
				'format', 'l.format',
				'catid', 'l.catid',
				'start_date', 'l.start_date',
				'end_date', 'l.end_date'
			);
		}

		$this->tjLmsParams = ComponentHelper::getParams('com_tjlms');
		parent::__construct($config);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.3.8
	 */
	protected function getListQuery()
	{
		// Get the instance of db.
		$db = $this->getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$db->qn(
				array('l.id', 'l.title', 'l.description', 'l.no_of_attempts', 'l.attempts_grade',
					'l.consider_marks','l.format','l.total_marks','l.passing_marks','l.image'
					)
				)
			);
		$query->from($db->qn('#__tjlms_lessons', 'l'));
		$query->join('INNER', $db->qn('#__categories', 'c') . ' ON (' . $db->qn('c.id') . ' = ' . $db->qn('l.catid') . ')');

		$lessonEndDate = $this->state->get('filter.end_date');

		if (!empty($lessonEndDate))
		{
			$query->where('(' . $db->qn('l.end_date') . " >= " . $db->q($lessonEndDate) .
			'OR' . $db->qn('l.end_date') . " = " . $db->q('0000-00-00 00:00:00') . ')');
		}

		$currentDate = Factory::getDate()->toSql();

		/* Check the start date, end date, state and in_lib values.
		$query->where($db->qn('l.start_date') . " <= " . $db->q($currentDate));
		$query->where($db->qn('l.end_date') . " >= " . $db->q($currentDate));*/
		$query->where($db->qn('l.state') . ' = ' . $db->q('1'));

		$inLibrary = $this->state->get("filter.in_lib", 1);

		if (is_numeric($inLibrary))
		{
			$query->where($db->qn('l.in_lib') . ' = ' . (int) $inLibrary);
		}

		$courseId = $this->state->get("filter.course_id");

		if (!empty($courseId))
		{
			$query->where($db->qn('l.course_id') . ' = ' . (int) $courseId);
		}

		$considerMarks = (Int) $this->state->get("filter.consider_marks");

		if ($considerMarks)
		{
			$query->where($db->qn('l.consider_marks') . ' = ' . $considerMarks);
		}

		$query->where($db->qn('l.media_id') . " >  0");

		// Add lesson format based filter
		$lessonFormat = $this->state->get('filter.format');

		if (!empty($lessonFormat))
		{
			$query->where($db->qn('l.format') . ' = ' . $db->q($lessonFormat));
		}

		// Add category based filter.
		$showSubCat = (Int) $this->state->get('filter.showSubCat');
		$catId      = (Int) $this->state->get('filter.catid');

		if (!empty($catId))
		{
			// Show the subcategories if menu allows.
			if ($showSubCat)
			{
				$catTable = Table::getInstance('Category', 'JTable');
				$catTable->load((int) $catId);
				$query->where($db->qn('c.lft') . ' >= ' . (Int) $catTable->lft);
				$query->where($db->qn('c.rgt') . ' <= ' . (Int) $catTable->rgt);
			}
			else
			{
				$query->where($db->qn('l.catid') . ' = ' . $db->escape($catId));
			}
		}

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->qn('l.id') . ' = ' . (int) substr($db->escape($search), 3));
			}
			else
			{
				$query->where($db->qn('l.title') . ' LIKE ' . $db->Quote('%' . $db->escape($search, true) . '%'));
			}
		}

		$query->order('l.ordering', 'ASC');

		return $query;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the model
	 * configuration flag to ignore the request is set.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.3.8
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Set menu params category in the state.
		$app		= Factory::getApplication();
		$menuParams = new Registry;

		if ($menu = $app->getMenu()->getActive())
		{
			$menuParams->loadString($menu->getParams());
			$catId 		= (Int) $menuParams->get('catid', '');
			$showSubCat = (Int) $menuParams->get('showSubcatLessons', '0');

			// If the menu category is not set then only get the filters value.
			if (empty($catId))
			{
				$catId 		= (Int) $this->getUserStateFromRequest($this->context . '.filter.catid', 'filter_catid', '0', 'INT');
				$showSubCat = 0;
			}
			else
			{
				// If menu category is set and child category selected from view then set the child category to filter.
				$allCats	= $this->getSubCategories($catId);
				$viewCatId	= (Int) $this->getUserStateFromRequest($this->context . '.filter.catid', 'filter_catid', '0', 'INT');

				if (in_array($viewCatId, $allCats))
				{
					$catId = $viewCatId;
				}
			}

			// Set the category.
			$this->setState('filter.catid', (Int) $catId);
			$this->setState('filter.showSubCat', $showSubCat);
		}

		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.3.8
	 */
	public function getItems()
	{
		$items = parent::getItems();
		$app   = Factory::getApplication();

		// Set menu params in the state
		$menuParams = new Registry;

		// Get the lesson image size from menu params.
		if ($menu = $app->getMenu()->getActive())
		{
			$menuParams->loadString($menu->getParams());
		}

		$lessonImagesSize = $menuParams->get('lesson_images_size', 'media_s');

		$lessonIds = array_map(
			function($item)	{
				return $item->id;
			},
			$items
		);

		JLoader::import('components.com_tjlms.models.lessontrack', JPATH_SITE);
		$lessonTrackmodel = BaseDatabaseModel::getInstance('lessonTrack', 'tjlmsModel');
		$lessonTrackmodel->setState("unique_users", true);
		$lessonTrackmodel->setState("lesson_id", $lessonIds);
		$attemptsCount    = $lessonTrackmodel->getItems();

		$attemptsCountPivoted = ArrayHelper::pivot((array) $attemptsCount, 'lesson_id');

		JLoader::import('components.com_tjlms.models.lesson', JPATH_SITE);
		$lessonModel          = BaseDatabaseModel::getInstance('lesson', 'tjlmsModel');

		// Check if items are present or not.
		if (!empty($items))
		{
			$tjlmsLessonHelper = new TjlmsLessonHelper;
			$comtjlmsHelper    = new comtjlmsHelper;

			foreach ($items as $ind => $obj)
			{
				// Get the attempt count for lesson and likes for the lesson.
				$items[$ind]->attemptForLessons = isset($attemptsCountPivoted[$obj->id]) ? $attemptsCountPivoted[$obj->id]->unique_users: 0;
				$obj->likesForLesson            = $comtjlmsHelper->getLikesForItem($obj->id, 'com_tjlms.lesson');
				$obj->image                     = $lessonModel->getLessonImage($obj->id, $lessonImagesSize);
				$items[$ind]->url               = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=lesson&lesson_id=' . $obj->id);
			}
		}

		return $items;
	}

	/**
	 * Method to get subcategories for the menu category.
	 *
	 * @param   INT  $menuCatId  Menu category.
	 *
	 * @return  mixed  An array of categories.
	 *
	 * @since   1.3.8
	 */
	public function getSubCategories($menuCatId)
	{
		// Get the menu categoroy details.
		$catTable = Table::getInstance('Category', 'JTable');
		$catTable->load((int) $menuCatId);

		// Get the categories with right access and right id.
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->qn('c.id'));
		$query->from($db->qn('#__categories', 'c'));
		$query->where($db->qn('c.lft') . ' >= ' . (Int) $catTable->lft);
		$query->where($db->qn('c.rgt') . ' <= ' . (Int) $catTable->rgt);
		$db->setQuery($query);

		return $db->loadColumn();
	}
}
