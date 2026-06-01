<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

JLoader::import('components.com_tjlms.models.courses', JPATH_SITE);
JLoader::import('components.com_tjlms.models.manageenrollments', JPATH_ADMINISTRATOR);

/**
 * Class to suggest course to a user
 *
 * @since  1.3.22
 */
class TjSuggestCourses
{
	public static $hard   = 'hard';

	public static $medium = 'medium';

	public static $easy   = 'easy';

	/**
	 *  Get all enrolled courses
	 *
	 * @return  object|boolean
	 *
	 * @since   1.3.22
	 */
	public static function enrolledCourses()
	{
		$user = Factory::getUser();

		if (!$user->id)
		{
			return false;
		}

		$enrollmentsModel = BaseDatabaseModel::getInstance('Manageenrollments', 'TjlmsModel', array('ignore_request' => true));

		$enrollmentsModel->setState('filter.user_id', $user->id);

		return $enrollmentsModel->getItems();
	}

	/**
	 *  Fetch random courses
	 *
	 * @return  object
	 *
	 * @since   1.3.22
	 */
	public static function getRandomCourses()
	{
		$coursesModel = BaseDatabaseModel::getInstance('courses', 'TjlmsModel', array('ignore_request' => true));

		$menuParams = new Registry;
		$coursesModel->setState('params', $menuParams);

		// $coursesModel->setState('list.limit', 3);

		return $coursesModel->getItems();
	}

	/**
	 *  Get suggested courses categories
	 *
	 * @return  array
	 *
	 * @since   1.3.22
	 */
	public static function getSuggestedCourses()
	{
		$returnArray = array ();

		$courses = self::enrolledCourses();

		$suggestFromEnrolledCategories = 1;

		if (empty($courses))
		{
			$courses                       = self::getRandomCourses();
			$suggestFromEnrolledCategories = 0;
		}

		$courseIds = array_map(
			function($e)
			{
				return is_object($e) ? ($e->course_id ? $e->course_id : $e->id) : ($e['course_id'] ? $e['course_id'] : $e['id']);
			},
			$courses
		);

		$categoryIds = array_map(
			function($e)
			{
				return is_object($e) ? $e->catid : $e['catid'];
			},
			$courses
		);

		$returnArray['category']                             = $categoryIds;
		$returnArray['courses']                              = $courseIds;
		$returnArray['suggested_from_enrolled_categegories'] = $suggestFromEnrolledCategories;

		return $returnArray;
	}

	/**
	 *  Check correct answers count
	 *
	 * @param   Array  $questions  Object of questions and its answers from chatbot
	 *
	 * @return  integer|boolean
	 *
	 * @since   1.3.22
	 */
	public static function correctAnswersCount($questions)
	{
		$user = Factory::getUser();

		if (!$user->id)
		{
			return false;
		}

		$count = 0;

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tmt/tables');
		$answerTable = Table::getInstance('Answers', 'TmtTable');

		foreach ($questions as $qVal)
		{
			if (!empty($qVal['answers']))
			{
				foreach ($qVal['answers'] as $aVal)
				{
					$answerTable->load(array("id" => $aVal['id'], "question_id" => $qVal['id']));

					if ($answerTable->is_correct)
					{
						$count++;

						break;
					}
				}
			}
		}

		return $count;
	}

	/**
	 *  Suggest courses based on correct answers
	 *
	 * @param   Array  $questions  Object of questions and its answers from chatbot
	 * @param   Array  $options    Extra options for filters
	 *
	 * @return  object
	 *
	 * @since   1.3.22
	 */
	public static function suggestCourses($questions = array(), $options = array())
	{
		$coursesModel = BaseDatabaseModel::getInstance('courses', 'TjlmsModel', array('ignore_request' => true));

		if (!empty($questions))
		{
			$tagFilter          = array();
			$tagFilter['title'] = self::$easy;

			$correctAnswerCount = self::correctAnswersCount($questions);

			if ($correctAnswerCount >= 3)
			{
				$tagFilter['title'] = self::$hard;
			}
			elseif ($correctAnswerCount == 2)
			{
				$tagFilter['title'] = self::$medium;
			}

			$tag = TagsHelper::searchTags($tagFilter);

			$tagsArray   = array ();
			$tagsArray[] = $tag[0]->value;

			if (!empty($tagsArray))
			{
				$coursesModel->setState('filter.tag', $tagsArray);
			}

			// Get question category Title
			$questionCategoryTitle = array_map(
				function($e)
				{
					return (is_object($e) ? $e->category : $e['category']);
				},
				$questions
			);

			$questionCategoryTitle = array_unique($questionCategoryTitle);

			$categoryIds = self::getCategoryIds($questionCategoryTitle);

			if (!empty($categoryIds))
			{
				$coursesModel->setState('com_tjlms.filter.category_filter', $categoryIds);
			}
		}

		$suggestedCourses = self::getSuggestedCourses();

		$menuParams = new Registry;
		$coursesModel->setState('params', $menuParams);

		// Set filters
		if (empty($questions))
		{
			$coursesModel->setState('com_tjlms.filter.category_filter', $suggestedCourses['category']);
		}

		if ($suggestedCourses['suggested_from_enrolled_categegories'])
		{
			$coursesModel->setState('com_tjlms.filter.course_exclude', $suggestedCourses['courses']);
		}
		else
		{
			$coursesModel->setState('com_tjlms.filter.course_include', $suggestedCourses['courses']);
		}

		if (!empty($options['limit']))
		{
			$coursesModel->setState('list.limit', $options['limit']);
		}

		if (!empty($options['limitstart']))
		{
			$coursesModel->setState('list.start', $options['limitstart']);
		}

		return $coursesModel->getItems();
	}

	/**
	 *  Get Course category ids by matching Question categoty title
	 *
	 * @param   Array  $questionCategoryTitle  Question categoty title
	 *
	 * @return  object
	 *
	 * @since   1.3.22
	 */
	public static function getCategoryIds($questionCategoryTitle)
	{
		$categoryObj = Categories::getInstance('tjlms');

		$categoryIds = array ();

		if ($categoryObj)
		{
			$categoryList = $categoryObj->get('root')->getChildren(true);

			if (!empty($categoryList))
			{
				foreach ($categoryList as $value)
				{
					if (in_array($value->title, $questionCategoryTitle))
					{
						$categoryIds[] = $value->id;
					}
				}
			}
		}

		return $categoryIds;
	}
}
