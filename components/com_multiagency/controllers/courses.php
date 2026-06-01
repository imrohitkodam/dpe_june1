<?php
/**
 * @package    Agency
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * Course list controller class.
 *
 * @since  1.0
 */
class MultiagencyControllerCourses extends BaseController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional
	 * @param   array   $config  Configuration array for model. Optional
	 *
	 * @return object	The model
	 *
	 * @since	1.0
	 */
	public function &getModel($name = 'Courses', $prefix = 'MultiagencyModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, array('ignore_request' => true));

		return $model;
	}

	/**
	 * This method used for enrollment process in multiagency
	 *
	 * @return void
	 *
	 * @since  1.0
	 */
	public function enrollment()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app = Factory::getApplication();
		$this->license = $app->input->get('license', '0', 'INT');
		$post      = $app->input->post;

		$selectedCourse = $post->get('selectedcourse', '', 'array');

		// Check authorization
		$subuserAuth = RBACL::authorise(Factory::getUser()->id, 'com_multiagency', 'core.manageenrollment', 'com_multiagency');

		if (!$subuserAuth)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return $app->setHeader('status', 403, true);
		}

		// Load Licences model
		$licencesModel = $this->getModel('Licences', 'MultiagencyModel');

		// Get license data
		$licenseModel = $this->getModel('LicenceForm', 'MultiagencyModel');
		$licenseData = $licenseModel->getData($this->license);
		$licenseType = $licenseData->type;
		$agencyId = $licenseData->multiagency_id;

		// If license type is all means all courses are used for enrollment process.
		if ($licenseType == 'all')
		{
			$selectedCourse = $selectedCourse;
		}
		else
		{
			$selectedCourse = array($licenseData->course_id);
		}

		$model = $this->getModel('enrollment', 'MultiagencyModel');
		$input = $app->input;
		$post = $input->post;

		$uId = $post->get('uid', array(), 'ARRAY');
		$type = 'success';
		$success = $failed = 0;

		$checkEnrolledUser = count($uId) + $licenseData->used_seats;

		if ($checkEnrolledUser > $licenseData->total_seats && $licenseData->total_seats > 0)
		{
			$msg = Text::_('COM_MULTIAGENCY_NO_TOTAL_SEATS');
			$app->enqueueMessage($msg, 'error');
			$this->setRedirect(Route::_('index.php?option=com_multiagency&view=enrollment&tmpl=component&license=' . $this->license, false));

			return false;
		}

		// Check course is not empty
		if (!empty($selectedCourse))
		{
			$result = $model->userEnrollment($selectedCourse, $uId);

			if (empty($result))
			{
				$msg = $model->getError();
				$app->enqueueMessage($msg, 'error');
			}
			else
			{
				if ($result['success'])
				{
					$msg = Text::sprintf('COM_MULTIAGENCY_COURSE_ENROLL_SUCCESS', $result['success']);
					$app->enqueueMessage($msg, 'success');
				}

				if ($result['failed'])
				{
					$msg = Text::sprintf('COM_MULTIAGENCY_COURSE_ENROLL_FAILED', $result['failed']);
					$app->enqueueMessage($msg, 'error');
				}

				if ($result['success'] == 0 && $result['failed'] == 0)
				{
					$msg = Text::sprintf('COM_MULTIAGENCY_COURSE_NO_USERS_TO_ENROLL', $result['failed']);
					$app->enqueueMessage($msg, 'error');
				}
			}
		}
		else
		{
			$msg = Text::_('COM_MULTIAGENCY_NO_COURSE_SELECTED');
			$app->enqueueMessage($msg, 'error');
		}

		$this->setRedirect(Route::_('index.php?option=com_multiagency&view=enrollment&tmpl=component&license=' . $this->license, false));
	}
}
