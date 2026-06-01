<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,api,lms
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * User Api.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_api
 *
 * @since       1.0
 */
class LmsApiResourceCourses extends ApiResource
{
	protected $items = array();

	/**
	 * Function post
	 *
	 * @return void
	 */
	public function post()
	{
		$this->plugin->setResponse("Use GET method");

		return;
	}

	/**
	 * Function get for users record.
	 *
	 * @return void
	 */
	public function get()
	{
		$result = new stdClass;

		JLoader::import('components.com_tjlms.models.courses', JPATH_SITE);
		$coursesModel = BaseDatabaseModel::getInstance('courses', 'TjlmsModel', array('ignore_request' => true));

		$input        = Factory::getApplication()->input;
		$filter       = InputFilter::getInstance();
		$filtersArray = $input->get('filters', array(), 'ARRAY');

		if (isset($filtersArray['search']))
		{
			$search = $filter->clean($filtersArray['search'], 'STRING');
			$coursesModel->setState('com_tjlms.filter.filter_search', $search);
		}

		if (isset($filtersArray['category']))
		{
			$category = $filter->clean($filtersArray['category'], 'INT');
			$coursesModel->setState('com_tjlms.filter.category_filter', $category);
		}

		if (isset($filtersArray['tag']))
		{
			$tag = $filter->clean($filtersArray['tag'], 'INT');
			$coursesModel->setState('filter.tag', $tag);
		}

		if (isset($filtersArray['type']))
		{
			$type = $filter->clean($filtersArray['type'], 'INT');
			$coursesModel->setState('com_tjlms.filter.course_type', $type);
		}

		if (isset($filtersArray['author']))
		{
			$author = $filter->clean($filtersArray['author'], 'INT');
			$coursesModel->setState('com_tjlms.filter.creator_filter', $author);
		}

		if (isset($filtersArray['courses_to_show']))
		{
			$flag = $filter->clean($filtersArray['courses_to_show'], 'STRING');

			if ($filtersArray['courses_to_show'] == 'featured')
			{
				$coursesModel->setState('com_tjlms.filter.featured', 1);
			}
			elseif (in_array($filtersArray['courses_to_show'], array('enrolled','liked','upcomingCourses')))
			{
				$coursesModel->courses_to_show = $flag;
			}
			elseif ($filtersArray['courses_to_show'] == 'completed')
			{
				$coursesModel->setState('com_tjlms.filter.course_status', 'completedcourses');
			}
		}

		$limit      = $input->getInt('limit', 0);
		$coursesModel->setState('list.limit', $limit);
		$limitstart = $input->getInt('limitstart', 0);
		$coursesModel->setState('list.start', $limitstart);

		/*set menu params in the state*/
		$menuParams = new Registry;
		$coursesModel->setState('params', $menuParams);

		$this->items = $coursesModel->getItems();

		// Get the validation messages.
		$errors = $coursesModel->getErrors();

		if (!empty($errors))
		{
			$msg = array();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$msg[] = $errors[$i]->getMessage();
				}
				else
				{
					$msg[] = $errors[$i];
				}
			}

			ApiError::raiseError("400", implode("\n", $msg), 'APIValidationException');
		}

		// Process data for APIs
		$this->getApiItems();

		$result->result = $this->items;
		$result->total  = $coursesModel->gettotal();
		unset($this->items);
		$this->plugin->setResponse($result);

		return;
	}

	/**
	 * Method to process courses data for API.
	 *
	 * @return  null
	 *
	 * @since   1.0.0
	 */
	private function getApiItems()
	{
		if (!empty($this->items))
		{
			$params  = ComponentHelper::getParams('com_tjlms');
			$userCol = $params->get('show_user_or_username', 'name');
			$userCol = ($userCol == 'username') ? 'username' : 'name';

			JLoader::import('components.com_tjlms.helpers.courses', JPATH_SITE);
			JLoader::import('components.com_tjlms.helpers.main', JPATH_SITE);
			JLoader::import('components.com_tjlms.models.enrolment', JPATH_SITE);
			JLoader::import('components.com_tjlms.helpers.tracking', JPATH_SITE);

			$tjlmsCoursesHelper = new TjlmsCoursesHelper;
			$comtjlmsHelper 	= new comtjlmsHelper;
			$enrollmentModel 	= new TjlmsModelEnrolment;
			$trackingHelper		= new ComtjlmstrackingHelper;

			$userId = Factory::getUser()->id;

			foreach ($this->items as $ind => &$objCopy)
			{
				// Course Metadata
				$obj                      = new stdClass;
				$obj->course_id           = $objCopy->id;
				$obj->course_title        = $objCopy->title;
				$obj->course_description  = $objCopy->short_desc;
				$obj->course_state        = $objCopy->state;
				$obj->course_type         = $objCopy->type;
				$obj->course_cat_id       = $objCopy->catid;
				$obj->course_cat_title    = $tjlmsCoursesHelper->getCourseCat($objCopy, 'title');
				$obj->course_creator_id   = $objCopy->created_by;
				$obj->course_creator_name = Factory::getUser($objCopy->created_by)->$userCol;
				$obj->course_alias        = $objCopy->alias;
				$obj->course_image        = $objCopy->image;
				$obj->course_url          = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $objCopy->id, false, -1);

				// Like and dislike count
				$itemLikeDislike = $comtjlmsHelper->getItemJlikes($objCopy->id, 'com_tjlms.course');

				$obj->course_no_of_likes    = isset($itemLikeDislike['likes']) ? (int) $itemLikeDislike['likes'] : 0;
				$obj->course_no_of_dislikes = isset($itemLikeDislike['dislikes']) ? (int) $itemLikeDislike['dislikes'] : 0;

				// Enrollment Detail
				$enrolled_count          = count($comtjlmsHelper->getCourseEnrolledUsers($objCopy->id));
				$obj->enrolled_users_cnt = $comtjlmsHelper->custom_number_format($enrolled_count);

				// Paid Plan
				$obj->course_subscription_plans = array();

				if ($objCopy->type)
				{
					$obj->course_subscription_plans = $tjlmsCoursesHelper->getCourseSubplans($objCopy->id);
					$this->processSubscription($obj->course_subscription_plans);
				}

				$obj->user_enrolled = $enrollmentModel->checkUserEnrollment($objCopy->id, $userId) ? 1 : 0;
				$obj->user_progress = new stdClass;

				// Course Track
				if ($obj->user_enrolled)
				{
					$courseTrack = $trackingHelper->getCourseTrackEntry($objCopy->id, $userId);

					$user_progress                          = new stdClass;
					$user_progress->no_of_lesson            = $courseTrack['totalLessons'];
					$user_progress->no_of_completed_lessons = $courseTrack['completedLessons'];
					$user_progress->completion_percentage   = $courseTrack['completionPercent'];
					$obj->user_progress                     = $user_progress;
				}

				// Assign the new Object
				$objCopy = $obj;

				$obj = null;
			}
		}
	}

	/**
	 * Method to process courses data for API.
	 *
	 * @param   MIX  &$plans  Plans data
	 *
	 * @return  null
	 *
	 * @since   1.0.0
	 */
	private function processSubscription(&$plans)
	{
		if (!empty($plans))
		{
			foreach ($plans as &$plan)
			{
				// Plan Metadata
				$obj                    = new stdClass;
				$obj->plan_id           = $plan->id;
				$obj->plan_time_measure = $plan->time_measure;
				$obj->plan_price        = $plan->price;
				$obj->plan_duration     = $plan->duration;

				// Assign the new Object
				$plan = $obj;

				$obj = null;
			}
		}
	}
}
