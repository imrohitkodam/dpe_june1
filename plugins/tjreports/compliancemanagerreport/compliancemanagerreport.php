<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Data\DataObject;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

// If plg_dpeaddtodo enable then load following js

if (PluginHelper::isEnabled('system', 'dpeaddtodo'))
{
	HTMLHelper::script('plugins/system/dpeaddtodo/addtodo.js');
	
}

/**
 * Compliance Manager report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelComplianceManagerreport extends TjreportsModelReports
{
	protected $default_order       = 'id';

	protected $default_order_dir   = 'DESC';

	public $showSearchResetButton  = -1; 

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     BaseDatabaseModel
	 * @since   1.6
	 */
	public function __construct($config = array())
	{	
		Factory::getApplication()->input->set('report', 'compliancemanagerreport');

		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

			$this->columns = array(
			'title' => array('table_column' => 'title', 'title' => 'PLG_DPE_COMPLIANCEMANAGER_COURSENAME'),
			'clusterName' => array('table_column' => 'clusterName', 'title' => 'PLG_DPE_COMPLIANCEMANAGER_ORGANIZATION'),
			'userName' => array('table_column' => 'userName', 'title' => 'PLG_DPE_COMPLIANCEMANAGER__USERUSERNAME'),
			'email' => array('table_column' => 'email', 'title' => 'PLG_DPE_COMPLIANCEMANAGER_REPORT_EMAIL', 'emailColumn' => true),
			'read' => array('table_column' => 'read', 'title' => 'PLG_DPE_COMPLIANCEMANAGER_REPORT_READANDUNDERSTOOD'),
			'used' => array('table_column' => 'used', 'title' => 'PLG_DPE_COMPLIANCEMANAGER_REPORT_USEDINPRACICE'),
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return STRING Client
	 *
	 * @since   2.0
	 * 
	 */
	public function getPluginDetail()
	{
		$detail = array('client' => 'com_dpe', 'title' => Text::_('PLG_DPE_COMPLIANCEMANAGER_REPORT_TITLE'));

		return $detail;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItems()
	{	

		 $filters = (array) $this->getState('filters');
	     $session = Factory::getSession();

 		if($session->get('reportCluster') && ($session->get('prevOrgId') != $session->get('reportCluster')))
		{	
			$filters['cluster'] = ($session->get('reportCluster'));
			$this->setState('filters', $filters);
		}
		
		
		if(isset($filters['cluster']) && ($session->get('reportCluster') != $filters['cluster']))
		{
			$session->set('reportCluster', $filters['cluster']);
		}

		if ($filters['cluster'] != $session->get('reportCluster'))
		{
			$filters['cluster'] = ($session->get('reportCluster'));
			$this->setState('filters', $filters);
		}

		$filters = (array) $this->getState('filters');
		
		if (!isset($filters['lession_id']) && !isset($filters['cluster']))
		{ 
			$filters['lession_id'] = $prevLesson = $this->getLessonFilter()[0]->value;
			
			$this->setState('filters', $filters);
			$session->set('prevLesson', $prevLesson);
		}

		if (isset($filters['cluster_id']))
		{
			$user     	      = Factory::getUser();
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
			$fieldsValueTable = Table::getInstance('ClusterUsers', 'ClusterTable');
			$fieldsValueTable->load(array('user_id' => $user->id));

			$filters['cluster'] = $fieldsValueTable->cluster_id;
			$this->setState('filters', $filters);
		}	

		if (isset($filters['cluster']) && isset($filters['cluster_id']))
		{
			
			$filters['lession_id'] = $prevLesson = $this->getLessonFilter()[0]->value;
			$this->setState('filters', $filters);

			$session->set('prevLesson', $prevLesson);
		}

		// Add additional columns which are not part of the query
		$items 			= parent::getItems();
		$colToshow		= $this->getState('colToshow');
		$items 			= $this->sortCustomColumns($items);

		// add lesson url to used in addtodo functionality.
		if (isset($filters['lession_id']) && !empty($items))
		{	

			$courseUrl = Route::link("site", "index.php?option=com_tjlms&view=lesson&lesson_id=".$this->lessonId);	
			$uri = Uri::getInstance();
			$items[0]['url']   = $uri->getScheme().'://'.$uri->getHost().$courseUrl;
		}
		
		foreach ($items as $key => $item)
		{
			
			if ($item['used'] == 1)
			{
				$item['used'] =  Text::_('PLG_DPE_COMPLIANCEMANAGER_YES');
			}
			else
			{
				$item['used'] = Text::_('PLG_DPE_COMPLIANCEMANAGER_NO');
			}

			if ($item['read'] == 1)
			{
				$item['read'] = Text::_('PLG_DPE_COMPLIANCEMANAGER_YES');
			}
			else
			{
				$item['read'] = Text::_('PLG_DPE_COMPLIANCEMANAGER_NO');
			}
			
			$items[$key] = $item;
		}
	


		

		return $items;
	}

	/**
	 * Method to get a DataObjectbaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DataObjectbaseQuery  A DataObjectbaseQuery object to retrieve the data set.
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{

		$filters      = (array) $this->getState('filters');		
		$session      = Factory::getSession();
		$prevLessonId = $session->get('prevLesson');
		$prevOrgId    = $session->get('prevOrgId');

		if($filters['cluster'] || $session->get('reportCluster') || $prevOrgId)
		{
			$session->set('reportCluster', ($filters['cluster'])?$filters['cluster']:$prevOrgId);
		}


		if($session->get('reportCluster'))
		{
			$filters['cluster'] = $session->get('reportCluster');
		}

		if ((isset($filters['lession_id']) && $filters['lession_id'] != $prevLessonId) && $filters['cluster'] == $prevOrgId)
		{
			$session->set('prevLesson', $filters['lession_id']);
		}
		
		if (isset($filters['cluster']) && ($filters['cluster'] != $prevOrgId) && ($filters['lession_id'] == $prevLessonId) && (!$this->getState('list.start')))
		{   
			$session->clear('prevOrgId');
			$prevOrgId = $session->set('prevOrgId', $filters['cluster']);
			$filters['lession_id'] =  $this->getLessonFilter()[0]->value;
			$session->set('prevLesson', $filters['lession_id']);
			$this->setState('filters', $filters);
			
			if(!$filters['lession_id'])
			{
				$filters['lession_id'] = $prevLessonId;
				$this->setState('filters', $filters);
			}
		}

		if (isset($filters['cluster']) && ($filters['cluster'] != $prevOrgId) && ($filters['lession_id'] != $prevLessonId))
		{  
			$filters['lession_id'] = $prevLesson = $this->getLessonFilter()[0]->value;
			$prevOrgId = $session->set('prevOrgId', $filters['cluster']);
			$this->setState('filters', $filters);

		}  

		if (isset($filters['cluster']) && ($filters['cluster'] != $prevOrgId) && (!isset($filters['lession_id'])))
		{ 
			$filters['lession_id'] = $prevLesson = $this->getLessonFilter()[0]->value;
			$this->setState('filters', $filters);
		}  

		if (isset($filters['cluster']) && ($filters['cluster'] == $prevOrgId) && ($filters['lession_id'] != $prevLessonId) && isset($filters['lession_id']))
		{ 
			$session->clear('prevOrgId');
			$prevOrgId = $session->set('prevOrgId', $filters['cluster']);
			$session->set('prevLesson', $filters['lession_id']);
			$this->setState('filters', $filters);
		}

		// call when there is  filter data empty
		if ( empty($filters['lession_id']) && empty($filters['cluster']))
		{   
				$filters['lession_id'] = $this->getLessonFilter()[0]->value;
				
		}

		$colToshow = (array) $this->getState('colToshow');
		$db        = Factory::getDbo();
		$query     = $db->getQuery(true);
		

		$query->select('cluster.name as clusterName,user.name as userName,user.email as email,extendTodo.read,extendTodo.used, user.id as user_id, MAX(todo.start_date) AS start_date');
		$subQuery = $db->getQuery(true);
		$subQuery->select($db->quoteName('todo1.title'))
		    ->from($db->quoteName('z467w_jlike_todos') . " AS " . $db->quoteName('todo1'))
		    ->where($db->quoteName('todo1.id') . " = MIN(" . $db->quoteName('todo.id') . ")");
		$query->select("(" . $subQuery->__toString() . ") AS title");

		$query->from($db->qn( '#__jlike_todos', 'todo'));
		$query->leftJoin($db->quoteName('#__users', 'user') . ' ON (' . $db->quoteName('user.id') . ' = ' . $db->quoteName('todo.assigned_to') . ')');

		$query->leftJoin($db->quoteName('#__jlike_todos_extended', 'extendTodo') . ' ON (' . $db->quoteName('todo.id') . ' = ' . $db->quoteName('extendTodo.todo_id') . ')');
		$query->leftJoin($db->quoteName('#__tj_cluster_nodes', 'clusterNode') . ' ON (' . $db->quoteName('user.id') . ' = ' . $db->quoteName('clusterNode.user_id') . ')');

		$query->leftJoin($db->quoteName('#__tj_clusters', 'cluster') . ' ON (' . $db->quoteName('cluster.id') . ' = ' . $db->quoteName('clusterNode.cluster_id') . ')');
		$query->leftJoin($db->quoteName('#__tjlms_lesson_cluster_xref', 'clusterx') . ' ON (' . $db->quoteName('clusterx.cluster_id') . ' = ' . $db->quoteName('clusterNode.cluster_id') . ')');
		$query->leftJoin($db->quoteName('#__tjlms_lessons', 'lesson') . ' ON (' . $db->quoteName('lesson.id') . ' = ' . $db->quoteName('clusterx.lesson_id') . ')');

		if ($filters['lession_id'])
		{	
			$this->lessonId = $filters['lession_id'];
			Table::addIncludePath(JPATH_SITE . '/components/com_jlike/tables');
			$table = Table::getInstance('Content', 'JlikeTable');			
			$table->load(array("element_id" => $filters['lession_id'], "element" => 'com_tjlms.lesson' ));

			$contentId = $table->id;
			
			if (!$filters['cluster'] )
			{
				$filters['cluster'] = $prevOrg = $this->getClusterBylessonId($filters['lession_id']);

			    $session->set('prevOrgId', $prevOrg);
				$this->setState('filters', $filters);
			}

			if (is_numeric($contentId))
			{
				$query->where($db->qn('todo.content_id') . ' = ' . (int) $contentId);
			}
			else
			{
				return false;
			}
 		}
 		

		$clusterId = isset($filters['cluster']) ? $filters['cluster'] : '';
		
		// Filter for Organisation type	

		if ($clusterId)
		{	
			if (is_numeric($clusterId))
			{
				$query->where($db->qn('clusterNode.cluster_id') . ' = ' . (int) $clusterId);
				$query->where($db->qn('clusterx.cluster_id') . ' = ' . (int) $clusterId);

			}
		}
		else
		{
			return false;
		}

		$query->where($db->qn('user.block') . ' = 0');
		$query->where($db->qn('lesson.course_id') . ' = 0 ');

		$used = $filters['used'];

		if (is_numeric($used))
		{
			if ($used)
			{
				$query->where($db->qn('extendTodo.used') . ' = ' . (int) $used);
			}
			else
			{
				$query->where("(" . $db->qn('extendTodo.used') . ' = 0' . " OR extendTodo.todo_id IS NULL)");
			}
		}

		$read = $filters['read'];

		if (is_numeric($read))
		{
			if ($read)
			{
				$query->where($db->qn('extendTodo.read') . ' = ' . (int) $read);
			}
			else
			{
				$query->where("(" . $db->qn('extendTodo.read') . ' = 0' . " OR extendTodo.todo_id IS NULL)");
			}
		}

		 $limit      = $this->getState('list.limit');
		 $limitStart = $this->getState('list.start');
		
		if (!empty($limit))
		{
			 $query->setlimit($limit, $limitStart);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.id');
		$orderDirn = $this->state->get('list.direction', 'DESC');

		if ($orderCol == 'read')
		{
			$orderCol = 'extendTodo.read';
		}
		else if($orderCol == 'title')
		{
			$orderCol = 'todo.title';
		}
		else
		{
			$orderCol = $orderCol;
		}

		$query->group('todo.assigned_to,extendTodo.todo_id');
		$query->having("start_date > '0000-00-00 00:00:00'");
		
		if ($orderCol && $orderDirn)
		{
			$query->order($this->_db->escape('LOWER('.$orderCol .')'.  ' ' . $orderDirn));
			$query->order($this->_db->qn('todo.id') . 'ASC');
		}
		else
		{
			$query->order($this->_db->qn('todo.id') . 'ASC');
		}

		return $query;
	}

	/**
	 * Function to get the lesson filter
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getLessonFilter()
	{
		$user     	  = Factory::getUser();
		$filters      = (array) $this->getState('filters');

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
		$fieldsValueTable = Table::getInstance('ClusterUsers', 'ClusterTable');
		$fieldsValueTable->load(array('user_id' => $user->id));

	    JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
		$query    = $this->_db->getQuery(true);
		$db       = Factory::getDbo();
		$subQuery = $db->getQuery(true);
		$subQuery->select('lesson_id')->from($db->qn('#__tjlms_lesson_cluster_xref'))
		->where($db->qn('cluster_id') . '='. $fieldsValueTable->cluster_id);

		$query->select('DISTINCT(l.id) as id,l.title');
		$query->from($this->_db->qn('#__tjlms_lessons', 'l'));
		$query->InnerJoin($this->_db->quoteName('#__tjlms_lesson_cluster_xref', 'lc') . ' ON (' . $this->_db->quoteName('l.id') . ' = ' . $this->_db->quoteName('lc.lesson_id') . ')');
		$query->where($this->_db->qn('l.course_id') . ' = 0');
		$query->where($this->_db->qn('l.state') . ' = 1');
		$query->order($this->_db->qn('l.id') . 'DESC');
		
		if ($filters['cluster'])
		{
		  $query->where($this->_db->qn('lc.cluster_id') . ' = '.$filters['cluster']);	
		}
		else
		{    
			// Check user has permission for admin cluster or staff cluster only used when there is no filter for cluster id set .
		
			if (empty($filters['cluster']) && (!$user->authorise('core.manageall', 'com_cluster')))
			{ 
				$query->where($this->_db->qn('l.id') . ' IN ('.$subQuery .')');
			}
		}
		
		$this->_db->setQuery($query);

		$lessons = $this->_db->loadObjectList();
		
		if (!$lessons)
		{   
			if (empty($filters['cluster']))
			{
				$filters['cluster'] = $prevLesson = $fieldsValueTable->cluster_id;
				$this->setState('filters', $filters);	
			}
			
			return false;
		}
		$lessonFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_DOCUMENT'));

		if (!empty($lessons))
		{
			foreach ($lessons as $eachLessons)
			{
				$lessonFilter[] = HTMLHelper::_('select.option', $eachLessons->id, $eachLessons->title);
			}
		}
		
		Unset($lessonFilter[0]); 
		rsort($lessonFilter); 

		return $lessonFilter;
	}
	/**
	 * Create an array of filters
	 *
	 * @return    void
	 *
	 * @since    1.0
	 */
	public function displayFilters()
	{
		JLoader::import('components.com_tjlms.models.reports', JPATH_ADMINISTRATOR);
		$TjlmsModelReports 	   = new TjlmsModelReports;
		$lessonFilter 		   = $this->getLessonFilter();
		$read 				   = array(array('value' => "",'text' => Text::_('PLG_DPE_COMPLIANCEMANAGER_FILTER_SELECT_READ_UNDERSTOOD')), array('value' => 1,'text' => Text::_('PLG_DPE_COMPLIANCEMANAGER_YES')),array('value' => 0,'text' =>Text::_('PLG_DPE_COMPLIANCEMANAGER_NO')))	 ;

		$used 				   = array(array('value' => "",'text' => Text::_('PLG_DPE_COMPLIANCEMANAGER_FILTER_SELECT_USED_PRACTICE')), array('value' => 1,'text' => Text::_('PLG_DPE_COMPLIANCEMANAGER_YES')),array('value' => 0,'text'=> Text::_('PLG_DPE_COMPLIANCEMANAGER_NO')))	 ;

		$plgSystemTjlmsCluster = PluginHelper::getPlugin('system', 'dpe_tjlms_cluster');

			if (!empty($plgSystemTjlmsCluster))
			{
				FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
				$cluster      = FormHelper::loadFieldType('cluster', false);
				$clusterArray = $cluster->getOptionsExternally();
				unset($clusterArray[0]);
			}
			
		$dispFilters = array(
			array(
				'id' => array('search_type' => 'text', 'type' => 'equal', 'searchin' => 'a.id'),
				'name' => array(
					'search_type' => 'text', 'select_options' => $lessonFilter, 'type' => 'equal', 'searchin' => 'a.title'
					),
				),
				array(
				'lession_id' => array(
					'search_type' => 'select', 'select_options' => $lessonFilter, 'type' => 'equal', 'searchin' => 'c.id'
					),
				'cluster' => array(
								'search_type' => 'select', 'select_options' => $clusterArray, 'type' => 'equal', 'searchin' => 'tjc.client_id'
						),
				'read' => array(
								'search_type' => 'select', 'select_options' => $read, 'type' => 'equal', 'searchin' => 'tjc.client_id'
						),
				'used' => array(
								'search_type' => 'select', 'select_options' => $used, 'type' => 'equal', 'searchin' => 'tjc.client_id'
						),
				)
		);

		return $dispFilters;
	}
	
	/**
	 * Fetch cluster Id by lesson Id 
	 *
	 * @return    Array
	 *
	 * @since    1.0
	 */
	public function getClusterBylessonId($lessonId)
	{
		if (!$lessonId)
		{
			return;
		}

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_dpe/tables');
		$fieldsValueTable = Table::getInstance('TjlmsClusterXref', 'DpeTable');
		$fieldsValueTable->load(array('lesson_id' => (int) $lessonId));

		return $fieldsValueTable->cluster_id;
	}

	/**
	 * Method to get user details for todo
	 * This method must be included in every Report to use the Add todo functionality
	 *
	 * @param   Array  $data    filter data
	 *
	 * @return  Array  userdata
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getUserDeatilsforAdtodo($data)
	{	
		if(empty($data))
		{
			return false;
		}

		if ($data['filters']['allUser'] == 'add_all_users_with_filters')
		{	
			$limit = $this->getState('list.limit');
			$this->setState('list.limit','');
			$userData = $this->getItems();

			$this->setState('list.limit',$limit);
			return $userData ;
		}
	}

}
