<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_trading
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;

jimport('joomla.plugin.plugin');

/**
 * User Api.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_api
 *
 * @since       1.0
 */
class LmsApiResourceActivities extends ApiResource
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

		JLoader::import('components.com_tjlms.models.activities', JPATH_ADMINISTRATOR);
		$activitiesModel = BaseDatabaseModel::getInstance('activities', 'TjlmsModel', array('ignore_request' => true));

		$input 	 		= Factory::getApplication()->input;
		$filter			= InputFilter::getInstance();
		$filtersArray 	= $input->get('filters', array(), 'ARRAY');

		if (isset($filtersArray['search']))
		{
			$search = $filter->clean($filtersArray['search'], 'STRING');
			$activitiesModel->setState('com_tjlms.filter.filter_search', $search);
		}

		if (isset($filtersArray['startdate']))
		{
			$startdate = $filter->clean($filtersArray['startdate']);
			$activitiesModel->setState('com_tjlms.filter.startdate', $startdate);
		}

		if (isset($filtersArray['enddate']))
		{
			$enddate = $filter->clean($filtersArray['enddate']);
			$activitiesModel->setState('com_tjlms.filter.enddate', $enddate);
		}

		if (isset($filtersArray['type']))
		{
			$type = $filter->clean($filtersArray['type'], 'STRING');

			$actions = array('course.create' => 'COURSE_CREATED', 'course.enroll' => 'ENROLL',
			'course.recommend' => 'COURSE_RECOMMENDED', 'course.complete' => 'COURSE_COMPLETED',
			'lesson.attemptstart' => 'ATTEMPT', 'lesson.attemptend' => 'ATTEMPT_END',
			'lms.login' => 'LOGIN' , 'lms.logout' => 'LOGOUT'
			);

			$activitiesModel->setState('com_tjlms.filter.type', $actions[$type]);
		}

		$limit = $input->getInt('limit', 0);
		$activitiesModel->setState('list.limit', $limit);
		$limitstart = $input->getInt('limitstart', 0);
		$activitiesModel->setState('list.start', $limitstart);

		$this->items = $activitiesModel->getItems();

		// Get the validation messages.
		$errors = $activitiesModel->getErrors();

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
		$result->total = $activitiesModel->gettotal();
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
			JLoader::import('components.com_tjlms.models.enrolment', JPATH_SITE);
			JLoader::import('components.com_tjlms.helpers.tracking', JPATH_SITE);

			$enrollmentModel 	= new TjlmsModelEnrolment;
			$trackingHelper		= new ComtjlmstrackingHelper;

			$actions = array('COURSE_CREATED' => 'course.create', 'ENROLL' => 'course.enroll',
			'COURSE_RECOMMENDED' => 'course.recommend', 'COURSE_COMPLETED' => 'course.complete',
			'ATTEMPT' => 'lesson.attemptstart', 'ATTEMPT_END' => 'lesson.attemptend',
			'LOGIN' => 'lms.login', 'LOGOUT' => 'lms.logout'
			);

			foreach ($this->items as $ind => &$objCopy)
			{
				// Course Metadata
				$obj = new stdClass;
				$obj->id				= $objCopy->id;
				$obj->type			 	= $actions[$objCopy->action];
				$obj->actor_id			= $objCopy->actor_id;

				$obj->actor = new stdClass;
				$obj->actor->id 		= $objCopy->actor_id;
				$obj->actor->type 		= 'user';
				$obj->actor->name		= $objCopy->name;

				$obj->object_id	= '';
				$obj->object = new stdClass;
				$obj->object->id = '';
				$obj->object->type = '';
				$obj->object->name = '';

				$obj->target_id		= '';
				$obj->target 		= new stdClass;
				$obj->target->id	= '';
				$obj->target->type	= '';
				$obj->target->name	= '';

				switch ($obj->type)
				{
					case "course.enroll":
						$obj->object_id		= $enrollmentModel->checkUserEnrollment($objCopy->course_id, $objCopy->actor_id) ? 1 : 0;
						$obj->object->id	= $obj->object_id;
						$obj->object->type	= "enrollment";
						$obj->target_id		= $objCopy->course_id;
						$obj->target->id	= $objCopy->course_id;
						$obj->target->type	= 'course';
						$obj->target->name	= $objCopy->title;
						break;

					case "lesson.attemptstart":
					case "lesson.attemptend":
						$params 			= json_decode($objCopy->params);

						$lessonTrack = $trackingHelper->istrackpresent($objCopy->element_id, $params->attempt, $obj->actor->id);

						if ($lessonTrack)
						{
							$obj->object_id 	= $lessonTrack->id;
							$obj->object->id	= $obj->object_id;
							$obj->object->type	= "attempt";
							$obj->object->name	= $params->attempt;
						}

						$obj->target_id		= $objCopy->element_id;
						$obj->target->id	= $objCopy->element_id;
						$obj->target->type	= 'lesson';
						$obj->target->name	= $objCopy->element;
						break;

					case "course.create":
					case "course.complete":
						$obj->object_id		= $objCopy->course_id;
						$obj->object->id	= $objCopy->course_id;
						$obj->object->type	= 'course';
						$obj->object->name	= $objCopy->title;
						break;

					case "course.recommend":
						$obj->object_id 	= $objCopy->course_id;
						$obj->object->id	= $objCopy->course_id;
						$obj->object->type	= 'course';
						$obj->object->name	= $objCopy->title;

						$params = json_decode($objCopy->params);
						$target = Factory::getUser($params->target_id);

						if ($target->id)
						{
							$obj->target_id		= $params->target_id;
							$obj->target->id	= $params->target_id;
							$obj->target->type 	= 'user';
							$obj->target->name 	= $target->name;
						}
						else
						{
							unset($this->items[$ind]);
						}

						break;
				}

				$obj->template			= '';
				$obj->formatted_text	= $objCopy->actionString;
				$obj->created_date		= $objCopy->added_time;
				$obj->created_date		= $objCopy->added_time;

				// Assign the new Object
				$objCopy = $obj;

				$obj = null;
			}
		}
	}
}
