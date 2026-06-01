<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,joomlaUsergroup
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die();

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Joomla user group tjintegration Plugin
 *
 * @since  1.3.10
 */
class PlgTjlmsJoomlaUsergroup extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.3.10
	 */
	protected $autoloadLanguage = true;

	/**
	 * The form event. Load additional parameters when available into the field form.
	 * Only when the type of the form is of interest.
	 *
	 * @return  array
	 *
	 * @since   1.3.10
	 */
	public function onPrepareIntegrationField()
	{
		$app    = Factory::getApplication();
		$option = $app->input->get("option");
		$view   = $app->input->get("view", '');

		if ($option == 'com_tjlms' && $view == 'course')
		{
			return array(
				'path' => JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/joomlausergroup.xml', 'name' => $this->_name
			);
		}
	}

	/**
	 * Function used as a trigger after user successfully enrolled  for a course.
	 *
	 * @param   INT  $actorId     user has been enrolled
	 * @param   INT  $state       Enrollment state
	 * @param   INT  $courseId    course ID
	 * @param   INT  $enrolledBy  user who enrolled the actor
	 * @param   INT  $notifyUser  send notification or Not
	 *
	 * @return  boolean
	 *
	 * @since   1.3.10
	 */
	public function onAfterCourseEnrol($actorId, $state, $courseId, $enrolledBy, $notifyUser = 1)
	{
		require_once JPATH_SITE . '/components/com_tjlms/models/course.php';
		$courseModel = new TjlmsModelCourse;
		$course       = $courseModel->getItem($courseId);
		$courseParams = json_decode($course->course_info->params);

		$user = Factory::getUser($actorId);

		if ($user->id && !empty($courseParams->joomlausergroup->onAfterCourseEnrolUserGroup))
		{
			try
			{
				$groups = array_merge($user->groups, $courseParams->joomlausergroup->onAfterCourseEnrolUserGroup);

				return UserHelper::setUserGroups($user->id, $groups);
			}
			catch (Exception $e)
			{
				return false;
			}
		}
	}
}
