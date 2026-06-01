<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,EasySocial
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * EasySocial point tjintegration Plugin
 *
 * @since  1.3.34
 */
class PlgTjlmsEspoint extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  1.3.34
	 */
	protected $autoloadLanguage = true;

	/**
	 * The form event. Load additional parameters when available into the field form.
	 * Only when the type of the form is of interest.
	 *
	 * @return  array
	 *
	 * @since   1.3.34
	 */
	public function onPrepareIntegrationField()
	{
		$params     = ComponentHelper::getParams('com_tjlms');
		$app        = Factory::getApplication();
		$esFilePath = JPATH_ROOT . '/administrator/components/com_easysocial/includes/easysocial.php';

		if ($params['social_integration'] == 'easysocial' && $app->isClient('administrator') && File::exists($esFilePath)
			&& ComponentHelper::isEnabled('com_easysocial'))
		{
			$jinput = $app->input;
			$option = $jinput->get("option");
			$view   = $jinput->get("view", '');
			$cid    = $jinput->get('id', 0, 'INT');

			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
			$courseModel = BaseDatabaseModel::getInstance('course', 'TjlmsModel');
			$course = $courseModel->getCourse($cid);

			$document = Factory::getDocument();
			$script = 'jQuery(document).ready(function(){
				jQuery("#jform_params_espoint_onAfterCourseCompletion").val(' . $course->params["espoint"]["onAfterCourseCompletion"]["0"] . ')
			});';
			$document->addScriptDeclaration($script);

			if ($option == 'com_tjlms' && $view == 'course')
			{
				return array(
				'path' => JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/espoint.xml', 'name' => $this->_name
				);
			}
		}
	}

	/**
	 * On Creating a course
	 *
	 * Method is called after a course create.
	 * This method create a EasySocail group.
	 *
	 * @param   INT  $courseId  course id of course created
	 *
	 * @return  mixed
	 *
	 * @since   ; 1.3.34
	 */
	public function onAfterCourseCreate($courseId)
	{
		$esFilePath = JPATH_ROOT . '/administrator/components/com_easysocial/includes/easysocial.php';

		if (!File::exists($esFilePath))
		{
			return;
		}

		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
		$courseModel = BaseDatabaseModel::getInstance('course', 'TjlmsModel');
		require_once JPATH_ROOT . '/administrator/components/com_easysocial/includes/foundry.php';
		$point 	= FD::table('Points');
		$course = $courseModel->getCourse($courseId);
		$pointsData = new stdClass;
		$pointsData->command = 'course.' . $courseId . '.onAfterCourseCompletion';
		$pointsData->extension = 'com_tjlms';
		$state 	= $point->load(array('command' => $pointsData->command, 'extension' => $pointsData->extension));
		$pointsData->id = '';

		if ($state)
		{
			$pointsData->id = $point->id;
		}

		$pointsData->title       = Text::sprintf("PLG_TJLMS_ESPOINT_POINT_TITLE", $course->title);
		$pointsData->description = Text::sprintf("PLG_TJLMS_ESPOINT_POINT_TITLE_DESC", $course->title);
		$pointsData->alias       = "course" . $course->alias . "onAfterCourseCompletion";
		$pointsData->state       = true;

		if (!empty($course->params['espoint']['onAfterCourseCompletion']['0']))
		{
			$pointsData->points = $course->params['espoint']['onAfterCourseCompletion']['0'];
		}
		elseif (!empty($point->id))
		{
			$point->delete($point->id);
		}
		else
		{
			return;
		}

		$point->bind($pointsData);

		// Store it now.
		return $point->store();
	}
}
