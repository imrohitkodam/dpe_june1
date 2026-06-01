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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
jimport('techjoomla.common');

require_once JPATH_ADMINISTRATOR . '/components/com_tmt/helpers/tmt.php';
JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of Tmt records.
 *
 * @since  1.0.0
 */
class TmtModelquestions extends ListModel
{
	protected $questionMediaClient = 'tjlms.question';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @since   1.6
	 * @see     JController
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id',
				'a.id',
				'ordering',
				'a.ordering',
				'title',
				'a.title',
				'created_on',
				'a.created_on',
				'category',
				'a.category',
				'gradingtype',
				'a.gradingtype',
				'type',
				'a.type',
				'state',
				'a.state',
				'marks',
				'a.marks',
				'level',
				'a.level'
			);
		}

		parent::__construct($config);
		$this->comtjlmsHelper = new comtjlmsHelper;
		$this->techjoomlacommon = new TechjoomlaCommon;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// List state information.
		parent::populateState('a.created_on', 'DESC');

		// Initialise variables.
		$app = Factory::getApplication();

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		// Set filters
		$level = $app->getUserStateFromRequest($this->context . '.filter.level', 'filter_level', '', 'string');
		$this->setState('filter.level', $level);

		$type = $app->getUserStateFromRequest($this->context . '.filter.type', 'filter_type', '', 'string');
		$this->setState('filter.type', $type);

		$category = $app->getUserStateFromRequest($this->context . '.filter.category', 'filter_category', '', 'category');
		$this->setState('filter.category', $category);

		// Category title filter
		$categoryTitle = $app->getUserStateFromRequest($this->context . '.filter.category_title', 'filter_category_title', '', 'category_title');
		$this->setState('filter.category_title', $categoryTitle);

		if ($app->input->get('gradingtype'))
		{
			$this->setState('filter.gradingtype', $app->input->get('gradingtype'));
		}
		else
		{
			$gradingtype = $app->getUserStateFromRequest($this->context . '.filter.gradingtype', 'gradingtype');
			$this->setState('filter.gradingtype', $gradingtype);
		}

		$unique = $app->getUserStateFromRequest('com_tmt' . 'filter.unique', 'unique');

		$unique = (string) preg_replace('/[^0-9_]/i', '', $unique);
		$this->setState('filter.unique', $unique);

		$created_by = $app->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by');
		$this->setState('filter.created_by', $created_by);

		// Set ordering
		$orderCol = $app->getUserStateFromRequest($this->context . '.filter_order', 'filter_order');

		if (!in_array($orderCol, $this->filter_fields))
		{
			$orderCol = 'a.created_on';
		}

		$this->setState('list.ordering', $orderCol);

		// Set ordering direction
		$listOrder = $app->getUserStateFromRequest($this->context . '.filter_order_Dir', 'filter_order_Dir');

		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'DESC';
		}

		if (!empty($listOrder))
		{
			$this->setState('list.direction', $listOrder);
		}
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.0.0
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$query = $this->_db->getQuery(true);
		$user = Factory::getUser();
		$layout = Factory::getApplication()->input->getInt('layout', 'default');

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*'));
		$query->from($this->_db->qn('#__tmt_questions', 'a'));

		// Join over the foreign key 'category_id'
		$query->select($this->_db->qn('c.title', 'category'));
		$query->join('LEFT', $this->_db->qn('#__categories', 'c') . ' ON(' . $this->_db->qn('c.id') . ' = ' . $this->_db->qn('a.category_id') . ')');

		// Fetch Question's Media
		$questionMedia = $this->getState("filter.media");

		if (!empty($questionMedia))
		{
			// Fetch question media elements
			$query->select('mediaQr.media_id, mediaQr.client_id, mediaQr.source, mediaQr.original_filename, mediaQr.type AS media_type');

			// Question Media query join - start
			$questionMediaQuery = $this->_db->getQuery(true);
			$questionMediaQuery->select($this->_db->qn(array('xref.id', 'xref.media_id', 'xref.client_id', 'mf.source', 'mf.original_filename', 'mf.type')));
			$questionMediaQuery->from($this->_db->qn('#__tj_media_files_xref', 'xref'));
			$questionMediaQuery->join('INNER', $this->_db->qn('#__tj_media_files', 'mf')
					. ' ON (' . $this->_db->qn('xref.media_id') . ' = ' . $this->_db->qn('mf.id') . ')');

			$questionMediaQuery->where($this->_db->qn('xref.client') . '=' . $this->_db->quote($this->questionMediaClient));

			// Join here
			$query->leftJoin('(' . $questionMediaQuery . ') AS mediaQr
				ON ( ' . $this->_db->qn('a.id') . ' = ' . $this->_db->qn('mediaQr.client_id') . ')');

			// Question Media query join - end
		}

		// Join over the created by field 'created_by'
		$tjlmsparams    = ComponentHelper::getParams('com_tjlms');
		$show_user_or_username = $tjlmsparams->get('show_user_or_username', 'name');

		if ($show_user_or_username == 'username')
		{
			$query->select($this->_db->qn('cr.username', 'created_by_alias'));
		}
		elseif ($show_user_or_username == 'name')
		{
			$query->select($this->_db->qn('cr.name', 'created_by_alias'));
		}
		else
		{
			$query->select($this->_db->qn('cr.id', 'created_by_alias'));
		}

		$query->join('LEFT', $this->_db->qn('#__users', 'cr') . ' ON(' . $this->_db->qn('cr.id') . ' = ' . $this->_db->qn('a.created_by') . ')');

		// Get questions from published categories only.
		$query->where($this->_db->qn('c.published') . '=1');

		// Get questions as per the grading type passed

		if ($this->getState('filter.gradingtype'))
		{
			$query->where('a.gradingtype =' . $this->_db->quote($this->getState('filter.gradingtype')));
		}

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where($this->_db->qn('a.state') . ' = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where($this->_db->qn('a.state') . ' IN (0, 1)');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($this->_db->qn('a.id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $this->_db->q('%' . $this->_db->escape($search, true) . '%');
				$query->where('( a.title LIKE ' . $search .
					' OR a.alias LIKE ' . $search . ' )'
				);
			}
		}

		if ($layout == 'modal')
		{
			// Get only published questions for test - add questions - popup
			$query->where($this->_db->qn('a.state') . ' = 1');

			if ($this->getState('filter.unique'))
			{
				$arr = explode('_', $this->getState('filter.unique'));

				// Get the questions already added to the quiz
				$subQ = $this->_db->getQuery(true);
				$subQ->select($this->_db->qn("question_id"))->from($this->_db->qn("#__tmt_tests_questions"))
					->where($this->_db->qn("test_id") . " = " . $this->_db->q($arr[0]));

				$query->where($this->_db->qn("a.id") . " NOT IN (" . $subQ . ")");
			}
		}

		// Filtering type
		$filter_type = $this->getState("filter.type");

		if ($filter_type)
		{
			$query->where($this->_db->qn('a.type') . ' = ' . $this->_db->q($filter_type));
		}

		// Filtering level
		$filter_level = $this->getState("filter.level");

		if ($filter_level)
		{
			$query->where($this->_db->qn('a.level') . ' = ' . $this->_db->q($filter_level));
		}

		// Filtering category_id
		$filter_category = $this->getState("filter.category");

		if ($filter_category)
		{
			$query->where($this->_db->qn('a.category_id') . ' = ' . (int) $filter_category);
		}

		// Filter by Categoty title
		$categoryTitle = $this->getState("filter.category_title");

		if (!empty($categoryTitle))
		{
			if (is_array($categoryTitle))
			{
				// Joomla $this->_db->quote every element of array.
				$categoryTitle = array_map(array($this->_db, 'quote'), $categoryTitle);

				// Create safe string of array.
				$categoryTitle = implode(',', $categoryTitle);

				$query->where($this->_db->qn('c.title') . ' IN (' . $categoryTitle . ')');
			}
			else
			{
				$query->where($this->_db->qn('c.title') . ' = ' . $this->_db->q($categoryTitle));
			}
		}

		// Filtering created_by
		$filter_created_by = $this->getState("filter.created_by");

		if ($filter_created_by)
		{
			$query->where($this->_db->qn('a.created_by') . ' = ' . (int) $filter_created_by);
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($this->getState('list.ordering') . ' ' . $this->getState('list.direction'));
		}

		return $query;
	}

	/**
	 * To get the records
	 *
	 * @return  Object
	 *
	 * @since  1.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		// Fetch Answers
		$fetchAnswers      = $this->getState('filter.answers', '');
		$showCorrectAnswer = $this->getState('filter.show_correct_answer', 0);

		JLoader::import('components.com_tmt.models.answers', JPATH_ADMINISTRATOR);

		$mediaLib = TJMediaStorageLocal::getInstance();

		$mediaPath = Uri::root() . $mediaLib->mediaUploadPath;

		$questionMedia = $this->getState("filter.media");

		// Set readable names for question type
		foreach ($items as $item)
		{
			// Add media source URL
			if ($questionMedia && $item->source)
			{
				$item->source_url = $mediaPath . '/' . $item->source;
			}

			switch ($item->type)
			{
				case "radio":
					$item->type = Text::_('COM_TMT_QTYPE_MCQ_SINGLE');
				break;

				case "checkbox":
					$item->type = Text::_('COM_TMT_QTYPE_MCQ_MULTIPLE');
				break;

				case "text":
					$item->type = Text::_('COM_TMT_QTYPE_SUB_TEXT');
				break;

				case "textarea":
					$item->type = Text::_('COM_TMT_QTYPE_SUB_TEXTAREA');
				break;

				case "file_upload":
					$item->type = Text::_('COM_TMT_QTYPE_SUB_FILE_UPLOAD');
				break;

				default:
					$item->type = $item->type;
			}

			if ($fetchAnswers)
			{
				$answers = array ();

				$answersModel = BaseDatabaseModel::getInstance('answers', 'TmtModel', array('ignore_request' => true));
				$answersModel->setState('filter.question_id', $item->id);

				if ($showCorrectAnswer)
				{
					$answersModel->setState('filter.show_correct_answer', 1);
				}

				$answers = $answersModel->getItems();

				if (!empty($answers))
				{
					$item->answers = $answers;
				}
			}
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
	public function setItemState($items, $state)
	{
		$app = Factory::getApplication();

		$count = 0;
		$not_allowed_del = array();

		if (is_array($items))
		{
			foreach ($items as $id)
			{
				$canEdit = TmtHelper::canManageQuestion($id);

				if (!$canEdit)
				{
					Log::add(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), Log::WARNING, 'jerror');

					continue;
				}

				// Check if a questions can be deleted.
				// If question is used in 1 or more tests, it can't be deleted.
				$isUsed = TMT::Question($id)->isUsed();

				if (empty($isUsed) || $state == 1)
				{
					$object                = new stdClass;
					$object->id            = $id;
					$object->state         = $state;

					if (!$this->_db->updateObject('#__tmt_questions', $object, 'id'))
					{
						$this->setError($this->_db->getErrorMsg());

						return false;
					}

					$count++;
				}
				else
				{
					// Show which questions can not be deleted & why.
					$query = $this->_db->getQuery(true);

					try
					{
						$query->select($this->_db->qn(array('title')));
						$query->from($this->_db->qn('#__tmt_questions'));
						$query->where($this->_db->qn('id') . ' = ' . (int) $id);

						$this->_db->setQuery($query);
						$qstnTitle = $this->_db->loadResult();
					}
					catch (Exception $e)
					{
						$this->setError($e->getMessage());

						return false;
					}

					$not_allowed_del[] = $id . ' : ' . $qstnTitle;
				}
			}

			if (count($not_allowed_del))
			{
				$app->enqueueMessage(Text::sprintf(Text::_('COM_TMT_Q_LIST_ERROR_CHANGE_STATUS_Q_USED'), implode(', ', $not_allowed_del)), 'notice');
			}
		}

		return $count;
	}

	/**
	 * update lesson entry if new module is assign to it.
	 *
	 * @param   string  $question_Id  A prefix for the store id.
	 * @param   string  $grpId        A prefix for the store id.
	 * @param   string  $testId       A prefix for the store id.
	 *
	 * @return  JSON
	 */
	public function updatequestionSection($question_Id, $grpId, $testId )
	{
		$db	= Factory::getDBO();
		$query = $db->getQuery(true);
		$query->update('#__tmt_tests_questions');
		$query->set('section_id=' . $grpId);
		$query->where('question_id=' . $question_Id . ' AND test_id=' . $testId);

		$db->setQuery($query);

		if (!$db->execute())
		{
			echo $this->_db->getErrorMsg();

			return false;
		}

		return true;
	}

	/**
	 * function is used to save sorting of LESSONS.
	 *
	 * @param   string  $testId  A prefix for the store id.
	 * @param   string  $grpId   A prefix for the store id.
	 *
	 * @return  JSON
	 */
	public function getQuestionOrderList($testId, $grpId)
	{
		$db	= Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('question_id as id, `order`');
		$query->from('#__tmt_tests_questions');
		$query->where('test_id=' . (int) $testId);
		$query->where('section_id=' . (int) $grpId);

		$db->setQuery($query);
		$question_order = $db->loadobjectlist();

		if (!empty($question_order) && count($question_order) > 0)
		{
			$list = array();

			foreach ($question_order as $key => $q_order)
			{
				$list[$q_order->id] = $q_order->order;
			}

			return $list;
		}
		else
		{
				return false;
		}
	}

	/**
	 * update lesson order as per sorting done.
	 *
	 * @param   string  $qid      Question Id
	 * @param   string  $orderid  New Order Id
	 * @param   string  $testid   Test Id
	 * @param   string  $section  Section Id
	 *
	 * @return  JSON
	 */
	public function switchOrderQuestion($qid, $orderid, $testid, $section)
	{
		$db	= Factory::getDBO();
		$query = $db->getQuery(true);
		$query->update('#__tmt_tests_questions');
		$query->set('`order`=' . (int) $orderid);
		$query->set('`section_id`=' . (int) $section);
		$query->where('question_id=' . (int) $qid . ' AND test_id=' . (int) $testid);

		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Method to delete items
	 *
	 * @param   array  $items  An array of category ids
	 *
	 * @return  int  $count  Count of total records deleted
	 *
	 * @since   1.0
	 */
	public function delete($items)
	{
		$app = Factory::getApplication();
		$count = 0;
		$not_allowed_del = array();

		if (is_array($items))
		{
			foreach ($items as $id)
			{
				// Check if a questions can be deleted.
				// If question is used in 1 or more tests, it can't be deleted.
				$isUsed = TMT::Question($id)->isUsed();

				if (empty($isUsed))
				{
					$canDelete = TmtHelper::canManageQuestion($id);

					if (!$canDelete)
					{
						Log::add(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), Log::WARNING, 'jerror');

						continue;
					}

					$query = $this->_db->getQuery(true);

					$conditions = array(
						$this->_db->qn('id') . ' = ' . (int) $id
					);

					$query->delete($this->_db->qn('#__tmt_questions'));
					$query->where($conditions);

					$this->_db->setQuery($query);

					if (!$this->_db->execute())
					{
						$this->setError($this->_db->getErrorMsg());

						return 0;
					}
					else
					{
						$query = $this->_db->getQuery(true);

						$conditions = array(
							$this->_db->qn('question_id') . ' = ' . (int) $id
						);

						$query->delete($this->_db->qn('#__tmt_answers'));
						$query->where($conditions);

						$this->_db->setQuery($query);

						if (!$this->_db->execute())
						{
							$this->setError($this->_db->getErrorMsg());

							return 0;
						}
					}

					$count++;
				}
				else
				{
					// Show which questions can not be deleted & why.
					$query = $this->_db->getQuery(true);

					$query->select($this->_db->qn(array('title')));
					$query->from($this->_db->qn('#__tmt_questions'));
					$query->where($this->_db->qn('id') . ' = ' . (int) $id);

					$this->_db->setQuery($query);
					$qstnTitle = $this->_db->loadResult();

					$not_allowed_del[] = $id . ' : ' . $qstnTitle . '<br/>';
				}
			}

			if (count($not_allowed_del))
			{
				$app->enqueueMessage(Text::sprintf(Text::_('COM_TMT_Q_LIST_ERROR_DELETE_Q_USED'), implode(',', $not_allowed_del)), 'notice');
			}
		}

		return $count;
	}
}
