<?php
/**
 * @version    SVN: <svn_id>
 * @package    Tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tjlms records.
 */
class TjlmsModelLessons extends JModelList {

	/**
	 * Constructor.
	 *
	 * @param    array    An optional associative array of configuration settings.
	 * @see        JController
	 * @since    1.6
	 */
	public function __construct($config = array()) {
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
								'id', 'a.id',
				'ordering', 'a.ordering',
				'state', 'a.state',
				'created_by', 'a.created_by',
				'name', 'a.name',
				'course_id', 'a.course_id',
				'mod_id', 'a.mod_id',
				'short_desc', 'a.short_desc',
				'despcription', 'a.despcription',
				'img', 'a.img',
				'free_lesson', 'a.free_lesson',
				'no_of_attempts', 'a.no_of_attempts',
				'attempts_grade', 'a.attempts_grade',
				'consider_marks', 'a.consider_marks',
				'format', 'a.format',
				'eligibility_criteria', 'a.eligibility_criteria',

			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null) {
		// Initialise variables.
		$app = JFactory::getApplication('administrator');

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$published = $app->getUserStateFromRequest($this->context . '.filter.state', 'filter_published', '', 'string');
		$this->setState('filter.state', $published);



		// Load the parameters.
		$params = JComponentHelper::getParams('com_tjlms');
		$this->setState('params', $params);

		// List state information.
		parent::populateState('a.name', 'asc');
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param	string		$id	A prefix for the store id.
	 * @return	string		A store id.
	 * @since	1.6
	 */
	protected function getStoreId($id = '') {
		// Compile the store id.
		$id.= ':' . $this->getState('filter.search');
		$id.= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return	JDatabaseQuery
	 * @since	1.6
	 */
	protected function getListQuery() {
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
				$this->getState(
						'list.select', 'a.*'
				)
		);
		$query->from('`#__tjlms_lesson` AS a');


		// Join over the users for the checked out user
		$query->select("uc.name AS editor");
		$query->join("LEFT", "#__users AS uc ON uc.id=a.checked_out");
		// Join over the user field 'created_by'
		$query->select('created_by.name AS created_by');
		$query->join('LEFT', '#__users AS created_by ON created_by.id = a.created_by');



		// Filter by published state
		$published = $this->getState('filter.state');
		if (is_numeric($published)) {
			$query->where('a.state = ' . (int) $published);
		} else if ($published === '') {
			$query->where('(a.state IN (0, 1))');
		}

		// Filter by search in title
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('a.id = ' . (int) substr($search, 3));
			} else {
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('( a.name LIKE '.$search.' )');
			}
		}




		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');
		if ($orderCol && $orderDirn) {
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

		public function getItems() {
		$items = parent::getItems();

		return $items;
	}

	/**
	 * update lesson entry if new module is assign to it.
	 *
	 **/
	/*public function updateLessonsModule( $lessonId, $modId, $courseId )
	{
		$db	= JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->update( '#__tjlms_lesson' );
		$query->set( 'mod_id='.$modId );
		$query->where( 'id='.$lessonId.' AND course_id='.$courseId );
		$db->setQuery($query);
		if( !$db->execute() )
		{
			echo $this->_db->getErrorMsg();
			return false;
		}
		return true;
	}*/
public function updateLessonsModule( $lessonId, $modId, $courseId )
	{
		$db	= JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->update( '#__tjlms_lesson' );
		$query->set( 'mod_id='.$modId );
		$query->where( 'id='.$lessonId.' AND course_id='.$courseId );
		$db->setQuery($query);
		if( !$db->execute() )
		{
			echo $this->_db->getErrorMsg();
			return false;
		}
		return true;
	}
	/**
	 * update lesson order as per sorting done.
	 *
	 **/
	/*function switchOrderLesson($key,$newRank,$course_id,$mod_id)
	{

		$db	= JFactory::getDBO();
		$query = "UPDATE `#__tjlms_lesson` SET `ordering`=".$newRank." WHERE id=".$key." AND course_id=".$course_id." AND mod_id=".$mod_id;
		$db->setQuery($query);
		$db->execute();
	}*/
	/**
	 * update lesson order as per sorting done.
	 *
	 **/
	function switchOrderLesson($key,$newRank,$course_id,$mod_id)
	{

		$db	= JFactory::getDBO();
		$query = "UPDATE `#__tjlms_lesson` as l  SET `l.order`=".$newRank." WHERE l.id=".$key." AND l.course_id=".$course_id." AND l.mod_id=".$mod_id;
		$db->setQuery($query);
		$db->execute();
	}


	/**
	 * function is used to save sorting of LESSONS.
	 */
	/*public function getLessonsOrderList($course_id,$mod_id)
	{
		$db	= JFactory::getDBO();

		$query = "SELECT `id`,`ordering` FROM `#__tjlms_lesson` WHERE course_id=".$course_id." AND mod_id=".$mod_id;

		$db->setQuery($query);
		$lesson_order=$db->loadobjectlist();

		//
			if (!empty($lesson_order) && count($lesson_order) > 0)
			{
				$list=array();
				foreach($lesson_order as $key=>$l_order)
				{
					$list[$l_order->id]=$l_order->order;
				}
				return $list;
			}
			else
			{
					return false;
			}
	}*/
	/**
	 * function is used to save sorting of LESSONS.
	 */
	public function getLessonsOrderList($course_id,$mod_id)
	{
		$db	= JFactory::getDBO();

		$query = "SELECT `l.id`,`l.order` FROM `#__tjlms_lesson` as l WHERE l.course_id=".$course_id." AND l.mod_id=".$mod_id;

		$db->setQuery($query);
		$lesson_order=$db->loadobjectlist();

		//
			if (!empty($lesson_order) && count($lesson_order) > 0)
			{
				$list=array();
				foreach($lesson_order as $key=>$l_order)
				{
					$list[$l_order->id]=$l_order->order;
				}
				return $list;
			}
			else
			{
					return false;
			}
	}


}
