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

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
jimport('joomla.application.component.model');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.archive');
jimport('joomla.utilities.simplexml');

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelreports extends BaseDatabaseModel
{
	/**
	 * Constructor
	 *
	 * @since 1.5
	 */
	public function __construct()
	{
		parent::__construct();
		$this->tjlmsdbhelper          = new tjlmsdbhelper;
		$this->comtjlmsHelper         = new comtjlmsHelper;
		$this->comtjlmstrackingHelper = new comtjlmstrackingHelper;

		// Get TjLms params
		$this->tjlmsparams = ComponentHelper::getParams('com_tjlms');
	}

	/**
	 *   Function to get report
	 *
	 * @param   INT  $course_id  Course ID
	 * @param   INT  $user_id    User ID
	 *
	 * @return  INT  Table ID
	 *
	 * @since  1.0.0
	 */
	public function getReport($course_id, $user_id)
	{
		$rowresults = array();
		$lessons = $this->tjlmsdbhelper->get_records('*', 'tjlms_lessons', array(
			"course_id" => $course_id
		), '', 'loadObjectList');

		foreach ($lessons as $lesson)
		{
			$rowresults[$lesson->id]['id']   = $lesson->id;
			$rowresults[$lesson->id]['name'] = $lesson->title;
			$att_tracks                          = $this->tjlmsdbhelper->get_records('*', 'tjlms_lesson_track', array(
				"lesson_id" => $lesson->id,
				"user_id" => $user_id
			), '', 'loadObjectList');
			$rowresults[$lesson->id]['attempts'] = count($att_tracks);

			$score_n_status = $this->comtjlmstrackingHelper->getLessonattemptsGrading($lesson, $user_id);

			$rowresults[$lesson->id]['score'] = '';
			$rowresults[$lesson->id]['lesson_status'] = '';

			if ($score_n_status)
			{
				$rowresults[$lesson->id]['score']         = $score_n_status->score;
				$rowresults[$lesson->id]['lesson_status'] = $score_n_status->lesson_status;
			}

			/*foreach($att_tracks as $track)
			{
			$rowresults[$lesson->id]['attempts'][$i]	=	new stdClass();
			$rowresults[$lesson->id]['attempts'][$i]->attempt	=	$track->attempt;
			$rowresults[$lesson->id]['attempts'][$i]->score = $track->score;
			$rowresults[$lesson->id]['attempts'][$i]->lesson_status	=	$track->lesson_status;
			$i++;
			}*/
		}

		return $rowresults;
	}

	/**
	 *   Function to get report
	 *
	 * @param   INT  $lesson_id  Lesson ID
	 * @param   INT  $user_id    User ID
	 *
	 * @return  INT  Table ID
	 *
	 * @since  1.0.0
	 */
	public function getattemptsReport($lesson_id, $user_id)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);

		// Time spent by user
		$query->select('TIME_TO_SEC(lt.time_spent) as attempttimespent');
		$query->select('lt.*');
		$query->from($db->quoteName('#__tjlms_lesson_track') . ' as lt');
		$query->where($db->quoteName('lt.user_id') . ' = ' . (int) $user_id);
		$query->where($db->quoteName('lt.lesson_id') . ' = ' . (int) $lesson_id);

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		// Load the results as a list of stdClass objects (see later for more options on retrieving data).
		$att_tracks = $db->loadObjectList();
		$dateFormat = $this->tjlmsparams->get('date_format_show', 'Y-m-d H:i:s');

		foreach ($att_tracks as $track)
		{
			$track->time_spent = $this->comtjlmsHelper->secToHours($track->attempttimespent);
			$track->local_timestart = HTMLHelper::date($track->timestart, $dateFormat, true);
			$track->local_last_accessed_on = HTMLHelper::date($track->last_accessed_on, $dateFormat, true);
		}

		return $att_tracks;
	}

	/**
	 *   Function to get report
	 *
	 * @param   INT  $lessonId  Lesson ID
	 *
	 * @return  INT  Table ID
	 *
	 * @since   1.0.0
	 */
	public function getQuiztjlmsOption($lessonId)
	{
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('c.id as id, c.title, c.image, c.certificate_term');
		$query->from('`#__tjlms_courses` as c');
		$query->join('LEFT', '#__tjlms_enrolled_users as eu ON eu.course_id=c.id');
		$query->where('eu.user_id=' . $userId . ' AND c.state=1 AND eu.state=1 ');

		// Set the query for execution.
		$db->setQuery($query);
		$showAnswerSheet = $db->loadresult();

		return $showAnswerSheet;
	}

	/**
	 * Function to get subformat from lesson id
	 *
	 * @param   INT  $lessson_id  Lesson ID
	 *
	 * @return  STRING  subformat
	 *
	 * @since   1.0.0
	 */
	public function getSubformat($lessson_id)
	{
		$db = Factory::getDBO();
		$queryForFormat = $db->getQuery(true);
		$queryForFormat->select('m.sub_format');
		$queryForFormat->from('#__tjlms_media as m');
		$queryForFormat->join('LEFT', '#__tjlms_lessons as l ON l.media_id=m.id');
		$queryForFormat->where("l.id = " . $lessson_id);
		$db->setQuery($queryForFormat);
		$res = $db->loadColumn();

		return $res;
	}
}
