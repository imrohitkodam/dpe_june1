<?php
/**
 * @package    DPE
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Data\DataObject;
use Joomla\CMS\Factory;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;

// If plg_system_sendemail enable then load following js
if (PluginHelper::isEnabled('system', 'sendemail'))
{	
	
	HTMLHelper::script('media/editors/tinymce/tinymce.min.js');
	HTMLHelper::script('plugins/system/sendemail/bulksendemail.js');
}

if (PluginHelper::isEnabled('system', 'dpeaddtodo'))
{	
	HTMLHelper::script('plugins/system/dpeaddtodo/addtodo.js');
}

JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);

/**
 * Attempt report plugin of TJReport
 *
 * @since  __DEPLOY_VERSION__
 */
class TjreportsModelDpeenrolmentreport extends TjreportsModelReports
{
	// To hide search icon/button from report filters
	public $showSearchResetButton = -1;
	

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     BaseDatabaseModel
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct($config = array())
	{
		Factory::getApplication()->input->set('report', 'dpeenrolmentreport');

		$this->columns = array(
			'school_name' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_DPE_ENROLMENT_SCHOOL'),
			'name' => array('table_column' => 'u.name', 'title' => 'PLG_TJREPORTS_DPE_ENROLMENT_NAME'),
			'email' => array('table_column' => 'u.email', 'title' => 'PLG_TJREPORTS_DPE_ENROLMENT_EMAIL', 'emailColumn' => true),
			'enrolled_on_time' => array('table_column' => '', 'title' => 'PLG_TJREPORTS_USER_ENROLLED_ON'),

			'timeend'          => array('table_column' => '', 'title' => 'PLG_TJREPORTS_COMPLETED_DATE'),
			'enrolstatus' => array('title' => 'PLG_TJREPORTS_DPE_ENROLMENT_STATUS', 'disable_sorting' => true),
			'usergroup' => array('title' => 'PLG_TJREPORTS_DPE_ENROLMENT_USER_ROLE', 'disable_sorting' => true)
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return array<string,mixed|string> Plugin Details
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getPluginDetail()
	{
		$detail = array('client' => 'com_sla', 'title' => Text::_('PLG_TJREPORTS_DPE_ENROLMENT_REPORT'));

		return $detail;
	}

	/**
	 * Create an array of filters
	 *
	 * @return    void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function displayFilters()
	{
		// $userFilter   = $this->getUserFilter();
		$courseFilter = $this->getCourseFilter();

		// To get Agency dropdown list
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields');
		$clusterList = FormHelper::loadFieldType('Cluster', false);
		$clusterOptions = $clusterList->getOptionsExternally();

		$groups  = HTMLHelper::_('user.groups', true);
		array_unshift($groups, HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_ENROLLED_USER_ACCESS')));

		$courseStatusFilter = array("" => Text::_('PLG_TJREPORTS_DPE_ENROLMENT_COURSE_STATUS'),"C" => "Completed", "I" => "Incomplete","Ex"=>"Expired");

		$courseEnrolFilter = array("E" => "Enrolled", "NE" => "Unenrolled");

		JLoader::register("School", JPATH_SITE . '/components/com_dpe/controllers/school.php');
		JLoader::load("School");
		$schoolController = new DpeControllerSchool;
		$tags = $schoolController->getTagsList();
		$user           = Factory::getUser();
		$isDpeAdmin = $user->authorise('core.manageall', 'com_cluster');

		 $params     			   = ComponentHelper::getParams('com_multiagency');
	   $orgAdminRoleId            = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
	   $orgAdminRoleId 		   = in_array($orgAdminRoleId, $user->groups);
		
		if($orgAdminRoleId && !$isDpeAdmin)
				{
					JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
				$dpeModel = DPE::model('school', array('ignore_request' => true));
				$tags = $dpeModel->getAgencyTags($orgAdminRoleId);
				}

		if ($isDpeAdmin || $orgAdminRoleId)
		{
			$tagFilter =  array(
					'search_type' => 'select', 'select_options' => $tags, 'type' => 'equal', 'multiple'=> 'multiple'
					);
		}
		if(($orgAdminRoleId || $isDpeAdmin ) && (count($clusterOptions)>1))
		{
		  $clusterOptions[0]->value = "All" ;
		}
		
		$dispFilters = array(
			array(
				/*
				'name' => array(
					'search_type' => 'select', 'select_options' => $userFilter, 'type' => 'equal', 'searchin' => 'u.id'
				),
				*/
			),
			array(
				'enrolled_on_time' => array(
					'search_type' => 'date.range',
					'searchin'    => 'enrolled_on_time',
					'enrolled_on_time_from' => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_FORM'),
					'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'enrolled_on_time_to'   => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_TO'),
					'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'))
				),
				'timeend' => array(
					'search_type'  => 'date.range',
					'searchin'     => 'timeend',
					'timeend_from' => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_FORM'),
					'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'timeend_to'   => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_TO'),
					'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'))
				)
			),
			array(
				'course_id' => array(
					'search_type' => 'select', 'select_options' => $courseFilter, 'type' => 'equal'
				),
				'cluster_id' => array(
					'search_type' => 'select', 'select_options' => $clusterOptions, 'type' => 'equal'
				),

				'cstatus' => array(
					'search_type' => 'select', 'select_options' => $courseStatusFilter
				),
				'tags' => $tagFilter,
				'enrolstatus' => array(
					'search_type' => 'select', 'select_options' => $courseEnrolFilter
				),
				
			)
		);

		return $dispFilters;
	}

	/**
	 * Function to get the course filter
	 *
	 * @return  array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getCourse()
	{
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/fields/');
		$Courses = FormHelper::loadFieldType('courses', false);
		$Courses->__set('order', 'c.id');
		$Courses->__set('direction', ' ASC');
		$courses = $Courses->getOptionsExternally();

		$helperPath = JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php';
		JLoader::register('TjlmsHelper', $helperPath);
		JLoader::load('TjlmsHelper');
		$i = 0;
		$coursIds = array();

		foreach ($courses as $course)
		{
			$coursIds[] = $course->value;
		}

		$dpeParams    = ComponentHelper::getParams('com_dpe');
		$defaultCourseId = $dpeParams->get('coursefilter', 0);

		foreach( $coursIds as $key  => $courseId)
		{
			if ($courseId == $defaultCourseId) {
		        $targetItem = $coursIds[$key]; // Store the target object
		        unset($coursIds[$key]); // Remove it from its original position
		        break;
    			}
		}

		// Reindex the array and insert the target item at index 1
		$coursIds = array_values($coursIds); // Reset numeric keys
		array_splice($coursIds, 0, 0, [$targetItem]); // Insert at index 1

		return $coursIds;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getItems()
	{	
		$filters = (array) $this->getState('filters');

		$session  = Factory::getSession();

		if (!empty($filters['cluster_id']) && $filters['cluster_id'] != 'All') {
	    	// Add session when filter has some value
			$session->set('reportCluster', $filters['cluster_id']);

		}elseif($filters['cluster_id'] == 'All')
		{	
			$session->clear('reportCluster');
			$filters['cluster_id'] = '';
			$this->setState('filters', $filters);
		} else {
			$filters['cluster_id'] = $session->get('reportCluster');
			$this->setState('filters', $filters);
		}

		$filters = (array) $this->getState('filters');

		// if add todo plugin enable set the filter to show the addtodo button 
		if (PluginHelper::isEnabled('system', 'dpeaddtodo'))
		{	
			
			$filters['addAllUserTodocheckBox'] =  1;
			$this->setState('filters', $filters);
		}

		$this->courseIds = $this->getCourse();	

		if (empty($filters['course_id']) && $filters['enrolstatus'] != "NE")
		{
			 $filters['course_id'] = $this->courseIds[0];
			$this->setState('filters', $filters);
		}

		// Add additional columns which are not part of the query
		$items = parent::getItems();

		JLoader::import('components.com_tjlms.helpers.tracking', JPATH_SITE);
		$trackingHelper = new ComtjlmstrackingHelper;

		$colToshow = $this->getState('colToshow');

		$params = DPE::config();

		foreach ($items as $key => &$item)
		{
			$course_id 		= $filters['course_id'];
			$assigned_user 	= $item['user_id'];		

			// Get the user object.
			if (in_array('usergroup', $colToshow) )
			{
				$userGroups = $this->getUsersGroups($item['user_id'], $item['schoolId']);



						if (in_array('Admin',$userGroups))
						{
							$item['usergroup']  = 'Admin';
						}
						elseif(in_array('Trustee',$userGroups))
						{
							$item['usergroup']  = 'Trustee';
						}
						elseif(in_array('Staff',$userGroups))
						{
							$item['usergroup']  = 'Staff';
						}
						else
						{
							$item['usergroup']  = 'Admin';
						}
	
				
			}

			$progress = $trackingHelper->getCourseTrackEntry($course_id, $assigned_user);

			if (in_array('cstatus', $colToshow))
			{
				$item['cstatus'] = ' - ';

				if (isset($progress['status']) || $filters['enrolstatus'] == "E")
				{
					if (($progress['status'] == 'C') && ($filters['cstatus'] != "Ex"))
					{
						$item['cstatus'] = "Completed";
					}
					else if(($progress['status'] == 'C') || ($filters['cstatus'] == "Ex"))
					{
						$item['cstatus'] = "Expired";
					}
					else
					{
						$item['cstatus'] = "Incomplete";
					}
				}
			}

			if (in_array('enrolstatus', $colToshow))
			{
				if ($filters['enrolstatus'] == "NE")
				{
					$item['enrolstatus'] = Text::_('PLG_TJREPORTS_DPE_ENROLMENT_STATUS_UNENROLLED');
				}
				else
				{
					$item['enrolstatus'] = Text::_('PLG_TJREPORTS_DPE_ENROLMENT_STATUS_ENROLLED');
				}
			}
		}



		$user          = Factory::getUser();
		$usersClusters = array();

		// If organisation don't have elearning access then show no access message

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			if (! empty($filters['cluster_id']))
			{
				if (!RBACL::check($user->id, 'com_cluster', 'core.viewShika', 'com_tjlms', $filters['cluster_id']))
				{
					$items['noaccessmessage'] = Text::_('PLG_TJREPORTS_DPE_NO_ELEARNING_ACCESS');
				}
			}
			elseif (empty($filters['cluster_id']))
			{
				// If cluster is not set then check cluster is having elearning tool access if not then show no access message
				$cluster = FormHelper::loadFieldType('cluster', false);
				$clusterList = $cluster->getOptionsExternally();

				if (!empty($clusterList))
				{
					foreach ($clusterList as $clusterList)
					{
						if (!empty($clusterList->value))
						{
							// Load data for org's who have elearing access
							if (RBACL::check($user->id, 'com_cluster', 'core.viewShika', 'com_tjlms', $clusterList->value))
							{
								$usersClusters[] = $clusterList->value;
							}
						}
					}

					$usersClusters = array_filter($usersClusters);

					if (empty($usersClusters))
					{
						$items['noaccessmessage'] = Text::_('PLG_TJREPORTS_DPE_NO_ELEARNING_ACCESS');
					}
				}
			}
		}

		// Added to get the url for Addtodo functionality
		if ($filters['course_id'])
		{	

			JLoader::register('TjlmscourseHelperRoute', JPATH_SITE. '/components/com_tjlms/helpers/route.php');

			 $courseUrl    = TjlmscourseHelperRoute::getCourseRoute($filters['course_id']);
			 $items[0]['url'] = $courseUrl;
		}
	return $items;
	}

	/**
	 * Method to get a DataObjectbaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DataObjectbaseQuery  A DataObjectbaseQuery object to retrieve the data set.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getListQuery()
	{
		$db      = $this->_db;
		$user    = Factory::getUser();
		$filters = (array) $this->getState('filters');
		$colToshow = $this->getState('colToshow');
		$query     = parent::getListQuery();

		// Reset the course filter by search course_id
		if (!isset($filters['enrolstatus']) && empty($filters['enrolstatus']))
		{
			$filters['enrolstatus'] = "E";
			$this->setState('filters', $filters);
		}

		if ($filters['enrolstatus'] == "NE")
		{
			$filters['cstatus']     = "";
			$this->setState('filters', $filters);
		}

		// Must have columns to get details of non linked data like completion
		$query->select(array('u.id as user_id', 'tjc.name as school_name', 'tjc.client_id as schoolId'));
		
		if ($filters['enrolstatus'] != "NE")
		{
			$query->select('eu.enrolled_on_time as enrolled_on_time');
			$query->select('cst.timeend as timeend');
		}
		
		$query->from('#__users AS u');

		if ($filters['enrolstatus'] == "E")
		{

			// Get enrolled status of selected course
			//$enrolledStatusJoin = $db->quoteName('eu.course_id') . ' = ' . (int) $filters['course_id'];
			
			$query->join('LEFT', $db->qn('#__tjlms_enrolled_users', 'eu') . ' ON (' .
				$db->qn('u.id') . ' = ' . $db->qn('eu.user_id')
				. ' AND ' . $db->qn('eu.course_id') . ' = ' . $filters['course_id'] . ' ) ');

			$query->join('INNER', $db->qn('#__tjlms_course_track', 'cst') . ' ON (' . $db->qn('cst.course_id') .
			' = ' . $db->qn('eu.course_id') . ' AND ' . $db->qn('cst.user_id') . ' = ' . $db->qn('eu.user_id')
			. ' AND ' . $db->qn('cst.course_id') . ' = ' . $filters['course_id'] . ' ) ');

			$query->join('LEFT', $db->qn('#__tjlms_courses', 'c') . ' ON (' . $db->qn('c.id') . ' = ' .
				$db->qn('eu.course_id') . 'AND' .$db->qn('eu.course_id') . ' = ' . $filters['course_id'] . ' )');

			if (isset($filters['cstatus']) && !empty($filters['cstatus']))
			{
				if ($filters['cstatus'] == "I")
				{
					$query->where("(cst.status= '' OR cst.status IS NULL OR cst.status= 'I')");
				}
				elseif ($filters['cstatus'] == "C")
				{
					$query->where("cst.status='C'");
				}
			}
		}
		elseif ($filters['enrolstatus'] == "NE")
		{
			$subQueryCourse = $db->getQuery(true);
			$subQueryCourse->select('eu.user_id');
			$subQueryCourse->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));
			$subQueryCourse->join('LEFT', $db->qn('#__tjlms_courses', 'c') . ' ON (' . $db->qn('c.id') . ' = ' . $db->qn('eu.course_id') . ')');
			$subQueryCourse->where($db->quoteName('c.id') . ' = ' . (int) $filters['course_id']);

			$query->where($db->qn('u.id') . ' NOT IN (' . $subQueryCourse . ')');
		}

		$query->join('INNER', $db->qn('#__tj_cluster_nodes', 'tjcn') . ' ON (' .
			$db->qn('u.id') . ' = ' . $db->qn('tjcn.user_id') . ')');
		$query->join('INNER', $db->qn('#__tj_clusters', 'tjc') . ' ON (' .
			$db->qn('tjcn.cluster_id') . ' = ' . $db->qn('tjc.id') . ')');

		$usersClusters = array();

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			$cluster = FormHelper::loadFieldType('cluster', false);
			$clusterList = $cluster->getOptionsExternally();

			if (!empty($clusterList))
			{
				foreach ($clusterList as $clusterList)
				{
					if (!empty($clusterList->value))
					{
						// Load data for org's who have elearing access
						if (RBACL::check($user->id, 'com_cluster', 'core.viewShika', 'com_tjlms', $clusterList->value))
						{
							$usersClusters[] = $clusterList->value;
						}
					}
				}
			}

			if ($filters['cluster_id'] && in_array($filters['cluster_id'], $usersClusters))
			{
				$query->where($db->qn('tjc.id') . ' = ' . (int) $filters['cluster_id']);
			}
			else
			{
				$query->where($db->qn('tjc.id') . " IN ('" . implode("','", $usersClusters) . "')");
			}
			
		}

		if ($filters['cluster_id'] && $user->authorise('core.manageall', 'com_cluster'))
		{
			$query->where($db->qn('tjc.id') . ' = ' . (int) $filters['cluster_id']);
		}

		// Tags Filter
		if (is_array($filters['tags']))
		{ 
			$tags = implode(',',$filters['tags']);
		}
		
		// checked for DPE admin
		if ($tags && $user->authorise('core.manageall', 'com_cluster'))
		{
			$query->JOIN('INNER', $this->_db->qn('#__contentitem_tag_map', 'tag_map') . ' ON (' . $this->_db->qn('tjc.client_id')
					. ' = ' . $this->_db->qn('tag_map.content_item_id') . ')');
		
			$query->where($this->_db->quoteName('tag_map.tag_id') . " IN ( " . $tags.')');
			$query->where($this->_db->quoteName('tag_map.type_alias') . " LIKE 'com_multiagency.multiagency'");
		}

		// show the users data those certificate is not expired if certificate is generated.

			if ($filters['enrolstatus'] == "E")
			{	

				$subQuerycertificate = $db->getQuery(true);
				$subQuerycertificate->select('cert.user_id');
				$subQuerycertificate->from($db->quoteName('#__tjlms_enrolled_users', 'eus'));
				$subQuerycertificate->join('LEFT', $db->qn('#__tjlms_courses', 'c') . ' ON (' . $db->qn('c.id') . ' = ' . $db->qn('eus.course_id') . ')');
				$subQuerycertificate->join('RIGHT', $db->qn('#__tj_certificate_issue', 'cert') . ' ON (' . $db->qn('cert.user_id') . ' = ' . $db->qn('eus.user_id') . ')');
			
				$subQuerycertificate->where($db->quoteName('cert.client_id') . ' = ' . (int) $filters['course_id']);
				$subQuerycertificate->where($db->quoteName('cst.status') . ' = "C"');

				
				if((!$filters['cstatus'] ) || ($filters['cstatus'] == 'C') || ($filters['cstatus'] == 'I'))
				{		
					$date = new Date('now');
						$subQueryExists = $db->getQuery(true);
						$subQueryExists->select('enu.user_id');
						$subQueryExists->from($db->quoteName('#__tjlms_enrolled_users', 'enu'));
						$subQueryExists->innerJoin($db->quoteName('#__tjlms_course_track', 'cst') . ' ON (' .
						    $db->quoteName('cst.course_id') . ' = ' . $db->quoteName('enu.course_id') .
						    ' AND ' . $db->quoteName('cst.user_id') . ' = ' . $db->quoteName('enu.user_id') .
						    ' AND ' . $db->quoteName('cst.course_id') . ' = '.(int) $filters['course_id'].')');
						    
						$subQueryExists->where($db->quoteName('u.id') . " NOT IN ( SELECT  cert.user_id  FROM #__tjlms_enrolled_users AS eus
						        RIGHT JOIN #__tj_certificate_issue AS cert ON (cert.user_id = eus.user_id)
						        WHERE cert.client_id =" . (int) $filters['course_id']."
						        AND cst.status = 'C'
						        AND cert.expired_on > '0000-00-00 00:00:00'
						        AND cert.expired_on < '".$date ."'
						         
						        AND u.id = eus.user_id
						    )");
						$subQueryExists->where("u.id = enu.user_id");

						$query->where($db->quoteName('u.id') . ' IN (' . $subQueryExists . ')');
						

				}
		
				

				if ($filters['cstatus'] == "Ex")
				{
					$subQuerycertificate->where($db->quoteName('cert.expired_on') . ' > "0000-00-00 00:00:00"');
					$subQuerycertificate->where($db->quoteName('cert.expired_on') . ' < "' . new Date('now').'"');
					
					$subQuerycertificate->where($db->quoteName('cst.status') . ' = "C"');
					$query->where($db->qn('u.id') . ' IN (' . $subQuerycertificate . ')');
				}
				
			}

		$query->where($db->qn('u.block') . ' = 0');
		$query->order($db->quoteName('u.id'));
		$query->group($db->quoteName('u.id'));
 		
 		 $limit      = $this->getState('list.limit');
		 $limitStart = $this->getState('list.start');
		
		if (!empty($limit))
		{
			 $query->setlimit($limit, $limitStart);
		}
		return $query;
	}

	/**
	 * Function to get the course filter
	 *
	 * @return  object
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getCourseFilter()
	{
		$app          = Factory::getApplication();

		$coursesFilter = array();
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjlms/models/fields/');
		$Courses = FormHelper::loadFieldType('courses', false);
		$courses = $Courses->getOptionsExternally();
		$user = Factory::getUser();

		
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
		$courseTable = Table::getInstance('course', 'TjlmsTable');
		
		// Check the user access level to view the course in list
		foreach( $courses as $key  => $course)
		{
			$courseTable->load(array('id' => $course->value));

   			$factory = $app->bootComponent('com_users')->getMVCFactory();
			/** @var \Joomla\Component\users\Administrator\Model\LevelModel $levelModel */
			$levelModel = $factory->createModel('Level', 'Administrator');
			$acessGroup = $levelModel->getItem($courseTable->access)->rules;


			if(!array_intersect($user->groups, $acessGroup))
			{
				unset($courses[$key]);
			}
		}

		// Currently only one course is present so we are hiding default option
		$coursesFilter[] = HTMLHelper::_('select.option', '', Text::_('PLG_TJREPORTS_DPE_ENROLMENT_COURSENAME'));

		if (!empty($courses))
		{
			foreach ($courses as $course)
			{
				$coursesFilter[] = HTMLHelper::_('select.option', $course->value, $course->text);
			}
		}

		return $coursesFilter;
	}

	/**
	 * Function to get the user filter
	 *
	 * @return  object
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getUserFilter()
	{
		$db = Factory::getDbo();
		$agencyId = array();
		$team = array();

		$user = Factory::getUser();

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);

			$agencyId = array();

			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

			if (!empty($clusters))
			{
				foreach ($clusters as $cluster)
				{
					if (RBACL::check($user->id, 'com_cluster', 'core.adduser', 'com_multiagency', $cluster->cluster_id))
					{
						$agencyId[] = $cluster->client_id;
					}
				}
			}
		}

		$query = $db->getQuery(true);
		$query->select('distinct(u.id), u.name');
		$query->from($db->qn('#__tjsu_users', 'su'));
		$query->join('INNER', $db->qn('#__users', 'u')
		. ' ON (' . $db->qn('u.id') . ' = ' . $db->qn('su.user_id') . ')');
		$query->where($db->qn('su.client') . " = 'com_multiagency'");
		$query->where($db->qn('u.block') . ' = 0');
		$query->order($db->escape('u.name' . ' ' . 'asc'));

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			$query->where($db->qn('client_id') . " IN ('" . implode("','", $agencyId) . "')");
		}

		$db->setQuery($query);
		$userInfo = $db->loadObjectList();

		$userFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJREPORTS_FILTER_SELECT_USER'));

		if (!empty($userInfo))
		{
			foreach ($userInfo as $userdata)
			{
				$userFilter[] = HTMLHelper::_('select.option', $userdata->id, $userdata->name);
			}
		}

		return $userFilter;
	}

	/**
	 * Method to get the total number of items for the data set.
	 *
	 * @return  integer  The total number of items available in the data set.
	 *
	 * @since   1.6
	 */
	public function getTotal()
	{
		// Get a storage key.
		$store = $this->getStoreId('getTotal');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		try
		{
			// Load the total and add the total to the internal cache.
			$query = $this->getListQuery();
			$query->clear('select')->clear('order')->clear('limit')->clear('offset')->select('COUNT(u.id)');

			$this->cache[$store] = (int) $this->_getListCount($query);
		}
		catch (\RuntimeException $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		return $this->cache[$store];
	}

	/**
	 * Method to get user groups
	 *
	 * @param   Int  $userId    User Id
	 * @param   Int  $schoolId  school Id
	 *
	 * @return  Array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getUsersGroups($userId, $schoolId)
	{
		if (!$userId)
		{
			return array();
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Get the titles for the user groups.
		$query = $db->getQuery(true)
		->select($db->quoteName('tjro.name'))
		->from($db->quoteName('#__tjsu_users', 'tjsu'))
		->join('INNER', $db->qn('#__tjsu_roles', 'tjro') . ' ON (' .
			$db->qn('tjro.id') . ' = ' . $db->qn('tjsu.role_id') . ')')
		->where($db->quoteName('tjsu.user_id') . ' = ' . (int) $userId)
		->where($db->quoteName('tjsu.client_id') . ' = ' . (int) $schoolId)
		->where($db->quoteName('tjsu.client') . " = 'com_multiagency'");
		$db->setQuery($query);

		// Set the titles for the user groups.
		return $db->loadColumn();
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

			$query = $this->getListQuery();
			$query->clear('select')->clear('order')->clear('limit')->clear('offset')->select('u.id as user_id');
			$this->_getList ($query, $limitstart=0,$limit=0 );

			$userData = parent::_getList($query, $limitstart, $limit);
			$this->setState('list.limit',$limit);
			
			return $userData ;
		}
	}
}
