<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;

jimport('joomla.application.component.modellist');

require_once JPATH_SITE . '/components/com_tjlms/helpers/courses.php';

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.6
 */
class TjlmsModelCourseLessons extends ListModel
{
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   string  $lessonId  A prefix for the store id.
	 * @param   string  $userId    A prefix for the store id.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.6
	 */
	public function getEnrolledCoursesByLesson($lessonId, $userId)
	{
		return;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	items
	 *
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		$query = $this->_db->getQuery(true);
		$query->select("*");
		$query->from($this->_db->qn('#__tjlms_courses_lessons'));

		if ($this->getState("filter.lesson"))
		{
			$query->where($this->_db->qn('lesson_id') . '=' . $this->_db->q((int) $this->getState("filter.lesson")));
		}

		if ($this->getState("filter.lesson"))
		{
			$query->where($this->_db->qn('course_id') . '=' . $this->_db->q((int) $this->getState("filter.course")));
		}

		return $query;
	}

	/**
	 * update lesson entry if new module is assign to it.
	 *
	 * @param   string  $lessonId  A prefix for the store id.
	 * @param   string  $modId     A prefix for the store id.
	 * @param   string  $courseId  A prefix for the store id.
	 *
	 * @return  JSON
	 */
	public function updateLessonsModule( $lessonId, $modId, $courseId )
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->qn('#__tjlms_lessons'));
			$query->set($this->_db->qn('mod_id') . '=' . $this->_db->q((int) $modId));
			$query->where($this->_db->qn('id') . '=' . $this->_db->q((int) $lessonId));
			$query->where($this->_db->qn('course_id') . '=' . $this->_db->q((int) $courseId));
			$this->_db->setQuery($query);

			if (!$this->_db->execute())
			{
				echo $this->_db->getErrorMsg();

				return false;
			}

			return true;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * update lesson order as per sorting done.
	 *
	 * @param   string  $key        A prefix for the store id.
	 * @param   string  $newRank    A prefix for the store id.
	 * @param   string  $course_id  A prefix for the store id.
	 * @param   string  $mod_id     A prefix for the store id.
	 *
	 * @return  JSON
	 */
	public function switchOrderLesson($key,$newRank,$course_id,$mod_id)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->update($this->_db->qn('#__tjlms_lessons'));
			$query->set($this->_db->qn('ordering') . '=' . $this->_db->q((int) $newRank));
			$query->where($this->_db->qn('id') . '=' . $this->_db->q((int) $key));
			$query->where($this->_db->qn('course_id') . '=' . $this->_db->q((int) $course_id));
			$query->where($this->_db->qn('mod_id') . '=' . $this->_db->q((int) $mod_id));

			$this->_db->setQuery($query);
			$this->_db->execute();
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}

	/**
	 * function is used to save sorting of LESSONS.
	 *
	 * @param   string  $course_id  A prefix for the store id.
	 * @param   string  $mod_id     A prefix for the store id.
	 *
	 * @return  JSON
	 */
	public function getLessonsOrderList($course_id,$mod_id)
	{
		try
		{
			$query = $this->_db->getQuery(true);
			$query->select($this->_db->qn(array('id','ordering')));
			$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
			$query->where($this->_db->qn('course_id') . ' = ' . $this->_db->q((int) $course_id));
			$query->where($this->_db->qn('mod_id') . ' = ' . $this->_db->q((int) $mod_id));
			$this->_db->setQuery($query);

			$lesson_order = $this->_db->loadobjectlist();

				if (!empty($lesson_order) && count($lesson_order) > 0)
				{
					$list = array();

					foreach ($lesson_order as $key => $l_order)
					{
						$list[$l_order->id] = $l_order->ordering;
					}

					return $list;
				}
				else
				{
						return false;
				}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}
}
