<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tjlms
 * @copyright  Copyright (C) 2005 - 2015. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\CMSPlugin;

jimport('joomla.filesystem.file');
jimport('joomla.html.parameter');
jimport('joomla.plugin.plugin');
jimport('joomla.application.component.helper');

// Load language file for plugin.
$lang = Factory::getLanguage();
$lang->load('plg_system_lmsacymailing', JPATH_ADMINISTRATOR);

if (!File::exists(JPATH_ADMINISTRATOR . '/components/com_acymailing/helpers/helper.php'))
{
	echo Text::_("PLG_TJLMS_SYSTEM_ACYMAILING_NOCOMPONENT_ERROR_MSG");

	return false;
}

include_once JPATH_ADMINISTRATOR . '/components/com_acymailing/helpers/helper.php';
JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

/**
 * Methods supporting a list of Tjlms action.
 *
 * @since  1.0.0
 */

class PlgSystemLmsacymailing extends CMSPlugin
{
	/**
	 * Constructor - Function used as a contructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @retunr  class object
	 *
	 * @since  1.0.0
	 */
	public function __construct($subject, $config)
	{
		parent::__construct($subject, $config);
		$this->db		= Factory::getDbo();
	}

	/**
	 * Function used as a trigger after each course creation.
	 * This adds an entry into the acymailing list
	 *
	 * @param   INT     $courseId       course ID
	 * @param   INT     $courseCreator  course creator user ID
	 * @param   STRING  $courseTitle    course tilte
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterCourseCreation($courseId, $courseCreator, $courseTitle)
	{
		$query	= $this->db->getQuery(true);

		$query	->select('*')
				->from($this->db->quoteName('#__tjlms_courses'))
				->where($this->db->quoteName('id') . ' = ' . $courseId);

		$this->db		->setQuery($query);
		$courseDetail = $this->db->loadObject();

		$newlist				= new stdClass;
		$newlist->name			= $courseTitle;
		$newlist->description	= $courseDetail->short_desc;
		$newlist->published		= 1;
		$newlist->userid		= $courseCreator;

		$listClass = acymailing_get('class.list');
		$listid = $listClass->save($newlist);

		$params = array();

		if ($courseDetail->params)
		{
			$params = (array) json_decode($courseDetail->params);
		}

		$params['metadata.acymailing_list'] = $listid;
		$courseDetail->params = json_encode($params, false);

		if (!$this->db->updateObject('#__tjlms_courses', $courseDetail, 'id'))
		{
			echo $db->getErrorMsg();
		}

		return true;
	}

	/**
	 * Function used as a trigger after user successfully enrolled  for a course.
	 *
	 * @param   INT  $actorId     user has been enrolled
	 * @param   INT  $state       Enrollment state
	 * @param   INT  $courseId    course ID
	 * @param   INT  $enrolledBy  user who enrolled the actor
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterCourseEnrol($actorId, $state, $courseId, $enrolledBy)
	{
		$actorDetails = Factory::getUser($actorId);
		$myUser				= new stdClass;
		$myUser->email		= $actorDetails->email;
		$myUser->name		= $actorDetails->name;
		$myUser->userid		= $actorId;
		$myUser->confirmed	= 1;
		$myUser->source		= $courseId;
		$subscriberClass	= acymailing_get('class.subscriber');
		$subid				= $subscriberClass->save($myUser);

		if ($subid)
		{
			$enrolTable = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $this->db));
			$enrolTable->load(array('user_id' => $actorId, 'course_id' => $courseId));

			if ($enrolTable->id)
			{
				$this->saveSubscription($enrolTable->course_id, $enrolTable->user_id, $enrolTable->state);
			}
		}

		return true;
	}

	/**
	 * Function is triggered when enrollment state is changed
	 *
	 * @param   INT  $enrolmentId  primary key of the enrolment table
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterEnrolementStatusChange($enrolmentId)
	{
		$enrolTable = Table::getInstance('Enrolledusers', 'TjlmsTable', array('dbo', $this->db));
		$enrolTable->load($enrolmentId);

		if ($enrolTable->id)
		{
			$this->saveSubscription($enrolTable->course_id, $enrolTable->user_id, $enrolTable->state);
		}

		return true;
	}

	/**
	 * Function is triggered when enrollements are deleted from manageenrollments
	 *
	 * @param   INT  $enrolmentIds      array of primary keys of the enrolment table
	 * @param   INT  $enrolmentDetails  array([enrolmentid]= object(course_id,user_id))
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterEnrolementsDelete($enrolmentIds, $enrolmentDetails)
	{
		foreach ($enrolmentDetails as $enrolmentId => $object)
		{
			$this->saveSubscription($object->course_id, $object->user_id, '-1');
		}

		return true;
	}

	/**
	 * Function is called internally to subscribe or unsubscribe a user from a list
	 *
	 * @param   INT  $courseId  Course id from which user is enrolled/deenrolled/removed
	 * @param   INT  $userId    Use who is getting  enrolled/deenrolled/removed
	 * @param   INT  $state     1 to publish/ 0 to unplubhish/ -1 to remove the entry
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	private function saveSubscription($courseId, $userId, $state)
	{
		$courseTable = Table::getInstance('Course', 'TjlmsTable', array('dbo', $this->db));
		$courseTable->load(array('id' => $courseId));

		$courseParams = json_decode($courseTable->params);
		$listId = $courseParams->{'metadata.acymailing_list'};

		if ($listId)
		{
			$subscriberClass	= acymailing_get('class.subscriber');
			$subid				= $subscriberClass->subid($userId);

			$newList	=	array();

			if ($state == 0 || $state == 1)
			{
				$newList	=	array('status'	=> ($state) ? '1' : '-1');
			}

			$list	=	array($listId => $newList);

			$status = $subscriberClass->saveSubscription($subid, $list);

			$application = Factory::getApplication();

			if ($status)
			{
				if ($state == 0 || $state == 1)
				{
					$msg = ($state == 1) ? Text::_('PLG_TJLMS_SYSTEM_ACYMAILING_LISTSUB') : Text::_('PLG_TJLMS_SYSTEM_ACYMAILING_LISTUNSUB');
				}
				else
				{
					$msg = Text::_('PLG_TJLMS_SYSTEM_ACYMAILING_LISTREMOVE');
				}

				$application->enqueueMessage($msg);
			}
			else
			{
				$application->enqueueMessage(Text::_('PLG_TJLMS_SYSTEM_ACYMAILING_ERROR_MSG'), 'error');
			}
		}

		return true;
	}
}
