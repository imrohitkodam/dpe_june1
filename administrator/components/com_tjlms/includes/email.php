<?php
/**
 * @package     TJLMS
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('techjoomla.tjnotifications.tjnotifications');

/**
 * TJLMS email class for all kinds of email methods
 *
 * @since  _DEPLOY_VERSION_
 */
class TjLmsEmail
{
	public $app;

	public $global;

	public $options;

	public $sitename;

	public $loggedInUser;

	public $tjnotificationClient = 'com_tjlms';

	/**
	 * Method acts as a consturctor
	 *
	 * @since   _DEPLOY_VERSION_
	 */
	public function __construct()
	{
		$this->app              = Factory::getApplication();
		$this->loggedInUser     = Factory::getUser();
		$this->sitename         = method_exists($this->app, 'getCfg') ? $this->app->getCfg('sitename') : $this->app->get('sitename');
		$this->global           = new stdClass;
		$this->global->sitename = $this->sitename;
		$this->options          = new Registry;
	}

	/**
	 * Function use to create mail content for attempts exhausted
	 *
	 * @param   INT     $userId  user id
	 * @param   OBJECT  $lesson  Lesson Details
	 *
	 * @return  void
	 *
	 * @since  1.3.34
	 */
	public function onAfterAttemptsExhaustedAndFailed($userId, $lesson)
	{
		$recipients = array();

		// Get all admin users
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__users'));
		$query->where($db->quoteName('sendEmail') . '= 1');
		$db->setQuery($query);
		$adminUsers = $db->loadObjectList();

		foreach ($adminUsers as $adminUser)
		{
			$recipients[]                = Factory::getUser($adminUser->id);
			$recipients['email']['to'][] = Factory::getUser($adminUser->id)->email;
		}

		JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
		$courseModel = BaseDatabaseModel::getInstance('Course', 'TjlmsModel', array('ignore_request' => true));
		$courseDetail = $courseModel->getItem($lesson->course_id);

		$courseCreatorEmail = Factory::getUser($courseDetail->created_by)->email;

		if (!in_array($courseCreatorEmail, $recipients['email']['to']))
		{
			$recipients[]                = Factory::getUser($courseDetail->created_by);
			$recipients['email']['to'][] = Factory::getUser($courseDetail->created_by)->email;
		}

		$user = Factory::getUser($userId);

		$replacements          = new stdClass;
		$replacements->student = $user;
		$replacements->lesson  = $lesson;

		$key    = 'attemptsExhaustedAndFailedAdmin#' . $lesson->id;
		$this->options->set('subject', $user);

		Tjnotifications::send($this->tjnotificationClient, $key, $recipients, $replacements, $this->options);
	}
}
