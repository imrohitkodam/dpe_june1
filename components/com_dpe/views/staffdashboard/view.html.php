<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\View\HtmlView;

JLoader::import('components.com_tjcertificate.includes.tjcertificate', JPATH_ADMINISTRATOR);
JLoader::import('components.com_tjlms.includes.tjlms', JPATH_ADMINISTRATOR);
JLoader::import('components.com_tjlms.models.courses', JPATH_SITE);
JLoader::import('components.com_multiagency.includes.multiagency', JPATH_SITE);
JLoader::import('components.com_tjucm.includes.tjucm', JPATH_SITE);
JLoader::import('components.com_tjlms.helpers.courses', JPATH_SITE);
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);

/**
 * View to edit
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeViewStaffDashboard extends HtmlView
{
	protected $state;

	protected $item;

	protected $params;

	protected $clusterId;

	protected $user;

	protected $assignedDocuments;

	protected $myCertificates;

	protected $enrolledCourses;

	protected $completedDocuments = 0;

	protected $latestCourses;

	protected $allocatedTools;

	protected $assignedRecordsSar;

	protected $assignedRecordsFoi;

	protected $assignedRecordsBreach;

	protected $ticketData;

	protected $complianceData;

	protected $breachLogData;

	protected $sarLogData;

	protected $foiLogData;

	protected $checklist;

	protected $phishing;

	protected $onGoingCourses = 0;

	protected $todos;

	protected $totalTodos;

	protected $completedTodos;

	protected $userClusters;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$user           = Factory::getUser();
		$params         = ComponentHelper::getParams('com_dpe');
		$assignDocLimit = $params->get('showAssignDocLimit');
		$menuParams     = new Registry;

		$coursesModel = TjLms::model('courses', array('ignore_request' => true));
		$coursesModel->setState('params', $menuParams);
		$coursesModel->setState('list.limit', $params->get('showLatestCourseLimit'));
		$coursesModel->courses_to_show  = 'notEnrolled';
		$this->latestCourses            = $coursesModel->getItems();

		$coursesModel2 = TjLms::model('courses', array('ignore_request' => true));
		$coursesModel2->setState('params', $menuParams);
		$coursesModel2->courses_to_show  = 'completed';
		$this->completeCourses           = $coursesModel2->getItems();

		$coursesModel3 = TjLms::model('courses', array('ignore_request' => true));
		$coursesModel3->setState('params', $menuParams);
		$coursesModel3->setState('list.limit', $params->get('showEnrolledLimit'));
		$coursesModel3->courses_to_show  = 'enrolled';
		$this->enrolledCourses           = $coursesModel3->getItems();

		$enrollCountCourseModel = TjLms::model('courses', array('ignore_request' => true));
		$enrollCountCourseModel->setState('params', $menuParams);
		$enrollCountCourseModel->courses_to_show  = 'enrolled';
		$enrolledCourses = $enrollCountCourseModel->getItems();

		$TjlmsCoursesHelper = new TjlmsCoursesHelper;
		$onGoingCourses     = array();

		foreach ($enrolledCourses as $enrolledCourse)
		{
			$courseProgress = $TjlmsCoursesHelper->getCourseProgress($enrolledCourse->id, $enrolledCourse->userEnrollment->user_id);

			if ($courseProgress['status'] == "I" || $courseProgress['status'] == "")
			{
				$onGoingCourses[] = $enrolledCourse;
			}
		}

		$this->onGoingCourses = count($onGoingCourses);

		$myassignmentsModel = DPE::model('myassignments', array('ignore_request' => true));
		$this->assignedDocuments = $myassignmentsModel->getItems();


		// Calculate completed document count
		if (! empty($this->assignedDocuments))
		{
			foreach ($this->assignedDocuments as $assignedDocument)
			{
				$intractions = new Registry($assignedDocument->params);

				if ($intractions['read_interaction'] && $intractions['practice_interaction'])
				{
					if ($assignedDocument->read && $assignedDocument->used)
					{
						$this->completedDocuments++;
					}
				}
				elseif ($intractions['read_interaction'] && !$intractions['practice_interaction'])
				{
					if ($assignedDocument->read)
					{
						$this->completedDocuments++;
					}
				}
			}
		}

		$certificateModel = TJCERT::model('Certificates', array('ignore_request' => true));

		// Get published certificates
		$certificateModel->setState('filter.state', 1);
		$certificateModel->setState('filter.user_id', Factory::getUser()->id);
		$certificateModel->setState('list.limit', $params->get('showCertificateLimit'));
		$this->myCertificates = $certificateModel->getItems();

		$foiTypeTable = Tjucm::table('type');
		$foiTypeTable->load(array('unique_identifier' => 'com_tjucm.FOIlog'));

		$foilogItemsModel = Tjucm::model('Items', array('ignore_request' => true));
		$foilogItemsModel->setState("ucmType.id", $foiTypeTable->id);
		$foilogItemsModel->setState("ucm.client", 'com_tjucm.FOIlog');
		$foilogItemsModel->setState("list.direction", 'DESC');
		$foilogItemsModel->setState("list.ordering", 'a.id');
		$foilogItemsModel->setState("assigned", true);
		$foilogItemsModel->setState("list.limit", $params->get('showFoiAssignedLimit'));
		$this->assignedRecordsFoi = $foilogItemsModel->getItems();

		$breachTypeTable = Tjucm::table('type');
		$breachTypeTable->load(array('unique_identifier' => 'com_tjucm.breachlog'));

		$breachlogItemsModel = Tjucm::model('Items', array('ignore_request' => true));
		$breachlogItemsModel->setState("ucmType.id", $breachTypeTable->id);
		$breachlogItemsModel->setState("ucm.client", 'com_tjucm.breachlog');
		$breachlogItemsModel->setState("list.direction", 'DESC');
		$breachlogItemsModel->setState("list.ordering", 'a.id');
		$breachlogItemsModel->setState("assigned", true);
		$breachlogItemsModel->setState("list.limit", $params->get('showBreachAssignedLimit'));
		$this->assignedRecordsBreach = $breachlogItemsModel->getItems();

		$sarTypeTable = Tjucm::table('type');
		$sarTypeTable->load(array('unique_identifier' => 'com_tjucm.sarlog'));

		$sarlogItemsModel = Tjucm::model('Items', array('ignore_request' => true));
		$sarlogItemsModel->setState("ucmType.id", $sarTypeTable->id);
		$sarlogItemsModel->setState("ucm.client", 'com_tjucm.sarlog');
		$sarlogItemsModel->setState("list.direction", 'DESC');
		$sarlogItemsModel->setState("list.ordering", 'a.id');
		$sarlogItemsModel->setState("assigned", true);
		$sarlogItemsModel->setState("list.limit", $params->get('showsSarLogAssignedLimit'));
		$this->assignedRecordsSar = $sarlogItemsModel->getItems();

		$this->ticketData     = $this->get('TicketFormattedData');
		$this->complianceData = $this->get('ComplianceFormattedData');
		$this->breachLogData  = $this->get('BreachLogFormattedData');
		$this->sarLogData     = $this->get('SarLogFormattedData');
		$this->foiLogData     = $this->get('FoiLogFormattedData');
		$this->ropData        = $this->get('RopFormattedData');
		$this->checklist      = $this->get('ChecklistFormattedData');
		$this->phishing       = $this->get('PhishingFormattedData');
		$this->redaction      = $this->get('RedactionFormattedData');

		$todoModel = Jlike::model('Recommendations', array('ignore_request' => true));
		$todoModel->setState("list.direction", 'DESC');
		$todoModel->setState("list.ordering", 'a.id');
		$todoModel->setState('assigned_to', $user->id);
		$todoModel->setState('filter.type', "assign");
		$todoModel->setState('filter.status', "I");
		$todoModel->setState('list.ordering', "a.due_date");
		$todoModel->setState('list.direction', "DESC");
		$todoModel->setState('view', "dashboard");
		$this->todos = $todoModel->getItems();

		$allTodoModel = Jlike::model('Recommendations', array('ignore_request' => true));
		$allTodoModel->setState('assigned_to', $user->id);
		$allTodoModel->setState('filter.type', "assign");
		$allTodoModel->setState('view', "dashboard");
		$this->totalTodos = count($allTodoModel->getItems());

		$completeTodoModel = Jlike::model('Recommendations', array('ignore_request' => true));
		$completeTodoModel->setState('assigned_to', $user->id);
		$completeTodoModel->setState('type', "assign");
		$completeTodoModel->setState('filter.status', "C");
		$completeTodoModel->setState('view', "dashboard");
		$this->completedTodos = count($completeTodoModel->getItems());

		$clusterUserModel   = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$this->userClusters = $clusterUserModel->getUsersClusters($user->id);

		parent::display($tpl);
	}
}
