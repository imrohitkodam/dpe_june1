<?php
/**
 * @package    Com_Dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2019 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
use Joomla\Data\DataObject;
use Joomla\CMS\Factory;

jimport('joomla.application.component.modellist');


require_once JPATH_BASE . '/components/com_jlike/models/recommendations.php';

/**
 * Methods supporting a list of Subusers records.
 *
 * @since  1.6
 */
class DpeModelMyAssignments extends JlikeModelRecommendations
{
	/**
	 * constructor function
	 *
	 * @since  1.0
	 */
	public function __construct()
	{
		$path = JPATH_COMPONENT . '/helpers/' . 'lesson.php';

		if (!class_exists('TjlmsLessonHelper'))
		{
			// Require_once $path;
			JLoader::register('TjlmsLessonHelper', $path);
			JLoader::load('TjlmsLessonHelper');
		}

		$this->tjlmsLessonHelper = new TjlmsLessonHelper;

		parent::__construct();
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   DataObjectbaseQuery
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db       = $this->getDbo();
		$subQuery = $db->getQuery(true);
		$query    = $db->getQuery(true);
		$user     = Factory::getUser();

		// Select the required fields from the table.
		$query
			->select(
				$this->getState(
					'list.select', 'DISTINCT a.*'
				)
			);

		$query->from($db->quoteName('#__jlike_todos', 'a'));

		// Join over the content for content title & url
		$query->select($db->quoteName(array('c.title','c.url', 'c.element_id', 'c.params', 'tl.description')));
		$query->join('INNER', $db->quoteName('#__jlike_content', 'c') . ' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.content_id') . ')');

		// Join over the todos extended for interactions
		$query->select($db->quoteName(array('te.read', 'te.used', 'te.consented')));
		$query->join('LEFT', $db->quoteName('#__jlike_todos_extended', 'te') .
		' ON (' . $db->quoteName('a.id') . ' = ' . $db->quoteName('te.todo_id') . ')');

		// Join over the media table to get document format
		$query->select('tm.source');
		$query->join('INNER', $this->_db->qn('#__tjlms_lessons', 'tl') . 'ON(' . $this->_db->qn('c.element_id') . '=' . $this->_db->qn('tl.id') . ')');
		$query->join('INNER', $this->_db->qn('#__tjlms_media', 'tm') . 'ON(' . $this->_db->qn('tl.media_id') . '=' . $this->_db->qn('tm.id') . ')');
		$query->join('INNER', $db->qn('#__tjlms_lesson_cluster_xref', 'lc') . 'ON(' . $db->qn('tl.id') . '=' . $db->qn('lc.lesson_id') . ')');
		$query->join('INNER', $db->qn('#__tj_clusters', 'cl') . 'ON(' . $db->qn('lc.cluster_id') . '=' . $db->qn('cl.id') . ')');
		$query->join('INNER', $db->qn('#__tj_cluster_nodes', 'cu') . ' ON (' . $db->qn('cl.id') . ' = ' . $db->qn('cu.cluster_id') .
		' AND ' . $db->qn('a.assigned_to') . ' = ' . $db->qn('cu.user_id') . ')');

		// Following code is used to load active licence org of user
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters         = $clusterUserModel->getUsersClusters($user->id);

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			foreach ($clusters as $cluster)
			{
				$clusterIds[] = $cluster->cluster_id;
			}

			// Adding where clause to load documents only from active licence org
			$query->where($db->qn('cl.id') . " IN ('" . implode("','", (array) $clusterIds) . "')");
		}

		// Create the nested Query to get lesson track status
		$subQuery->select($db->quoteName('lesson_status'))
		->from($db->quoteName('#__tjlms_lesson_track', 'lt'))
		->where($db->quoteName('lt.lesson_id') . ' = ' . $db->quoteName('c.element_id'))
		->where($db->quoteName('lt.user_id') . ' = ' . $db->quoteName('a.assigned_to'))
		->order($db->quoteName('lt.id') . ' DESC')
		->setLimit('1');
		$query->select('(' . $subQuery . ' ) as status');

		// Get only assignment of logged in user
		$query->where($db->quoteName('a.assigned_to') . ' = ' . $db->quote($user->id));

		// Show assignment of published org only
		$query->where($db->quoteName('cl.state') . ' = 1');

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));

				// Compile the different search clauses.
				$searches   = array();
				$searches[] = 'c.title LIKE ' . $search;

				// Add the clauses to the query.
				$query->where('(' . implode(' OR ', $searches) . ')');
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		$query->group('a.content_id');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}
		else
		{
			$query->order($this->_db->qn('a.id') . ' DESC');
		}

		return $query;
	}

/*
	public function getDocumentStatusCount($userId, $status)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('COUNT(*)');
		$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
		$query->join('INNER', $db->quoteName('#__tjlms_lessons', 'l') . ' ON (' . $db->quoteName('lt.lesson_id') . ' = ' . $db->quoteName('l.id') . ')');
		$query->where($db->quoteName('user_id')." = ".$db->quote($userId));
		$query->where($db->quoteName('lesson_status')." = ".$db->quote($status));

		$db->setQuery($query);
		$count = $db->loadObjectList();
	}
*/
}
