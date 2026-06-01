<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport,StudentCourse
 *
 * @copyright   Copyright (C) 2009 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\Data\DataObject;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');
JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);

/**
 * Student course report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelDpeStudentcoursereport extends TjreportsModelReports
{
	protected $default_order       = 'id';

	protected $default_order_dir   = 'ASC';

	public $clusterOptions;

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
		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		// To get Agency dropdown list
		FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields');
		$clusterList          = FormHelper::loadFieldType('Cluster', false);
		$this->clusterOptions = $clusterList->getOptionsExternally();

		$this->columns = array(
			'title'            => array('table_column' => 'c.title', 'title' => 'COM_TJLMS_COURSE_NAME'),
			'name'             => array('table_column' => 'u.name', 'title' => 'COM_TJLMS_ENROLMENT_USER_NAME'),
			'email'            => array('table_column' => 'u.email', 'title' => 'COM_TJLMS_ENROLMENT_USER_EMAIL_ADDRESS'),
			'status'           => array('table_column' => '', 'title' => 'COM_TJLMS_ATTEMPTREPORT_STATUS'),
			'certificate_term' => array('table_column' => 'c.certificate_term', 'title' => 'COM_TJLMS_CERTIFICATE_TERM'),
			'enrolled_on_time' => array('table_column' => 'eu.enrolled_on_time', 'title' => 'COM_TJLMS_USER_ENROLLED_ON'),
			'timestart'        => array('table_column' => 'ct.timestart', 'title' => 'COM_TJLMS_USER_START_DATE'),
			'timeend'          => array('table_column' => 'ct.timeend', 'title' => 'COM_TJLMS_USER_COMPLETED_DATE'),
			'completion'       => array('title' => 'COM_TJLMS_COMPLETION', 'disable_sorting' => true),
			'totaltimespent'   => array('title' => 'COM_TJLMS_REPORT_TIMESPENT', 'disable_sorting' => true),
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return STRING Client
	 *
	 * @since   2.0
	 * */
	public function getPluginDetail()
	{
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_TJREPORTS_STUDENTCOURSEREPORT_TITLE'));

		return $detail;
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
		$TjlmsModelReports = new TjlmsModelReports;
		$catFilter         = $TjlmsModelReports->getCatFilter();
		$userFilter        = $TjlmsModelReports->getUserFilter();
		$courseFilter 	   = $TjlmsModelReports->getCourseFilter();
		$filters           = (array) $this->getState('filters');
		$user              = Factory::getUser();

		// To set the calendar field date format
		$filters['dateFormat'] = Text::_('PLG_TJREPORTS_DPE_DUE_DATE_FORMAT');
		$this->setState('filters', $filters);

		$statusArray   = array();
		$statusArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_STATUS'));
		$statusArray[] = HTMLHelper::_('select.option', 'I', Text::_('COM_TJLMS_LESSONSTATUS_INCOMPLETE'));
		$statusArray[] = HTMLHelper::_('select.option', 'C', Text::_('COM_TJLMS_FILTER_STATUS_COMPLETED'));

		$activeArray   = array();
		$activeArray[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_COURSES_TYPE_FILTER'));
		$activeArray[] = HTMLHelper::_('select.option', '0', Text::_('COM_TJLMS_YES'));
		$activeArray[] = HTMLHelper::_('select.option', '1', Text::_('COM_TJLMS_NO'));

		JLoader::register("School", JPATH_SITE . '/components/com_dpe/controllers/school.php');
		JLoader::load("School");
		$schoolController = new DpeControllerSchool;
		$tags = $schoolController->getTagsList();
       $params     			   = ComponentHelper::getParams('com_multiagency');
	   $orgAdminRoleId            = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
	   $orgAdminRoleId 		   = in_array($orgAdminRoleId, $user->groups);

	  $isDpeAdmin = $user->authorise('core.manageall', 'com_cluster');

				if($orgAdminRoleId && !$isDpeAdmin)
				{
					JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
				$dpeModel = DPE::model('school', array('ignore_request' => true));
				$tags = $dpeModel->getAgencyTags($orgAdminRoleId);
				}
		$user          = Factory::getUser();

		// To add all value if the user has admin dpe admin acess and admin acess with multiple organisation.
		if(($orgAdminRoleId || $isDpeAdmin || $user->authorise('core.manageall', 'com_cluster')) && (count($this->clusterOptions)>1))
		{
		  $this->clusterOptions[0]->value = "All" ;
		}
		
		if ($isDpeAdmin || $orgAdminRoleId)
		{
			$tagFilter =  array(
					'search_type' => 'select', 'select_options' => $tags, 'type' => 'equal', 'multiple'=> 'multiple'
					);
		}

		$dispFilters = array(
			array(
				'title' => array(
					'search_type' => 'select', 'select_options' => $courseFilter, 'type' => 'equal', 'searchin' => 'c.id'
				),
				'name' => array(
					'search_type' => 'text', 'searchin' => 'u.name'
				),
				'status' => array(
					'search_type' => 'select', 'select_options' => $statusArray
				),
				'enrolled_on_time' => array(
					'search_type' => 'date.range',
					'searchin'    => 'enrolled_on_time',
					'enrolled_on_time_from' => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_FORM'),
					'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'enrolled_on_time_to'   => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_TO'),
					'onChange' => 'tjrContentUI.report.attachCalSubmit(this);'))
				),
				'timestart' => array(
					'search_type'    => 'date.range',
					'searchin'       => 'timestart',
					'timestart_from' => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_FORM'),
					'onChange' => 'tjrContentUI.report.attachCalSubmit(this);')),
					'timestart_to'   => array('attrib' => array('placeholder' => Text::_('PLG_TJREPORTS_DPE_DUE_DATE_PLACEHOLDER_TO'),
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
					'tags' => $tagFilter,
					'cluster_id' => array(
							'search_type' => 'select', 'select_options' => $this->clusterOptions, 'type' => 'equal', 'searchin' => 'tjc.id'
					),
					
			)
		);

		// Joomla fields integration
		// Call parent function to set filters for custom fields
		if (method_exists(get_parent_class($this), 'setCustomFieldsDisplayFilters'))
		{
			parent::setCustomFieldsDisplayFilters($dispFilters);
		}

		return $dispFilters;
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
		$db        = $this->_db;
		$query     = parent::getListQuery();
		$colToshow = $this->getState('colToshow');
		$filters   = $this->getState('filters');
		$user      = Factory::getUser();
		$userId    = $user->id;

		$query->select(array('c.id as course_id', 'eu.user_id as user_id'));
		$query->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));

		$query->join('INNER', $db->quoteName('#__tjlms_courses', 'c') .
		' ON (' . $db->quoteName('c.id') . ' = ' . $db->quoteName('eu.course_id') . ')');
		$query->where('eu.state=1');
		$query->where('c.state=1');

		// DPE: load enable users only
		$query->join('INNER', $db->quoteName('#__users', 'u') . 'ON (' . $db->quoteName('u.id') . ' = ' . $db->quoteName('eu.user_id') . ')');
		$query->where('u.block = 0');

		if (in_array('cat_title', $colToshow))
		{
			$query->join('LEFT', '#__categories AS cat ON c.catid = cat.id');
		}

		if (in_array('status', $colToshow))
		{
			$query->select(
							'IF(ct.status="c","' . Text::_('COM_TJLMS_FILTER_STATUS_COMPLETED')
							. '","' . Text::_('COM_TJLMS_LESSONSTATUS_INCOMPLETE') . '") AS status'
						);
		}

		if (array_intersect(array('status', 'timestart', 'timeend'), $colToshow))
		{
			$query->join('LEFT', $db->quoteName('#__tjlms_course_track', 'ct') . ' ON (' . $db->quoteName('ct.course_id') . ' = ' . $db->quoteName('c.id') . ' AND ' . $db->quoteName('ct.user_id') . ' = ' . $db->quoteName('eu.user_id') . ')');
		}

		// DPE: Join cluster table to filter the result as per organisation filter seleted
		$query->join('INNER', $db->qn('#__tj_cluster_nodes', 'tjcn') . ' ON (' .
			$db->qn('eu.user_id') . ' = ' . $db->qn('tjcn.user_id') . ')');
		$query->join('INNER', $db->qn('#__tj_clusters', 'tjc') . ' ON (' .
			$db->qn('tjcn.cluster_id') . ' = ' . $db->qn('tjc.id') . ')');

		$isDpeAdmin = $user->authorise('core.manageall', 'com_cluster');

		// DPE: If user is not dpe admin then load users from own schools only
		if (!$isDpeAdmin)
		{
			$usersClusters = array();

			foreach ($this->clusterOptions as $cluster)
			{
				if (!empty($cluster->value))
				{
					if (RBACL::check($user->id, 'com_cluster', 'core.viewShika', 'com_tjlms', $cluster->value))
					{
						$usersClusters[] = $cluster->value;
					}
				}
			}

			$query->where($db->qn('tjc.id') . " IN ('" . implode("','", $usersClusters) . "')");
		}

		$reportId = $this->getDefaultReport($this->name);
		$viewAll  = $this->checkpermissions($reportId);

		if ($viewAll == null || $viewAll === false)
		{
			$query->where('eu.user_id=0');
		}

		if (isset($filters['status']) && !empty($filters['status']))
		{
			if ($filters['status'] == "C")
			{
				$query->where('ct.status="C"');
			}
			else
			{
				$query->where('ct.status!="C"');
			}
		}

		// Tags Filter
		$tags = empty($filters['tags']) ? '':implode(',',(array)$filters['tags']);
		
		if ($tags && $user->authorise('core.manageall', 'com_cluster'))
		{
			$query->JOIN('INNER', $this->_db->qn('#__contentitem_tag_map', 'tag_map') . ' ON (' . $this->_db->qn('tjc.client_id')
					. ' = ' . $this->_db->qn('tag_map.content_item_id') . ')');
		
			$query->where($this->_db->quoteName('tag_map.tag_id') . " IN ( " . $tags.')');
			$query->where($this->_db->quoteName('tag_map.type_alias') . " LIKE 'com_multiagency.multiagency'");
		}
		
		return $query;
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
		// Add additional columns which are not part of the query
		
		// To get and set the session filter value of organisation
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

		$items = parent::getItems();

		jimport('techjoomla.common');
		JLoader::import('components.com_tjlms.helpers.tracking', JPATH_SITE);
		$tjCommon 		= new TechjoomlaCommon;
		$trackingHelper = new ComtjlmstrackingHelper;

		$db        = $this->_db;
		$colToshow = $this->getState('colToshow');

		$lmsparams      = ComponentHelper::getParams('com_tjlms');
		$dateFormatShow = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		foreach ($items as $ind => &$item)
		{
			$course_id = $item['course_id'];
			$user_id   = $item['user_id'];

			if (empty($item['lastVisitDate']) || $item['lastVisitDate'] == '0000-00-00 00:00:00')
			{
				$item['lastVisitDate'] = ' - ';
			}
			else
			{
				$item['lastVisitDate'] = $tjCommon->getDateInLocal($item['lastVisitDate'], 0, $dateFormatShow);
			}

			if (in_array('enrolled_on_time', $colToshow))
			{
				$item['enrolled_on_time'] = $tjCommon->getDateInLocal($item['enrolled_on_time'], 0, $dateFormatShow);
			}

			if (in_array('timestart', $colToshow))
			{
				if ($item['timestart'] == '0000-00-00 00:00:00')
				{
					$item['timestart'] = '-';
				}
				else
				{
					$item['timestart'] = $tjCommon->getDateInLocal($item['timestart'], 0, $dateFormatShow);
				}
			}

			if (in_array('timeend', $colToshow))
			{
				if ($item['timeend'] == '0000-00-00 00:00:00')
				{
					$item['timeend'] = '-';
				}
				else
				{
					$item['timeend'] = $tjCommon->getDateInLocal($item['timeend'], 0, $dateFormatShow);
				}
			}

			if (in_array('certificate_term', $colToshow))
			{
				$cer_term = Text::_("COM_TJREPORTS_FORM_OPT_COURSE_CERTIFICATE_TERM_NOCERTI");

				if ($item['certificate_term'] == "1")
				{
					$cer_term = Text::_("COM_TJREPORTS_FORM_OPT_COURSE_CERTIFICATE_TERM_COMPALL");
				}
				elseif ($item['certificate_term'] == "2")
				{
					$cer_term = Text::_("COM_TJREPORTS_FORM_OPT_COURSE_CERTIFICATE_TERM_PASSALL");
				}

				$item['certificate_term'] = $cer_term;
			}

			if (in_array('completion', $colToshow))
			{
				$progress = $trackingHelper->getCourseTrackEntry($course_id, $user_id);
				$item['completion'] = floor($progress['completionPercent']);
			}

			if (in_array('totaltimespent', $colToshow))
			{
				// Get total time spent
				$query = $db->getQuery(true);
				$query->select('SEC_TO_TIME(SUM(TIME_TO_SEC(time_spent))) as totalTimeSpent');
				$query->from($db->quoteName('#__tjlms_lesson_track', 'lt'));
				$query->join('INNER', $db->quoteName('#__tjlms_lessons', 'l') . 'ON (' . $db->quoteName('lt.lesson_id') . ' = ' . $db->quoteName('l.id') . ')');
				$query->where($db->quoteName('lt.user_id') . " = " . $db->quote($user_id));
				$query->where($db->quoteName('l.course_id') . " = " . $db->quote($course_id));
				$db->setQuery($query);
				$totaltimespent = $db->loadResult();

				$item['totaltimespent'] = '-';

				if (!empty($totaltimespent) && $totaltimespent != '00:00:00')
				{
					$item['totaltimespent'] = $totaltimespent;
				}
			}
		}

		$filters = (array) $this->getState('filters');

		// If organisation don't have elearning access then show no access message

		$user          = Factory::getUser();
		$usersClusters = array();

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

		$items = $this->sortCustomColumns($items);

		return $items;
	}

	/**
	 * Create an array of fields in the form of Google data studio requires
	 * Array(
	 *   array(
	 *		'name' => internal name of the field
	 * 		'label' => Name to be displayed on the report
	 *      'dataType' => 'NUMBER' OR 'STRING' OR 'BOOLEAN'
	 * 		'semantics' => array('conceptType' => 'DIMENSION' OR 'METRIC')
	 * 	  ),
	 * )
	 *
	 * More information about fields https://developers.google.com/datastudio/connector/reference#data_types
	 *
	 * @return  ARRAY
	 *
	 * @since   1.3.31
	 */
	public function getGDSFields()
	{
		return array(
			array('name' => 'course_id', 'label' => Text::_('COM_TJLMS_COURSE_ID'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'title', 'label' => Text::_('COM_TJLMS_COURSE_NAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'cat_title', 'label' => Text::_('COM_TJLMS_COURSE_CAT'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'user_id', 'label' => Text::_('COM_TJLMS_ENROLMENT_USERID'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'name', 'label' => Text::_('COM_TJLMS_ENROLMENT_USER_NAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'username', 'label' => Text::_('COM_TJLMS_REPORT_USERUSERNAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'email', 'label' => Text::_('COM_TJLMS_REPORT_USEREMAIL'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'usergroup', 'label' => Text::_('COM_TJLMS_REPORT_USERGROUP'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'timestart', 'label' => Text::_('COM_TJLMS_USER_START_DATE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'timeend', 'label' => Text::_('COM_TJLMS_COURSE_COMPLETED_DATE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'status', 'label' => Text::_('COM_TJLMS_ATTEMPTREPORT_STATUS'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'certificate_term', 'label' => Text::_('COM_TJLMS_CERTIFICATE_TERM'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'enrolled_on_time', 'label' => Text::_('COM_TJLMS_USER_ENROLLED_ON'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'completion', 'label' => Text::_('COM_TJLMS_COMPLETION'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'METRIC')),
			array('name' => 'totaltimespent', 'label' => Text::_('COM_TJLMS_REPORT_TIMESPENT'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'lastVisitDate', 'label' => Text::_('COM_TJLMS_USER_LAST_LOGIN_DATE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'acl_title', 'label' => Text::_('COM_TJLMS_ACCESS_LEVEL'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
		);
	}
}
