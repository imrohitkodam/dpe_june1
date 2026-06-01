<?php
/**
 * @package     Shika
 * @subpackage  mod_lms_categorylist
 * @copyright   Copyright (C) 2009-2020 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.techjoomla.com
 */
// No direct access.
defined('_JEXEC') or die;
jimport('joomla.filesystem.file');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

if (File::exists(JPATH_SITE . '/components/com_tjlms/tjlms.php'))
{
	// Load js assets
	jimport('joomla.filesystem.file');
	$tjStrapperPath = JPATH_SITE . '/media/techjoomla_strapper/tjstrapper.php';

	if (File::exists($tjStrapperPath))
	{
		require_once $tjStrapperPath;
		TjStrapper::loadTjAssets('com_tjlms');
	}

	$showrecommend = 0;
	$showassign    = 0;
	$app           = Factory::getApplication();

	$mod_data            = new stdClass;
	$mod_data->course_id = $course_id = $app->input->get('id', '', 'INT');

	if ($course_id)
	{
		$mod_data->tjlmsparams        = ComponentHelper::getParams('com_tjlms');
		$mod_data->social_integration = $mod_data->tjlmsparams->get('social_integration');
		$mod_data->oluser             = Factory::getUser();
		$mod_data->oluser_id          = $mod_data->oluser->id;
		$mod_data->course_icons_path  = Uri::root(true) . '/media/com_tjlms/images/default/icons/';

		$mod_data->jLikepluginParams = '';
		$jLikeplugin = PluginHelper::getPlugin('content', 'jlike_tjlms');

		if (!empty($jLikeplugin))
		{
			// Get Params each component
			PluginHelper::importPlugin('content', 'jlike_tjlms');
			$paramsArray = Factory::getApplication()->triggerEvent('onJlike_tjlmsGetParams', array());
			$mod_data->jLikepluginParams = !empty ($paramsArray[0]) ? $paramsArray[0] : '';
		}

		$tjlmsparams           = $app->getParams('com_tjlms');
		$show_user_or_username = $tjlmsparams->get('show_user_or_username', 'name');

		// Get TJCertificate data for shown certificate.
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjlms/models', 'TjlmsModel');
		$model                               = BaseDatabaseModel::getInstance('course', 'TjlmsModel', array('ignore_request' => true));
		$certificateData                     = $model->checkCertificateIssued($course_id, $mod_data->oluser_id);
		
		$mod_data->course_info               = $model->getcourseinfo($course_id);
		$mod_data->course_info->certficateId = !empty($certificateData[0]->id) ? $certificateData[0]->id : '';
		$course                              = $mod_data->course_info;

		if ($params->get('taught_by', 1))
		{
			$mod_data->getCreatedInfo = $model->getCreatedInfo($mod_data->course_info->created_by);
		}

		if ($params->get('enrolled', 1))
		{
			$opts = array ();
			$opts['limit'] = 6;
			$mod_data->getallenroledUsersinfo = $model->getallenroledUsersinfo($course_id, $opts);
		}

		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);
		$canManage = TjlmsHelper::canManageCourseEnrollment($course->id);

		if ($params->get('assign_user', 1) && $canManage )
		{
			if (!empty($mod_data->jLikepluginParams))
			{
				if ($mod_data->jLikepluginParams->get('assignment') == 1)
				{
					$showassign = 1;
					$mod_data->getuserAssignedUsers = $model->getuserAssignedUsersInfo($course_id);
				}
			}
		}

		if ($params->get('recommend', 1) && $mod_data->oluser_id)
		{
			if (!empty($mod_data->jLikepluginParams))
			{
				if ($mod_data->jLikepluginParams->get('recommendation') == 1)
				{
					$showrecommend = 1;
					$mod_data->getuserRecommendedUsers = $model->getuserRecommendedUsers($course_id, $mod_data->oluser_id);
				}
			}
		}

		$mod_data->checkifuserenroled = $model->checkifuserenroled($course_id, $mod_data->oluser_id, $mod_data->course_info->type);

		if ($params->get('progress', 1))
		{
			/*$mod_data->CheckIfUserHasProgress = $model->CheckIfUserHasProgress($course_id, $mod_data->oluser_id);*/

			// Decide if user can access the lessons of the course and also if LSM should track the attempts
			$mod_data->usercanAccess = 0;
			$mod_data->trackCourse = 1;
			$enrolment_pending = 0;

			if ($mod_data->oluser_id > 0 && $mod_data->checkifuserenroled == 1)
			{
				$mod_data->usercanAccess = 1;
			}
			elseif ($mod_data->oluser_id > 0 && ($mod_data->checkifuserenroled == '' || $mod_data->checkifuserenroled == 0))
			{
				$mod_data->trackCourse = 0;
			}

			$mod_data->allow_creator = $mod_data->tjlmsparams->get('allow_creator');

			if ($mod_data->allow_creator == 1)
			{
				if ($mod_data->oluser_id == $mod_data->course_info->created_by)
				{
					$mod_data->usercanAccess = 1;
				}
			}

			if ($mod_data->oluser_id <= 0 && $mod_data->course_info->access == 1)
			{
				$mod_data->usercanAccess = 1;
				$mod_data->trackCourse = 0;
			}

			// If Enrolment is pending
			if ($mod_data->usercanAccess == 0 && $mod_data->checkifuserenroled == 0 && $mod_data->checkifuserenroled != '')
			{
				$enrolment_pending = 1;
			}

			$mod_data->course_icons_path = Uri::root(true) . '/media/com_tjlms/images/default/icons/';

			JLoader::import('components.com_tjlms.helpers.tracking', JPATH_SITE);
			$trackingHelper = new comtjlmstrackingHelper;

			JLoader::import('components.com_tjlms.includes.coursetrack', JPATH_ADMINISTRATOR);

			$courseTrackEntry = $trackingHelper->getCourseTrackEntry($course_id, $mod_data->oluser_id);

			$courseTrack    = TjLms::Coursetrack($mod_data->oluser_id, $course_id);
			$courseProgress = $courseTrack->getProgress($courseTrackEntry);

			$mod_data->progress_in_percent = 0;

			if (isset($courseProgress["completionPercent"]) && !empty($courseProgress["completionPercent"]))
			{
				$mod_data->progress_in_percent = $courseProgress["completionPercent"];
			}

			// Set TJcertificate expiry and shown certificate.
			$mod_data->isExpired = false;

			if (!empty($certificateData[0]->id))
			{
				JLoader::import('components.com_tjcertificate.includes.tjcertificate', JPATH_ADMINISTRATOR);
				$tjCert                  = TJCERT::Certificate();
				$certificateObj          = $tjCert->validateCertificate($certificateData[0]->unique_certificate_id);
				$mod_data->certificateId = $certificateObj->id;

				if (!$certificateObj->id)
				{
					$mod_data->isExpired = true;
				}
			}
		}

		if ($params->get('group_info', 1))
		{
			// For now we are showing only one ES group info.We need to change this for multiple groups.
			$esGroupInfo = json_decode($mod_data->course_info->params);

			if(!empty($esGroupInfo->esgroup))
			{
				// Get the Group to which course is assigned
				$tjlmsCoursesHelper     = new tjlmsCoursesHelper;
				$mod_data->getgroupinfo = $tjlmsCoursesHelper->getgroupinfo($esGroupInfo->esgroup->onAfterEnrollEsGroups[0]);
			}
		}

		if ($params->get('fields', 1))
		{
			$modData = new stdClass;

			// Get the fields data for respective course and format it accordingly.
			$results = Factory::getApplication()->triggerEvent('onContentBeforeDisplay', array('com_tjlms.course', &$mod_data->course_info, &$mod_data->course_info->params));
			$fieldsData = array_filter(explode('<dd class="field-entry ">', $results[0]));

			$modData->fieldsData = $fieldsData;
		}

		require ModuleHelper::getLayoutPath('mod_lms_course_blocks', $params->get('layout', 'default'));
	}
}
