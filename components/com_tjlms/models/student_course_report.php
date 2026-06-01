<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;

jimport('joomla.application.component.model');

/**
 * Student report model.
 *
 * @since  1.0.0
 */
class TjlmsModelStudent_Course_Report extends BaseDatabaseModel
{
	/**
	 * @var		string	The prefix to use with controller messages.
	 *
	 * @since	1.6
	 */
	protected $text_prefix = 'COM_TJLMS';

	/**
	 * Get progress of user for the course
	 *
	 * @param   string  $course_id   key
	 * @param   string  $student_id  urlVar
	 *
	 * @return  void
	 */
	public function getUserCourseProgress($course_id, $student_id )
	{
		$db = Factory::getDBO();
		$progress = new stdClass;
		$query = $db->getQuery(true);
		$query->select('COUNT(lt.id)')
		->from(' #__tjlms_lesson_track AS lt')
		->join('LEFT', '#__tjlms_lessons AS l ON l.id = lt.lesson_id ')
		->where('lt.user_id =' . (int) $student_id)
		->where('l.course_id =' . (int) $course_id)
		->where('lt.lesson_status =' . $db->quote('completed') .
					' OR lt.lesson_status =' . $db->quote('passed') .
					' OR lt.lesson_status =' . $db->quote('failed')
					);
		$db->setQuery($query);
		$progress->complete = $db->loadResult();

		$query = $db->getQuery(true);
		$query->select('COUNT(l.id)')
		->from(' #__tjlms_lesson_track AS lt')
		->join('RIGHT', '#__tjlms_lessons AS l ON l.id = lt.lesson_id ')
		->where('lt.user_id =' . (int) $student_id)
		->where('l.course_id =' . (int) $course_id)
		->where('lt.lesson_status =' . $db->quote('not_attempted'));
		$db->setQuery($query);
		$progress->pending = $db->loadResult();

		return $progress;
	}

	/**
	 * Get course details for the student
	 *
	 * @param   string  $course_id  key
	 * @param   string  $user_id    urlVar
	 *
	 * @return  void
	 */
	public function getUserCourseDetails($course_id,$user_id)
	{
		$tjlmsdbhelper	=	new tjlmsdbhelper;

		$lessons = $tjlmsdbhelper->get_records('*', 'tjlms_lessons', array("course_id" => $course_id), '', 'loadObjectList');
		$course_details	= array;

		foreach ($lessons as $k => $lesson)
		{
			$course_details[$k]	=	new stdClass;
			$course_details[$k]->name = $lesson->name;
			$scorm	= $tjlmsdbhelper->get_records('id', 'tjlms_scorm', array("lesson_id" => $lesson->id), '', 'loadResult');

			$scoes	=	$tjlmsdbhelper->get_records('*', 'tjlms_scorm_scoes', array("scorm" => $scorm), '', 'loadObjectList');
			$course_details[$k]->score = $this->scorm_grade_user_attempt($scoes, $user_id, $attempt = 1);
			$course_details[$k]->attempt	= '1';
		}

		return $course_details;
	}

	/**
	 * Function to save field data
	 *
	 * @param   string  $scoes    key
	 * @param   string  $userid   urlVar
	 * @param   string  $attempt  urlVar
	 *
	 * @return  void
	 */
	public function scorm_grade_user_attempt($scoes, $userid, $attempt=1)
	{
		$attemptscore = new stdClass;
		$attemptscore->scoes = 0;
		$attemptscore->values = 0;
		$attemptscore->max = 0;
		$attemptscore->sum = 0;
		$attemptscore->lastmodify = 0;

		foreach ($scoes as $sco)
		{
			if ($userdata = $this->scorm_get_tracks($sco->id, $userid, $attempt))
			{
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

		$score = $attemptscore->max;

		/*switch ($scorm->grademethod) {
			case GRADEHIGHEST:
				$score = (float) $attemptscore->max;
			break;
			case GRADEAVERAGE:
				if ($attemptscore->values > 0) {
					$score = $attemptscore->sum/$attemptscore->values;
				} else {
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
				$score = $attemptscore->max;   // Remote Learner GRADEHIGHEST is default
		}-*/

		return $score;
	}

	/**
	 * Function to save field data
	 *
	 * @param   array  $scoid    trackdata
	 * @param   array  $userid   trackdata
	 * @param   array  $attempt  trackdata
	 *
	 * @return  void
	 */
	public function scorm_get_tracks($scoid, $userid, $attempt='')
	{
		// Gets all tracks of specified sco and user.
		global $DB;
		$attempt = 1;

		$this->tjlmsdbhelper	=	new tjlmsdbhelper;
		$tracks = $this->tjlmsdbhelper->get_records('*', 'tjlms_scorm_scoes_track', array('userid' => $userid, 'sco_id' => $scoid,
		'attempt' => $attempt), '', 'loadObjectList');

		if ($tracks = $this->tjlmsdbhelper->get_records('*', 'tjlms_scorm_scoes_track',
			array('userid' => $userid, 'sco_id' => $scoid, 'attempt' => $attempt), '', 'loadObjectList'))
		{
			$usertrack = $this->scorm_format_interactions($tracks);
			$usertrack->userid = $userid;
			$usertrack->sco_id = $scoid;

			return $usertrack;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Function to save field data
	 *
	 * @param   array  $trackdata  trackdata
	 *
	 * @return  void
	 */
	public function scorm_format_interactions($trackdata)
	{
		$usertrack = new stdClass;

		// Defined in order to unify scorm1.2 and scorm2004.
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
}
