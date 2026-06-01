<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;


/**
 * Scorm helper
 *
 * @since  1.0.0
 * */
class ComtjlmsScormHelper
{
	/**
	 * Constructor
	 *
	 * @since  1.0.0
	 * */
	public function __construct()
	{
		$this->tjlmsdbhelperObj	=	new tjlmsdbhelper;
		$this->comtjlmstrackingHelper = new comtjlmstrackingHelper;
		$this->tjlmsLessonHelper = new TjlmsLessonHelper;
	}

	/**
	 * Get number of attempts done or can called as last attempt of a user against a sco
	 *
	 * @param   INT  $scormId  id of scorm
	 * @param   INT  $userId   id of user
	 *
	 * @return attempts done
	 *
	 * @since  1.0.0
	 */
	public function scorm_get_last_attempt($scormId, $userId)
	{
		$db = Factory::getDBO();
		$query	= $db->getQuery(true);

		$query->select('MAX(attempt)');
		$query->from($db->quoteName('#__tjlms_scorm_scoes_track'));
		$query->where($db->quoteName('userid') . " = " . $db->quote($userId));
		$query->where($db->quoteName('scorm_id') . " = " . $db->quote($scormId));

		$db->setQuery($query);
		$lastattempt = $db->loadResult();

		if (empty($lastattempt))
		{
			return '1';
		}
		else
		{
			return $lastattempt;
		}
	}

	/**
	 * Get informtation of sco from __tjlms_scorm_scoes
	 *
	 * @param   INT  $scormId  id of the scorm
	 * @param   INT  $scoId    id of the sco
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 */
	public function getSCOdata($scormId, $scoId)
	{
		$db	= Factory::getDBO();
		$query	= $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_scorm_scoes'));
		$query->where($db->quoteName('scorm_id') . " = " . $db->quote($scormId));
		$query->where($db->quoteName('id') . " = " . $db->quote($scoId));

		$db->setQuery($query);
		$res = $db->loadObject();

		return $res;
	}

	/**
	 * Get object of scorm from tjlms_scorm
	 *
	 * @param   INT  $scorm_id  id of the scorm
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 */
	public function getScormData($scorm_id)
	{
		$db	= Factory::getDBO();
		$query	= $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_scorm'));
		$query->where($db->quoteName('id') . " = " . $db->quote($scorm_id));
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Get all the scoes belonging to scorm
	 *
	 * @param   INT  $scormId  id of the scorm
	 *
	 * @return  Object list
	 *
	 * @since  1.0.0
	 */
	public function getScormScoes($scormId)
	{
		$db = Factory::getDBO();
		$query	= $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_scorm_scoes'));
		$query->where($db->quoteName('scorm_id') . " = " . $db->quote($scormId));
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get element value from scorm_scoes_track table
	 *
	 * @param   INT     $scormId  id of the scorm
	 * @param   INT     $scoId    id of the sco
	 * @param   INT     $attempt  attempt
	 * @param   INT     $userId   id of the user
	 * @param   STRING  $element  The element who's value is to be checked
	 *
	 * @return  Object list
	 *
	 * @since  1.0.0
	 */
	public function gettrackforElementofSco($scormId, $scoId, $attempt, $userId, $element)
	{
		$db = Factory::getDBO();
		$query	= $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_scorm_scoes_track'));
		$query->where($db->quoteName('scorm_id') . " = " . $db->quote($scormId));
		$query->where($db->quoteName('sco_id') . " = " . $db->quote($scoId));
		$query->where($db->quoteName('attempt') . " = " . $db->quote($attempt));
		$query->where($db->quoteName('userid') . " = " . $db->quote($userId));
		$query->where($db->quoteName('element') . " = " . $db->quote($element));
		$db->setQuery($query);

		return $db->loadObject();
	}

	/**
	 * Get Toc tree
	 *
	 * @param   INT  $scorm_id  id of the scorm
	 * @param   INT  $user_id   id of the user
	 *
	 * @return  Object list
	 *
	 * @since  1.0.0
	 */
	public function getTocTree($scorm_id, $user_id)
	{
		$db	= Factory::getDBO();
		$result	= '';

		// If the scrom package is Multisco then only take TOC
		$query	= $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_scorm_scoes'));
		$query->where($db->quoteName('scorm_id') . " = " . $db->quote($scorm_id));
		$query->where($db->quoteName('launch') . " != '' ");
		$db->setQuery($query);
		$res = $db->loadObjectList();

		if (count($res) > 1)
		{
			// Get the scoes
			$scoes	=	self::getScormScoes($scorm_id);

			foreach ($scoes as $sco)
			{
				// Get tjlms_scorm_scoes_data of scorm
				if ($scodatas = self::scorm_get_sco($sco->id))
				{
					foreach ($scodatas as $name => $value)
					{
						$sco->$name = $value;
					}
				}
			}

			$result	= $this->scorm_get_toc_object($scoes, $scorm_id, $user_id);
		}

		return $result;
	}

	/**
	 * Get number of attempts done or can called as last attempt of a user against a sco
	 *
	 * @param   INT  $sco_id   id of sco
	 * @param   INT  $user_id  id of user
	 *
	 * @return attempts done
	 *
	 * @since  1.0.0
	 */
	public function getScoTotalAttemptsdone($sco_id, $user_id)
	{
		$db = Factory::getDBO();
		$query	= $db->getQuery(true);

		$query->select('MAX(attempt)');
		$query->from($db->quoteName('#__tjlms_scorm_scoes_track'));
		$query->where($db->quoteName('sco_id') . " = " . $db->quote($sco_id));
		$query->where($db->quoteName('userid') . " = " . $db->quote($user_id));
		$db->setQuery($query);

		$total_attempts	=	$db->loadResult();

		if ($total_attempts > 0)
		{
			return $total_attempts;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Get TOC object for scorm-will be used in case of multi sco
	 *
	 * @param   ARRAY  $scoes     contating each sco obj belonging to scorm
	 * @param   INT    $scorm_id  id of the scorm
	 * @param   INT    $user_id   id of the user
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function scorm_get_toc_object($scoes, $scorm_id, $user_id)
	{
		$result	=	array();
		$scormdata	=	self::getScormData($scorm_id);
		$modestr = $currentorg = '';
		$incomplete = false;
		$attempt	= 1;

		if (!empty($scoes))
		{
			// Retrieve user tracking data for each learning object.
			$usertracks = array();

			foreach ($scoes as $sco)
			{
				$attemptsdonebyuser = $this->getScoTotalAttemptsdone($sco->id, $user_id);
				$sco->attemptsdonebyuser = $attemptsdonebyuser;

				if (!isset($sco->isvisible))
				{
					$sco->isvisible = true;
				}

				if (empty($sco->title))
				{
					$sco->title = $sco->identifier;
				}

				if (strcasecmp($scormdata->version, 'SCORM_1.3') == 0)
				{
					$sco->prereq = true;
				}
				else
				{
					$sco->prereq = empty($sco->prerequisites) || scorm_eval_prerequisites($sco->prerequisites, $usertracks);
				}

				if ($sco->isvisible)
				{
					if (!empty($sco->launch))
					{
						$sco->statusdetails = new stdClass;
						$sco->statusdetails	=	$this->getstatusDetails($scormdata->version, $scorm_id, $sco->id, $user_id, $attemptsdonebyuser);
					}
				}

				$sco->url = 'a=' . $scorm_id . '&scoid=' . $sco->id . '&currentorg=' . $currentorg . $modestr . '&attempt=' . $attempt;

				if (!in_array($sco->id, array_keys($result)))
				{
					$result[$sco->id] = $sco;
				}
			}

			/*foreach ($scoes as $sco) {

				$attemptsdonebyuser = $this->getScoTotalAttemptsdone($sco->id,$user_id);

				if (!isset($sco->isvisible)) {
					$sco->isvisible = true;
				}

				if (empty($sco->title)) {
					$sco->title = $sco->identifier;
				}

				if(strcasecmp($scormdata->version,'SCORM_1.3') == 0){
					$sco->prereq = true;
				} else {
					$sco->prereq = empty($sco->prerequisites) || scorm_eval_prerequisites($sco->prerequisites, $usertracks);
				}

				if ($sco->isvisible) {
					if (!empty($sco->launch)) {

						$scoid = $sco->id;
						if (isset($usertracks[$sco->identifier])) {

							$usertrack = $usertracks[$sco->identifier];
							$strstatus = $usertrack->status;

							$statusicon = $usertrack->status;
							if ($sco->scormtype == 'sco') {
								$statusicon = '<img src="'.$OUTPUT->pix_url($usertrack->status, 'scorm').'" alt="'.$strstatus
								.'" title="'.$strstatus.'" />';
								$statusicon = $usertrack->status;
							} else {
								$statusicon = '<img src="'.$OUTPUT->pix_url('asset', 'scorm').'" alt="'.get_string('assetlaunched', 'scorm')
								.'" title="'.get_string('assetlaunched', 'scorm').'" />';
								$statusicon = 'complete';
							}

							if (($usertrack->status == 'notattempted') || ($usertrack->status == 'incomplete') || ($usertrack->status == 'browsed')) {
								$incomplete = true;

							}

							$strsuspended = 'suspended';

							$exitvar = 'cmi.core.exit';

							if (strcasecmp($scormdata->version,'SCORM_1.3') == 0) {
								$exitvar = 'cmi.exit';
							}

							if ($incomplete && isset($usertrack->{$exitvar}) && ($usertrack->{$exitvar} == 'suspend')) {
								$statusicon = $strstatus.' - '.$strsuspended;
							}
							$attemptsdonebyuser	=	$usertrack->attemptsdonebyuser;

						} else {
							$scoid = $sco->id;
							$incomplete = true;

							if ($sco->scormtype == 'sco') {
								$statusicon = 'notattempted';
							} else {
								$statusicon = 'asset';
							}
							$attemptsdonebyuser	= 0;
						}
					}
				}

				if (empty($statusicon)) {
					$sco->statusicon = '<img src="'.$OUTPUT->pix_url('notattempted', 'scorm')
					.'"alt="'.get_string('notattempted', 'scorm').'" title="'.get_string('notattempted', 'scorm').'" />';
					 $sco->statusicon = 'notattempted';
				} else {
					$sco->statusicon = $statusicon;
				}
				$sco->url = 'a='.$scorm_id.'&scoid='.$sco->id.'&currentorg='.$currentorg.$modestr.'&attempt='.$attempt;
				$sco->incomplete = $incomplete;

				if (!in_array($sco->id, array_keys($result))) {
					$result[$sco->id] = $sco;
				}
			}*/
		}

		// Get the parent scoes!
		$result = $this->scorm_get_toc_get_parent_child($result);

		// Be safe, prevent warnings from showing up while returning array
		if (!isset($scoid))
		{
			$scoid = '';
		}

		return array('scoes' => $result, 'usertracks' => $usertracks, 'scoid' => $scoid);
	}

	/**
	 * Get status deatils like started accession on, timespent, last accessed on
	 *
	 * @param   STRING  $sversion      version of scorm
	 * @param   INT     $scormId       id of the scorm
	 * @param   INT     $scoId         id of the sco
	 * @param   INT     $oluser_id     id of the user
	 * @param   INT     $last_attempt  attempt
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function getstatusDetails($sversion, $scormId, $scoId, $oluser_id, $last_attempt)
	{
		$statusDetails = '';

		if ($last_attempt > 0)
		{
			$statusDetails = new stdClass;
			$statusDetails->started_on = Text::_('COM_TJLMS_NEVER');
			$statusDetails->last_accessed_on = Text::_('COM_TJLMS_NEVER');
			$statusDetails->total_time_spent = 0;

			// Get started on
			$track = self::gettrackforElementofSco($scormId, $scoId, 1, $oluser_id, 'x.start.time');

			if ($track)
			{
				$statusDetails->started_on = date('Y-m-d', $track->value);
			}

			// Get last accessed on
			$lasttrack = self::gettrackforElementofSco($scormId, $scoId, $last_attempt, $oluser_id, 'x.start.time');

			if ($lasttrack)
			{
				$statusDetails->last_accessed_on = date('Y-m-d', $lasttrack->value);
			}

			// Get total time spent
			$element	= 'cmi.total_time';

			if (strcasecmp($sversion, 'SCORM_1.3') == 0)
			{
					$element	= 'cmi.total_time';
			}

			$db = Factory::getDBO();
			$query	= $db->getQuery(true);
			$query->select('SUM(TIME_TO_SEC(value)) as time_spent');
			$query->from($db->quoteName('#__tjlms_scorm_scoes_track'));
			$query->where($db->quoteName('sco_id') . " = " . $db->quote($scoId));
			$query->where($db->quoteName('userid') . " = " . $db->quote($oluser_id));
			$query->where($db->quoteName('element') . " = " . $db->quote($element));
			$db->setQuery($query);
			$total_time_spent = $db->loadResult();

			$statusDetails->total_time_spent = gmdate('H:i:s', $total_time_spent);
			$statusDetails->lastAttemptStatus = $this->lastAttemptStatus($sversion, $scormId, $scoId, $oluser_id, $last_attempt);

			$statusNscore = $this->getstatusbyAttemptsgrading($sversion, $scormId, $scoId, $oluser_id, $last_attempt);
			/*print_r($statusNscore);die;*/
			$statusDetails->status = $statusNscore->lesson_status;
			$statusDetails->score = $statusNscore->score;
		}

		return $statusDetails;
	}

	/**
	 * Get status of a user for attempt provided
	 *
	 * @param   STRING  $sversion  version of scorm
	 * @param   INT     $scormId   id of the scorm
	 * @param   INT     $scoId     id of the sco
	 * @param   INT     $userId    id of the user
	 * @param   INT     $attempt   attempt
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function getstatusbyAttemptsgrading($sversion, $scormId, $scoId, $userId, $attempt)
	{
		$statuselement	= 'cmi.core.lesson_status';
		$scoreelement = 'cmi.core.score.raw';

		if (strcasecmp($sversion, 'SCORM_1.3') == 0)
		{
			$statuselement = 'cmi.completion_status';
			$scoreelement = 'cmi.score.raw';
		}

		$db = Factory::getDBO();
		$res	=	new stdClass;
		$res->score = '0';
		$res->lesson_status = Text::_('COM_TJLMS_NOT_STARTED');

		$scorm = self::getScormData($scormId);
		$lesson = $this->tjlmsLessonHelper->getLessonColumn($scorm->lesson_id, 'attempts_grade');

		if (!empty($lesson))
		{
			switch ($lesson->attempts_grade)
			{
				case "0" :
					$result = '';

					$query	= $db->getQuery(true);
					$query->select($db->quoteName(array('attempt', 'value')));
					$query->from($db->quoteName('#__tjlms_scorm_scoes_track'));
					$query->where(
								$db->quoteName('value') . " =  (select max(value)
								from  #__tjlms_scorm_scoes_track st
								where st.sco_id='" . $scoId . "'
								AND st.userid= " . $userId . " AND element = " . $db->quote($scoreelement) . ")");
					$query->where($db->quoteName('userid') . " = " . $db->quote($userId));
					$query->where($db->quoteName('sco_id') . " = " . $db->quote($scoId));
					$query->where($db->quoteName('element') . " = " . $db->quote($scoreelement));
					$db->setQuery($query);
					$result = $db->loadObject();

					if ($result)
					{
						$res->score = $result->score;
						$res->lesson_status = $this->gettrackforElementofSco($scormId, $scoId,  $result->attempt, $userId, $statuselement);
					}
					else
					{
						$query	= $db->getQuery(true);
						$query->select($db->quoteName(array('value')));
						$query->from($db->quoteName('#__tjlms_scorm_scoes_track'));
						$query->where($db->quoteName('userid') . " = " . $db->quote($userId));
						$query->where($db->quoteName('sco_id') . " = " . $db->quote($scoId));
						$query->where($db->quoteName('element') . " = " . $db->quote($statuselement));
						$query->where(" ( " . $db->quoteName('value') . " =  'completed'" . " OR " . $db->quoteName('value') . " =  'passed'" . " )");
						$query->order('attempt DESC');
						$db->setQuery($query);
						$res->lesson_status = $db->loadResult();
					}

				case "1" :

						/*$sql = "select (AVG(score)) from  #__tjlms_lesson_track
								where lesson_id='" . $lesson->id . "' AND user_id=" . $user_id;
						$db->setQuery($sql);
						$score = $db->loadResult();

						$sql = "select lesson_status from  #__tjlms_lesson_track
								where lesson_id='" . $lesson->id . "' AND  user_id=" . $user_id;

						$db->setQuery($sql);
						$status = $db->loadColumn();*/

						$query	= $db->getQuery(true);
						$query->select('AVG(value)');
						$query->from($db->quoteName('#__tjlms_scorm_scoes_track'));
						$query->where($db->quoteName('userid') . " = " . $db->quote($userId));
						$query->where($db->quoteName('sco_id') . " = " . $db->quote($scoId));
						$query->where($db->quoteName('element') . " = " . $db->quote($scoreelement));
						$db->setQuery($query);
						$result = $db->loadResult();

						if ($result)
						{
							$res->score = $result;
						}

						/*if (cmi.student_data.mastery_score !== '' && cmi.core.score.raw !== '') {*/

						break;
				case "2" :
						$track = $this->gettrackforElementofSco($scormId, $scoId, 1, $userId, $scoreelement);

						if ($track)
						{
							$res->score = $track->value;
						}

						$track = $this->gettrackforElementofSco($scormId, $scoId, 1, $userId, $statuselement);

						if ($track)
						{
							$res->lesson_status = $track->value;
						}

						break;
				case "3" :

						$query	= $db->getQuery(true);
						$query->select($db->quoteName(array('attempt','value')));
						$query->from($db->quoteName('#__tjlms_scorm_scoes_track'));
						$query->where($db->quoteName('userid') . " = " . $db->quote($userId));
						$query->where($db->quoteName('sco_id') . " = " . $db->quote($scoId));
						$query->where($db->quoteName('element') . " = " . $db->quote($statuselement));
						$query->where(" ( " . $db->quoteName('value') . " =  'completed'" . " OR " . $db->quoteName('value') . " =  'passed'" . " )");
						$query->order('attempt DESC');
						$db->setQuery($query);
						$result = $db->loadObject();

						if ($result)
						{
							$res->lesson_status = $result->value;
							$track = $this->gettrackforElementofSco($scormId, $scoId, $result->attempt, $userId, $scoreelement);

							if ($track)
							{
								$res->score = $track->value;
							}
						}

						break;
			}
		}

		return $res;
	}

	/**
	 * Get status of a user for attempt provided
	 *
	 * @param   STRING  $sversion  version of scorm
	 * @param   INT     $scormId   id of the scorm
	 * @param   INT     $scoId     id of the sco
	 * @param   INT     $userId    id of the user
	 * @param   INT     $attempt   attempt
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function lastAttemptStatus($sversion, $scormId, $scoId, $userId, $attempt)
	{
		$element	= 'cmi.core.lesson_status';

		if (strcasecmp($sversion, 'SCORM_1.3') == 0)
		{
			$element = 'cmi.completion_status';
		}

		$track = self::gettrackforElementofSco($scormId, $scoId, $attempt, $userId, $element);

		if ($track)
		{
			return $track->value;
		}
		else
		{
			return '';
		}
		/*return $this->tjlmsdbhelperObj->get_records('value as status','tjlms_scorm_scoes_track',
		 array('sco_id'=>$sco_id,'userid'=>$user_id,'element'=>$element,'attempt'=>$attemptsdonebyuser),'','loadResult');*/
	}

	/**
	 * Used to get which attempt should user start on clicking on Launch button
	 *
	 * @param   INT  $sversion         scorm version
	 * @param   INT  $scormId          scorm id
	 * @param   INT  $scoId            sco id
	 * @param   INT  $userId           user id
	 * @param   INT  $lastAttempt      last attempt number done by a user
	 * @param   INT  $attemptsAllowed  number of attempts allowed
	 *
	 * @return 0  if last attempt is incomplete
	 * return attempt > 0 if new attempt
	 * return -1 if attempts are exhausted
	 *
	 * @since 1.0.0
	 * */
	public function getScoAttempttobeLaunched($sversion, $scormId, $scoId, $userId, $lastAttempt, $attemptsAllowed)
	{
		$lastAttemptStatus = self::lastAttemptStatus($sversion, $scormId, $scoId, $userId, $lastAttempt);

		$attempt = 0;

		/*ATTEMPT == 0 --> FOR OLD ATTEMPT....WILL ASK FOR RESUME
		ATTEMPT + 1 --> NEW ATTEMPT
		ATTEMPT -1 --> ATTMEPT NOT ALLOWED*/

		/* Last attempt is complete*/
		if (($lastAttemptStatus == 'completed' || $lastAttemptStatus == 'passed' || $lastAttemptStatus == 'failed'))
		{
			if ($attemptsAllowed > 0)
			{
				if ($lastAttempt < $attemptsAllowed)
				{
					$attempt = $lastAttempt + 1;
				}
				else
				{
					$attempt = -1;
				}
			}
			else
			{
				$attempt = $lastAttempt + 1;
			}
		}
		else
		{
			// If last attempt is the last allowed attempt
			if ($lastAttempt == $attemptsAllowed)
			{
				$attempt = $lastAttempt;
			}
		}

		return $attempt;
	}

	/**
	 * Get parent of provided scos
	 *
	 * @param   INT  $result  array of scoes
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function scorm_get_toc_get_parent_child($result)
	{
		$final = array();
		$level = 0;
		$prevparent = '/';
		ksort($result);

		foreach ($result as $sco)
		{
			if ($sco->parent == '/')
			{
				$final[$level][$sco->identifier] = $sco;
				$prevparent = $sco->identifier;
				unset($result[$sco->id]);
			}
			else
			{
				if ($sco->parent == $prevparent)
				{
					$final[$level][$sco->identifier] = $sco;
					$prevparent = $sco->identifier;
					unset($result[$sco->id]);
				}
				else
				{
					if (!empty($final[$level]))
					{
						$found = false;

						foreach ($final[$level] as $fin)
						{
							if ($sco->parent == $fin->identifier)
							{
								$found = true;
							}
						}

						if ($found)
						{
							$final[$level][$sco->identifier] = $sco;
							unset($result[$sco->id]);
							$found = false;
						}
						else
						{
							$level++;
							$final[$level][$sco->identifier] = $sco;
							unset($result[$sco->id]);
						}
					}
				}
			}
		}

		for ($i = 0; $i <= $level; $i++)
		{
			$prevparent = '';

			foreach ($final[$i] as $ident => $sco)
			{
				if (empty($prevparent))
				{
					$prevparent = $ident;
				}

				if (!isset($final[$i][$prevparent]->children))
				{
					$final[$i][$prevparent]->children = array();
				}

				if ($sco->parent == $prevparent)
				{
					$final[$i][$prevparent]->children[] = $sco;
					$prevparent = $ident;
				}
				else
				{
					$parent = false;

					foreach ($final[$i] as $identifier => $scoobj)
					{
						if ($identifier == $sco->parent)
						{
							$parent = $identifier;
						}
					}

					if ($parent !== false)
					{
						$final[$i][$parent]->children[] = $sco;
					}
				}
			}
		}

		$results = array();

		for ($i = 0; $i <= $level; $i++)
		{
			$keys = array_keys($final[$i]);
			$results[] = $final[$i][$keys[0]];
		}

		return $results;
	}

	/**
	 * data stored for sco against user and attempt
	 *
	 * @param   INT  $scorm_id  id of the scorm
	 * @param   INT  $scoid     id of the sco
	 * @param   INT  $userid    id of the user
	 * @param   INT  $attempt   attempt
	 *
	 * @return  Object list of user data
	 *
	 * @since  1.0.0
	 */
	public function getUserScodata($scorm_id, $scoid, $userid, $attempt)
	{
		$mode = '';

		$app = Factory::getApplication();
		$oluser	=	Factory::getUser($userid);
		$scormdata	=	$this->getScormData($scorm_id);

		$userdata = new stdClass;
		$userdata->status = '';
		$userdata->score_raw = '';

		if ($usertrack = $this->scorm_get_scoestracks($scorm_id, $scoid, $userid, $attempt))
		{
			if ((strcasecmp($scormdata->version, 'SCORM_1.2') == 0)
				|| (isset($usertrack->{'cmi.exit'}) && ($usertrack->{'cmi.exit'} == 'suspend')))
			{
					foreach ($usertrack as $key => $value)
					{
						$userdata->$key = addslashes($value);
					}
			}
			else
			{
				$userdata->status = '';
				$userdata->score_raw = '';
			}
		}

		if ($app->getUserState('com_tjlms' . 'lesson.resetProgress') == 1)
		{
			$userdata->{'cmi.suspend_data'} = '';
			$userdata->{'cmi.lesson_location'} = '';
		}

		$userdata->student_id = $oluser->id;
		$userdata->student_name = $oluser->name;
		$userdata->mode = 'normal';

		if (!empty($mode))
		{
			$userdata->mode = $mode;
		}

		if ($userdata->mode == 'normal')
		{
			$userdata->credit = 'credit';
		}
		else
		{
			$userdata->credit = 'no-credit';
		}

		if ($scodatas = $this->scorm_get_sco($scoid))
		{
			foreach ($scodatas as $key => $value)
			{
				$userdata->$key = $value;
			}
		}

		/*else {
			print_error('cannotfindsco', 'scorm');
		}
		if (!$sco = scorm_get_sco($scoid)) {
			print_error('cannotfindsco', 'scorm');
		}*/

		// TODO : check which if to use
		if ((strcasecmp($scormdata->version, 'SCORM_1.3') == 0))
		{
			$db = Factory::getDBO();
			$query	= $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__tjlms_scorm_seq_objective'));
			$query->where($db->quoteName('sco_id') . " = " . $db->quote($scoid));
			$db->setQuery($query);
			$objectives = $db->loadObjectList();

			// @$objectives = $this->tjlmsdbhelperObj->get_records('*','tjlms_scorm_seq_objective', array('sco_id'=>$scoid),'','loadObjectList');
			$index = 0;

			foreach ($objectives as $objective)
			{
				if (!empty($objective->minnormalizedmeasure))
				{
					$userdata->{'cmi.scaled_passing_score'} = $objective->minnormalizedmeasure;
				}

				if (!empty($objective->objectiveid))
				{
					$userdata->{'cmi.objectives.N' . $index . '.id'} = $objective->objectiveid;
					$index++;
				}
			}
		}

		return $userdata;
	}

	/**
	 * Get additional parameters associated with sco defined in manifest.. like isvisible mastery score
	 *
	 * @param   INT  $scoId  id of the sco
	 *
	 * @return  Object list
	 *
	 * @since  1.0.0
	 */
	public function scorm_get_sco($scoId)
	{
		// @$scormid = $this->tjlmsdbhelperObj->get_records('scorm','tjlms_scorm_scoes',array('id'=>$scoid),'','loadObject')

		$db = Factory::getDBO();
		$query	= $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_scorm_scoes_data'));
		$query->where($db->quoteName('sco_id') . " = " . $db->quote($scoId));
		$db->setQuery($query);
		$sco = new stdClass;

		if ($scodatas = $db->loadObjectList())
		{
			foreach ($scodatas as $scodata)
			{
				$sco->{$scodata->name} = $scodata->value;
			}
		}

		/*if ($sco = $DB->get_record('scorm_scoes', array('id'=>$id))) {
			$sco = ($what == SCO_DATA) ? new stdClass() : $sco;
			if (($what != SCO_ONLY) && ($scodatas = $DB->get_records('scorm_scoes_data', array('scoid'=>$id)))) {
				foreach ($scodatas as $scodata) {
					$sco->{$scodata->name} = $scodata->value;
				}
			} else if (($what != SCO_ONLY) && (!($scodatas = $DB->get_records('scorm_scoes_data', array('scoid'=>$id))))) {
				$sco->parameters = '';
			}
			return $sco;
		} else {
			return false;
		}*/

		return $sco;
	}

	/**
	 * Get data tracked for sco against user and attept
	 *
	 * @param   INT  $scoId    id of the sco
	 * @param   INT  $attempt  attempt
	 * @param   INT  $userId   id of the user
	 *
	 * @return  Object list
	 *
	 * @since  1.0.0
	 */
	public function gettracksofSco($scoId, $attempt, $userId)
	{
		$db = Factory::getDBO();
		$query	= $db->getQuery(true);
		$query->select($db->quoteName(array('element', 'value')));
		$query->from($db->quoteName('#__tjlms_scorm_scoes_track'));
		$query->where($db->quoteName('sco_id') . " = " . $db->quote($scoId));
		$query->where($db->quoteName('attempt') . " = " . $db->quote($attempt));
		$query->where($db->quoteName('userid') . " = " . $db->quote($userId));
		$query->order('element ASC');
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Get data tracked for sco against user and attept
	 *
	 * @param   OBJECT  $scorm    Scorm object
	 * @param   INT     $scoid    id of the sco
	 * @param   INT     $userid   id of the user
	 * @param   INT     $attempt  attempt
	 *
	 * @return  Object list
	 *
	 * @since  1.0.0
	 */
	public function scorm_get_scoestracks($scorm, $scoid, $userid, $attempt)
	{
		/*if ($tracks = $this->tjlmsdbhelperObj->get_records(' element , value ','tjlms_scorm_scoes_track ',
		 *  array('userid'=>$userid, 'sco_id'=>$scoid, 'attempt'=>$attempt), 'element ASC','loadObjectList'))
		{*/
		if ($tracks = self::gettracksofSco($scoid, $attempt, $userid))
		{
			$usertrack = new stdClass;
			$usertrack->userid = $userid;
			$usertrack->scoid = $scoid;

			// Defined in order to unify scorm1.2 and scorm2004
			$usertrack->score_raw = '';
			$usertrack->status = '';
			$usertrack->total_time = '00:00:00';
			$usertrack->session_time = '00:00:00';
			$usertrack->timemodified = 0;

			foreach ($tracks as $track)
			{
				$element = $track->element;
				$usertrack->{$element} = $track->value;

				switch ($element)
				{
					case 'cmi.core.lesson_status':
					case 'cmi.completion_status':

						if ($track->value == 'not attempted')
						{
							$track->value = 'notattempted';
						}

						$usertrack->status = $track->value;
					break;
					case 'cmi.core.score.raw':
					case 'cmi.score.raw':
						$usertrack->score_raw = (float) sprintf('%2.2f', $track->value);
					break;
					case 'cmi.core.session_time':
					case 'cmi.session_time':
						$usertrack->session_time = $track->value;
					break;
					case 'cmi.core.total_time':
					case 'cmi.total_time':
						$usertrack->total_time = $track->value;
					break;
				}

				if (isset($track->timemodified) && ($track->timemodified > $usertrack->timemodified))
				{
					$usertrack->timemodified = $track->timemodified;
				}
			}

			if (is_array($usertrack))
			{
				ksort($usertrack);
			}

			return $usertrack;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Triggered from API to get the value of elements
	 *
	 * @param   STRING  $sversion      Scorm version
	 * @param   ARRAY   $userdata  	   if user has attempted scorm previously, then data stored aginst him
	 * @param   STRING  $element_name  Main element
	 * @param   ARRAY   $children      Children of element
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 * */
	public function scorm_reconstitute_array_element($sversion, $userdata, $element_name, $children)
	{
		// Reconstitute comments_from_learner and comments_from_lms
		$current = '';
		$current_subelement = '';
		$current_sub = '';
		$count = 0;
		$count_sub = 0;
		$scormseperator = '_';

		if (strcasecmp($sversion, 'SCORM_1.2') != 0)
		{
			// Scorm 1.3 elements use a . instead of an _
			$scormseperator = '.';
		}

		// Filter out the ones we want
		$element_list = array();

		foreach ($userdata as $element => $value)
		{
			if (substr($element, 0, strlen($element_name)) == $element_name)
			{
				$element_list[$element] = $value;
			}
		}

		// Sort elements in .n array order
		uksort($element_list, "comtjlmsScormHelper::scorm_element_cmp");

		// Generate JavaScript
		foreach ($element_list as $element => $value)
		{
			if (strcasecmp($sversion, 'SCORM_1.2') != 0)
			{
				$element = preg_replace('/\.(\d+)\./', ".N\$1.", $element);
				preg_match('/\.(N\d+)\./', $element, $matches);
			}
			else
			{
				$element = preg_replace('/\.(\d+)\./', "_\$1.", $element);
				preg_match('/\_(\d+)\./', $element, $matches);
			}

			if (count($matches) > 0 && $current != $matches[1])
			{
				if ($count_sub > 0)
				{
					echo '	' . $element_name . $scormseperator . $current . '.' . $current_subelement . '._count = ' . $count_sub . ";\n";
				}

				$current = $matches[1];
				$count++;
				$current_subelement = '';
				$current_sub = '';
				$count_sub = 0;
				$end = strpos($element, $matches[1]) + strlen($matches[1]);
				$subelement = substr($element, 0, $end);
				echo '	' . $subelement . " = new Object();\n";

				// Now add the children
				foreach ($children as $child)
				{
					echo '	' . $subelement . "." . $child . " = new Object();\n";
					echo '	' . $subelement . "." . $child . "._children = " . $child . "_children;\n";
				}
			}

			// Now - flesh out the second level elements if there are any
			if (strcasecmp($sversion, 'SCORM_1.2') != 0)
			{
				$element = preg_replace('/(.*?\.N\d+\..*?)\.(\d+)\./', "\$1.N\$2.", $element);
				preg_match('/.*?\.N\d+\.(.*?)\.(N\d+)\./', $element, $matches);
			}
			else
			{
				$element = preg_replace('/(.*?\_\d+\..*?)\.(\d+)\./', "\$1_\$2.", $element);
				preg_match('/.*?\_\d+\.(.*?)\_(\d+)\./', $element, $matches);
			}

			// Check the sub element type
			if (count($matches) > 0 && $current_subelement != $matches[1])
			{
				if ($count_sub > 0)
				{
					echo '	' . $element_name . $scormseperator . $current . '.' . $current_subelement . '._count = ' . $count_sub . ";\n";
				}

				$current_subelement = $matches[1];
				$current_sub = '';
				$count_sub = 0;
				$end = strpos($element, $matches[1]) + strlen($matches[1]);
				$subelement = substr($element, 0, $end);
				echo '	' . $subelement . " = new Object();\n";
			}

			// Now check the subelement subscript
			if (count($matches) > 0 && $current_sub != $matches[2])
			{
				$current_sub = $matches[2];
				$count_sub++;
				$end = strrpos($element, $matches[2]) + strlen($matches[2]);
				$subelement = substr($element, 0, $end);
				echo '	' . $subelement . " = new Object();\n";
			}

			echo '	' . $element . ' = \'' . $value . "';\n";
		}

		if ($count_sub > 0)
		{
			echo '	' . $element_name . $scormseperator . $current . '.' . $current_subelement . '._count = ' . $count_sub . ";\n";
		}

		if ($count > 0)
		{
			echo '	' . $element_name . '._count = ' . $count . ";\n";
		}
	}

	/**
	 * Used to compare two elements
	 *
	 * @param   STRING  $a  First element
	 * @param   STRING  $b  Second element
	 *
	 * @return 1 is 1st is greater
	 *
	 * @since 1.0.0
	 * */
	public function scorm_element_cmp($a, $b)
	{
		preg_match('/.*?(\d+)\./', $a, $matches);
		$left = intval($matches[1]);
		preg_match('/.?(\d+)\./', $b, $matches);
		$right = intval($matches[1]);

		if ($left < $right)
		{
			// Smaller
			return -1;
		}
		elseif ($left > $right)
		{
			// Bigger
			return 1;
		}
		else
		{
			// Look for a second level qualifier eg cmi.interactions_0.correct_responses_0.pattern
			if (preg_match('/.*?(\d+)\.(.*?)\.(\d+)\./', $a, $matches))
			{
				$leftterm = intval($matches[2]);
				$left = intval($matches[3]);

				if (preg_match('/.*?(\d+)\.(.*?)\.(\d+)\./', $b, $matches))
				{
					$rightterm = intval($matches[2]);
					$right = intval($matches[3]);

					if ($leftterm < $rightterm)
					{
						// Smaller
						return -1;
					}
					elseif ($leftterm > $rightterm)
					{
						// Bigger
						return 1;
					}
					else
					{
						if ($left < $right)
						{
							// Smaller
							return -1;
						}
						elseif ($left > $right)
						{
							// Bigger
							return 1;
						}
					}
				}
			}

			// Fall back for no second level matches or second level matches are equal
			return 0;
		}
	}

	/**
	 * Used to insert/update the elements (key) and values in scorm_scoes_track table
	 * if element stated lesson status or score we need to update lesson_track table
	 *
	 * @param   INT     $user             id of the user
	 * @param   INT     $scorm            id of the scorm
	 * @param   INT     $scoes            id of the sco
	 * @param   INT     $usertracks       attempt agains which score and status is to add or update
	 * @param   INT     $cmid             attempt agains which score and status is to add or update
	 * @param   INT     $toclink          attempt agains which score and status is to add or update
	 * @param   STRING  $currentorg       element that needs to be inserted or updated
	 * @param   STRING  $attempt          value to be assigned to element
	 * @param   INT     $play             attempt agains which score and status is to get
	 * @param   INT     $organizationsco  attempt agains which score and status is to get
	 * @param   INT     $children         attempt agains which score and status is to get
	 *
	 * @return object with all possible values of score and lesson status
	 *
	 * @since 1.0.0
	 * */
	public function scorm_format_toc_for_treeview($user, $scorm, $scoes, $usertracks,
		$cmid, $toclink=TOCJSLINK, $currentorg='', $attempt='', $play=false, $organizationsco=null, $children=false)
	{
		global $CFG;

		$result = new stdClass;
		$result->prerequisites = true;
		$result->incomplete = true;

		if (!$children)
		{
			$attemptsmade = scorm_get_attempt_count($user->id, $scorm);
			$result->attemptleft = $scorm->maxattempt == 0 ? 1 : $scorm->maxattempt - $attemptsmade;
		}

		if (!$children)
		{
			$result->toc = "<ul>\n";

			if (!$play && !empty($organizationsco))
			{
				$result->toc .= "\t<li>" . $organizationsco->title . "</li>\n";
			}

			echo $organizationsco->title;
		}

		$prevsco = '';

		if (!empty($scoes))
		{
			foreach ($scoes as $sco)
			{
				$result->toc .= "\t<li>\n";
				$scoid = $sco->id;

				$sco->isvisible = true;

				if ($sco->isvisible)
				{
					$score = '';

					if (isset($usertracks[$sco->identifier]))
					{
						$viewscore = has_capability('mod/scorm:viewscores', context_module::instance($cmid));

						if (isset($usertracks[$sco->identifier]->score_raw) && $viewscore)
						{
							if ($usertracks[$sco->identifier]->score_raw != '')
							{
								$score = '(' . get_string('score', 'scorm') . ':&nbsp;' . $usertracks[$sco->identifier]->score_raw . ')';
							}
						}
					}

					if (!empty($sco->prereq))
					{
						if ($sco->id == $scoid)
						{
							$result->prerequisites = true;
						}

						if (!empty($prevsco) && scorm_version_check($scorm->version, SCORM_13) && !empty($prevsco->hidecontinue))
						{
							if ($sco->scormtype == 'sco')
							{
								$result->toc .= '<span>' . $sco->statusicon . '&nbsp;' . format_string($sco->title) . '</span>';
							}
							else
							{
								$result->toc .= '<span>&nbsp;' . format_string($sco->title) . '</span>';
							}
						}
						elseif ($toclink == TOCFULLURL)
						{
							$url = $CFG->wwwroot . '/mod/scorm/player.php?' . $sco->url;

							if (!empty($sco->launch))
							{
								if ($sco->scormtype == 'sco')
								{
									$result->toc .= $sco->statusicon . '&nbsp;<a href="' . $url . '">' . format_string($sco->title) . '</a>' . $score . "\n";
								}
								else
								{
									$result->toc .= '&nbsp;<a href="' . $url . '">' . format_string($sco->title) . '</a>' . $score . "\n";
								}
							}
							else
							{
								if ($sco->scormtype == 'sco')
								{
									$result->toc .= $sco->statusicon . '&nbsp;' . format_string($sco->title) . $score . "\n";
								}
								else
								{
									$result->toc .= '&nbsp;' . format_string($sco->title) . $score . "\n";
								}
							}
						}
						else
						{
							if (!empty($sco->launch))
							{
								if ($sco->scormtype == 'sco')
								{
									$result->toc .= '<a title="' . $sco->url . '">' . $sco->statusicon . '&nbsp;' . format_string($sco->title) . '&nbsp;' . $score . '</a>';
								}
								else
								{
									$result->toc .= '<a title="' . $sco->url . '">&nbsp;' . format_string($sco->title) . '&nbsp;' . $score . '</a>';
								}
							}
							else
							{
								if ($sco->scormtype == 'sco')
								{
									$result->toc .= '<span>' . $sco->statusicon . '&nbsp;' . format_string($sco->title) . '</span>';
								}
								else
								{
									$result->toc .= '<span>&nbsp;' . format_string($sco->title) . '</span>';
								}
							}
						}
					}
					else
					{
						if ($play)
						{
							if ($sco->scormtype == 'sco')
							{
								$result->toc .= '<span>' . $sco->statusicon . '&nbsp;' . format_string($sco->title) . '</span>';
							}
							else
							{
								$result->toc .= '&nbsp;' . format_string($sco->title) . '</span>';
							}
						}
						else
						{
							if ($sco->scormtype == 'sco')
							{
								$result->toc .= $sco->statusicon . '&nbsp;' . format_string($sco->title) . "\n";
							}
							else
							{
								$result->toc .= '&nbsp;' . format_string($sco->title) . "\n";
							}
						}
					}
				}
				else
				{
					$result->toc .= "\t\t&nbsp;" . format_string($sco->title) . "\n";
				}

				if (!empty($sco->children))
				{
					$result->toc .= "\n\t\t<ul>\n";
					$childresult  = scorm_format_toc_for_treeview(
									$user, $scorm, $sco->children,
									$usertracks, $cmid, $toclink, $currentorg, $attempt, $play, $organizationsco, true
									);
					$result->toc .= $childresult->toc;
					$result->toc .= "\t\t</ul>\n";
					$result->toc .= "\t</li>\n";
				}
				else
				{
					$result->toc .= "\t</li>\n";
				}

				$prevsco = $sco;
			}

			$result->incomplete = $sco->incomplete;
		}

		if (!$children)
		{
			$result->toc .= "</ul>\n";
		}

		return $result;
	}

	/**
	 * Used to insert/update the elements (key) and values in scorm_scoes_track table
	 * if element stated lesson status or score we need to update lesson_track table
	 *
	 * @param   INT     $userid          id of the user
	 * @param   INT     $scormid         id of the scorm
	 * @param   INT     $scoid           id of the sco
	 * @param   INT     $attempt         attempt agains which score and status is to add or update
	 * @param   STRING  $element         element that needs to be inserted or updated
	 * @param   STRING  $value           value to be assigned to element
	 * @param   INT     $forcecompleted  attempt agains which score and status is to get
	 * @param   Object  $allTracks       All SCO tracks
	 *
	 * @return object with all possible values of score and lesson status
	 *
	 * @since 1.0.0
	 * */
	public function scorm_insert_track($userid, $scormid, $scoid, $attempt, $element, $value, $forcecompleted=false, $allTracks = null)
	{
		// Do not log time if course if public and user is not enrolled
		// JLoader::import('components.com_tjlms.helpers.lesson', JPATH_SITE);
		// JLoader::import('components.com_tjlms.models.course', JPATH_SITE);
		// JLoader::import('components.com_tjlms.models.lesson', JPATH_SITE);

		$db = Factory::getDBO();
		$id = null;

		// $scorm = self::getScormData($scormid);
		// $lesson_id = $scorm->lesson_id;

		// $lessonModel = BaseDatabaseModel::getInstance('Lesson', 'TjlmsModel');
		// $courseModel = BaseDatabaseModel::getInstance('Course', 'TjlmsModel');
		// $lesson = $lessonModel->getlessondata($lesson_id);
		// $course_info = $courseModel->getcourseinfo($lesson->course_id);
		// $tjlmsLessonHelper = new TjlmsLessonHelper;

		// $usercanAccess = $lessonModel->canUserLaunch($lesson->id, $userid);

		// if (!$usercanAccess['access'] || !$usercanAccess['track'])
		// {
		// 	return true;
		// }

		if ($userid > 0)
		{
			if ($forcecompleted)
			{
				// TODO - this could be broadened to encompass SCORM 2004 in future
				if (($element == 'cmi.core.lesson_status') && ($value == 'incomplete'))
				{
					if ($track = self::gettrackforElementofSco($scormid, $scoid, $attempt, $userid, 'cmi.core.score.raw'))
					{
						$value = 'completed';
					}
				}

				if ($element == 'cmi.core.score.raw')
				{
					/*if ($tracktest = $this->tjlmsdbhelperObj->get_records('*','tjlms_scorm_scoes_track',
					array('userid'=>$userid, 'scorm_id'=>$scormid ,'attempt'=>$attempt ,'element'=>'cmi.core.lesson_status'),'','loadObject()')) { */

					if ($tracktest = self::gettrackforElementofSco($scormid, $scoid, $attempt, $userid, 'cmi.core.lesson_status'))
					{
						if ($tracktest->value == "incomplete")
						{
							$tracktest->value = "completed";
							$db->updateObject('#__tjlms_scorm_scoes_track', $tracktest, 'id');
						}
					}
				}
				/*if (($element == 'cmi.success_status') && ($value == 'passed' || $value == 'failed')) {
					if ($DB->get_record('scorm_scoes_data', array('scoid' => $scoid, 'name' => 'objectivesetbycontent'))) {
						$objectiveprogressstatus = true;
						$objectivesatisfiedstatus = false;
						if ($value == 'passed') {
							$objectivesatisfiedstatus = true;
						}

						if ($track = $DB->get_record('scorm_scoes_track', array('userid' => $userid,
																				'scormid' => $scormid,
																				'scoid' => $scoid,
																				'attempt' => $attempt,
																				'element' => 'objectiveprogressstatus'))) {
							$track->value = $objectiveprogressstatus;
							$track->timemodified = time();
							$DB->update_record('scorm_scoes_track', $track);
							$id = $track->id;
						} else {
							$track = new stdClass();
							$track->userid = $userid;
							$track->scormid = $scormid;
							$track->scoid = $scoid;
							$track->attempt = $attempt;
							$track->element = 'objectiveprogressstatus';
							$track->value = $objectiveprogressstatus;
							$track->timemodified = time();
							$id = $DB->insert_record('scorm_scoes_track', $track);
						}
						if ($objectivesatisfiedstatus) {
							if ($track = $DB->get_record('scorm_scoes_track', array('userid' => $userid,
																					'scormid' => $scormid,
																					'scoid' => $scoid,
																					'attempt' => $attempt,
																					'element' => 'objectivesatisfiedstatus'))) {
								$track->value = $objectivesatisfiedstatus;
								$track->timemodified = time();
								$DB->update_record('scorm_scoes_track', $track);
								$id = $track->id;
							} else {
								$track = new stdClass();
								$track->userid = $userid;
								$track->scormid = $scormid;
								$track->scoid = $scoid;
								$track->attempt = $attempt;
								$track->element = 'objectivesatisfiedstatus';
								$track->value = $objectivesatisfiedstatus;
								$track->timemodified = time();
								$id = $DB->insert_record('scorm_scoes_track', $track);
								ob_start();
								$filepath = $CFG->dataroot."\\temp\\tempfile.txt";
								$fh = fopen($filepath, "a+");
								var_dump($track);
								$string = ob_get_clean();
								fwrite($fh, $string);
								fclose($fh);
							}
						}
					}
				}*/
			}

			/*$track = $this->tjlmsdbhelperObj->get_records('*','tjlms_scorm_scoes_track', array('userid' => $userid,
																	'scorm_id' => $scormid,
																	'sco_id' => $scoid,
																	'attempt' => $attempt,
																	'element' => $element),'','loadObject');*/

			if ($allTracks !== null)
			{
				if (isset($allTracks[$element])) {
					$track = $allTracks[$element];
				}
			}
			else
			{
				$track = self::gettrackforElementofSco($scormid, $scoid, $attempt, $userid, $element);
			}

			if ($track )
			{
				if ($element != 'x.start.time' )
				{
					// Don't update x.start.time - keep the original value.
					if ($track->value != $value)
					{
						$track->value = $value;
						$track->timemodified = time();
						$db->updateObject('#__tjlms_scorm_scoes_track', $track, 'id');
						$id = $track->id;
					}
				}
			}
			else
			{
				$track = new stdClass;
				$track->userid = $userid;
				$track->scorm_id = $scormid;
				$track->sco_id = $scoid;
				$track->attempt = $attempt;
				$track->element = $element;
				$track->value = $value;
				$track->timemodified = time();
				$id = $db->insertObject('#__tjlms_scorm_scoes_track', $track);
			}

			// TODO : check for multisco
			$statusvariables = array('cmi.completion_status', 'cmi.core.lesson_status', 'cmi.success_status', 'cmi.core.total_time', 'cmi.total_time');

			if (strstr($element, '.score.raw') || (in_array($element, $statusvariables)))
			{
				$scormStatus = '';

				if (in_array($element, array('cmi.completion_status', 'cmi.core.lesson_status', 'cmi.success_status')))
				{
					$scormStatus = $value;
				}

				$scoreandstatus	=	$this->updatelmsScormuserscore_by_attempt($scormid, $attempt, $userid, $scormStatus);
			}
		}

		return $id;
	}

	/**
	 * Get all elements value from scorm_scoes_track table
	 *
	 * @param   INT     $scormId  id of the scorm
	 * @param   INT     $scoId    id of the sco
	 * @param   INT     $attempt  attempt
	 * @param   INT     $userId   id of the user
	 *
	 * @return  Object list
	 *
	 * @since  1.5.0
	 */
	public function getAllScoTracksForUser($scormId, $scoId, $attempt, $userId)
	{
		$db = JFactory::getDBO();
		$query	= $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__tjlms_scorm_scoes_track'));
		$query->where($db->quoteName('userid') . " = " . $db->quote($userId));
		$query->where($db->quoteName('scorm_id') . " = " . $db->quote($scormId));
		$query->where($db->quoteName('sco_id') . " = " . $db->quote($scoId));
		$query->where($db->quoteName('attempt') . " = " . $db->quote($attempt));

		$db->setQuery($query);

		return $db->loadObjectList('element');
	}

	/**
	 * Used to upldate the status and score of a user for attempt
	 *
	 * @param   OBJECT  $scorm_id     id of scorm
	 * @param   INT     $attempt      attempt agains which score and status is to get
	 * @param   INT     $userid       id of the user
	 * @param   STRING  $scormStatus  Scorm status
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 * */
	public function updatelmsScormuserscore_by_attempt($scorm_id, $attempt, $userid, $scormStatus)
	{
		/*$scorm	= $this->tjlmsdbhelperObj->get_records('id,scormtype,
		 lesson_id,grademethod','tjlms_scorm', array("id"=>$scorm_id),'','loadObject');*/

		$scorm = self::getScormData($scorm_id);
		$lesson_id = $scorm->lesson_id;

		if ($scorm->scormtype == 'native')
		{
			$scoes	=	self::getScormScoes($scorm_id);

			$attemptstatus	=	$this->scorm_grade_user_attempt($scorm, $scoes, $userid, $attempt, $scormStatus);

			$trackObj = new stdClass;
			$trackObj->attempt = $attempt;
			$trackObj->lesson_status = $attemptstatus->status;
			$trackObj->score = $attemptstatus->score;
			$trackObj->time_spent = $attemptstatus->time_spent;

			$parsed = date_parse($trackObj->time_spent);
			$trackObj->time_spent = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];

			$this->comtjlmstrackingHelper->update_lesson_track($lesson_id, $userid, $trackObj, 0);

			/*$this->comtjlmstrackingHelper->update_lesson_track($lesson_id,$attempt,
		 $attemptstatus->score,$attemptstatus->status,$userid,'','',$attemptstatus->time_spent,0);*/
		}
	}

	/**
	 * Converts SCORM duration notation to the format that lesson track demands
	 * The function works with both SCORM 1.2 and SCORM 2004 time formats
	 *
	 * @param   string  $duration  SCORM duration
	 *
	 * @return  string human-readable date/time
	 */
	public function scorm_format_duration($duration)
	{
		if ($duration[0] == 'P')
		{
			// If timestamp starts with 'P' - it's a SCORM 2004 format

			$pos = strpos($duration, '.');

			if ($pos !== false)
			{
				$tempArr = explode('.', $duration);
				$duration = $tempArr[0] . 'S';
			}

			$interval = new DateInterval($duration);
			$years  = $interval->format('%y');
			$months  = $interval->format('%m');
			$days  = $interval->format('%d');
			$hours  = $interval->format('%h');

			$hours += floor($years * 8765.82);
			$hours += floor($months * 730.001);
			$hours += floor($days * 24);

			$secs  = $interval->format('%s');

			// Convert hours into sec
			$secs  += floor($hours * 3600);

			// Convert mins to sec
			$mins  = $interval->format('%i');
			$secs  += floor($mins * 60);
			$ret = gmdate("H:i:s", $secs);
		}
		else
		{
			$ret = $duration;
		}

		return $ret;
	}

	/**
	 * Get the score and status of scorm
	 *
	 * @param   OBJECT  $scorm        the scorm object from scorm table
	 * @param   ARRAY   $scoes        the scoes belonging scorm object from scorm_scoes table
	 * @param   INT     $userid       id of the user
	 * @param   INT     $attempt      attempt agains which score and status is to get
	 * @param   STRING  $scormStatus  Scorm status
	 *
	 * @return object with all possible values of score and lesson status
	 *
	 * @since 1.0.0
	 * */
	public function scorm_grade_user_attempt($scorm, $scoes, $userid, $attempt, $scormStatus)
	{
		$attemptscore = new stdClass;
		$attemptscore->scoes = 0;
		$attemptscore->values = 0;
		$attemptscore->max = 0;
		$attemptscore->sum = 0;
		$attemptscore->lastmodify = 0;
		$attemptscore->status = 0;
		$attemptscore->time_spent = '00:00:00';
		$available_scoes = 0;

		$attemptscore->status = 'incomplete';

		foreach ($scoes as $sco)
		{
			if ($sco->launch != '')
			{
				$available_scoes++;
			}

			if ($userdata = $this->scorm_get_tracks($sco->id, $userid, $attempt))
			{
				$attemptscore->time_spent = $this->scorm_format_duration($userdata->total_time);

				/*$attemptscore->time_spent = $this->comtjlmstrackingHelper->sum_the_time($attemptscore->time_spent, $userdata->total_time);*/

				if (($userdata->status == 'completed') || ($userdata->status == 'passed'))
				{
					$attemptscore->scoes++;
				}

				if (!empty($userdata->score_raw) || (isset($scorm->type) && $scorm->type == 'sco' && isset($userdata->score_raw)))
				{
					$attemptscore->values++;
					$attemptscore->sum += $userdata->score_raw;
					$attemptscore->max = ($userdata->score_raw > $attemptscore->max)?$userdata->score_raw:$attemptscore->max;

					if (isset($userdata->timemodified) && ($userdata->timemodified > $attemptscore->lastmodify))
					{
						$attemptscore->lastmodify = $userdata->timemodified;
					}
					else
					{
						$attemptscore->lastmodify = 0;
					}
				}
			}
		}

		switch ($scorm->grademethod)
		{
			case GRADEHIGHEST:
				$score = (float) $attemptscore->max;
			break;
			case GRADEAVERAGE:
				if ($attemptscore->values > 0)
				{
					$score = $attemptscore->sum / $attemptscore->values;
				}
				else
				{
					$score = 0;
				}
			break;
			case GRADESUM:
				$score = $attemptscore->sum;
			break;
			case GRADESCOES:
				$score = $attemptscore->scoes;
			break;
			default:
				// Remote Learner GRADEHIGHEST is default
				$score = $attemptscore->max;
		}

		$attemptscore->score	=	$score;

		if ($scorm->passing_score)
		{
			$scorm->passing_score >= $attemptscore->score;
			$attemptscore->status = 'passed';
		}
		elseif ($available_scoes == $attemptscore->scoes)
		{
			$attemptscore->status = 'completed';
		}

		if (!empty($scormStatus))
		{
			$attemptscore->status = $scormStatus;
		}

		return $attemptscore;
	}

	/**
	 * Gets all tracks of specified sco and user.
	 *
	 * @param   INT  $scoid    id of the sco
	 * @param   INT  $userid   id of the user
	 * @param   INT  $attempt  attempt
	 *
	 * @return  object of usertrack
	 *
	 * @since 1.0.0
	 * */
	public function scorm_get_tracks($scoid, $userid, $attempt)
	{
		$db = Factory::getDBO();
		$query	= $db->getQuery(true);

		$query->select('*');
		$query->from($db->quoteName('#__tjlms_scorm_scoes_track'));
		$query->where($db->quoteName('userid') . " = " . $db->quote($userid));
		$query->where($db->quoteName('sco_id') . " = " . $db->quote($scoid));
		$query->where($db->quoteName('attempt') . " = " . $db->quote($attempt));
		$db->setQuery($query);

		/*if ($tracks = $this->tjlmsdbhelperObj->get_records('*','tjlms_scorm_scoes_track',
															array('userid'=>$userid, 'sco_id'=>$scoid,
														'attempt'=>$attempt), '', 'loadObjectList'))
		{*/
		if ($tracks = $db->loadObjectList())
		{
			$usertrack = $this->scorm_format_interactions($tracks);
			$usertrack->userid = $userid;
			$usertrack->scoid = $scoid;

			return $usertrack;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Defined in order to unify scorm1.2 and scorm2004.
	 *
	 * @param   OBJECT  $trackdata  the scorm_scoes track data
	 *
	 * @return  object of result and message
	 *
	 * @since 1.0.0
	 * */
	public function scorm_format_interactions($trackdata)
	{
		$usertrack = new stdClass;

		$usertrack->score_raw = '';
		$usertrack->status = '';
		$usertrack->total_time = '00:00:00';
		$usertrack->session_time = '00:00:00';
		$usertrack->timemodified = 0;

		foreach ($trackdata as $track)
		{
			$element = $track->element;
			$usertrack->{$element} = $track->value;

			switch ($element)
			{
				case 'cmi.core.lesson_status':
				case 'cmi.completion_status':
					if ($track->value == 'not attempted')
					{
						$track->value = 'notattempted';
					}

					$usertrack->status = $track->value;
					break;
				case 'cmi.core.score.raw':
				case 'cmi.score.raw':
					$usertrack->score_raw = (float) sprintf('%2.2f', $track->value);
					break;
				case 'cmi.core.session_time':
				case 'cmi.session_time':
					$usertrack->session_time = $track->value;
					break;
				case 'cmi.core.total_time':
				case 'cmi.total_time':
					$usertrack->total_time = $track->value;
					break;
			}

			if (isset($track->timemodified) && ($track->timemodified > $usertrack->timemodified))
			{
				$usertrack->timemodified = $track->timemodified;
			}
		}

		return $usertrack;
	}

	/*
	function readElement($VarName) {

		global $SCOInstanceID,$attempts;

		$safeVarName = mysql_escape_string($VarName);
		$db=Factory::getDBO();
		$query="select value from #__tjlms_scorm_scoes_track where ((course_id=$SCOInstanceID)
		 and (element='$safeVarName') and (userid='".Factory::getUser()->id."') and (attempt='".$attempts."')) ";
		$db->setQuery($query);
		$result = $db->loadResult();
		return $result;
	}

	function writeElement($VarName,$VarValue) {

		/*global $SCOInstanceID,$attempts;
		$safeVarName = mysql_escape_string($VarName);
		$safeVarValue = mysql_escape_string($VarValue);
		$db=Factory::getDBO();
		$query="update #__tjlms_scorm_scoes_track set value='$safeVarValue'
		where ((course_id=$SCOInstanceID) and (element='$safeVarName') and (userid='".Factory::getUser()->id."') and (attempt='".$attempts."')) ";
		$db->setQuery($query);
		$db->execute();

		return;

	}

	function initializeElement($VarName,$VarValue) {

		/*global $SCOInstanceID,$attempts;

		// make safe for the database
		$safeVarName = mysql_escape_string($VarName);
		$safeVarValue = mysql_escape_string($VarValue);

		$db=Factory::getDBO();
		$query="select element from #__tjlms_scorm_scoes_track where
		((course_id=$SCOInstanceID) and (element='$safeVarName') and (userid='".Factory::getUser()->id."') and (attempt='".$attempts."')) ";
		$db->setQuery($query);
		$result = $db->loadObject();

		// if nothing found ...
		if (!($result)) {
			$data=new stdClass;
			$data->userid=Factory::getUser()->id;
			$data->course_id=$SCOInstanceID;
			$data->attempt=$attempts;
			$data->element=$safeVarName;
			$data->value=$safeVarValue;
			$data->timemodified=time();
			$db->insertObject( '#__tjlms_scorm_scoes_track', $data, 'id' );
		}

	}





	/*

	function initializeSCO() {

		global $scorm,$attempts,$scoid;
		// has the SCO previously been initialized?
		$db=Factory::getDBO();
		$query= "SELECT * FROM #__tjlms_scorm_track where ((scorm_id =$scorm) and (user_id='".JFactory::getUser()->id."') and (attempt='".$attempts."')) ";
		$db->setQuery($query);
		$res = $db->loadObject();

		$query	=	" SELECT * FROM #__tjlms_course where id='".$scorm."'";
		$db->setQuery($query);
		$c = $db->loadObject();

		if (!$res) {
			$data=new stdClass;
			$data->user_id=Factory::getUser()->id;
			$data->scorm_id=$scorm;
			$data->attempt=$attempts;
			$data->timestart=time();
			$data->timeend='';
			$data->score='';
			$data->last_accessed_on=time();
			$data->lesson_status='not attempted';
			$db->insertObject( '#__tjlms_scorm_track', $data, 'id' );

			$data=new stdClass;
			$data->user_id=Factory::getUser()->id;
			$data->action=Text::_("ACCESSED");
			$data->element=$c->title;
			$data->information=$attempts.' '.Text::_("ATTEMPT");
			$data->time=time();
		 if(!$db->insertObject( '#__tjlms_activities', $data, 'id' ))
			{
				echo $db->stderr();
				return false;
		}
		}
		else
		{
			 $data=new stdClass;
			 $data->id=$res->id;
			$data->last_accessed_on=time();
			$db->updateObject( '#__tjlms_scorm_track', $data, 'id' );
		}



		$query= "select count(element) from #__tjlms_scorm_scoes_track where ((scorm_id=$scorm)
		and (userid='".Factory::getUser()->id."') and (attempt='".$attempts."')) ";
		$db->setQuery($query);
		$count = $db->loadResult();

		// not yet initialized - initialize all elements
		if (! $count) {

			comtjlmsScormHelper::initializeElement('cmi.start.time',time());
			// test score

			comtjlmsScormHelper::initializeElement('cmi.core.score.max','');
			comtjlmsScormHelper::initializeElement('cmi.core.score.min','');
			comtjlmsScormHelper::initializeElement('cmi.core.score.raw','');
			/*comtjlmsScormHelper::initializeElement('adlcp:masteryscore',comtjlmsScormHelper::getFromLMS('adlcp:masteryscore'));

			/*SCO launch and suspend data
			comtjlmsScormHelper::initializeElement('cmi.launch_data',comtjlmsScormHelper::getFromLMS('cmi.launch_data'));
			comtjlmsScormHelper::initializeElement('cmi.suspend_data','');

			// progress and completion tracking
			comtjlmsScormHelper::initializeElement('cmi.core.lesson_location','');
			comtjlmsScormHelper::initializeElement('cmi.core.credit','credit');
			comtjlmsScormHelper::initializeElement('cmi.core.lesson_status','not attempted');
			comtjlmsScormHelper::initializeElement('cmi.core.entry','ab-initio');
			comtjlmsScormHelper::initializeElement('cmi.core.exit','');

			// seat time
			comtjlmsScormHelper::initializeElement('cmi.core.total_time','0000:00:00');
			comtjlmsScormHelper::initializeElement('cmi.core.session_time','');

		}

		// new session so clear pre-existing session time
		comtjlmsScormHelper::writeElement('cmi.core.session_time','');

		// create the javascript code that will be used to set up the javascript cache,
		$initializeCache = "var cache = new Object();\n";

		$query="select element,value from #__tjlms_scorm_scoes_track where
		((scorm_id=$scorm) and (userid='".Factory::getUser()->id."') and (attempt='".$attempts."')) ";
		$db->setQuery($query);
		$result = $db->loadObjectList();
		if($result)
		foreach($result as $res) {

			// make the value safe by escaping quotes and special characters
			$jvarvalue = addslashes($res->value);

			// javascript to set the initial cache value
			$initializeCache .= "cache['$res->element'] = '$jvarvalue';\n";

		}

		// return javascript for cache initialization to the calling program
		return $initializeCache;

	}

	/* ------------------------------------------------------------------------------------
	 LMS-specific code
	------------------------------------------------------------------------------------
	function setInLMS($varname,$varvalue) {

		global $SCOInstanceID,$attempts;

		$db=Factory::getDBO();
		$query= "SELECT * FROM #__tjlms_scorm_track where ((course_id=$SCOInstanceID) and (
		* user_id='".JFactory::getUser()->id."') and (attempt='".$attempts."')) ";
		$db->setQuery($query);
		$res = $db->loadObjectList();
		if (!$res) {
			$data=new stdClass;
			$data->user_id=Factory::getUser()->id;
			$data->course_id=$SCOInstanceID;
			$data->attempt=$attempts;
			$data->timestart=time();
			$data->timeend='';
			$data->score='';
			$data->lesson_status='not attempted';
			$db->insertObject( '#__tjlms_course_track', $data, 'id' );
		}

		$safeVarName = mysql_escape_string($varname);
		$safeVarValue = mysql_escape_string($varvalue);
		$query="update #__tjlms_course_track set $safeVarName='$safeVarValue'
		* where ((course_id=$SCOInstanceID) and (user_id='".JFactory::getUser()->id."') and (attempt='". $attempts."')) ";
		$db->setQuery($query);
		$db->execute();

		return;

	}

	function getFromLMS($varname) {

		switch ($varname) {

			case 'cmi.core.student_name':
				$varvalue = Factory::getUser()->name;
				break;

			case 'cmi.core.student_id':
				$varvalue = Factory::getUser()->id;
				break;

			case 'cmi.launch_data':
				$varvalue = "";
				break;

			default:
				$varvalue = '';

		}

		return $varvalue;
	}*/
}
