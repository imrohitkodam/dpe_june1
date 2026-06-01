<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;

jimport('joomla.application.component.modellist');
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelLessontrack extends ListModel
{
	/**
	 * Function to get query.
	 *
	 * @return  STRING.
	 *
	 * @since 1.3.3
	 * */
	public function getListQuery()
	{
		$query = $this->_db->getQuery(true);
		$query->select('*');
		$query->from($this->_db->qn('#__tjlms_lesson_track', 'lt'));

		// Condition to get last attempt info of lessons of user
		if ($this->getState("lessons_last_attempt") && $this->getState("lesson_id") && $this->getState("user_id"))
		{
			// Subquery to get last attempt lesson track id
			$subQuery = $this->_db->getQuery(true);
			$subQuery->select('MAX(id)');
			$subQuery->from($this->_db->qn('#__tjlms_lesson_track'));

			$lessonIdsStr = $this->getState("lesson_id");

			if (is_array($lessonIdsStr))
			{
				$lessonIdsStr = implode(",", $lessonIdsStr);
			}

			$subQuery->where($this->_db->qn('lesson_id') . " IN ({$lessonIdsStr})");
			$subQuery->where($this->_db->qn('user_id') . '=' . (int) $this->getState("user_id"));
			$subQuery->group($this->_db->qn('lesson_id'));

			// Subquery added to get the data of last attempt lesson
			$query->where($this->_db->qn('id') . " IN ({$subQuery})");

			return $query;
		}

		if (is_array($this->getState("lesson_id")))
		{
			$lessonIdsStr = implode(",", $this->getState("lesson_id"));

			$query->where($this->_db->qn('lt.lesson_id') . " IN ({$lessonIdsStr})");
		}
		elseif ($this->getState("lesson_id"))
		{
			$query->where($this->_db->qn('lt.lesson_id') . '=' . (int) $this->getState("lesson_id"));
		}

		if ($this->getState("user_id"))
		{
			$query->where($this->_db->qn('lt.user_id') . '=' . (int) $this->getState("user_id"));
		}

		if ($this->getState("unique_users"))
		{
			$query->clear('select');
			$query->select('lt.lesson_id, count(*) as unique_users');
			$query->where('lt.attempt = 1');
			$query->group($this->_db->qn('lt.lesson_id'));
		}

		if ($this->getState("unique_lessons"))
		{
			$query->clear('select');
			$query->select('count(*) as unique_lessons');
			$query->group($this->_db->qn('lt.user_id'));
		}

		$courseId = (int) $this->getState("course_id");

		if ($courseId)
		{
			$query->join('LEFT', $this->_db->qn('#__tjlms_lessons', 'l') . ' ON (' . $this->_db->qn('l.id') . ' = ' . $this->_db->qn('lt.lesson_id') . ')');
			$query->where($this->_db->qn('l.course_id') . ' = ' . $this->_db->q((int) $courseId));
			$query->where($this->_db->qn('lt.user_id') . ' = ' . (int) $this->getState("user_id"));
		}

		if ($this->getState("last_attempt_track"))
		{
			$query->order($this->_db->qn('lt.id') . " " . 'DESC');
			$query->setlimit(1);
		}
		else
		{
			// Add the list ordering clause.
			$orderCol  = $this->getState('list.ordering');
			$orderDirn = $this->getState('list.direction');

			if (!in_array(strtolower($orderDirn), array('desc', 'asc')))
			{
				$orderDirn = 'desc';
			}

			if ($orderCol && $orderDirn)
			{
				$query->order($this->_db->escape($orderCol . ' ' . $orderDirn));
			}
		}

		return $query;
	}

	/**
	 * Get no of completed atttempts by a user for a lesson
	 *
	 * @param   int  $lessonId  Lesson id
	 *
	 * @param   int  $userId    User id
	 *
	 * @return   attempts count
	 *
	 * @since   1.0
	 */
	public function getLastAttemptonLesson($lessonId, $userId)
	{
		static $lessonAttempts = array();

		$hash = md5($lessonId . $userId);

		if (isset($lessonAttempts[$hash]))
		{
			return $lessonAttempts[$hash];
		}

		try
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select("*");
			$query->from($db->qn('#__tjlms_lesson_track'));
			$query->where($db->qn('lesson_id') . "=" . $db->q((int) $lessonId));
			$query->where($db->qn('user_id') . " = " . $db->q((int) $userId));
			$query->order("id DESC");
			$query->setLimit(1);
			$db->setQuery($query);

			return $lessonAttempts[$hash] = $db->loadObject();
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Function to expire the certificates & archive the corresponding lesson attempts.
	 *
	 * @param   array  $lessonsId  course id
	 * @param   array  $usersId    user id array
	 *
	 * @since   _DEPLOY_VERSION_
	 *
	 * @return  array|void
	 */
	public function archiveBulkAttempts($lessonsId, $usersId)
	{
		if (!empty($lessonsId) && !empty($usersId))
		{
			$db      = Factory::getDBO();
			$user_id = Factory::getUser()->id;
			$query   = $db->getQuery(true);
			$query->select('lt.*');
			$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
			$query->where('lt.user_id IN (' . implode(',', $usersId) . ')');
			$query->where('lt.lesson_id IN (' . implode(',', $lessonsId) . ')');
			$db->setQuery($query);

			$lessonTrackData = $db->loadObjectList();

			foreach ($lessonTrackData as $data)
			{
				$insertObj                  = $data;
				$insertObj->lesson_track_id = $data->id;
				$insertObj->archive_date    = Factory::getDate()->toSQL();
				
				// To check the lesson id is present in the lesson track archive table or not if not then insert 
				Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
				$tjLessontrackArchiveTable = Table::getInstance('lessontrackarchive', 'TjlmsTable');
				$tjLessontrackArchiveTable->load(array('lesson_track_id' => $data->id, 'id' => $data->id));

				if (empty($tjLessontrackArchiveTable->id))
				{
					$db->insertObject('#__tjlms_lesson_track_archive', $insertObj);
				}
				
			}

			// Archive scorm attempts.
			$this->archiveScormAttempts($lessonsId, $usersId);

			$lessonTrackIds = array_column($lessonTrackData, 'id');

			if (!empty($lessonTrackIds))
			{
				$db = Factory::getDbo();
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__tjlms_lesson_track'))
					->where('id IN (' . implode(',', $lessonTrackIds) . ')');
				$db->setQuery($query);

				$db->execute();
			}
		}
	}

	/**
	 * Function to expire the certificates & archive the corresponding lesson attempts.
	 *
	 * @param   array  $lessonsId  lesson id array
	 * @param   array  $usersId    user id array
	 *
	 * @since   _DEPLOY_VERSION_
	 *
	 * @return  array|void
	 */
	public function archiveScormAttempts($lessonsId, $usersId)
	{
		if (empty($lessonsId) && empty($usersId))
		{
			return false;
		}

		$db      = Factory::getDBO();
		$query   = $db->getQuery(true);
		$query->select('sc.id');
		$query->from($db->quoteName('#__tjlms_scorm', 'sc'));
		$query->where('sc.lesson_id IN (' . implode(',', $lessonsId) . ')');
		$db->setQuery($query);
		$scormData = $db->loadObjectList();
		$scormIds = array_column($scormData, 'id');

		if ($scormIds)
		{
			$query   = $db->getQuery(true);
			$query->select('sct.*');
			$query->from($db->quoteName('#__tjlms_scorm_scoes_track', 'sct'));
			$query->where('sct.scorm_id IN (' . implode(',', $scormIds) . ')');
			$query->where('sct.userid IN (' . implode(',', $usersId) . ')');
			$db->setQuery($query);
			$scormTrackData = $db->loadObjectList();

			foreach ($scormTrackData as $data)
			{
				$scormObj                       = $data;
				$scormObj->scorm_scoes_track_id = $data->id;
				$scormObj->archive_date         = Factory::getDate()->toSQL();

				Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
				$tjLessontrackArchiveTable = Table::getInstance('Scromscoestrackarchive', 'TjlmsTable');
				$tjLessontrackArchiveTable->load(array('scorm_scoes_track_id' => $data->id, 'id' => $data->id));

				if (empty($tjLessontrackArchiveTable->id))
				{
					$db->insertObject('#__tjlms_scorm_scoes_track_archive', $scormObj);
				}
			}

			$scormTrackIds = array_column($scormTrackData, 'id');

			if (!empty($scormTrackIds))
			{
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__tjlms_scorm_scoes_track'))
					->where('id IN (' . implode(',', $scormTrackIds) . ')');
				$db->setQuery($query);
				$db->execute();
			}
		}
	}
}
