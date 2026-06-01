<?php
/**
 * @package     Ekcontent
 * @subpackage  com_ekcontent
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Data\DataObject;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Config\Administrator\Model\ApplicationModel;

/**
 * Jlike Manage Model
 *
 * @since  1.6
 */
class TjlmsModelDatabase extends ListModel
{
	protected $extension_name = 'com_tjlms';

	/**
	 * Add the alises to the empty entries and update the redundant alias ent
	 *
	 * @param   STRING  $table_name    Name of the table
	 * @param   STRING  $table_prefix  prefix of the table
	 * @param   STRING  $title_field   the column storing the title
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @todo    Purge updates has to be replaced with an events system
	 */
	public function fixAlias($table_name, $table_prefix, $title_field='title')
	{
		$db = Factory::getDbo();

		$query = "UPDATE {$table_name} SET alias = LOWER(REPLACE({$title_field}, ' ', '-')) where alias is NULL OR alias='' ";
		$db->setQuery($query);
		$db->execute();

		$query = "select alias, count(*) as c from {$table_name} where alias is not NULL and alias!='' group by alias having c >1 order by c desc";
		$db->setQuery($query);
		$results = $db->loadObjectlist();

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$table = Table::getInstance($table_prefix, 'TjlmsTable', array('dbo', $db));

		foreach ($results as $aliasobj)
		{
			$query = "select id from {$table_name} where alias = '" . $aliasobj->alias . "' order by id asc";
			$db->setQuery($query);
			$cids = $db->loadColumn();

			unset($cids[0]);

			foreach ($cids as $cid)
			{
				$alias = $aliasobj->alias . uniqid();

				$table->id = $cid;
				$table->alias = $alias;
				$table->store();
			}
		}
	}

	/**
	 * Tries to run the add index queries having ignore keyword
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @todo    Purge updates has to be replaced with an events system
	 */
	public function fixIgnorekeyIndexes()
	{
		$db = Factory::getDbo();
		$file = JPATH_ADMINISTRATOR . '/components/com_tjlms/sql/ignorekeyindex.sql';
		$buffer = file_get_contents($file);

		// Create an array of queries from the sql file
		$queries = JDatabaseDriver::splitSql($buffer);

		foreach ($queries as $query)
		{
			// Fix up extra spaces around () and in general
			$find = array('#((\s*)\(\s*([^)\s]+)\s*)(\))#', '#(\s)(\s*)#');
			$replace = array('($2)', '$0');
			$updateQuery = preg_replace($find, $replace, $query);
			$wordArray = explode(' ', $updateQuery);

			if (isset($wordArray[2]))
			{
				if ($pos = strpos($wordArray[5], '('))
				{
					$index = substr($wordArray[5], 0, $pos);
				}
				else
				{
					$index = $wordArray[5];
				}

				$result = 'SHOW INDEXES IN ' . $wordArray[2] . ' WHERE Key_name = ' . "'" . $index . "'";
				$db->setQuery($result);
				$chk = $db->loadColumn();

				if (strtolower($wordArray[3] == 'add'))
				{
					if (empty($chk))
					{
						$db->setQuery($query);
						$db->execute();
					}
				}
				elseif (strtolower($wordArray[3] == 'drop'))
				{
					if (!empty($chk))
					{
						$db->setQuery($query);
						$db->execute();
					}
				}
			}
		}
	}

	/**
	 * Add the entry in the certificate table for default certificate and add that id as a certificate id for the
	 * courses having certificate
	 *
	 * @return  void
	 */
	public function setDefaultCertificate()
	{
		/*If certificate.php exists, do not oevrride it*/
		$cer_file = JPATH_ADMINISTRATOR . '/components/com_tjlms/certificate.php';

		if (!File::exists($cer_file))
		{
			$cer_file = JPATH_ADMINISTRATOR . '/components/com_tjlms/certificate_default.php';
		}

		$db = Factory::getDbo();

		// Create default category on installation if not exists
		$sql = $db->getQuery(true)->select(1)
		->from($db->quoteName('#__tjlms_certificate_template'))
		->setLimit(1);

		$db->setQuery($sql);
		$cer_rows = $db->loadResult();

		if (!$cer_rows)
		{
			include $cer_file;

			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
			$row = Table::getInstance('certificatetemplate', 'TjlmsTable', array());
			$row->created_by = Factory::getUser()->id;
			$row->title = 'Default certificate';
			$row->body = $certificate['message_body'];
			$row->state = '1';
			$row->access = '1';
			$row->created_date = Factory::getDate()->toSql();
			$row->store();

			$cer_id = $row->id;

			/* add cert_id column in courses table if not present*/
			$query = "SHOW COLUMNS FROM  `#__tjlms_courses` LIKE 'certificate_id'";
			$db->setQuery($query);
			$check_cert_col = $db->loadObject();

			if (empty($check_cert_col))
			{
				$query = "ALTER TABLE `#__tjlms_courses` add column `certificate_id` int(11) NOT NULL DEFAULT '0'";
				$db->setQuery($query);
				$db->execute();
			}

			/* Update the courses for the certificate id*/
			$query = $db->getQuery(true);

			// Fields to update.
			$fields = array(
				$db->quoteName('certificate_id') . ' = ' . $db->quote($cer_id),
			);

			// Conditions for which records should be updated.
			$conditions = array(
				$db->quoteName('certificate_id') . ' is NULL OR ' . $db->quoteName('certificate_id') . ' = 0',
				$db->quoteName('certificate_term') . ' !=0 '
			);

			$query->update($db->quoteName('#__tjlms_courses'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$result = $db->execute();

			/* Add entries in the certificate table for the users who has got certificate for courses*/
			$query = $db->getQuery(true);
			$query->select($db->quoteName(array('user_id', 'course_id')));
			$query->select($db->quoteName('ct.timeend', 'grant_date'));
			$query->select($db->quoteName('ct.id', 'course_track_id'));
			$query->from($db->quoteName('#__tjlms_course_track', 'ct'));
			$query->where($db->quoteName('ct.status') . " = 'C' ");

			$query->join('inner', $db->quoteName('#__tjlms_courses', 'c') . ' ON (' . $db->quoteName('ct.course_id') . ' = ' . $db->quoteName('c.id') . ')');
			$query->join('inner', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('ct.user_id') . ' = ' . $db->quoteName('u.id') . ')');
			$db->setQuery($query);

			// Load the results as a list of stdClass objects (see later for more options on retrieving data).
			$certificates = $db->loadObjectList();

			foreach ($certificates as $cer_entry)
			{
				if ($cer_entry->grant_date && $cer_entry->grant_date != '0000-00-00 00:00:00')
				{
					$grant_date = $cer_entry->grant_date;
				}
				else
				{
					// If course track does not have end time, calculate based on lessons details
					$grant_date = $this->getCourseCompletedDate($cer_entry->course_id, $cer_entry->user_id);

					// If course track has invalid value, update it too
					if ($cer_entry->course_track_id && $grant_date && $grant_date != '0000-00-00 00:00:00')
					{
						JLoader::import('components.com_tjlms.models.coursetrack', JPATH_SITE);
						$ctModel = BaseDatabaseModel::getInstance('Coursetrack', 'TjlmsModel');
						$data = array('id' => $cer_entry->course_track_id, 'timeend' => $grant_date);
						$ctModel->save($data);
					}
				}

				JLoader::import('components.com_tjlms.models.certificate', JPATH_SITE);
				$certModel = BaseDatabaseModel::getInstance('Certificate', 'TjlmsModel');
				$certdata = array('course_id' => $cer_entry->course_id, 'user_id' => $cer_entry->user_id, 'grant_date' => $grant_date);

				$certModel->save($certdata);
			}
		}

		return 1;
	}

	/**
	 * Execute the alter table queries where we need to change the column names
	 *
	 * @return  1
	 */
	public function fixColumnChange()
	{
		$db = Factory::getDbo();
		$file = JPATH_ADMINISTRATOR . '/components/com_tjlms/sql/changecol.sql';
		$buffer = file_get_contents($file);

		// Create an array of queries from the sql file
		$queries = JDatabaseDriver::splitSql($buffer);

		foreach ($queries as $query)
		{
			if ($query)
			{
				// Fix up extra spaces around () and in general
				$find = array('#((\s*)\(\s*([^)\s]+)\s*)(\))#', '#(\s)(\s*)#');
				$replace = array('($3)', '$1');
				$updateQuery = preg_replace($find, $replace, $query);
				$wordArray = explode(' ', $updateQuery);

				if (isset($wordArray[2]))
				{
					$table = $wordArray[2];
					$tobechanged = $wordArray[4];
					$changeto = $wordArray[5];

					$colquery = "SHOW COLUMNS FROM {$table}";
					$db->setQuery($colquery);
					$table_columns = $db->loadColumn();

					if (in_array($tobechanged, $table_columns) && !in_array($changeto, $table_columns))
					{
						$db->setQuery($query);
						$db->execute();
					}
				}
			}
		}

		$this->fixMissingColumns();

		return 1;
	}

	/**
	 * Fix missing columns
	 *
	 * @return  boolean
	 *
	 * @since  1.1
	 */
	public function fixMissingColumns()
	{
		$db = Factory::getDbo();
		$file = JPATH_ADMINISTRATOR . '/components/com_tjlms/sql/addcol.sql';
		$buffer = file_get_contents($file);

		// Create an array of queries from the sql file
		$queries = JDatabaseDriver::splitSql($buffer);

		foreach ($queries as $query)
		{
			if ($query)
			{
				// Fix up extra spaces around () and in general
				$find = array('#((\s*)\(\s*([^)\s]+)\s*)(\))#', '#(\s)(\s*)#');
				$replace = array('($3)', '$1');
				$updateQuery = preg_replace($find, $replace, $query);
				$wordArray = explode(' ', $updateQuery);

				if (isset($wordArray[2]))
				{
					$table = $wordArray[2];
					$addColumn = $wordArray[5];

					$colquery = "SHOW COLUMNS FROM {$table}";
					$db->setQuery($colquery);
					$table_columns = $db->loadColumn();

					if (!in_array(str_replace('`', '', $addColumn), $table_columns))
					{
						$db->setQuery($query);
						$db->execute();
					}
				}
			}
		}

		return 1;
	}

	/**
	 * Migrate course track table from old to new. Now we have added total lessons, passed lessons and status columns
	 *
	 * @return  boolean
	 *
	 * @since  1.1
	 */
	public function migrateCourseTrack()
	{
		$track_cnt = $this->getCourseTrackCount();

		if ($track_cnt > 0)
		{
			if ($track_cnt > 100)
			{
				$limit = 100;

				$chunks = ceil($track_cnt / $limit);

				for ($i = 0; $i < $chunks; $i++)
				{
					$start = $i * $limit;
					$this->updateTrackEntries($limit, $start);
				}
			}
			else
			{
				$this->updateTrackEntries();
			}
		}

		echo json_encode(1);
		jexit();
	}

	/**
	 * Migrate course track table from old to new. Now we have added total lessons, passed lessons and status columns
	 *
	 * @param   INT  $limit  No of entries to fetch
	 * @param   INT  $start  Starting number
	 *
	 * @return  boolean
	 *
	 * @since  1.1
	 */
	public function updateTrackEntries($limit=100, $start=0)
	{
		require_once JPATH_SITE . '/components/com_tjlms/helpers/courses.php';
		$helper_obj = new TjlmsCoursesHelper;

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array('ct.id,ct.course_id,ct.user_id'));
		$query->from('#__tjlms_course_track AS ct');
		$query->join('inner', '#__tjlms_courses AS c ON ct.course_id=c.id');
		$query->join('inner', '#__users AS u ON ct.user_id=u.id');
		$query->group(array('user_id','course_id'));
		$query->setLimit($limit, $start);
		$db->setQuery($query);
		$ct_entries = $db->loadObjectList();

		foreach ($ct_entries as $track)
		{
			$courseProgress = $helper_obj->getCourseProgress($track->course_id, $track->user_id);

			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$table = Table::getInstance('Coursetrack', 'TjlmsTable', array('dbo', $db));
			$table->id = $track->id;
			$table->no_of_lessons = $courseProgress['totalLessons'];
			$table->completed_lessons = $courseProgress['completedLessons'];
			$table->status = $courseProgress['status'];
			$table->store();
		}
	}

	/**
	 * Get the number of entries present in course track
	 *
	 * @return  count
	 *
	 * @since  1.1
	 */
	public function getCourseTrackCount()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array('COUNT(*)'));
		$query->from('#__tjlms_course_track AS ct');
		$query->join('inner', '#__tjlms_courses AS c ON ct.course_id=c.id');
		$query->join('inner', '#__users AS u ON ct.user_id=u.id');
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Get the number of entries present in course track
	 *
	 * @param   INT  $course_id  Course ID
	 * @param   INT  $user_id    User Id
	 *
	 * @return  date
	 *
	 * @since  1.1
	 */
	private function getCourseCompletedDate($course_id, $user_id)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array('max(timeend)'));
		$query->from('#__tjlms_lesson_track AS lt');
		$query->join('inner', '#__tjlms_lessons AS l ON l.id=lt.lesson_id');
		$query->where('l.course_id = ' . (int) $course_id);
		$query->where('lt.user_id = ' . (int) $user_id);
		$query->where('lt.lesson_status = "completed"');
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * Execute the alter table queries where we need to change the column names
	 *
	 * @return  1
	 */
	public function addReminderTemplates()
	{
		$db = Factory::getDbo();
		$file = JPATH_ADMINISTRATOR . '/components/com_tjlms/sql/reminders_template.sql';
		$buffer = file_get_contents($file);

		// Create an array of queries from the sql file
		$queries = JDatabaseDriver::splitSql($buffer);

		foreach ($queries as $query)
		{
			if ($query)
			{
				$db->setQuery($query);
				$db->execute();
			}
		}

		return 1;
	}

	/**
	 * Function used to Set permission as allowed for all user groups if not set to any other
	 *
	 * @return  void
	 *
	 * @since   1.1
	 */
	public function allowEnrolforallGroups()
	{
		$db        = Factory::getDbo();

		// Get order Ids of selected course
		$query = $db->getQuery(true);
		$query->select('rules');
		$query->from('#__assets');
		$query->where("name = 'com_tjlms'");
		$db->setQuery($query);
		$temp = $db->loadResult();

		$rules = (array) json_decode($temp);

		if (empty($rules) || !in_array('core.enroll', $rules))
		{
			$query = $db->getQuery(true)
				->select('a.id')
				->from('#__usergroups AS a');

			$db->setQuery($query);
			$groups = $db->loadColumn();

			//require_once JPATH_SITE . '/components/com_config/model/cms.php';
			//require_once JPATH_SITE . '/components/com_config/model/form.php';
			//require_once JPATH_ADMINISTRATOR . '/components/com_config/models/application.php';

			foreach ($groups as $gid)
			{
				// Get Post DATA
				$permissions = array(
					'component' => 'com_tjlms',
					'action'    => 'core.enroll',
					'rule'      => $gid,
					'value'     => '1',
					'title'     => 'com_tjlms'
				);

			if (JVERSION < 4.0)
			{
				// Load Permissions from Session and send to Model
				$model    = new ConfigModelApplication;
				$response = $model->storePermissions($permissions);
			}
			else
			{
				// Load Permissions from Session and send to Model
				$model    = new ApplicationModel;
				$response = $model->storePermissions($permissions);
			}			}
		}
	}
}
