<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_tjucmdpe
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Tjucm is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

jimport('techjoomla.tjnotifications.tjnotifications');

JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);

/**
 * Methods supporting a list of Tjlms action.
 *
 * @since  1.0.0
 */
class PlgSystemTjucmdpe extends CMSPlugin
{
	/**
	 * Function is triggered when agency is create
	 *
	 * @param   INT    $validData         Agency Id
	 * @param   ARRAY  $extra_jform_data  Field data
	 * @param   INT    $isNew             item id
	 *
	 * @return  null
	 *
	 * @since  1.0.0
	 */
	public function onUcmItemAfterSave($validData, $extra_jform_data, $isNew)
	{
		$input = Factory::getApplication()->input;
		$jform = $input->post->get('jform', array(), 'array');
		$client = $validData['client'];
		$user = Factory::getUser();

		// Used to check form submitted or not
		$config = Factory::getConfig();
		$mailonline = $config->get('mailonline');

		if ($mailonline == 0) {
			return true;
		}

		$params = ComponentHelper::getParams('com_dpe');
		$allDpeSelectedAdmins = $params->get("dpolistfornotification"); // DPE Admin Dpe hack
		// $allDpeSelectedAdminsNotification = $params->get("dpolistfornotification"); // DPE Admin Notification Dpe hack

		// $allDpeSelectedAdmins = array_unique(array_merge((array)$allDpeSelectedAdmins, $allDpeSelectedAdminsNotification));
		// DPE Admin Notification Dpe hack end

		$dpeModel = DPE::model('dashboard');
		$isChecklist = $dpeModel->isChecklist($validData['id']);

		if (count((array)$isChecklist) > 0) {


			// Get checklist name
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/models');
			$tjUcmModelType = BaseDatabaseModel::getInstance('Type', 'TjucmModel');
			$typeId = $tjUcmModelType->getTypeId($validData['client']);
			$typeData = $tjUcmModelType->getItem($typeId);

			// Get school name

			$clustertable = ClusterFactory::table("clusters");
			$clustertable->load(array('id' => $validData['cluster_id']));

			if (!$clustertable->client_id) {
				return false;
			}

			// Get the table of licence xref
			$licenceTable = Multiagency::table("licence");
			$licenceTable->load(array('multiagency_id' => $clustertable->client_id, 'state' => 1));


			if (!$licenceTable->id) {
				return false;
			}

			$params = ComponentHelper::getParams('com_multiagency');
			// $manager = $params->get('manager_role_id', '0', 'INT');

			/*
			 $db = Factory::getDbo();
			 $query = $db->getQuery(true);
			 $query->select("distinct(u.id)");
			 $query->from($db->qn("#__tjsu_users", "su"));
			 $query->join('left', '#__users AS u ON u.id = su.user_id');
			 $query->where($db->qn('su.client_id') . " in (" . $db->q($extra_jform_data['checklist_school']) . ")");
			 $query->where($db->qn('su.client') . '= "com_multiagency"');
			 $query->where($db->qn('su.role_id') . " in (" . $manager . ")");
			 $query->where($db->qn('u.id') . " not in (" . Factory::getUser()->get('id') . ")");
			 $db->setQuery($query);
			 $managerIds = $db->loadColumn();
			 */

			// Get DPE Admin list and comment creator and update code
			// Get Cluster Config

			$slaClusterXrefs = SlaFactory::table("slaclusterxrefs");
			$slaClusterXrefs->load(array('cluster_id' => $validData['cluster_id'], 'license_id' => $licenceTable->id));


			if ($slaClusterXrefs->notify_dpe_admin) {
				// Send to DPE Admin check if need to send all or selected users
				// All should use DPE Admin user group
				$selectedDpeAdminUsers = json_decode($slaClusterXrefs->dpeadmins);
				$dpeAdminUsers = array();

				if (in_array('all', (array)$selectedDpeAdminUsers)) {
					BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
					$usersModel = BaseDatabaseModel::getInstance('users', 'DpeModel', array("ignore_request" => true));
					$dpeAdminUsers = $usersModel->getDpeAdmins();
				}
				else {
					$dpeAdminUsers = $selectedDpeAdminUsers;
				}
			}

			// Write code Add DPE Lead consultant config
			$dpeLeadConsultant = array($slaClusterXrefs->lead_consultant_id);

			// Dpe admin users
			foreach ($dpeAdminUsers as $dpeAdminUser) {
				$dpeAdminUserIds[] = $dpeAdminUser;
			}

			$managerIds = array_unique(array_merge((array)$dpeAdminUserIds, $dpeLeadConsultant));

			if (($arraykey = array_search($user->id, $managerIds)) !== false) {
				unset($managerIds[$arraykey]);
			}

			$managerIds = array_values($managerIds);

			$options = new Registry;
			$options->set('subject.title', $typeData->title);


			// This is checklist send mail to logged in user i.e. Manager
			// Todo: Need to revamp code for checklist emails
			if (!$isNew) {
				$replacements = new stdclass;

				// Update mail to creater user
				$key = 'checklistUpdateToUser';

				$recipients = array(
					// Add specific to, cc (optional), bcc (optional)
					'email' => array(
						'to' => array(Factory::getUser()->email)
					)
				);

				$replacements->checklist = new stdclass;
				$replacements->checklist->username = Factory::getUser()->name;
				$replacements->checklist->type = $typeData->title;
				$replacements->checklist->schoolname = $clustertable->name;
				$replacements->checklist->checklistUrl = Uri::root() . substr(
					Route::_('index.php?option=com_dpe&view=dashboard'), strlen(Uri::base(true)) + 1
				);

				$res = ($recipients['email']['to'][0]) ?Tjnotifications::send("com_tjucm", $key, $recipients, $replacements, $options) : '';

				// Mail to remaining manager
				$key = 'checklistUpdateToManager';

				if (count($managerIds) > 0) {
					foreach ($managerIds as $mid) {
						if (Factory::getUser($mid)->block == 1) {
							continue;
						}

						$replacements = new stdclass;
						$recipients = array(
							// Add specific to, cc (optional), bcc (optional)
							'email' => array(
								'to' => array(Factory::getUser($mid)->email)
							)
						);
						$replacements->checklist = new stdclass;
						$replacements->checklist->username = Factory::getUser($mid)->name;
						$replacements->checklist->type = $typeData->title;
						$replacements->checklist->createdUser = Factory::getUser()->name;
						$replacements->checklist->schoolname = $clustertable->name;
						$replacements->checklist->checklistUrl = Uri::root() . substr(
							Route::_('index.php?option=com_dpe&view=dashboard'), strlen(Uri::base(true)) + 1
						);

						$res = ($recipients['email']['to'][0]) ?Tjnotifications::send("com_tjucm", $key, $recipients, $replacements, $options) : '';
					}
				}
			}
			else {
				// New create mail to creater
				$replacements = new stdclass;
				$key = 'checklistStartToUser';

				$recipients = array(
					// Add specific to, cc (optional), bcc (optional)
					'email' => array(
						'to' => array(Factory::getUser()->email)
					)
				);
				$replacements->checklist = new stdclass;
				$replacements->checklist->username = Factory::getUser()->name;
				$replacements->checklist->type = $typeData->title;
				$replacements->checklist->schoolname = $clustertable->name;
				$replacements->checklist->checklistUrl = Uri::root() . substr(
					Route::_('index.php?option=com_dpe&view=dashboard'), strlen(Uri::base(true)) + 1
				);

				$res = ($recipients['email']['to'][0]) ?Tjnotifications::send("com_tjucm", $key, $recipients, $replacements, $options) : '';

				// Mail to remaining manager
				$key = 'checklistStartToManager';

				if (count($managerIds) > 0) {
					foreach ($managerIds as $mid) {
						$replacements = new stdclass;

						if (Factory::getUser($mid)->block == 1) {
							continue;
						}

						$recipients = array(
							// Add specific to, cc (optional), bcc (optional)
							'email' => array(
								'to' => array(Factory::getUser($mid)->email)
							)
						);

						$replacements->checklist->username = Factory::getUser($mid)->name;
						$replacements->checklist->type = $typeData->title;
						$replacements->checklist->createdUser = Factory::getUser()->name;
						$replacements->checklist->schoolname = $clustertable->name;
						$replacements->checklist->checklistUrl = Uri::root() . substr(
							Route::_('index.php?option=com_dpe&view=dashboard'), strlen(Uri::base(true)) + 1
						);

						$res = ($recipients['email']['to'][0]) ?Tjnotifications::send("com_tjucm", $key, $recipients, $replacements, $options) : '';
					}
				}
			}
		}
		else {
			if ($isNew) {

				if ($client == 'com_tjucm.dpialite') {
					$key = 'notifyNewdpialite';

					BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
					$usersModel = BaseDatabaseModel::getInstance('users', 'DpeModel', array("ignore_request" => true));
					$dpeAdminUsersDpia = $usersModel->getDpeAdmins();
					$status = $extra_jform_data['com_tjucm_dpialite_Status'];

					foreach ($dpeAdminUsersDpia as $dpeAdminUsersDpi) {
						$this->onSendTjucmEmails($validData, $status, $key, $dpeAdminUsersDpi);
					}

				}
			}
			$clusterUserModel = ClusterFactory::model("ClusterUser");
			$clusters = $clusterUserModel->getUsersClusters();

			if (count($clusters) > 0) {
				foreach ($clusters as $cluster) {
					$clusterIds[] = $cluster->cluster_id;
				}
			}

			// DPE - To check field values
			if (!empty($extra_jform_data['com_tjucm_breachlog_clusterclusterid'])) {
				if (!in_array($extra_jform_data['com_tjucm_breachlog_clusterclusterid'], $clusterIds)) {
					return false;
				}
			}
			elseif (!empty($extra_jform_data['com_tjucm_sarlog_clusterclusterid'])) {
				if (!in_array($extra_jform_data['com_tjucm_sarlog_clusterclusterid'], $clusterIds)) {
					return false;
				}
			}
			elseif (!empty($extra_jform_data['com_tjucm_FOIlog_clusterclusterid'])) {
				if (!in_array($extra_jform_data['com_tjucm_FOIlog_clusterclusterid'], $clusterIds)) {
					return false;
				}
			}
			else {
				return false;
			}

			// Get Cluster data
			$clustertable = ClusterFactory::table('Clusters');
			$clustertable->load(array('id' => $validData['cluster_id']));

			// Get roles
			$subusersModelRole = RBACL::model('role');
			$roleIds = $subusersModelRole->getAuthorizeRoles(array('core.adduser'), 'com_multiagency');
			$dpeAdminRoleIds = $subusersModelRole->getAuthorizeRoles(array('core.create'), 'com_multiagency');

			// Get users by roles
			$subusersModelUsers = RBACL::model('users', array('ignore_request' => true));
			$subusersModelUsers->setState('filter.client_id', $clustertable->client_id);
			$subusersModelUsers->setState('filter.role_id', $roleIds);
			$subusersModelUsers->setState('filter.client', 'com_multiagency');
			$subusersModelUsers->setState('group_by', 'user_id');
			$subusersModelUsers->setState('filter.state', 0);
			$clusterUsers = $subusersModelUsers->getItems();

			if (!$clustertable->client_id) {
				return false;
			}

			// Get the table of licence xref
			$licenceTable = Multiagency::table("licence");
			$licenceTable->load(array('multiagency_id' => $clustertable->client_id, 'state' => 1));

			if (!$licenceTable->id) {
				return false;
			}

			// Get Cluster Config
			$slaClusterXrefs = SlaFactory::table("slaclusterxrefs");
			$slaClusterXrefs->load(array('cluster_id' => $validData['cluster_id'], 'license_id' => $licenceTable->id));

			if ($slaClusterXrefs->notify_dpe_admin) {
				// Send to DPE Admin check if need to send all or selected users
				// All should use DPE Admin user group
				$selectedDpeAdminUsers = json_decode($slaClusterXrefs->dpeadmins);
				$dpeAdminUsers = array();

				if (in_array('all', (array)$selectedDpeAdminUsers)) {
					BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
					$usersModel = BaseDatabaseModel::getInstance('users', 'DpeModel', array("ignore_request" => true));
					$dpeAdminUsers = $usersModel->getDpeAdmins();
				}
				else {
					$dpeAdminUsers = $selectedDpeAdminUsers;
				}
			}

			// Write code Add DPE Lead consultant config
			$dpeLeadConsultant = array($slaClusterXrefs->lead_consultant_id);
			$clusterUserIds = array();

			// School manager/Admin users
			foreach ($clusterUsers as $clusterUser) {
				$clusterUserIds[] = $clusterUser->user_id;
			}

			// Dpe admin users
			foreach ($dpeAdminUsers as $dpeAdminUser) {
				$dpeAdminUserIds[] = $dpeAdminUser;
			}

			if (!empty($clusterUserIds) && !empty($dpeAdminUserIds)) {
				$clusterUserIds = array_unique(array_merge($clusterUserIds, $dpeAdminUserIds));
			}

			if (!empty($clusterUserIds) && !empty($dpeLeadConsultant)) {
				$clusterUserIds = array_unique(array_merge($clusterUserIds, $dpeLeadConsultant));
			}

			if (($arraykey = array_search($user->id, $clusterUserIds)) !== false) {
				unset($clusterUserIds[$arraykey]);
			}

			if (!empty($allDpeSelectedAdmins)) {
				$clusterUserIds = array_unique(array_merge($clusterUserIds, $allDpeSelectedAdmins));
			}

			$clusterUserIds = array_values($clusterUserIds);
			// On new
			if ($isNew) {
				$assignee = array();

				if ($client == 'com_tjucm.sarlog') {
					$key = 'notifyNewSarLog';
					$status = $extra_jform_data['com_tjucm_sarlog_requeststatus'];
					$assignee = $extra_jform_data['com_tjucm_sarlog_assignee'];
				}
				elseif ($client == 'com_tjucm.breachlog') {
					$key = 'notifyNewBreachLog';
					$status = $extra_jform_data['com_tjucm_breachlog_breachstatus'];
					$assignee = $extra_jform_data['com_tjucm_breachlog_assignee'];
				}
				elseif ($client == 'com_tjucm.FOIlog') {
					$key = 'notifyNewFoiLog';
					$status = $extra_jform_data['com_tjucm_FOIlog_requeststatus'];
					$assignee = $extra_jform_data['com_tjucm_FOIlog_assignee'];
				}

				// Send emails to assignee
				if ($assignee && !$validData['draft']) {
					$assigneeUsers = array();

					// Get user assignee user list
					foreach ($assignee as $singleUser) {
						$assigneeUsers[] = Factory::getUser($singleUser)->get('name');
					}

					foreach ($assignee as $assigneeUser) {
						$validData['assigneeUsers'] = implode(',', $assigneeUsers);

						if (!empty($validData['assigneeUsers'])) {
							$this->onSendTjucmEmails($validData, $status, 'ucmRecordAssigneeEmail', $assigneeUser);
						}
					}
				}

				// $this->onSendTjucmEmails($validData, $status, $key, $validData['created_by']);

				// Send notification to school admin/manager

				if ($client == 'com_tjucm.sarlog') {
					$key = 'notifyNewSarToOther';
				}
				elseif ($client == 'com_tjucm.breachlog') {
					$key = 'notifyNewBreachToOther';
				}
				elseif ($client == 'com_tjucm.FOIlog') {
					$key = 'notifyNewFoiToOther';
				}
			}
			else {
				$assignee = array();
				$toolName = '';

				// On update
				if ($client == 'com_tjucm.sarlog') {
					$key = 'notifyUpdateSarLog';
					$status = $extra_jform_data['com_tjucm_sarlog_requeststatus'];
					$assignee = $extra_jform_data['com_tjucm_sarlog_assignee'];
					$toolName = Text::_('COM_DPE_SAR_LOG_TOOL_TITLE');
				}
				elseif ($client == 'com_tjucm.breachlog') {
					$key = 'notifyUpdateBreachLog';
					$status = $extra_jform_data['com_tjucm_breachlog_breachstatus'];
					$assignee = $extra_jform_data['com_tjucm_breachlog_assignee'];
					$toolName = Text::_('COM_DPE_BREACH_LOG_TOOL_TITLE');
				}
				elseif ($client == 'com_tjucm.FOIlog') {
					$key = 'notifyUpdateFoiLog';
					$status = $extra_jform_data['com_tjucm_FOIlog_requeststatus'];
					$assignee = $extra_jform_data['com_tjucm_FOIlog_assignee'];
					$toolName = Text::_('COM_DPE_FOI_LOG_TOOL_TITLE');
				}

				$assignee = array_filter((array)$assignee);

				if (!empty($assignee) && !$validData['draft']) {
					$validData['toolName'] = $toolName;
					$removedAssignee = array_diff((array)$extra_jform_data['oldAssignee'], $assignee);
					$newAssignee = array_diff($assignee, (array)$extra_jform_data['oldAssignee']);

					// Send notification if assignee removed
					if (!empty($removedAssignee)) {
						foreach ($removedAssignee as $removedUser) {
							$this->onSendTjucmEmails($validData, $status, 'ucmRecordDeassignEmail', $removedUser);
						}
					}

					// Send notification if new assignee added
					if (!empty($newAssignee)) {
						foreach ($newAssignee as $newUser) {
							$this->onSendTjucmEmails($validData, $status, 'ucmRecordAssigneeEmail', $newUser);
						}
					}
				}

			// $this->onSendTjucmEmails($validData, $status, $key, $validData['created_by']);
			}


			if (count($clusterUserIds) > 0) {
				foreach ($clusterUserIds as $clusterUserId) {
					$emailUserObject = Factory::getUser($clusterUserId);

					if (($clusterUserId > 0) && (Factory::getUser()->get('email') != $emailUserObject->get('email')) && ($emailUserObject->block != 1)) {
						$this->onSendTjucmEmails($validData, $status, $key, $clusterUserId);
					}
				}
			}
		}
	}


	/**
	 * Function is triggered when UCM Form is submitted.
	 *
	 * @param   INT    $data   Form Data
	 * @param   INT    $isNew  item id
	 *
	 * @return  null
	 *
	 * @since  1.0.0
	 */
	public function tjUcmOnafterSaveItem($data, $isNew)
	{
		// Update Parent Field on Add new record in subform.
		if ($data['client'] == 'com_tjucm.ropdataflow' && $isNew && $data['id'] && $data['PreElementValue']) {
			JLoader::import('components.com_tjfields.tables.field', JPATH_ADMINISTRATOR);
			$fieldTable = Table::getInstance('field', 'TjfieldsTable');
			$fieldTable->load(array('name' => 'com_tjucm_ropdataflow_parentstepindataflow'));

			if (!property_exists($fieldTable, 'id') || !$fieldTable->id) {
				return;
			}

			JLoader::import('components.com_tjfields.tables.fieldsvalue', JPATH_ADMINISTRATOR);
			$fieldsValueTable = Table::getInstance('fieldsvalue', 'TjfieldsTable');
			$fieldsValueTable->load(array('field_id' => $fieldTable->id, 'content_id' => $data['id']));

			$fieldsValue = array();
			$fieldsValue['id'] = null;

			if (property_exists($fieldsValueTable, 'id') && $fieldsValueTable->id) {
				$fieldsValue['id'] = $fieldsValueTable->id;
			}

			$fieldsValue['field_id'] = $fieldTable->id;
			$fieldsValue['content_id'] = $data['id'];
			$fieldsValue['value'] = $data['PreElementValue'];
			$fieldsValue['user_id'] = Factory::getUser()->id;
			$fieldsValue['client'] = $data['client'];

			$fieldsValueTable->save($fieldsValue);
		}
	}

	/**
	 * Function onAfterRender user to ad tjlms sidebar styling to category view
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterRoute()
	{
		$app = Factory::getApplication();
		$user = Factory::getUser();

		if ('com_jlike' == $app->getInput()->getCMD('option') && !$app->isClient('administrator')) {
			$input = Factory::getApplication()->input;
			$post = $input->post;
			$extraParams = $post->get('extraParams', array(), 'Array');

			if ($extraParams['element'] == 'com_tjucm.itemform') {
				$formId = $extraParams['cont_id'];

				/*
				 / Get log created by user
				 $db = Factory::getDbo();
				 $query = $db->getQuery(true);
				 $query->select('distinct(created_by)');
				 $query->from($db->quoteName('#__tj_ucm_data'));
				 $query->where($db->quoteName('id') . " =  " . $db->quote($formId));
				 $db->setQuery($query);
				 $created_by = $db->loadResult();
				 Get all allocated agency list
				 BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
				 $MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));
				 $params = ComponentHelper::getParams('com_multiagency');
				 $memberRole = $params->get('member_role_id', '0', 'INT');
				 $agencies = $MultiagencyModel->getAllocatedAgencies(Factory::getUser()->id, array($memberRole));
				 $agencyId = array();
				 $team = array();
				 foreach ($agencies as $agency)
				 {
				 $agencyId[] = $agency->id;
				 }
				 */

				FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
				$cluster = FormHelper::loadFieldType('cluster', false);
				$clusterList = $cluster->getOptionsExternally();

				$usersClusters = array();

				if (!empty($clusterList)) {
					foreach ($clusterList as $clusterList) {
						if (!empty($clusterList->value)) {
							$usersClusters[] = $clusterList->value;
						}
					}
				}

				/*
				 if (count($agencyId) > 0)
				 {
				 Get all users under me
				 $query = $db->getQuery(true);
				 $query->select('distinct(user_id)');
				 $query->from($db->quoteName('#__tjsu_users'));
				 $query->where($db->quoteName('client') . '= "com_multiagency"');
				 $query->where($db->quoteName('client_id') . " in ( " . implode(',', $agencyId) . ")");
				 $db->setQuery($query);
				 $team = $db->loadColumn();
				 }
				 if (count($team) > 0 && !Factory::getUser()->authorise('core.admin'))
				 {
				 if (!in_array($created_by, $team))
				 {
				 jexit();
				 }
				 }
				 */

				// Get Cluster ID of UCM record
				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
				$UCMDataTable = Table::getInstance('data', 'TjucmTable');
				$UCMDataTable->load(array('id' => $formId));

				$cluster_id = 0;

				if (property_exists($UCMDataTable, 'cluster_id')) {
					$cluster_id = $UCMDataTable->cluster_id;
				}

				if (!$user->authorise('core.manageall', 'com_cluster')) {
					if ((!in_array($cluster_id, $usersClusters)) || (!RBACL::check($user->id, 'com_cluster', 'core.adduser', 'com_multiagency', $cluster_id))) {
						jexit(Text::_('JERROR_ALERTNOAUTHOR'));

						return false;
					}
				}
			}
		}
	}

	/**
	 * Method to send emails.
	 *
	 * @param   array   $data    The ids actions.
	 * @param   string  $status  status of log.
	 * @param   string  $key     tj-notification key.
	 * @param   string  $userId  user id to send email.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function onSendTjucmEmails($data, $status, $key, $userId)
	{

		if (!empty($data) && $key) {
			// Get Item Id
			JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
			$tjUcmFrontendHelper = new TjucmHelpersTjucm;
			$itemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=items&client=' . $data['client']);

			// Get cluster name
			$clustertable = ClusterFactory::table('Clusters');
			$clustertable->load(array('id' => $data['cluster_id']));
			$userdata = Factory::getUser($userId);



			$options = new Registry;
			$options->set('log.id', $data['id']);

			$recipients = array(
				// Add specific to, cc (optional), bcc (optional)
				'email' => array(
					'to' => array($userdata->email)
				)
			);

			$replacements = new stdClass;
			$replacements->user = new stdClass;
			$replacements->user->name = $userdata->get('name');
			$replacements->user->school = $clustertable->name;
			$replacements->log = new stdClass;
			$replacements->log->status = $status;
			$replacements->log->id = $data['id'];
			$replacements->log->client = str_replace('com_tjucm.', '', $data['client']);

			if ($data['ucmnotification']) {
				$userdata->email = ($userdata->email) ? $userdata->email : $userId;
				$replacements->user->name = ($userdata->get('name')) ? $userdata->get('name') : $data['userIdToEmail'];

				$recipients = array(
					// Add specific to, cc (optional), bcc (optional)
					'email' => array(
						'to' => array(($userdata->email) ? $userdata->email : $data['userIdToEmail'])
					)
				);
			}

			if ($data['toolName']) {
				$replacements->log->name = $data['toolName'];
			}

			if ($data['assigneeUsers']) {
				$replacements->assignee = new stdClass; // DPE hack php test 8	
				$replacements->assignee->users = $data['assigneeUsers'];
			}

			$replacements->log->url = ($data['ucmnotification']) ? $data['url'] : Uri::root() . substr(
				Route::_(
				'index.php?option=com_tjucm&view=itemform&client=' . $data['client'] . '&id=' . $data['id'] . '&Itemid=' . $itemId
			), strlen(Uri::base(true)) + 1);


			return Tjnotifications::send("com_tjucm", $key, $recipients, $replacements, $options);
		}
	}

	/**
	 * Function is triggered when tjucm items render
	 *
	 * @param   String  $query    Query String
	 *
	 * @param   INT     $client   Ucm client
	 *
	 * @param   String  $context  Context
	 *
	 * @return  null
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onTjucmModelItemsGetListQuery($query, $client, $context)
	{
		$user = Factory::getUser();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
		$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
		$tjFieldFieldTable->load(array('client' => $client, 'type' => 'assignee', 'state' => 1));

		$db = Factory::getDbo();
		$subQuery = $db->getQuery(true);
		$subQuery->select(1);
		$subQuery->from($db->qn('#__tjfields_fields_value', 'fva'));
		$subQuery->join('LEFT', $db->qn('#__tjfields_fields_value', 'fva1') .
			' ON (' . $db->qn('fva.content_id') . ' = ' . $db->qn('fva1.content_id') . ')');
		$subQuery->where($db->qn('fva1.field_id') . ' = ' . (int)$tjFieldFieldTable->id);
		$subQuery->where('fva1.value' . " = " . (int)$user->id);
		$subQuery->where($db->qn('fva.content_id') . '=' . $db->qn('a.id'));
		$query->where("EXISTS (" . $subQuery . ")");
	}

	/**
	 * Function is triggered before item form display
	 *
	 * @param   array  $item       Ucm data
	 *
	 * @param   array  $formExtra  extra form data
	 *
	 * @return  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onBeforeTjucmItemFormDisplay($item, $formExtra)
	{
		$user = Factory::getUser();
		$app = Factory::getApplication();
		$mainHelper = JPATH_SITE . '/components/com_dpe/helpers/main.php';
		JLoader::register('DpeMainHelper', $mainHelper);

		$dpeMainHelper = new DpeMainHelper;
		$assignedUser = $dpeMainHelper->getFieldValues($user->id, $item->id, $item->client);

		return $assignedUser;
	}


	/**
	 * Returns fields that have changed between old and new values.
	 *
	 * Compares two associative arrays and returns only the keys with different values,
	 * excluding those where both old and new are empty or null.
	 *
	 * @param  array  $oldValues  Original field values.
	 * @param  array  $newValues  Updated field values.
	 *
	 * @return array  Changed fields with 'old' and 'new' values.
	 */
	public function onBeforeTjucmItemCopyCompareFields($oldValues, $newValues)
	{
		$differences = [];

		foreach ($newValues as $key => $newValue) {
			$oldValue = isset($oldValues[$key]) ? $oldValues[$key] : null;

			// Skip if both old and new values are empty or null
			if (($oldValue === "" || $oldValue === null) && ($newValue === "" || $newValue === null)) {
				continue;
			}

			// Compare and store differences
			if ($oldValue != $newValue) {
				$differences[$key] = [
					'old' => $oldValue,
					'new' => $newValue
				];
			}
		}

		return $differences;
	}

	/**
	 * Compare field value and store change in session if different.
	 *
	 * @param   int     $recordId   The item ID.
	 * @param   string  $newKey     The field name (e.g., 'title', 'status').
	 * @param   mixed   $newValue   New value (string or array).
	 * @param   string  $client     UCM client (e.g., 'com_tjucm.content').
	 * @param   array   $changeData Existing changeData array from session.
	 * @param   array   $newData    Existing newData array from session.
	 *
	 * @return  void
	 */
	public function onPrepareTjucmChangeSessionData($recordId, $newKey, $newValue, $client, &$changeData, &$newData)
	{
		$session = Factory::getSession();
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($db->quoteName(['fv.value', 'f.label']))
			->from($db->quoteName('#__tjfields_fields_value', 'fv'))
			->join('INNER', $db->quoteName('#__tjfields_fields', 'f') . ' ON ' . $db->quoteName('f.id') . ' = ' . $db->quoteName('fv.field_id'))
			->where($db->quoteName('f.name') . ' = ' . $db->quote($newKey))
			->where($db->quoteName('fv.content_id') . ' = ' . (int)$recordId)
			->where($db->quoteName('fv.client') . ' = ' . $db->quote($client));

		$db->setQuery($query);
		$results = $db->loadObjectList();

		if (!empty($results)) {
			$fieldLabel = $results[0]->label;

			$oldValues = array_map(function ($item) {
				return $item->value;
			}, $results);

			$oldValueStr = implode(',', $oldValues);
			$newValueStr = is_array($newValue) ? implode(',', $newValue) : $newValue;

			if ($newValueStr != $oldValueStr) {
				$formattedChange = "[$fieldLabel] : " . $oldValueStr . " → " . $newValueStr;
				$found = false;

				foreach ($changeData as $index => $entry) {
					if (strpos($entry, "[$fieldLabel]") === 0) {
						$changeData[$index] = $formattedChange;
						$found = true;
						break;
					}
				}

				if (!$found) {
					$changeData[] = $formattedChange;
				}

				$session->set('changeData', $changeData);
			}
			else {
				// Remove from changeData if same
				foreach ($changeData as $index => $entry) {
					if (strpos($entry, "[$fieldLabel]") === 0) {
						unset($changeData[$index]);
						$changeData = array_values($changeData);
						break;
					}
				}
			}
		}
		else {
			// No old value (new subform data)
			$query1 = $db->getQuery(true);

			$query1->select('label')
				->from($db->quoteName('#__tjfields_fields'))
				->where($db->quoteName('name') . ' = ' . $db->quote($newKey));

			$db->setQuery($query1);
			$result = $db->loadObject();

			$fieldLabel = $result ? $result->label : $newKey;

			if (is_array($newValue)) {
				$newValue = $newValue[0] ?? '';
			}

			$formattedNew = "[$fieldLabel] : " . $newValue;
			$found = false;

			foreach ($changeData as $index => $entry) {
				if (strpos($entry, "[$fieldLabel]") === 0) {
					$changeData[$index] = $formattedNew;
					$found = true;
					break;
				}
			}

			if (!$found) {
				$newData[] = $formattedNew;
			}

			$session->set('newData', $newData);
		}
	}
	/**
	 * Inserts a record into the copy tracking table.
	 *
	 * This method logs a file copy action, mapping the source file ID,
	 * the target organization (cluster), and the new file ID.
	 *
	 * @param   int  $clusterId  ID of the organization the file is copied to.
	 * @param   int  $cid        ID of the original file (parent).
	 * @param   int  $recordId   ID of the newly created (copied) file (child).
	 *
	 * @return  int|false  Inserted row ID on success, false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onInsertCopyTrackingRecord($clusterId, $cid, $recordId)
	{
		$db = Factory::getDbo();

		// Define the columns and corresponding values
		$columns = ['parent_ucm_id', 'copied_organization_id', 'child_ucm_id'];
		$values = [
			$db->quote($cid),
			$db->quote($clusterId),
			$db->quote($recordId),
		];

		// Build and set the insert query
		$query = $db->getQuery(true)
			->insert($db->quoteName('#__Ucm_genric_record_hierarchy_xref'))
			->columns(array_map([$db, 'quoteName'], $columns))
			->values(implode(',', $values));


		$db->setQuery($query);

		try {
			$db->execute();
			return $db->insertid(); // Return the last inserted ID
		}
		catch (Exception $e) {
			print_r($e->getMessage());
			die;
			Factory::getApplication()->enqueueMessage('Copy tracking insert failed: ' . $e->getMessage(), 'error');
			return false;
		}
	}
	/**
	 * Sends email to organization admins when an item's data changes after copy.
	 *
	 * @param   int     $recordId   The ID of the copied record.
	 * @param   string  $client     UCM client identifier.
	 *
	 * @return  bool  True on successful email dispatch, false otherwise.
	 */
	public function onSendEmailsChangeItemData($recordId, $client)
	{
		$db = Factory::getDbo();

		// Get organization IDs for the copied record
		$query = $db->getQuery(true)
			->select('copied_organization_id')
			->from('#__Ucm_genric_record_hierarchy_xref')
			->where('parent_ucm_id = ' . $db->quote($recordId));
		$db->setQuery($query);
		$organizationIds = array_map('intval', $db->loadColumn());

		if (empty($organizationIds)) {
			return false;
		}

		// Get ID of the "Admin" role
		$query = $db->getQuery(true)
			->select('id')
			->from('#__tjsu_roles')
			->where('name = ' . $db->quote('Admin'));
		$db->setQuery($query);
		$adminRoleId = (int)$db->loadResult();

		if (!$adminRoleId) {
			return false;
		}

		// Get email addresses of active admins in those organizations
		$query = $db->getQuery(true)
			->select('a.email')
			->from('#__users AS a')
			->join('INNER', '#__tjsu_users AS b ON a.id = b.user_id')
			->join('INNER', '#__tjmultiagency_multiagency AS c ON b.client_id = c.id')
			->join('INNER', '#__tj_clusters AS cluster ON c.id = cluster.client_id')
			->where('cluster.id IN (' . implode(',', $organizationIds) . ')')
			->where('b.role_id = ' . $adminRoleId)
			->where('a.block = 0');
		$db->setQuery($query);
		$adminEmails = $db->loadColumn();

		if (empty($adminEmails)) {
			return false;
		}

		// Load UCM data values
		JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_SITE);
		$TjfieldsHelper = new TjfieldsHelper;
		$ucmData = $TjfieldsHelper->FetchDatavalue([
			'content_id' => $recordId,
			'client' => $client,
		]);

		$options = new Registry;

		// Clean up client - remove 'com_tjucm.' prefix if it exists
		$prefixclient = str_replace('com_tjucm.', '', $client);

		foreach ($adminEmails as $adminEmail) {
			$replacements = new stdClass;
			$replacements->form = new stdClass;
			$replacements->form->formtype = $prefixclient;
			$replacements->form->formid = $recordId;
			$replacements->form->link = Uri::root() . 'index.php?option=com_tjucm&view=item&id=' . $recordId . '&client=' . $client;
			$recipients = array(
				'email' => array(
					'to' => array($adminEmail)
				)
			);

			$key = "UcmFormDataUpdated";

			$result = Tjnotifications::send("com_tjucm", $key, $recipients, $replacements, $options);
		}
	}



	/**
	 * Triggered before saving a UCM item. Fetches and formats existing data.
	 *
	 * - Loads UCM data based on `recordId` and `client`.
	 * - Filters out empty or placeholder values (e.g., "tjlist:-").
	 *
	 * @param   int      $recordId  The UCM item ID.
	 * @param   string   $client    The UCM client identifier.
	 *
	 * @return  array  An associative array of existing field values before save.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onTjUcmBeforeSaveItemOldData($recordId, $client)
	{
		// Load helper to fetch existing UCM data
		JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_SITE);
		$TjfieldsHelper = new TjfieldsHelper;

		$ucmData = $TjfieldsHelper->FetchDatavalue([
			'content_id' => $recordId,
			'client' => $client,
		]);

		$formattedData = [];

		if (!empty($ucmData) && is_array($ucmData)) {
			foreach ($ucmData as $fieldObj) {
				if (
				!is_object($fieldObj) ||
				!property_exists($fieldObj, 'name') ||
				!property_exists($fieldObj, 'value')
				) {
					continue;
				}

				$fieldName = $fieldObj->name;
				$fieldValue = $fieldObj->value;

				// Handle multi-select values (arrays)
				if (is_array($fieldValue)) {
					$filteredValues = array_filter($fieldValue, function ($item) {
						return is_object($item) && !empty($item->value) && $item->value !== 'tjlist:-';
					});

					// Convert objects to trimmed string values
					$formattedData[$fieldName] = array_map(function ($item) {
						return trim($item->value);
					}, $filteredValues);
				}
				else {
					// Handle single value
					$value = trim($fieldValue);
					$formattedData[$fieldName] = ($value !== 'tjlist:-') ? $value : "";
				}
			}
		}

		return $formattedData;
	}

	/**
	 * Function is triggered before save item
	 *
	 * @param   integer  $recordId  item id
	 *
	 * @param   string   $client    client
	 *
	 * @param   array    $data      ucm data
	 *
	 * @return  array
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onTjUcmBeforeSaveItemData($recordId, $client, $data)
	{
		if ($recordId && $client) {
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
			$tjFieldFieldTable = Table::getInstance('field', 'TjfieldsTable');
			$tjFieldFieldTable->load(array('client' => $client, 'type' => 'assignee', 'state' => 1));

			JLoader::import('components.com_tjfields.helpers.tjfields', JPATH_SITE);
			$TjfieldsHelper = new TjfieldsHelper;
			$ucmData = $TjfieldsHelper->FetchDatavalue(array('content_id' => $recordId, 'client' => $client));

			$oldAssignee = array();

			if (property_exists($tjFieldFieldTable, 'id') && !empty($tjFieldFieldTable->id)) {
				$oldAssignee = array_column((array)$ucmData[$tjFieldFieldTable->id]->value, 'value');
			}

			return $oldAssignee;
		}
	}

	/**
	 * Method to send success and File email after copying an item.
	 *
	 * @param   array   $data    The copied item data.
	 * @param   int     $result  The result is True on success, false on failure.
	 *
	 * @return  bool  True on success, false on failure.
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function onSendSuccessFailCopyItemEmails($clusterIds, $client, $userId, $result)
	{
		if (!empty($clusterIds) && $userId) {
			// Get Item ID
			JLoader::import("/components/com_tjucm/helpers/tjucm", JPATH_SITE);
			$tjUcmFrontendHelper = new TjucmHelpersTjucm;
			$itemId = $tjUcmFrontendHelper->getItemId('index.php?option=com_tjucm&view=items&client=' . $client);

			// Get cluster name
			$clusterNames = [];

			foreach ($clusterIds as $id) {
				$clustertable = ClusterFactory::table('Clusters');
				$clustertable->load(['id' => (int)$id]);

				if (!empty($clustertable->name)) {
					$clusterNames[] = $clustertable->name;
				}
			}

			$userdata = Factory::getUser($userId);

			// Get UCM type details
			$ucmtypetable = Table::getInstance('type', 'TjucmTable');
			$ucmtypetable->load(array('unique_identifier' => $client));

			$options = new Registry;
			$options->set('form.name', $ucmtypetable->title);

			$recipients = array(
				'email' => array(
					'to' => array($userdata->email)
				)
			);

			$replacements = new stdClass;
			$replacements->user = new stdClass;
			$replacements->user->name = $userdata->get('name');
			$replacements->user->school = implode(', ', $clusterNames);
			$replacements->user->formtitle = $ucmtypetable->title;
			$replacements->user->formname = str_replace('com_tjucm.', '', $client);

			// Check if send a mail is failuer or Success mail
			$key = $result ? "CopySuccessSendEmail" : "CopyFailSendEmail";

			return Tjnotifications::send("com_tjucm", $key, $recipients, $replacements, $options);
		}
	}

	/**
	 * Event handler triggered after a ticket is created to save custom time fields.
	 *
	 * @param   int    $ticketId  The ID of the created ticket.
	 * @param   array  $data      The ticket data submitted in the form.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onAfterTicketCreateSaveTimeSave(int $ticketId, array $data): void
	{
		// Get Joomla application
		$app = Factory::getApplication();
		$client = $data['client'] ? $data['client'] : '';
		if ($client) {
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/models');
			$tjUcmModelType = BaseDatabaseModel::getInstance('Type', 'TjucmModel');
			$typeId = $tjUcmModelType->getTypeId($client);
			$typeData = $tjUcmModelType->getItem($typeId);
		}

		try {
			// Extract hour and minute from submitted data
			$hours = isset($data['hours']) ? (int)$data['hours'] : 00;
			$minutes = isset($data['minutes']) ? (int)$data['minutes'] : 00;

			$user = Factory::getUser()->id;
			$clusterId = $data['cluster'];
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
			$clustertableInstance = Table::getInstance('Clusters', 'ClusterTable');
			$clustertableInstance->load(array('id' => $clusterId));
			$multiagencyId = $clustertableInstance->client_id;

			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
			$licenceTableInstance = Table::getInstance('licence', 'MultiagencyTable');
			// Get licence id
			$licenceTableInstance->load(array('multiagency_id' => $multiagencyId, 'state' => '1'));
			$licenceId = $licenceTableInstance->id;

			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sla/tables');
			$slaTable = Table::getInstance('SlaActivityTypes', 'SlaTable');
			$slaTable->load(array('title' => 'Support'));
			$activityTypeId = $slaTable->id;

			$activiteseRecord = $this->checkSupportActivity($licenceId, $activityTypeId, $clusterId);

			if ($activiteseRecord) {

				$timeValue = sprintf('%02d:%02d', $hours, $minutes);
				// Example: converting $data into insert-ready array
				$createdDate = \DateTime::createFromFormat('d-m-Y', $data['jform']['created_date']);


				$timelogData = [
					'id' => null,
					'activity_type_id' => 0,
					'client' => 'com_multiagency', // Component name
					'client_id' => (int)$activiteseRecord, // Example: 13374
					'activity_note' => (!empty($typeData->title) ? $typeData->title : Text::_('COM_DPE_TICKET_ACTIVITY_NOTE')) . ':' . $ticketId,
					'created_date' => $createdDate
					? $createdDate->format('Y-m-d H:i:s')
					: date('Y-m-d H:i:s'),
					'spent_time' => 0.00, // Numeric, not string
					'timelog' => $timeValue, // HH:MM:SS
					'state' => 1, // Example: 1
					'attachment' => '',
					'created_by' => (int)$user, // Logged-in user id
					'modified_date' => date('Y-m-d H:i:s'),
					'modified_by' => (int)$user, // Logged-in user id
				];

				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_timelog/tables');
				// Get table instance
				$timelogTable = Table::getInstance('activity', 'TimelogTable');
				// Save the data
				if ($timelogTable->save($timelogData)) {
					$timeLogId = (int)$timelogTable->id;

					$storeXrefData = [
						"ticket_log_id" => $ticketId,
						"client" => ($client) ? $client : 'ticket',
						"time_spent" => $timeValue, // hours.minutes or total hours
						"timelog_activity_id" => $timeLogId,
						"user_id" => (int)$user,
						"created_at" => date('Y-m-d H:i:s'),
						"created_by" => (int)$user,
						"license_id" => $licenceId,
						"multiagency_id" => $multiagencyId
					];


					Table::addIncludePath(JPATH_SITE . '/components/com_dpe/tables');
					// Get table instance
					$ticketsTimelogTable = Table::getInstance('ticketstimelog', 'DpeTable');
					// Save the data
					try {
						$ticketsTimelogTable->save($storeXrefData);
					}
					catch (\Exception $e) {
						echo $e->getMessage();
						$app->enqueueMessage("Failed to save time for ID: {$ticketId}. Error: " . $e->getMessage(), 'error');
					}
				}

			}
		}
		catch (\Exception $e) {
			$app->enqueueMessage("Failed to save time for ID: {$ticketId}. Error: " . $e->getMessage(), 'error');
		}
	}

	/**
	 * Check if a support activity exists for given license, activity type, and cluster.
	 * If not found, insert a new record into #__tj_sla_activities.
	 *
	 * Steps:
	 * 1. Look up #__tj_sla_activities for matching record (sla_activity_type_id, cluster_id, license_id).
	 * 2. If record exists → return true.
	 * 3. If not found → fetch sla_id from #__tj_sla_cluster_xref for the given license.
	 * 4. Construct and insert a new record with default values (sla_service_id = 0, etc.).
	 *
	 * @param  int $licenceId       License ID for which to check/create activity
	 * @param  int $activityTypeId  SLA activity type ID
	 * @param  int $clusterId       Cluster ID
	 *
	 * @return bool  True if activity exists or inserted successfully
	 *
	 * @throws RuntimeException  If SLA ID not found for given license
	 */
	private function checkSupportActivity($licenceId, $activityTypeId, $clusterId)
	{
		// Get DB
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$previousDueDate = Factory::getDate()->toSql();

		// Step 1: Check if record already exists
		$query->select('*')
			->from($db->quoteName('#__tj_sla_activities'))
			->where($db->quoteName('sla_activity_type_id') . ' = ' . (int)$activityTypeId)
			->where($db->quoteName('cluster_id') . ' = ' . (int)$clusterId)
			->where($db->quoteName('license_id') . ' = ' . (int)$licenceId)
			->where($db->quoteName('state') . ' = 1');


		$db->setQuery($query);
		$exists = $db->loadResult();

		if ($exists) {
			return $exists; // Record found, nothing to do
		}
		else {
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_sla/tables');
			$SlaClusterXref = \JTable::getInstance('SlaClusterXrefs', 'SlaTable');
			$SlaClusterXref->load(array('license_id' => $licenceId));

			if (!$SlaClusterXref->sla_id) {
				throw new \RuntimeException('SLA not found for license ID: ' . $licenceId);
			}

			$slaId = (int)$SlaClusterXref->sla_id;


			$activityData = [
				'license_id' => (int)$licenceId,
				'lead_consultant_id' => Factory::getUser()->id,
				'cluster_user' => '', // empty for now
				'sla_activity_type_id' => $activityTypeId,
				'activity_name' => 'Support1',
				'activity_desc' => '',
				'due_date' => '', // can set if needed
				'todo_id' => 0,
				'prev_due_date' => $previousDueDate, // current Joomla time
				'state' => '1',
				'created_on' => Factory::getDate()->toSql(), // current Joomla time
			];

			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_sla/models');
			$model = BaseDatabaseModel::getInstance('SlaActivity', 'SlaModel');
			$return = $model->save($activityData)->id;
			return $return;

		}
	}
}