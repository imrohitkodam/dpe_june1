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
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

jimport('joomla.plugin.plugin');

/**
 * User Api.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_api
 *
 * @since       1.0
 */
class LmsApiResourceCourse extends ApiResource
{
	protected $item = array();

	protected $userId = 0;

	/**
	 * Function get for users record.
	 *
	 * @return void
	 */
	public function get()
	{
		$result = new stdClass;
		$this->userId = Factory::getUser()->id;

		JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
		$courseModel = BaseDatabaseModel::getInstance('course', 'TjlmsModel', array('ignore_request' => true));

		$input 	 	= Factory::getApplication()->input;
		$course_id 	= $input->get('id', 0, 'INT');

		if (empty($course_id))
		{
			ApiError::raiseError("400", Text::_('PLG_API_TJLMS_REQUIRED_COURSE_DATA_EMPTY_MESSAGE'), 'APIValidationException');
		}
		else
		{
			$this->item = $courseModel->getData($course_id);

			$this->item->enrolled_users = $courseModel->getallenroledUsersinfo($course_id);
			$this->item->course_info    = $courseModel->getcourseinfo($course_id);

			// Get the validation messages.
			$errors = $courseModel->getErrors();

			if (!empty($errors))
			{
				$code = 500;
				$msg  = array();

				// Push up to three validation messages out to the user.
				for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
				{
					if ($errors[$i] instanceof Exception)
					{
						$code  = $errors[$i]->getCode();
						$msg[] = $errors[$i]->getMessage();
					}
					else
					{
						$msg[] = $errors[$i];
					}
				}

				ApiError::raiseError("400", implode("\n", $msg));
			}
			else
			{
				// Process data for APIs
				$this->getApiItem();

				$result->result = $this->item;
				unset($this->item);
				$this->plugin->setResponse($result);

				return;
			}
		}
	}

	/**
	 * Method to process courses data for API.
	 *
	 * @return  null
	 *
	 * @since   1.0.0
	 */
	private function getApiItem()
	{
		if (!empty($this->item))
		{
			// Filter course tags data
			if (!empty($this->item->course_tags->itemTags))
			{
				foreach ($this->item->course_tags->itemTags as &$tag)
				{
					$obj = new stdClass;
					$obj->title = $tag->title;
					$obj->alias = $tag->alias;

					$tag = $obj;
					$obj = null;
				}

				unset($this->item->course_tags->typeAlias);
			}

			// Filter Enrolled user data
			if (!empty($this->item->enrolled_users))
			{
				foreach ($this->item->enrolled_users as &$enrolled_users)
				{
					$obj = new stdClass;
					$obj->user_id 		= $enrolled_users->user_id;
					$obj->enrolled_on 	= $enrolled_users->enrolled_on_time;
					$obj->user_name 	= $enrolled_users->username;
					$obj->name 			= $enrolled_users->name;
					$obj->avatar 		= $enrolled_users->avatar;
					$obj->profileurl 	= $enrolled_users->profileurl;

					$enrolled_users = $obj;
				}
			}

			$this->item->course_subscription_plans = $this->item->subscriptionPlans;

			// Filter course subscription plans
			if (!empty($this->item->course_subscription_plans))
			{
				$this->processSubscription($this->item->course_subscription_plans);

				unset($this->item->subscriptionPlans);
			}

			$this->item->buy_url = null;

			if ($this->item->type == 1)
			{
				JLoader::import('components.com_tjlms.helpers.main', JPATH_SITE);
				$comtjlmsHelper = new ComtjlmsHelper;

				$this->item->buy_url = $comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=buy&course_id=' . $this->item->id, false, 1);
			}

			// Filter course like & dislike
			$this->item->course_no_of_likes	= $this->item->course_no_of_dislikes = 0;

			JLoader::import('components.com_jlike.models.jlike_likes', JPATH_SITE);
			$likesModel = BaseDatabaseModel::getInstance('jlike_Likes', 'JlikeModel', array('ignore_request' => true));

			// Like and dislike data
			$extraParams = array('plg_name' => 'jlike_tjlms', 'plg_type' => 'content');
			$likesData   = $likesModel->getData($this->item->id, 'com_tjlms.course', true, $extraParams);

			if (!empty($likesData))
			{
				$this->item->course_no_of_likes    = (int) $likesData['likecount'];
				$this->item->course_no_of_dislikes = (int) $likesData['dislikecount'];
				$this->item->course_likes          = $likesData['pwltcb'];
				unset($likesData);
			}

			$this->item->user_progress = new stdClass;

			JLoader::import('components.com_tjlms.helpers.tracking', JPATH_SITE);
			$comtjlmstrackingHelper = new comtjlmstrackingHelper;

			$courseTrack = $comtjlmstrackingHelper->getCourseTrackEntry($this->item->id, $this->userId);

			if ($courseTrack && !empty($this->item->userCourseTrack))
			{
				$courseProgress = $this->item->userCourseTrack->getProgress($courseTrack);
			}

			JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
			$tjlmsModelcourse = JModelLegacy::getInstance('course', 'TjlmsModel', array('ignore_request' => true));

			// Get certificate data for display certificate
			$certificateData    = $tjlmsModelcourse->checkCertificateIssued($this->item->id, $this->userId);
			$certficateId       = $certificateData[0]->id;
			$certificateExpired = 0;

			if ($certficateId)
			{
				JLoader::import('components.com_tjcertificate.includes.tjcertificate', JPATH_ADMINISTRATOR);
				$tjCert              = TJCERT::Certificate();
				$certificateObj      = $tjCert->validateCertificate($certificateData[0]->unique_certificate_id);

				if (!$certificateObj->id)
				{
					$certificateExpired = 1;
				}

				if ($courseProgress['completionPercent'] == 100)
				{
					if ($this->item->certificate_term != 0)
					{
						// Check for certificate expired
						if (!$certificateExpired && isset($certficateId))
						{
							// Get TJcertificate url for display certificate
							$urlOpts = array ();
							$certificateLink = TJCERT::Certificate($certficateId)->getUrl($urlOpts, false);

							$courseProgress['certificate_url'] = $certificateLink;
						}
					}
				}
			}

			// Filter course progress data
			if (!empty($courseProgress))
			{
				$user_progress                          = new stdClass;
				$user_progress->no_of_lesson            = $courseProgress['totalLessons'];
				$user_progress->no_of_completed_lessons = $courseProgress['completedLessons'];
				$user_progress->completion_percentage   = $courseProgress['completionPercent'];

				if (!empty($courseProgress['certificate_url']))
				{
					$user_progress->certificate_url   = $courseProgress['certificate_url'];
				}

				$this->item->user_progress              = $user_progress;
				unset($courseProgress);
			}

			if (!empty($this->item->originalImage))
			{
				$tjlmsparams   = JComponentHelper::getParams('com_tjlms');
				$courseImgPath = $tjlmsparams->get('course_image_upload_path');
				$root          = JString::rtrim(JURI::root(), '/');
				$imagePath     = JString::ltrim($courseImgPath . $this->item->originalImage, '/');

				$this->item->image = $root . '/' . $imagePath;
			}

			// Process modules and lesson data
			$this->processModuleLessons();

			// Process course at last. Used in Modules lesson
			$this->processCourseData();

			return;
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
				$obj = new stdClass;
				$obj->plan_id 			= $plan->id;
				$obj->plan_time_measure = $plan->time_measure;
				$obj->plan_price 		= $plan->price;
				$obj->plan_duration 	= $plan->duration;

				// Assign the new Object
				$plan = $obj;

				$obj = null;
			}
		}
	}

	/**
	 * Method to process Modules data for API.
	 *
	 * @return  null
	 *
	 * @since   1.0.0
	 */
	private function processModuleLessons()
	{
		if (!empty($this->item->course_info) && !empty($this->item->toc))
		{
			JLoader::import('components.com_tjlms.models.lesson', JPATH_SITE);
			$lessonModel = BaseDatabaseModel::getInstance('lesson', 'TjlmsModel', array('ignore_request' => true));

			JLoader::import('components.com_tjlms.helpers.main', JPATH_SITE);
			$comtjlmsHelper = new ComtjlmsHelper;

			$this->item->modules = array();
			$i = 0;

			foreach ($this->item->toc as $module)
			{
				$newModule = array();
				$newModule['module_id'] 	= $module->id;
				$newModule['module_title'] 	= $module->name;

				if (!empty($module->lessons))
				{
					$newModule['lessons'] 	= array();

					foreach ($module->lessons as $lesson)
					{
						$newLesson = new stdClass;

						// Basic details
						$newLesson->lesson_id          = $lesson->id;
						$newLesson->lesson_title       = $lesson->title;
						$newLesson->lesson_description = $lesson->description;
						$newLesson->lesson_state       = $lesson->state;
						$newLesson->lesson_start_date  = $lesson->start_date;
						$newLesson->lesson_end_date    = $lesson->end_date;
						$newLesson->lesson_format      = $lesson->format;
						$newLesson->consider_marks     = $lesson->consider_marks;

						$newLesson->lesson_url = $comtjlmsHelper->tjlmsRoute(
							"index.php?option=com_tjlms&view=lesson&lesson_id=" . $lesson->id . "&tmpl=component",
							false, 1
						);

						$newLesson->lesson_external_url = null;

						if ($lesson->format == 'video')
						{
							if (!empty($lesson->sub_format))
							{
								$subformat = explode(".", $lesson->sub_format);

								// Trigger all sub format  video plugins method that renders the video player
								PluginHelper::importPlugin($lesson->format);
								$result = Factory::getApplication()->triggerEvent('get' . ucfirst($subformat[0]) . 'ExternalURL', array($lesson->source));

								$newLesson->lesson_external_url = $result[0];
							}
						}

						if (!empty($lesson->sub_format))
						{
							$newLesson->lesson_sub_format 	= $lesson->sub_format;
						}

						$newLesson->source 	= $lesson->source;

						$newLesson->lesson_course_id 	= $lesson->course_id;
						$newLesson->lesson_alias 		= $lesson->alias;
						$newLesson->lesson_icon = JURI::root() . "media/com_tjlms/images/default/icons/" . $lesson->format . ".png";
						$newLesson->lesson_image  = '';

						// Lesson Image
						if ($lesson->storage != 'invalid' && !empty($lesson->image))
						{
							$newLesson->lesson_image = $lessonModel->getLessonImage($lesson->id, 'S_');
						}

						// Tracking detail
						$newLesson->lesson_tracking_details = new stdClass;
						$newLesson->lesson_tracking_details->no_of_attempts_allowed = $lesson->no_of_attempts;
						$newLesson->lesson_tracking_details->prerequisites = $lesson->eligibilty_criteria;

						// User tracking details
						$newLesson->lesson_user_tracking_details                          = new stdClass;
						$newLesson->lesson_user_tracking_details->no_of_attempts_done     = $lesson->userStatus['attemptsDone'];
						$newLesson->lesson_user_tracking_details->last_attempt_status     = $lesson->userStatus['completedAttempts'];
						$newLesson->lesson_user_tracking_details->attempts_grading_status = $lesson->userStatus['status'];
						$newLesson->lesson_user_tracking_details->score                   = $lesson->userStatus['score'];
						$newLesson->lesson_user_tracking_details->time_spent              = $lesson->userStatus['totalTimeSpent'];

						$can_access = $lessonModel->canUserLaunch($lesson->id, $this->userId);

						if (!empty($can_access))
						{
							$newLesson->can_access->success   = $can_access['access'];
							$newLesson->can_access->error_msg = $can_access['msg'];
						}

						$newModule['lessons'][] = $newLesson;

						unset($newLesson);
					}
				}

				$this->item->modules[$i] = $newModule;
				$i++;
			}

			unset($this->item->toc);
		}
	}

	/**
	 * Method to process course data for API.
	 *
	 * @return  null
	 *
	 * @since   1.0.0
	 */
	private function processCourseData()
	{
		if (!empty($this->item->course_info))
		{
			$mapping = array(
				'id' => 'course_id', 'title' => 'course_title', 'description' => 'course_description',
				'state' => 'course_state', 'type' => 'course_type',
				'alias' => 'course_alias', 'access' => 'access',
			);

			foreach ($mapping as $courseInfoKey => $mappingKey)
			{
				$this->item->$mappingKey = $this->item->course_info->$courseInfoKey;
			}

			$this->item->course_cat = array("id" => $this->item->course_info->catid, "title" => $this->item->course_info->category_title);
			$this->item->course_creator = array("id" => $this->item->course_info->created_by, "name" => $this->item->course_info->creator_name);
		}

		unset($this->item->course_info);
	}
}
