<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );
use Joomla\CMS\Factory;
use Joomla\CMS\Date\Date;

jimport('joomla.application.component.controller');

/**
 * Methods supporting a list of Tjlms scorm.
 *
 * @since  1.0.0
 */

class TjlmsControllerScorm extends TjlmsController
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since    1.6
	 */
	public function __construct ($config=array())
	{
		require_once JPATH_SITE . '/components/com_tjlms/libraries/scorm/scormhelper.php';

		$this->comtjlmsScormHelper	=	new comtjlmsScormHelper;
		parent::__construct($config);
	}

	/**
	 * storescotrackdata.
	 *
	 * @see     JController
	 *
	 * @return  string
	 *
	 * @since    1.6
	 */
	public function storescotrackdata()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');
		$document = Factory::getDocument();
		$document->setMimeEncoding('application/json');

		$data_submitted	= Factory::getApplication()->input->post->getArray();

		$scoid   =	$data_submitted['scoid'];
		$scorm   =	$data_submitted['scorm'];
		$attempt =	$data_submitted['attempt'];

		$userid  =	Factory::getUser()->id;

		if (!empty($scoid) && $userid > 0)
		{
			$result = true;
			$request = null;

			JLoader::import('components.com_tjlms.helpers.lesson', JPATH_SITE);
			JLoader::import('components.com_tjlms.models.lesson', JPATH_SITE);

			$scormObj          = $this->comtjlmsScormHelper->getScormData($scorm);
			$lesson_id         = $scormObj->lesson_id;
			$lessonModel       = JModelLegacy::getInstance('Lesson', 'TjlmsModel');
			$lesson            = $lessonModel->getlessondata($lesson_id);
			$tjlmsLessonHelper = new TjlmsLessonHelper;

			$usercanAccess = $lessonModel->canUserLaunch($lesson->id, $userid);

			if (!$usercanAccess['access'] || !$usercanAccess['track'])
			{
				return true;
			}

			// Consider cmi__success_status iff cmi__completion_status == complete
			if (isset($data_submitted['cmi__completion_status']) && $data_submitted['cmi__completion_status'] == 'incomplete')
			{
				unset($data_submitted['cmi__success_status']);
			}

			$allTracks = $this->comtjlmsScormHelper->getAllScoTracksForUser($scorm, $scoid, $attempt, $userid);

			foreach ($data_submitted as $element => $value)
			{
				$element = str_replace('__', '.', $element);

				if (substr($element, 0, 3) == 'cmi')
				{
					$result = $this->comtjlmsScormHelper->scorm_insert_track($userid, $scorm, $scoid, $attempt, $element, $value, false, $allTracks);
				}

				/*
				if (substr($element, 0, 15) == 'adl.nav.request') {
				// SCORM 2004 Sequencing Request
				require_once($CFG->dirroot.'/mod/scorm/datamodels/scorm_13lib.php');

				$search = array('@continue@', '@previous@', '@\{target=(\S+)\}choice@', '@exit@', '@exitAll@', '@abandon@', '@abandonAll@');
				$replace = array('continue_', 'previous_', '\1', 'exit_', 'exitall_', 'abandon_', 'abandonall');
				$action = preg_replace($search, $replace, $value);

				if ($action != $value) {
				// Evaluating navigation request
				$valid = scorm_seq_overall ($scoid, $USER->id, $action, $attempt);
				$valid = 'true';

				// Set valid request
				$search = array('@continue@', '@previous@', '@\{target=(\S+)\}choice@');
				$replace = array('true', 'true', 'true');
				$matched = preg_replace($search, $replace, $value);
				if ($matched == 'true') {
				$request = 'adl.nav.request_valid["'.$action.'"] = "'.$valid.'";';
				}
				}
				}*/
			}

			if ($result)
			{
				echo json_encode("true");
			}
			else
			{
				echo json_encode("false");
			}

			jexit();
			/*if ($request != null) {
				echo "\n".$request;
			}*/
		}

		echo json_encode("true");
		jexit();
	}

	/**
	 * setSCOinSession.
	 *
	 * @see        JController
	 *
	 * @return  string
	 *
	 * @since    1.6
	 */
	public function setSCOinSession()
	{
		$app       = Factory::getApplication();
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');
		$session = Factory::getSession();

		$scotitle	= $app->input->get('scotitle');
		$session->set('tjlms-SCO', $scotitle);

		echo json_encode(1);
		jexit();
	}

	/**
	 * update time spent in lesson track.
	 *
	 * @return  string
	 *
	 * @since    __DEPOLOY_VERSION__
	 */
	public function updateLessonTrackTimeSpent()
	{
		$app       = Factory::getApplication();

		// Start date and End date
		$sDate     = $app->input->get('startDate', '0', 'STRING');
		$eDate     = $app->input->get('endDate', '0', 'STRING');

		// Yersterday time stamp
		$yesterday = $app->input->get('yesterday', '0', 'INT');
		$yesterdayTimeStamp = strtotime(new Date(strtotime('-1 day')));

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('tss.*');
		$query->select('tjsc.lesson_id');
		$query->from($db->quoteName('#__tjlms_scorm_scoes_track', 'tss'));
		$query->join("LEFT", "#__tjlms_scorm AS tjsc ON tjsc.id = tss.scorm_id");
		$query->where(
			$db->quoteName('tss.element') . " = " . $db->quote('cmi.core.total_time') . 'OR'
				. $db->quoteName('tss.element') . " = " . $db->quote('cmi.total_time')
			);

		if ($sDate && $eDate)
		{
			if ((DateTime::createFromFormat('Y-m-d', $sDate) == true)
				&& (DateTime::createFromFormat('Y-m-d', $eDate) == true))
			{
				$startDate = strtotime(new Date($sDate));
				$endDate   = strtotime(new Date($eDate));

				$query->where("(tss.timemodified BETWEEN " . $db->quote($startDate) . " AND " . $db->quote($endDate) . " )");
			}
		}
		elseif ($yesterday)
		{
			$query->where($db->quoteName('timemodified') . " > " . $db->quote($yesterdayTimeStamp));
		}

		$db->setQuery($query);
		$scormScoesTrackData = $db->loadObjectList();

		foreach ($scormScoesTrackData as $scData)
		{
			$total_time = $this->comtjlmsScormHelper->scorm_format_duration($scData->value);
			$parsed = date_parse($total_time);
			$timeInSec = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];
			$scData->total_time = gmdate("H:i:s", $timeInSec);

			$query = $db->getQuery(true);
			$fields = array(
				$db->quoteName('time_spent') . ' = ' . $db->quote($scData->total_time)
			);
			$conditions = array(
				$db->quoteName('user_id') . ' = ' . $db->quote($scData->userid),
				$db->quoteName('attempt') . ' = ' . $db->quote($scData->attempt),
				$db->quoteName('lesson_id') . ' = ' . $db->quote($scData->lesson_id)
			);

			$query->update($db->quoteName('#__tjlms_lesson_track'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$result = $db->execute();
		}
	}
}
