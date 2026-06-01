<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Plugin\PluginHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\AdminController;

/**
 * Tjmodules list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerEnrolment extends AdminController
{
	/**
	 * Admin approval value
	 *
	 * @var    int
	 * @since  1.4.0
	 */
	public $enrollmentAdminApproval = 0;

	/**
	 * construct for enrollment
	 *
	 * @param   ARRAY  $config  Array
	 *
	 * @since  1.0.0
	 */
	public function __construct($config = array())
	{
		$this->_db = Factory::getDbo();

		// Include helper of tjlms
		$path                 = JPATH_SITE . '/components/com_tjlms/helpers/main.php';
		$this->comtjlmsHelper = '';

		if (File::exists($path))
		{
			if (!class_exists('comtjlmsHelper'))
			{
				JLoader::register('comtjlmsHelper', $path);
				JLoader::load('comtjlmsHelper');
			}

			$this->comtjlmsHelper = new comtjlmsHelper;
		}

		// Load jlike model to call api function for assigndetails and other
		$path                            = JPATH_SITE . '/components/com_jlike/models/recommendations.php';
		$this->JlikeModelRecommendations = "";

		if (File::exists($path))
		{
			if (!class_exists('JlikeModelRecommendations'))
			{
				JLoader::register('JlikeModelRecommendations', $path);
				JLoader::load('JlikeModelRecommendations');
			}

			$this->JlikeModelRecommendations = new JlikeModelRecommendations;
		}

		// Load jlike admin model content form to call api to get content id
		$path = JPATH_SITE . '/administrator/components/com_jlike/models/contentform.php';

		$this->JlikeModelContentForm = "";

		if (File::exists($path))
		{
			if (!class_exists('JlikeModelContentForm'))
			{
				JLoader::register('JlikeModelContentForm', $path);
				JLoader::load('JlikeModelContentForm');
			}

			$this->JlikeModelContentForm = new JlikeModelContentForm;
		}

		// Load jlike admin model content form to call api to get content id
		$path = JPATH_SITE . '/components/com_jlike/models/recommend.php';

		$this->JlikeModelRecommend = "";

		if (File::exists($path))
		{
			if (!class_exists('JlikeModelRecommend'))
			{
				JLoader::register('JlikeModelRecommend', $path);
				JLoader::load('JlikeModelRecommend');
			}

			$this->JlikeModelRecommend = new JlikeModelRecommend;
		}

		parent::__construct($config);
	}

	/**
	 * Assign User to particular course
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function assignUser()
	{
		$app            = Factory::getApplication();
		$input          = $app->input;
		$post           = $input->post;
		$rUrl           = $post->get('rUrl', '', 'STRING');
		$link           = base64_decode($rUrl);
		$courseAl       = $input->get('course_al', '0', 'INT');
		$selectedCourse = $post->get('selectedcourse', '', 'ARRAY');
		$com_fields     = $post->get('jform', array(), 'array');
		$data           = array();

		if ($selectedCourse)
		{
			// Filter for selected courses
			$selectedCourseFilter = $app->getUserStateFromRequest('com_tjlms.enrolment.filter.selectedcourse', 'selectedcourse', '', 'ARRAY');
			$this->getModel()->setState('filter.selectedcourse', $selectedCourseFilter);
		}

		$data['start_date'] = $post->get('start_date', '', 'DATE');

		$data['due_date']   = '';

		if ($post->get('due_date', '', 'DATE'))
		{
			$data['due_date'] = $post->get('due_date', '', 'DATE');
		}

		$data['notify_user'] = $post->get('notify_user_enroll', '0', 'INT');
		$userIds             = $post->get('cid', '', 'ARRAY');
		$data['type']        = 'assign';

		if (!empty($com_fields['com_fields']))
		{
			$data['com_fields']  =	$com_fields['com_fields'];
		}

		$enrollmentModel    = $this->getModel('Enrolment', 'TjlmsModel');
		$success            = $failed = 0;
		$prerequisiteCourse = 0;

		// Loop through each user;
		foreach ($selectedCourse as $course)
		{
			if ($userIds)
			{
				foreach ($userIds as $key => $cId)
				{
					$data['course_id'] = (int) $course;
					$data['user_id']   = (int) $cId;

					// Check prerequisite courses status before enrolling to the course. user need to complete prerequiste courses
					if (PluginHelper::isEnabled('tjlms', 'courseprerequisite'))
					{
						PluginHelper::importPlugin('tjlms');

						// Trigger all "checkPrerequisiteCourseStatus" plugins method
						$result = Factory::getApplication()->triggerEvent('onCheckPrerequisiteCourseStatus', array($data['course_id'], $data['user_id']));

						if (!$result[0])
						{
							$prerequisiteCourse++;

							continue;
						}
					}

					if ($prerequisiteCourse == 0)
					{
						if ($enrollmentModel->userAssignment($data))
						{
							$success++;
						}
						else
						{
							$failed++;
						}
					}
					else
					{
						$failed++;
					}
				}
			}
		}

		if ($prerequisiteCourse > 0)
		{
			$app->enqueueMessage(Text::sprintf('COM_TJLMS_VIEW_COURSE_PREREQUISITE_NOT_ALLOWED_ENROLLMENT', $prerequisiteCourse), 'error');
		}
		elseif($success)
		{
			// Add a message to the message queue
			$app->enqueueMessage(Text::sprintf('COM_TJLMS_COURSE_ASSIGN_SUCCESS', $success), 'success');
		}
		elseif($failed)
		{
			// Add a message to the message queue
			$app->enqueueMessage(Text::_('COM_TJLMS_COURSE_ASSIGN_FAILED'), 'error');
		}

		$flink = Route::_($link, false);
		$this->setRedirect($flink);
	}

	/**
	 * change Due date
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function batchAssign()
	{
		$model           = $this->getModel('manageenrollments');
		$modelEnrollment = $this->getModel('enrolment');
		$app             = Factory::getApplication();
		$input           = $app->input;
		$post            = $input->post;
		$data            = array();

		// Get data passed by the post from the view
		$batchAssignStartDate = $post->get('batch_start_date', '', 'DATE');
		$batchAssignEndDate   = $post->get('batch_due_date', '', 'DATE');

		$data['start_date']   = $batchAssignStartDate;
		$data['due_date']     = $batchAssignEndDate;
		$enrollmentsIds       = $post->get('cid', '', 'ARRAY');
		$data['notify_user']  = $post->get('notify_user_batch', '', 'INT');

		// Loop through data of each enrolment
		foreach ($enrollmentsIds as $key => $eid)
		{
			$enrollmentDetails = $model->getenrollmentdetails($eid);

			if ($enrollmentDetails)
			{
				$data['course_id'] = (int) $enrollmentDetails->course_id;
				$data['user_id']   = (int) $enrollmentDetails->user_id;

				$res               = $modelEnrollment->userEnrollment($data);
			}
		}

		if ($res)
		{
			// Add a message to the message queue
			$app->enqueueMessage(Text::_('COM_TJLMS_ASSIGN_DUEDATE_CHANGE'), 'success');
		}
		else
		{
			// Add a message to the message queue
			$app->enqueueMessage(Text::_('COM_TJLMS_BATCH_UPDATED_SUCCESSFULLY'), 'error');
		}

		$flink = Route::_('index.php?option=com_tjlms&view=manageenrollments', false);
		$this->setRedirect($flink);
	}

	/**
	 * Proxy for getModel.
	 *
	 * @param   STRING  $name    model name
	 * @param   STRING  $prefix  model prefix
	 * @param   ARRAY   $config  Array
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function getModel($name = 'enrolment', $prefix = 'TjlmsModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * function to enroll user from backend new
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function enrolUser()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app   = Factory::getApplication();
		$input = $app->input;
		$post  = $input->post;

		$cId            = $post->get('cid', array(), 'array');
		$selectedCourse = $post->get('selectedcourse', '', 'array');
		$com_fields     = $post->get('jform', array(), 'array');

		$selectedCourseFilter = $app->setUserState('com_tjlms.enrolment.filter.selectedcourse', $selectedCourse);

		$notifyUser = ($post->get('notify_user_enroll', '', 'INT')) ? $post->get('notify_user_enroll', '', 'INT') : 0;

		$rUrl = $post->get('rUrl', '', 'STRING');
		$link = base64_decode($rUrl);

		$this->userEnrollment($selectedCourse, $cId, $link, $notifyUser, $com_fields['com_fields']);
	}

	/**
	 * Function for enrollment
	 *
	 * @param   ARRAY   $selectedCourse  course ids
	 * @param   ARRAY   $cId             user ids going to enroll
	 * @param   STRING  $link            Course TOC link
	 * @param   INT     $notifyUser      mailing parameter
	 * @param   ARRAY   $fields          fields value
	 *
	 * @return  mixed
	 *
	 * @since   1.1
	 */
	public function userEnrollment($selectedCourse, $cId, $link, $notifyUser = 1, $fields = null)
	{
		$app                     = Factory::getApplication();
		$model                   = $this->getModel('Enrolment', 'TjlmsModel');
		$tjlmsParams             = ComponentHelper::getParams('com_tjlms');
		$enrollmentAdminApproval = (INT) $tjlmsParams->get('admin_approval');

		// Don't redirect to an external URL.
		if (!Uri::isInternal($link))
		{
			$msg  = Text::_('JINVALID_TOKEN');
			$type = 'error';
			$app->enqueueMessage($msg, $type);

			return false;
		}

		$msg          = Text::_('COM_TJLMS_COURSE_ENROLL_SUCCESS');
		$loggedInUser = Factory::getUser()->id;
		$type         = 'success';
		$success      =	$failed = 0;
		$selfEnrl     = 0;

		if (!empty($selectedCourse))
		{
			// Enrollment from manage enrollment view.
			foreach ($selectedCourse as $key => $courseId)
			{
				$nonEnrolledUsers = $model->getNonEnrolledUsers($cId, $courseId);

				if (!empty($nonEnrolledUsers))
				{
					foreach ($nonEnrolledUsers as $userId)
					{
						$data                = array();
						$data['user_id']     = (int) $userId;
						$data['course_id']   = (int) $courseId;
						$data['notify_user'] = $notifyUser;

						if ($app->isClient('administrator'))
						{
							$data['state'] = 1;
						}
						elseif ($enrollmentAdminApproval == '1')
						{
							$data['state'] = '0';
						}
						else
						{
							$data['state'] = 1;
						}

						if (!empty($fields))
						{
							$data['com_fields']  = $fields;
						}

						if (!$model->userEnrollment($data))
						{
							$msg  =	$model->getError();
							$type =	'error';
							$failed++;
						}
						else
						{
							$success++;

							if ($data['user_id'] == $loggedInUser)
							{
								$selfEnrl	= 1;
							}
						}
					}
				}
				else
				{
					$msg  = Text::_('COM_TJLMS_COURSE_SELECTED_USER_ALREADY_ENROLLED');
					$type = 'error';
					$app->enqueueMessage($msg, $type);
				}
			}
		}
		else
		{
			$msg  = Text::_('COM_TJLMS_NO_COURSE_SELECTED');
			$type = 'error';
		}

		$flink = Route::_($link, false);

		if ($success)
		{
			if ($selfEnrl == 1 && $success == 1 && ($app->isClient('administrator') || ($app->isClient('site') && !$enrollmentAdminApproval)))
			{
				$msg  = Text::_('COM_TJLMS_COURSE_ENROLL_SUCCESS_USERS_SELF');
				$type = 'success';
				$app->enqueueMessage($msg, $type);
				$success--;
			}
			elseif($app->isClient('site') && $enrollmentAdminApproval)
			{
				$msg  = Text::_('COM_TJLMS_COURSE_ENROLL_PENDING_USERS_SELF');
				$type = 'success';
				$app->enqueueMessage($msg, $type);
				$success--;
			}

			if ($success >= 1)
			{
				if ($selfEnrl == 1 && !$enrollmentAdminApproval)
				{
					$success--;
					$msg = Text::sprintf('COM_TJLMS_COURSE_ENROLL_SUCCESS_ADMIN_USERS', $success);
				}
				elseif (!$enrollmentAdminApproval || ($app->isClient('administrator')))
				{
					$msg = Text::sprintf('COM_TJLMS_COURSE_ENROLL_SUCCESS', $success);
				}

				$type = 'success';
				$app->enqueueMessage($msg, $type);
			}
		}

		if ($failed)
		{
			$app->enqueueMessage($msg, $type);
		}

		$app->redirect($flink);
	}

	/**
	 * common function to drive enrollment and assignment
	 *
	 * @return  void
	 *
	 * @since   1.1
	 */
	public function enrollAssignWrapper()
	{
		$app   = Factory::getApplication();
		$input = $app->input;
		$post  = $input->post;

		$dueDate = $post->get('due_date', '', 'DATE');

		if ($dueDate)
		{
			$this->assignUser();
		}
		else
		{
			$this->enrolUser();
		}
	}
}
