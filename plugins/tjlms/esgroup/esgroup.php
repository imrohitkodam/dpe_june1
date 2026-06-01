<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,EasySocial
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die();
use Joomla\CMS\Uri\Uri;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

require_once JPATH_ROOT . '/administrator/components/com_tjlms/elements/groupcategories.php';
require_once JPATH_ROOT . '/administrator/components/com_tjlms/elements/grouptype.php';
require_once JPATH_SITE . '/components/com_tjlms/helpers/courses.php';


/**
 * Joomla user group tjintegration Plugin
 *
 * @since  1.3.10
 */
class PlgTjlmsEsgroup extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
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
		$params = ComponentHelper::getParams('com_tjlms');
		$app    = Factory::getApplication();

		if ($params['social_integration'] == 'easysocial' && $app->isClient('administrator'))
		{
			$jinput = $app->input;
			$option = $jinput->get("option");
			$view   = $jinput->get("view", '');
			$cid    = $jinput->get('id', 0, 'INT');

			$options           = array();
			$options['IdOnly'] = 1;
			$ComtjlmsHelper    = new ComtjlmsHelper;
			$enrolled_users    = count($ComtjlmsHelper->getCourseEnrolledUsers($cid, $options));

			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models');
			$courseModel = BaseDatabaseModel::getInstance('course', 'TjlmsModel');
			$course = $courseModel->getCourse($cid);

			if (!empty($course->params['esgroup']['onAfterEnrollEsGroups']))
			{
				$groups = $course->params['esgroup']['onAfterEnrollEsGroups'];
				$groups = count($groups);
			}

			if (empty($groups))
			{
				$groups = 0;
			}

			$document = Factory::getDocument();
			$document->addScript(Uri::root(true) . '/plugins/tjlms/esgroup/esgroup.js');
			$document->addScriptDeclaration('esGroup.enrolledUsers= "' . $enrolled_users . '";');
			$document->addScriptDeclaration('esGroup.groups= ' . $groups . ';');
			$document->addScriptDeclaration('esGroup.init();');


			if ($option == 'com_tjlms' && $view == 'course')
			{
				if (File::exists(JPATH_ROOT . '/administrator/components/com_easysocial/includes/easysocial.php'))
				{
					return array(
						'path' => JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/esgroup.xml', 'name' => $this->_name
					);
				}
			}
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
	 * @return  mixed
	 *
	 * @since   1.3.10
	 */
	public function onAfterCourseEnrol($actorId, $state, $courseId, $enrolledBy, $notifyUser = 1)
	{
		// Previous code was calling backend's course model from order view, now it will call frontend's course model with below code.
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjlms/models', 'TjlmsModel');
		$courseModel = BaseDatabaseModel::getInstance('course', 'TjlmsModel', array('ignore_request' => true));
		$course       = $courseModel->getItem($courseId);
		$courseParams = json_decode($course->params);
		$state        = 1;

		// If for whatever reason, ES library still doesn't exist, we need to show proper message
		$mainFile = JPATH_ROOT . '/administrator/components/com_easysocial/includes/easysocial.php';

		if (!File::exists($mainFile))
		{
			return;
		}

		// Engine is required anywhere EasySocial is used.
		require_once $mainFile;

		$user = Factory::getUser($actorId);

		if ($user->id)
		{
			foreach ($courseParams->esgroup->onAfterEnrollEsGroups as $value)
			{
				$group = ES::group($value);

				if (!$group->createMember($user->id, true))
				{
					return false;
				}
			}
		}
	}

	/**
	 * On Creating a course
	 *
	 * Method is called after a course create.
	 * This method create a EasySocail group.
	 *
	 * @param   INT    $courseId       course id of course created
	 * @param   INT    $courseCreator  user id of user who created the course
	 * @param   ARRAY  $data           course data.
	 *
	 * @return  false
	 *
	 * @since   1.3.12
	 */
	public function onAfterCourseCreate($courseId, $courseCreator, $data)
	{
		$db = Factory::getDbo();

		$data['params'] = json_decode($data['params'], true);
		$autoCreateGroup = $data['params']['esgroup']['coursegroup'];

		$table = Table::getInstance('Course', 'TjlmsTable', array('dbo', $db));
		$group_created = '';

		if ($autoCreateGroup == 'create')
		{
			$group_created = $this->saveCourseGroup($data);

			if ($group_created)
			{
				// Save group ID in courses table
				$obj           = new stdclass;
				$obj->id       = $courseId;
				$obj->group_id = $group_created;

				$table->load($courseId);

				$onAfterEnrollEsGroups = array();
				$esgArray = array();
				$onAfterEnrollEsGroups['onAfterEnrollEsGroups'] = (array) $group_created;
				$esgArray['esgroup'] = $onAfterEnrollEsGroups;

				if (!empty($table->params))
				{
					$cparams = (array) json_decode($table->params);
					$cparams['esgroup'] = $onAfterEnrollEsGroups;
					$obj->params = json_encode($cparams);
				}
				else
				{
					$obj->params = json_encode($esgArray);
				}

				if (!$db->updateObject('#__tjlms_courses', $obj, 'id'))
				{
					echo $db->stderr();

					return false;
				}
			}
		}
	}

	/**
	 * Create group depending upon the integration set
	 *
	 * @param   ARRAY  $data  Course data
	 *
	 * @return   INT  Group ID
	 *
	 * @since 1.0.0
	 */
	public function saveCourseGroup($data)
	{
		$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';
		JLoader::import('comtjlmsHelper', $path);
		$this->ComtjlmsHelper = new comtjlmsHelper;

		$groupId = '';
		$options = array();

		$catId = $data['params']['esgroup']['groupCategory'];
		$type  = $data['params']['esgroup']['groupType'];

		if ($catId)
		{
			$data['uid'] = $data['created_by'];
			$data['type'] = $type;
			$options['catId'] = $catId;
			$groupId = $this->ComtjlmsHelper->sociallibraryobj->createGroup($data, $options);

			// Add course creator to the created group
			$this->ComtjlmsHelper->sociallibraryobj->addMemberToGroup($groupId, Factory::getUser($data['created_by']));
		}

		return $groupId;
	}
}
