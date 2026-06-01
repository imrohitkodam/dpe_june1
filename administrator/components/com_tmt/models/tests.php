<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tmt
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

jimport('joomla.application.component.modellist');
jimport('techjoomla.common');

/**
 * Methods supporting a list of Tmt records.
 *
 * @since  1.6
 */
class TmtModeltests extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see JController
	 *
	 * @since    1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
							'id', 'a.id',
			'ordering', 'a.ordering',
			'published', 'a.state',
			'coursecreators', 'a.created_by',
			'title', 'a.title',
			'description', 'a.description',
			'reviewers', 'a.reviewers',
			'show_time', 'a.show_time',
			'time_duration', 'a.time_duration',
			'show_time_finished', 'a.show_time_finished',
			'time_finished_duration', 'a.time_finished_duration',
			'total_marks', 'a.total_marks',
			'passing_marks', 'a.passing_marks',
			);
		}

		$this->comtjlmsHelper = new comtjlmsHelper;
		$this->techjoomlacommon = new TechjoomlaCommon;
		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   A prefix for the store id.
	 * @param   string  $direction  A prefix for the store id.
	 *
	 * @return	string		A store id.
	 *
	 * @since	1.6
	 */
	protected function populateState($ordering = 'a.id', $direction = 'desc')
	{
		// Initialise variables.
		$app = Factory::getApplication();

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$created_by = $app->getUserStateFromRequest($this->context . '.filter.coursecreators', 'filter_coursecreators', '', 'string');
		$this->setState('filter.coursecreators', $created_by);

		$courseId = $app->getUserStateFromRequest($this->context . '.cid', 'cid');
		$this->setState('cid', $courseId);

		$modId = $app->getUserStateFromRequest($this->context . '.mid', 'mid');
		$this->setState('mid', $modId);

		$gradingtype = $app->getUserStateFromRequest($this->context . '.gradingtype', 'gradingtype');
		$this->setState('gradingtype', $gradingtype);

		$published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);

		if ($layout == 'modal')
		{
			$this->setState('gradingtype', "quiz");
			$this->setState('filter.published', 1);
		}

		// List state information.
		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return	string		A store id.
	 *
	 * @since	1.6
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 *
	 * @since	1.6
	 */
	protected function getListQuery()
	{
		$gradingtype = $this->getState('gradingtype');

		// Create a new query object
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		$user = Factory::getUser();

		// Select the required fields from the table.
		$query->select(
				$this->getState(
					'list.select', 'a.*'
				)
		);
		$query->from('`#__tmt_tests` AS a');

		// Join over the created by field 'created_by'
		$query->select("created_by.name AS created_by");
		$query->join("LEFT", "#__users AS created_by ON created_by.id = a.created_by");
		$query->where("parent_id = '0'");

		// Filter by search in title
		$search = trim($this->getState('filter.search'));

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($this->_db->qn('a.id') . ' = ' . $this->_db->q((int) substr($search, 3)));
			}
			else
			{
				$search = '%' . $this->_db->escape($search, true) . '%';
				$query->where($this->_db->qn('a.title') . ' LIKE ' . $this->_db->q($search, false));
			}
		}

		// Filtering created_by
		$filter_created_by = $this->getState("filter.coursecreators");

		if ($filter_created_by)
		{
			$query->where("a.created_by = '" . $filter_created_by . "'");
		}

		if ($gradingtype)
		{
			$query->where($db->quoteName("gradingtype") . " = " . $db->quote($gradingtype));
		}

		$cid = $this->getState("cid");

		if ($cid)
		{
			$used_tests = $this->alreadyaddedtest($cid, $gradingtype);

			if (!empty($used_tests))
			{
				$used_tests_str = implode(",", $used_tests);
				$query->where("a.id NOT IN(" . $used_tests_str . ")");
			}
		}

		// Filter by published state
		$published = $this->getState('filter.published');

		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.state = 0 OR a.state = 1)');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'DESC');

		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		return $query;
	}

	/**
	 * Method to set item state
	 *
	 * @return  int     $count  Count of total records updated
	 *
	 * @since   1.0
	 */
	public function getItems()
	{
		$lmsparams = ComponentHelper::getParams('com_tjlms');
		$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		// Find no. of repsonses against each test
		$db = Factory::getDBO();

		if (!empty($items = parent::getItems()))
		{
			foreach ($items as $key => $item)
			{
				$item->created_on = $this->techjoomlacommon->getDateInLocal($item->created_on, 0, $date_format_show);

				$query = $db->getQuery(true);
				$query->select('COUNT(*) AS response');
				$query->from('`#__tmt_tests_attendees`');
				$query->where('test_id = ' . $item->id);
				$db->setQuery($query);
				$count = $db->loadResult();
				$item->responses = $count;

				/*invited invitation
				$item->invited = $this->invited($item->id);

				expired invitation
				$item->expired=$this->expired($item->id);

				rejected invitaion*/
				$item->rejected = $this->rejected($item->id);

				/* for clicked invitation
				$item->clicked = $this->clicked($item->id);

				for opened invitation
				$item->opened = $this->getOpened($item->id);

				/* for completed invitation*/
				$item->completed = $this->completed($item->id);
			}

			// Find no. of repsonses against each test
			$db = Factory::getDBO();

			foreach ($items as $item)
			{
				$query = $db->getQuery(true);
				$query->select('COUNT(tq.id) AS questions_count');
				$query->from('`#__tmt_tests_questions` AS tq');
				$query->where('test_id = ' . $item->id);
				$db->setQuery($query);
				$count = $db->loadResult();
				$item->questions_count = $count;
			}

			$items = array_values($items);
		}

		return $items;
	}

	/**
	 * Method to set item state
	 *
	 * @param   array   $items  An array of category ids
	 * @param   string  $state  The item state to be set 0 or 1 etc
	 *
	 * @return  int     $count  Count of total records updated
	 *
	 * @since   1.0
	 */
	public function setItemState($items,$state)
	{
		$db = $this->getDbo();
		$count = 0;

		if (is_array($items))
		{
			foreach ($items as $id)
			{
				$db = Factory::getDBO();
				$query = "UPDATE #__tmt_tests SET state=" . $state . " WHERE id=" . $id;
				$db->setQuery($query);

				if (!$db->execute())
				{
					$this->setError($this->_db->getErrorMsg());

					return 0;
				}

				$count++;
			}
		}

		return $count;
	}

	/**
	 * Method to delete quize
	 *
	 * @param   array  $lessonId  An array of category ids
	 *
	 * @return  int     $count  Count of total records deleted
	 *
	 * @since   1.0
	 */
	public function delete_lessonquiz($lessonId)
	{
		$db = $this->getDbo();
		$query = "DELETE FROM #__tjlms_tmtquiz WHERE lesson_id =" . $lessonId;
		$db->setQuery($query);

		if ($db->execute())
		{
			return 1;
		}
		// $this->delete($test_id);
	}

	/**
	 * Method to delete items
	 *
	 * @param   array  $items  An array of category ids
	 *
	 * @return  int     $count  Count of total records deleted
	 *
	 * @since   1.0
	 */
	public function delete($items)
	{
		$db = $this->getDbo();
		$count = 0;
		$app = Factory::getApplication();

		if (is_array($items))
		{
			$tmtTestsHelper = new tmtTestsHelper;

			foreach ($items as $id)
			{
					// Check if a test can be deleted.
					// If test has some invites or candidate data, it can't be deleted.
					$canBeDeleted = $tmtTestsHelper->canBeDeleted($id);

					if ($canBeDeleted === true)
					{
						$query = "DELETE FROM #__tmt_tests WHERE id=" . $id;
						$db->setQuery($query);

						if (!$db->execute())
						{
							$this->setError($this->_db->getErrorMsg());

							return 0;
						}
						else
						{
							// @TODO handle this
							// Delete corresponding test reviewers.
							$query = "DELETE FROM #__tmt_tests_reviewers WHERE test_id=" . $id;
							$db->setQuery($query);

							if (!$db->execute())
							{
								$this->setError($this->_db->getErrorMsg());

								return 0;
							}

							// Delete corresponding question assigned for this test.
							$query = "DELETE FROM #__tmt_tests_questions WHERE test_id=" . $id;
							$db->setQuery($query);

							if (!$db->execute())
							{
								$this->setError($this->_db->getErrorMsg());

								return 0;
							}
						}

						$count++;
					}
					else
					{
						// Show which tests can not be deleted & why.
						$query = "SELECT title FROM #__tmt_tests WHERE id=" . $id;
						$db->setQuery($query);
						$testTitle = $db->loadResult();
						$app->enqueueMessage(
						Text::sprintf(Text::_('COM_TMT_TESTS_ERROR_DELETE_TEST_USED'), $testTitle),
						'error');
					}
			}
		}

		return $count;
	}

	/**
	 * Already assigned test
	 *
	 * @param   int  $courseId     course id
	 * @param   int  $gradingType  type of quiz
	 *
	 * @return  boolean  true or false
	 *
	 * @since 1.0.0
	 */
	public function alreadyaddedtest($courseId, $gradingType = '')
	{
		$db = Factory::getDBO();

		$query = $db->getQuery(true);
		$query->select('tm.source');
		$query->from($db->quoteName('#__tjlms_lessons', 'l'));
		$query->join('LEFT', $db->quoteName('#__tjlms_media', 'tm') . ' ON (' . $db->quoteName('l.media_id') . ' = ' . $db->quoteName('tm.id') . ')');
		$query->where('l.course_id = ' . $courseId);

		if ($gradingType)
		{
			$query->where($db->quoteName('l.format') . " = " . $db->quote($gradingType));
		}

		$db->setQuery($query);

		return $tests = $db->loadColumn();
	}

	/**
	 * Rejected interview count
	 *
	 * @param   int  $id  set to 0
	 *
	 * @return  boolean  true or false
	 *
	 * @since 1.0.0
	 */
	public function rejected($id = 0)
	{
		$db = Factory::getDBO();
		$query = "SELECT COUNT(ti.id) AS rejected
		FROM #__tmt_tests_attendees AS ti
		WHERE ti.attempt_status = 2
		AND ti.test_id = " . $id;
		$db->setQuery($query);

		return $rejected = $db->loadResult();
	}

	/**
	 * Completed interview count
	 *
	 * @param   int  $id  set to 0
	 *
	 * @return  boolean  true or false
	 *
	 * @since 1.0.0
	 */
	public function completed($id = 0)
	{
		$db = Factory::getDBO();
		$query = "SELECT COUNT(ti.id) AS completed
		FROM #__tmt_tests_attendees AS ti
		WHERE ti.attempt_status = 1
		AND ti.test_id = " . $id;

		$db->setQuery($query);

		return $completed = $db->loadResult();
	}
}
