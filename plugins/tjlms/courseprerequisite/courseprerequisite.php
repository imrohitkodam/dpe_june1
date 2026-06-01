<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,courseprerequisite
 *
 * @copyright   Copyright (C) 2005 - 2020. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die();

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\Form;

/**
 * Course Prerequisite tjintegration Plugin
 *
 * @since  1.3.39
 */
class PlgTjlmsCourseprerequisite extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.3.39
	 */
	protected $autoloadLanguage = true;

	/**
	 * The form course. Load additional parameters when available into the course form under integration section.
	 * Only when the type of the form is of interest.
	 *
	 * @return  array
	 *
	 * @since   1.3.39
	 */
	public function onPrepareIntegrationField()
	{
		Form::addFieldPath(JPATH_PLUGINS . '/tjlms/courseprerequisite/fields');

		$app    = Factory::getApplication();
		$jinput = $app->input;
		$option = $jinput->get("option");
		$view   = $jinput->get("view", '');

		if ($app->isClient('administrator'))
		{
			if ($option == 'com_tjlms' && $view == 'course')
			{
				return array(
				'path' => JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/courseprerequisite.xml', 'name' => $this->_name
				);
			}
		}
	}

	/**
	 * Function used as a trigger for getting course  prerequisite.
	 *
	 * @param   Integer  $courseId  Course Id
	 * @param   Integer  $userId    Logged in user id
	 *
	 * @return  boolean
	 *
	 * @since   1.3.39
	 */
	public function onCheckPrerequisiteCourseStatus($courseId, $userId)
	{
		$course      = TjLms::course($courseId);
		$decodeCourseParams = json_decode($course->params);

		if (isset($decodeCourseParams->courseprerequisite))
		{
			$coursePrerequisite = $decodeCourseParams->courseprerequisite;
			$prerequisiteCourses = $coursePrerequisite->onBeforeEnrolCoursePrerequisite;

			if (empty($prerequisiteCourses['0']))
			{
				return true;
			}

			require_once JPATH_SITE . '/components/com_tjlms/helpers/courses.php';
			$tjlmsCoursesHelper = new TjlmsCoursesHelper;

			$count = 0;

			foreach ($prerequisiteCourses as $cId)
			{
				$courseProgresInfo = $tjlmsCoursesHelper->getCourseProgress($cId, $userId);

				if ($courseProgresInfo['completionPercent'] == '100')
				{
					$count++;
				}
			}

			if (count($prerequisiteCourses) != $count)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Function used as a trigger for getting course  prerequisite status.
	 *
	 * @param   Integer  $courseId  Course Id
	 * @param   Integer  $userId    Logged in user id
	 *
	 * @return  boolean
	 *
	 * @since   1.3.39
	 */
	public function onBeforeCourseEnrol($courseId, $userId)
	{
		if (!$courseId && !$userId)
		{
			return false;
		}

		$result = $this->onCheckPrerequisiteCourseStatus($courseId, $userId);

		if ($result)
		{
			return true;
		}

		return false;
	}
}
