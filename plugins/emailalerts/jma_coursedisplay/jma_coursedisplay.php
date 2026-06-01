<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_coursedisplay
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Mail\Mail;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

$jmailAlertsPluginPath    = JPATH_SITE . '/components/com_jmailalerts/helpers/plugins.php';
$jmaIntegrationHelperPath = JPATH_SITE . '/plugins/system/plg_sys_jma_integration/plg_sys_jma_integration/plugins.php';

// Include plugin helper file
// Else condition is needed when JMA integration plugin is used on sites where JMA is not installed
if (File::exists($jmailAlertsPluginPath))
{
	include_once $jmailAlertsPluginPath;
}
elseif (File::exists($jmaIntegrationHelperPath))
{
	include_once $jmaIntegrationHelperPath;
}

/**
 * This plugin pulls the inprogress courses data of user
 *
 * @since  _1.3.32_
 */
class PlgEmailalertsjma_Coursedisplay extends MailAlertsPlugin
{
	public $extension = 'com_tjlms';

	/**
	 * Plugin trigger to get latest matching records
	 *
	 * @param   string  $id               Userid or email id for user whom email will be sent
	 * @param   string  $lastEmailDate    Timestamp when last email was sent to that user
	 * @param   array   $userParams       Array of user's alert preference considering data tags
	 * @param   int     $fetchOnlyLatest  Decide to send only fresh content or not
	 *
	 * @return  array
	 *
	 * @since  1.3.32
	 */
	public function onEmail_jma_coursedisplay($id, $lastEmailDate, $userParams, $fetchOnlyLatest)
	{
		// This function is just a dummy
		// Let's call parent function
		return $this->onEmailTrigger($id, $lastEmailDate, $userParams, $fetchOnlyLatest);
	}

	/**
	 * Method to get the records based on user preferences.
	 *
	 * @param   int     $id               Userid or email id for user whom email will be sent
	 * @param   string  $lastEmailDate    Timestamp when last email was sent to that user
	 * @param   array   $userParams       Array of user's alert preference considering data tags
	 * @param   int     $fetchOnlyLatest  Decide to send only fresh content or not
	 *
	 * @return  array
	 *
	 * @since  1.3.32
	 */
	public function getList($id, $lastEmailDate, $userParams, $fetchOnlyLatest)
	{
		if ($this->params->get('show_in_progress_courses'))
		{
			$list->inprogressCourseList = $this->getCourseObj('in_progress', $id);
		}

		if ($this->params->get('show_recommended_courses'))
		{
			$list->recommendedCourseList = $this->getCourseObj('recommended', $id);
		}

		if ($this->params->get('show_recently_added_courses'))
		{
			$list->recentlyAddedCourseList = $this->getCourseObj('recently_added', $id);
		}

		if ($this->params->get('show_most_popular_courses'))
		{
			$list->mostPopularCourseList = $this->getCourseObj('most_popular', $id);
		}

		return $list;
	}

	/**
	 * Method to get course details.
	 *
	 * @param   String  $courseToShow  course to show
	 * @param   Int     $userId        user id
	 *
	 * @return  Object
	 *
	 * @since  1.3.32
	 */
	public function getCourseObj($courseToShow, $userId)
	{
		JLoader::import('components.com_tjlms.models.courses', JPATH_SITE);
		$order      = 'a.created';
		$direction  = 'DESC';
		$menuParams = new Registry;

		$tjlmsparams = ComponentHelper::getParams('com_tjlms');
		$model       = BaseDatabaseModel::getInstance('Courses', 'TjlmsModel', array('ignore_request' => true));

		$model->course_images_size = $tjlmsparams->get('course_images_size', 'S_');
		$model->setState('list.ordering', $order);
		$model->setState('list.direction', $direction);
		$model->setState('com_tjlms.filter.user_id', $userId);
		$model->setState('params', $menuParams);

		switch ($courseToShow)
		{
			case 'in_progress':
				$model->courses_to_show = 'enrolled';
				$model->setState('com_tjlms.filter.course_status', 'incompletedcourses');
				$model->setState('list.limit', $this->params->get('no_of_in_progress_courses'));
				break;

			case 'recommended':
				require_once JPATH_ROOT . '/components/com_tjlms/libraries/suggestcourses.php';
				$suggestCourses   = new TjSuggestCourses;
				$options          = array();
				$options['limit'] = $this->params->get('no_of_recommended_courses');
				$questions        = array();

				return $suggestCourses->suggestCourses($questions, $options);

			case 'recently_added':
				$model->courses_to_show = 'notEnrolled';
				$model->setState('list.limit', $this->params->get('no_of_recently_added_courses'));
				break;

			case 'most_popular':
				$model->courses_to_show = 'mostPopular';
				$model->setState('com_tjlms.filter.popular_course_limit', $this->params->get('no_of_most_popular_courses'));
				break;

			default:

				break;
		}

		return $model->getItems();
	}
}
