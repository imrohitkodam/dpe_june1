<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\ListModel;

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);

/**
 * Methods supporting a list of Tmt records.
 *
 * @since  _DEPLOY_VERSION_
 */
class TmtModelAnswers extends ListModel
{
	protected $answerMediaClient = 'tjlms.answer';

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
				'id', 'a.id',
				'question_id', 'a.question_id',
				'answer', 'a.answer',
				'marks', 'a.marks',
				'is_correct', 'a.is_correct',
				'order', 'a.order'
			);
		}

		parent::__construct($config);
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
		$app = Factory::getApplication();

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);

		$questionId = $app->getUserStateFromRequest($this->context . '.filter.question_id', 'filter_question_id', '', 'integer');
		$this->setState('filter.question_id', $questionId);

		$showCorrectAnswer = $app->getUserStateFromRequest($this->context . '.filter.show_correct_answer', 'filter_show_correct_answer', '', 'integer');

		$this->setState('filter.show_correct_answer', $showCorrectAnswer);

		// Load the parameters.
		$params = ComponentHelper::getParams('com_tmt');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.order', 'ASC');
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
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('a.id, a.question_id, a.answer, a.comments');

		// Filter by show_correct_answer
		$showCorrectAnswer = $this->getState('filter.show_correct_answer');

		if ($showCorrectAnswer)
		{
			$query->select('a.is_correct, a.marks');
		}

		// Fetch answer media elements
		$query->select('mediaQr.media_id, mediaQr.client_id, mediaQr.source, mediaQr.original_filename, mediaQr.type AS media_type');

		$query->from('`#__tmt_answers` AS a');

		// Answer Media query join - start
		$answerMediaQuery = $this->_db->getQuery(true);
		$answerMediaQuery->select($this->_db->qn(array('xref.id', 'xref.media_id', 'xref.client_id', 'mf.source', 'mf.original_filename', 'mf.type')));
		$answerMediaQuery->from($this->_db->qn('#__tj_media_files_xref', 'xref'));
		$answerMediaQuery->join('INNER', $this->_db->qn('#__tj_media_files', 'mf')
				. ' ON (' . $this->_db->qn('xref.media_id') . ' = ' . $this->_db->qn('mf.id') . ')');

		$answerMediaQuery->where($this->_db->qn('xref.client') . '=' . $this->_db->quote($this->answerMediaClient));

		// Join here
		$query->leftJoin('(' . $answerMediaQuery . ') AS mediaQr
			ON ( ' . $this->_db->qn('a.id') . ' = ' . $this->_db->qn('mediaQr.client_id') . ')');

		// Answer Media query join - end

		// Filter by Question
		$questionId = $this->getState('filter.question_id');

		if ($questionId)
		{
			$query->where('a.question_id =' . (int) $questionId);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . $db->escape($search, true) . '%');
			}
		}

		// Filter by answer ids
		$answerId  = $this->getState('filter.id');

		if (is_numeric($answerId))
		{
			$query->where($db->quoteName('a.id') . ' = ' . (int) $answerId);
		}
		elseif (is_array($answerId))
		{
			$answerId = ArrayHelper::toInteger($answerId);
			$answerId = implode(',', $answerId);

			if (!empty($answerId))
			{
				$query->where($db->quoteName('a.id') . ' IN (' . $answerId . ')');
			}
		}

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
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

		$mediaLib = TJMediaStorageLocal::getInstance();

		$mediaPath = Uri::root() . $mediaLib->mediaUploadPath;

		foreach ($items as $item)
		{
			// Add media source URL
			if ($item->source)
			{
				$item->source_url = $mediaPath . '/' . $item->source;
			}
		}

		return $items;
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
	 * @return  string  A store id.
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
}
