<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
/**
 * Helper Class to for tests
 *
 * @since       1.0
 *
 * @deprecated  1.4.0  This class will be removed and some replacements will be provided in utilities library & Test Model
 */
class TmtTestsHelper
{
	/**
	 * Method to validate if logged in user is author if id is not null
	 *
	 * @param   int  $id  question id
	 *
	 * @return  boolean  true/false
	 *
	 * @since  1.0
	 */
	public static function get_q_image_title($id)
	{
		if ($id)
		{
			$query = $db->getQuery(true);
			$query->select('img_title AS path');
			$query->from($db->quoteName('#__tmt_questions_image'));
			$query->where($db->quoteName('q_id') . ' = ' . $db->quote($id));
			$db->setQuery($query);

			return $res = $db->loadResult();
		}
	}

	/**
	 * Method to get image title of answer
	 *
	 * @param   int  $id  answer id
	 *
	 * @return  string
	 *
	 * @since 1.0
	 */
	public static function get_a_image_title($id)
	{
		if ($id)
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('img_title AS path');
			$query->from($db->quoteName('#__tmt_answers_image'));
			$query->where($db->quoteName('a_id') . ' = ' . $db->quote($id));
			$db->setQuery($query);

			return $res = $db->loadResult();
		}
	}

	/**
	 * Method to get image path of question
	 *
	 * @param   int  $id  question id
	 *
	 * @return  string
	 *
	 * @since 1.0
	 */
	public static function get_q_image($id)
	{
		if ($id)
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('img_path AS path');
			$query->from($db->quoteName('#__tmt_questions_image'));
			$query->where($db->quoteName('q_id') . ' = ' . $db->quote($id));
			$db->setquery($query);

			return $res = $db->loadResult();
		}
	}

	/**
	 * Method to get image path of answer
	 *
	 * @param   int  $id  answer id
	 *
	 * @return  string
	 *
	 * @since 1.0
	 */
	public static function get_a_image($id)
	{
		if ($id)
		{
			$db = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('img_path AS path');
			$query->from($db->quoteName('#__tmt_answers_image'));
			$query->where($db->quoteName('a_id') . ' = ' . $db->quote($id));
			$db->setquery($query);
			$res = $db->loadResult();

			return $res;
		}
	}

	/**
	 * Method to check creator of test
	 *
	 * @param   int  $id            test id
	 * @param   int  $companyUsers  user id
	 *
	 * @return  boolean  true/false
	 *
	 * @since 1.0
	 */
	public function checkCreator($id = null, $companyUsers = null)
	{
		$db = Factory::getDBO();

		if ($id)
		{
			$query = $db->getQuery(true);
			$query->select('t.created_by');
			$query->from($db->quoteName('#__tmt_tests', 't'));
			$query->where($db->quoteName('t.id') . ' = ' . $db->quote($id));
			$db->setQuery($query);
			$creator = $db->loadResult();

			if (is_array($companyUsers))
			{
				if (in_array($creator, $companyUsers))
				{
					return true;
				}
			}
			else
			{
				if ($creator == Factory::getUser()->id)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Method to check if a test can be deleted.
	 * If test has valid invitations or other data, it can't be deleted.
	 *
	 * @param   int  $id  test id
	 *
	 * @return  boolean  true/false
	 *
	 * @since 1.0
	 */
	public function canBeDeleted($id = null)
	{
		$db = Factory::getDBO();

		if ($id)
		{
			$query = $db->getQuery(true);
			$query->select('id');
			$query->from($db->quoteName('#__tjlms_tmtquiz', 't'));
			$query->where($db->quoteName('test_id') . ' = ' . $db->quote($id));
			$db->setQuery($query);
			$invites = $db->loadObjectlist();

			if (count($invites))
			{
				// Don't allow delete, if valid invitations or other data.
				return false;
			}
			else
			{
				// Allow deleting.
				return true;
			}
		}

		return false;
	}

	/**
	 * Method to get some tests created by login user, called from LMS
	 *
	 * @return  oject html
	 *
	 * @since 1.0
	 */
	public function getTestStatus()
	{
		$input 		= Factory::getApplication()->input;
		$teststatus = $input->get('teststatus');
		$options = array();
		$options[] = JHTML::_('select.option', '', 'Test Status');
		$options[] = JHTML::_('select.option', '0', 'Pending');
		$options[] = JHTML::_('select.option', '1', 'Reviewed');

		$attr = 'class="inputbox input-medium com_tmt_button" size="1" onchange="submitform( );"';

		return JHTML::_('select.genericlist', $options, 'teststatus', $attr, 'value', 'text', $teststatus);
	}

	/**
	 * Method to get some tests created by login user, called from LMS
	 *
	 * @return  oject html
	 *
	 * @since 1.0
	 */
	public function getTestName()
	{
		$db       = Factory::getDBO();
		$input    = Factory::getApplication()->input;
		$testname = $input->get('testname', 0, 'INT');

		if ($testname)
		{
			$testid = $testname;
		}
		else
		{
			$testid = $input->get('test_id', 0, 'INT');
		}

		$query = $db->getQuery(true);
		$query->select('id AS value, title AS text');
		$query->from($db->quoteName('#__tmt_tests'));
		$query->where($db->quoteName('created_by') . ' = ' . Factory::getUser()->id);
		$query->order('id ASC');
		$db->setQuery($query);
		$row = $db->loadAssocList();

		$options = array();
		$options[] = JHTML::_('select.option', '', 'Test Name');

		foreach ($row AS $key => $val)
		{
			$key++;
			$options[] = JHTML::_('select.option', $val['value'], $val['text']);
		}

		$attr = 'class="inputbox input-medium com_tmt_button" size="1" onchange="submitform( );"';

		return JHTML::_('select.genericlist', $options, 'testname', $attr, 'value', 'text', $testid);
	}

	/**
	 * Method to check if a user has some test created, called from LMS
	 *
	 * @param   int  $uid  id of user
	 *
	 * @return  test_count
	 *
	 * @since 1.0
	 */
	public function getUserTests($uid)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('COUNT(id)');
		$query->from($db->quoteName('#__tmt_tests'));
		$query->where($db->quoteName('created_by') . ' = ' . (int) $uid);
		$db->setQuery($query);
		$test_count = $db->loadResult();

		if (empty($test_count))
		{
			return 0;
		}
		else
		{
			return $test_count;
		}
	}

	/**
	 * Method to get job status
	 *
	 * @return  object
	 *
	 * @since  1.0
	 */
	public function getJobStatus()
	{
		$db        = Factory::getDBO();
		$input     = Factory::getApplication()->input;
		$jobstatus = $input->get('jobstatus', '', 'STRING');
		$SubusersHelper = new SubusersHelper;
		$uid = Factory::getUser()->id;
		$company = $SubusersHelper->getMyCompaniesListData($uid);
		$companyuser = implode(",", $db->quote($company));

		$query = $db->getQuery(true);
		$query->select('status AS value, status AS text');
		$query->from($db->quoteName('#__ja_jobs'));
		$query->order('id ASC');
		$query->group('status');

		if ($companyuser)
		{
			$query->where($db->quoteName('user_id') . ' IN (' . $companyuser . ')');
		}

		$db->setquery($query);
		$row = $db->loadAssocList();

		$options = array();
		$options[] = JHTML::_('select.option', '', 'Job Status');

		foreach ($row AS $key => $val)
		{
			$key++;
			$options[] = JHTML::_('select.option', $val['value'], $val['text']);
		}

		$attr = 'class="inputbox input-medium com_tmt_button" size="1" onchange="submitform( );"';

		return JHTML::_('select.genericlist', $options, 'jobstatus', $attr, 'value', 'text', $jobstatus);
	}

	/**
	 * Method to get job name selectbox
	 *
	 * @return  object
	 *
	 * @since  1.0
	 */
	public function getJobName()
	{
		$db = Factory::getDBO();
		$input     = Factory::getApplication()->input;
		$job_name = $input->get('job_name', '', 'STRING');
		$SubusersHelper = new SubusersHelper;
		$uid = Factory::getUser()->id;
		$company = $SubusersHelper->getMyCompaniesListData($uid);
		$companyuser = implode(",", $db->quote($company));

		$query = $db->getQuery(true);
		$query->select('id AS value, title AS text');
		$query->from($db->quoteName('#__ja_jobs'));
		$query->order('id ASC');

		if ($companyuser)
		{
			$query->where($db->quoteName('user_id') . ' IN (' . $companyuser . ')');
		}

		$db->setquery($query);
		$row = $db->loadAssocList();

		$options = array();
		$options[] = JHTML::_('select.option', '', 'Job Title');

		foreach ($row AS $key => $val)
		{
			$key++;
			$options[] = JHTML::_('select.option', $val['value'], $val['text']);
		}

		$attr = 'class="inputbox input-medium com_tmt_button" size="1" onchange="submitform( );"';

		return JHTML::_('select.genericlist', $options, 'job_name', $attr, 'value', 'text', $job_name);
	}

	/**
	 * Method to get test name
	 *
	 * @param   int  $id  test id
	 *
	 * @return  string  $testname
	 *
	 * @since  1.0
	 */
	public function getDisplayTestnm($id)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('title AS test_name')
			->from('#__tmt_tests')
			->where('id =' . (int) $id)
			->order('id ASC');
		$db->setQuery($query);
		$testname = $db->loadResult();

		return $testname;
	}

	/**
	 * Method to get candidate name from users
	 *
	 * @param   int  $id  user id
	 *
	 * @return  string  $candidate_name
	 *
	 * @since  1.0
	 */
	public function getDisplayCandinm($id)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('name AS candidate_name')
			->from('#__users')
			->where('id =' . (int) $id)
			->order('id ASC');
		$db->setQuery($query);
		$candidate_name = $db->loadResult();

		return $candidate_name;
	}

	/**
	 * Method to get objective test
	 *
	 * @param   int  $id  test id
	 *
	 * @return  int  $countofans
	 *
	 * @since  1.0
	 */
	public function getObjectiveTest($id)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('isObjective')
			->from(' #__tmt_tests')
			->where('id =' . (int) $id);
		$db->setQuery($query);
		$isObjective = $db->loadResult();

		return $isObjective;
	}

	/**
	 * Method to get test type
	 *
	 * @param   int  $id  test id
	 *
	 * @return  string  type of test
	 *
	 * @since  1.0
	 */
	public function getTestType($id)
	{
		$db = Factory::getDBO();
		$sql = "SELECT gradingtype
		FROM #__tmt_tests
		WHERE id=" . $id;
		$db->setQuery($sql);
		$qztype = $db->loadResult();

		return $qztype;
	}

	/**
	 * Method to get count of questions with no attempt
	 *
	 * @param   int  $user_id    user id
	 * @param   int  $test_id    test id
	 * @param   int  $invite_id  invite id
	 *
	 * @return  int  $countofans
	 *
	 * @since  1.0
	 */
	public function getNoQueAttempt($user_id, $test_id, $invite_id)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('count(*)')
			->from(' #__tmt_tests_answers')
			->where(' user_id =' . (int) $user_id)
			->where(' test_id =' . (int) $test_id)
			->where(' invite_id =' . (int) $invite_id);
		$db->setQuery($query);
		$countofans = $db->loadResult();

		return $countofans;
	}

	/**
	 * Method to get time format in hours , mins, seconds
	 *
	 * @param   int  $time  total time in secs
	 *
	 * @return  $time_format
	 *
	 * @since  1.0
	 */
	public function getTestTimeFormat($time)
	{
		$attempt_time_taken = array();
		$time_hr = floor($time / 3600);

		if ($time_hr)
		{
			$attempt_time_taken[] = Text::sprintf('COM_TMT_HOURS', $time_hr);
		}

		$time_min = floor(($time / 60) % 60);

		if ($time_min != 0)
		{
			$attempt_time_taken[] = Text::sprintf('COM_TMT_MINUTES', $time_min);
		}

		$time_sec = floor($time % (60));

		if ($time_sec != 0)
		{
			$attempt_time_taken[] = Text::sprintf('COM_TMT_SECONDS', $time_sec);
		}

		return implode(' , ', $attempt_time_taken);
	}

	/**
	 * Method to get lesson data from test id
	 *
	 * @param   int  $test_id  test id
	 *
	 * @return  string  lesson object
	 *
	 * @since  1.0
	 */
	/*public function getLessonFromTest($test_id)
	{
		if ($test_id)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('l.*');
			$query->from('`#__tjlms_lessons` AS l');
			$query->leftjoin('`#__tjlms_media` AS m ON m.id = l.media_id');
			$query->where('m.source = ' . (int) ($test_id));
			$db->setQuery($query);
			$lesson = $db->loadObject();

			return $lesson;
		}
	}*/

	/**
	 * Method to get lesson data from test id
	 *
	 * @param   int  $lessonId  lesson id
	 *
	 * @return  string  lesson object
	 *
	 * @since  1.0
	 */
	public function getTestFromLesson($lessonId)
	{
		if ($lessonId)
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('tt.*');
			$query->from('`#__tmt_tests` AS tt');
			$query->leftjoin('`#__tjlms_media` AS m ON m.source = tt.id');
			$query->leftjoin('`#__tjlms_lessons` AS l ON l.media_id = m.id');
			$query->where('l.id = ' . (int) ($lessonId));
			$db->setQuery($query);

			$test = $db->loadObject();

			return $test;
		}
	}
}
