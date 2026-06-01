<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Plugin\PluginHelper;


jimport('joomla.application.component.controlleradmin');

/**
 * Tjmodules list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerEnrollUser extends AdminController
{
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
		$this->app = Factory::getApplication();
		$this->input = $this->app->input;
		$this->post = $this->input->post;

		// Include helper of tjlms
		$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';
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

		$this->loadLanguage('com_jlike');

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
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$courseAl = $this->input->get('course_al', '0', 'INT');
		$selectedCourse = $this->post->get('selectedcourse', '', 'ARRAY');
		$data = array();
		$data['type'] = $this->post->get('type');
		$data['group_assignment'] = $this->post->get('group_assignment', 0, 'INT');
		$data['onlysubuser'] = $this->post->get('onlysubuser', 0, 'INT');

		if ($selectedCourse)
		{
			// Filter for selected courses
			$selectedCourseFilter = $this->app->getUserStateFromRequest('com_tjlms.enrolment.filter.selectedcourse', 'selectedcourse', '', 'ARRAY');
			$this->getModel()->setState('filter.selectedcourse', $selectedCourseFilter);
		}

		$data['start_date'] = $this->post->get('start_date', '', 'DATE');

		$data['due_date'] = '';

		if ($this->post->get('due_date', '', 'DATE'))
		{
			$data['due_date'] = $this->post->get('due_date', '', 'DATE');
		}

		$data['update_existing_users']  = $this->post->get('update_existing_users', 0, 'INT');
		$data['notify_user'] = $this->post->get('notify_user', '0', 'INT');
		$userIds      = $this->post->get('cid', '', 'ARRAY');
		$filter = InputFilter::getInstance();

		foreach ($userIds as $value)
		{
			$userIdsArray[] = $filter->clean($value, 'int');
		}

		$success            = $failed = 0;
		$enrollmentModel    = $this->getModel();
		$prerequisiteCourse = 0;

		// Loop through each user
		foreach ($selectedCourse as $course)
		{
			if ($userIdsArray)
			{
				foreach ($userIdsArray as $key => $cId)
				{
					$data['course_id'] = (int) $course;
					$data['user_id'] = (int) $cId;

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

		if ($success && $data['due_date'])
		{
			$msg = Text::sprintf('COM_TJLMS_COURSE_ASSIGN_SUCCESS', $success);
			$type = 'success';
		}
		elseif ($prerequisiteCourse > 0)
		{
			$msg = Text::sprintf('COM_TJLMS_VIEW_COURSE_PREREQUISITE_NOT_ALLOWED_ENROLLMENT', $prerequisiteCourse);
			$type = 'error';
		}
		elseif ($failed && $data['due_date'])
		{
			$msg = Text::sprintf('COM_TJLMS_COURSE_ASSIGN_FAILED', $failed);
			$type = 'error';
		}
		elseif ($success)
		{
			$msg = Text::sprintf('COM_TJLMS_RECOMMEND_SUCCESSFULL', $success);
			$type = 'success';
		}
		else
		{
			$msg = Text::sprintf('COM_TJLMS_RECOMMEND_ERROR', $failed);
			$type = 'error';
		}

		$this->app->enqueueMessage($msg, $type);
		$link = 'index.php?option=com_tjlms&view=enrolluser&tmpl=component&type=' . $data['type'];

		if ($selectedCourse)
		{
			$link .= '&selectedcourse[]=' . (int) $selectedCourse[0];
		}

		if ($courseAl)
		{
			$link .= '&course_al=' . $courseAl;
		}

		$flink = $this->comtjlmsHelper->tjlmsRoute($link, false);
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
	public function getModel($name = 'EnrollUser', $prefix = 'TjlmsModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * Function used for enrollment
	 *
	 * @return  void
	 *
	 * @since   1.1
	 */
	private function userEnrollment()
	{
		$model = $this->getModel();
		$app   = Factory::getApplication();
		$cId = $this->post->get('cid', array(), 'ARRAY');
		$filter = InputFilter::getInstance();

		foreach ($cId as $value)
		{
			$cId[] = $filter->clean($value, 'int');
		}

		$selectedCourse = $this->post->get('selectedcourse', '', 'ARRAY');
		$courseAl = $this->input->get('course_al', '0', 'INT');
		$action = $this->post->get('type');
		$selectedCourseFilter = $this->app->setUserState('com_tjlms.enrolluser.filter.selectedcourse', $selectedCourse);

		$notifyUser = ($this->post->get('notify_user', '', 'INT')) ? $this->post->get('notify_user', '', 'INT') : 0;

		$loggedInUser = Factory::getUser()->id;
		$type = 'success';
		$success = $failed = 0;

		if (!empty($selectedCourse))
		{
			// Enrollment from manage enrollment view.
			foreach ($selectedCourse as $key => $courseId)
			{
				$courseId = (int) $courseId;
				$nonEnrolledUsers = $model->getNonEnrolledUsers($cId, $courseId);

				foreach ($nonEnrolledUsers as $userId)
				{
					$data = array();
					$data['user_id'] = (int) $userId;
					$data['course_id'] = (int) $courseId;
					$data['notify_user'] = $notifyUser;

					if (!$model->userEnrollment($data))
					{
						$failed ++;
					}
					else
					{
						$success ++;
					}
				}
			}
		}
		else
		{
			$msg = Text::_('COM_TJLMS_NO_COURSE_SELECTED');
			$type = 'error';
		}

		$link = 'index.php?option=com_tjlms&view=enrolluser&tmpl=component' . '&type=' . $action;

		if ($selectedCourse)
		{
			$link .= '&selectedcourse[]=' . (int) $selectedCourse[0];
		}

		if ($courseAl)
		{
			$link .= '&course_al=' . $courseAl;
		}

		if ($success)
		{
			$msg = Text::sprintf('COM_TJLMS_COURSE_ENROLL_SUCCESS', $success);
			$app->enqueueMessage($msg, 'success');
		}

		if ($failed)
		{
			$msg = Text::sprintf('COM_TJLMS_COURSE_ENROLL_FAILED', $failed);
			$app->enqueueMessage($msg, 'error');
		}

		if (!$success && !$failed)
		{
			$msg = Text::sprintf('COM_TJLMS_COURSE_NO_USERS_TO_ENROLL', $failed);
			$app->enqueueMessage($msg, 'error');
		}

		$link = $this->comtjlmsHelper->tjlmsRoute($link, false);
		$this->setRedirect($link);
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
		$action = $this->post->get('type');

		if ($action == "enroll")
		{
			$this->userEnrollment();

			return true;
		}
		elseif ($action == 'assign' || $action == "reco")
		{
			$this->assignUser();

			return true;
		}
		elseif ($action == "enrollGroup" || $action == "assignGroup")
		{
			$this->enrollGroup($action);

			return true;
		}

		return false;
	}

	/**
	 * common function to drive group enrollment and group assignment
	 *
	 * @param   STRING  $action  action
	 *
	 * @return  void
	 *
	 * @since   1.1
	 */
	public function enrollGroup($action)
	{
		$users = array();
		$groups = $this->post->get('user_groups', '', 'ARRAY');
		$onlysubuser = $this->post->get('onlysubuser', 0, 'INT');

		if (!empty($groups))
		{
			foreach ($groups as $group)
			{
				$group_users = Access::getUsersByGroup($group);
				$users	= array_merge($users, $group_users);
			}

			$users = array_unique($users);

			// Filter Manager related data
			JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);
			$canEnroll = TjlmsHelper::canManageEnrollment();

			// If only Manager, send only to subusers
			if ($canEnroll === -2 || !empty($onlysubuser))
			{
				$hasUsers = TjlmsHelper::getSubusers();
				$users = array_intersect($users, $hasUsers);
			}
		}

		$this->post->set('cid', $users);

		if ($action == "enrollGroup")
		{
			$this->userEnrollment();
		}
		elseif ($action == "assignGroup")
		{
			$this->post->set('group_assignment', 1);
			$this->assignUser();
		}

		return true;
	}

	/**
	 * Method to load language of TjReport Plugin
	 *
	 * @param   string  $extension  Extension Name
	 * @param   string  $basePath   The basepath to use
	 *
	 * @return  Void
	 *
	 * @since   3.0
	 */
	public function loadLanguage($extension, $basePath = JPATH_BASE)
	{
		$extension = strtolower($extension);
		$lang      = Factory::getLanguage();

		// If language already loaded, don't load it again.
		if ($lang->getPaths($extension))
		{
			return true;
		}

		return $lang->load($extension, $basePath, null, false, true);
	}
}
