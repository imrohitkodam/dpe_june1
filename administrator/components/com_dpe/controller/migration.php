<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Dpe
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\LogEntry;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

jimport('joomla.log.logger.formattedtext');
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);
JLoader::import('components.com_cluster.libraries.cluster', JPATH_ADMINISTRATOR);
JLoader::import('components.com_multiagency.includes.multiagency', JPATH_SITE);

include_once JPATH_SITE . '/components/com_multiagency/includes/multiagency.php';
include_once JPATH_SITE . '/components/com_cluster/includes/cluster.php';

/**
 * Migration controller class.
 *
 * @since  1.0
 */
class DpeControllerMigration extends BaseController
{
	/**
	 * Add licence type if missing
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function addLicenceType()
	{
		$app = Factory::getApplication();
		$params = ComponentHelper::getParams('com_dpe');
		$key = $params->get('migrationKey');

		$userKey = Factory::getApplication()->input->get('key');

		$user = Factory::getUser();

		// Allow only admin to run the script
		if ($key === $userKey)
		{
			// Config Error log file
			$config = array(
					'text_file' => 'licenceFormError.log'
			);

			$logger = new FormattedtextLogger($config);

			// Config for Success log File
			$licenceFormSuccessConfig = array(
					'text_file' => 'licenceFormSuccess.log'
			);

			$licenceFormSuccessLogger = new FormattedtextLogger($licenceFormSuccessConfig);

			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			// Get the licences
			$query->select('*');
			$query->from($db->quoteName('#__tjmultiagency_licences'));
			$db->setQuery($query);
			$licences = $db->loadAssocList();

			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');

			foreach ($licences as $licence)
			{
				$model = BaseDatabaseModel::getInstance('LicenceForm', 'MultiagencyModel');

				if (!$licence['type'] && $licence['course_id'] != 0)
				{
					$item = (array) $model->getData($licence['id']);

					$item['licence_type'] = 'per';

					$result = $model->save($item);

					// Update the entry in licence table
					if (!$result)
					{
							$msg = Text::_('COM_DPE_ENTRY_NOT_UPDATED') . $model->getError() . "--" .
							Text::_('COM_DPE_FORM_LBL_LICENCE_ID') . "--" . $licence['id'];
							$licenceEntry = new LogEntry($msg);
							$logger->addEntry($licenceEntry);
					}
					else
					{
						$msg1 = Text::_('COM_DPE_ENTRY_UPDATED') . "--" .
						Text::_('COM_DPE_FORM_LBL_LICENCE_ID') . "--" . $result;
						$licenceSuccessEntry = new LogEntry($msg1);
						$licenceFormSuccessLogger->addEntry($licenceSuccessEntry);
					}
				}
			}
		}
		else
		{
			$loginUrlWithReturn = Route::_('index.php?option=com_users');
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect($loginUrlWithReturn, 403);
		}
	}

	/**
	 * Add Cluster type if missing
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function addCluster()
	{
		$app = Factory::getApplication();
		$params = ComponentHelper::getParams('com_dpe');
		$key = $params->get('migrationKey');

		$userKey = $app->input->get('key');

		$user = Factory::getUser();

		// Allow only admin to run the script
		if ($key === $userKey)
		{
			// Config Error log file
			$config = array(
					'text_file' => 'clusterEnteryError.log'
			);

			$logger = new FormattedtextLogger($config);

			// Config Success log file
			$clusterEnterySuccessConfig = array(
					'text_file' => 'clusterEnterySuccess.log'
			);

			$clusterEnterySuccessLogger = new FormattedtextLogger($clusterEnterySuccessConfig);

			$db    = Factory::getDbo();

			$subQuery = $db->getQuery(true);

			$subQuery->select('client_id');
			$subQuery->from($db->quoteName('#__tj_clusters'));
			$db->setQuery($subQuery);
			$clusters = $db->loadAssocList();

			foreach ($clusters as $cluster)
			{
				$clusterClients[] = $cluster['client_id'];
			}

			$query = $db->getQuery(true);

			// Get all the agencies who's entry is not in cluster
			$query->select('*');
			$query->from($db->quoteName('#__tjmultiagency_multiagency'));

			if (count($clusterClients))
			{
			$query->where($db->quoteName('id') . 'NOT IN(' . implode(",", $clusterClients) . ')');
			}

			$db->setQuery($query);
			$multiagencies = $db->loadAssocList();

			$data = array();

			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/models');

			foreach ($multiagencies as $multiagency)
			{
				$clusterModel = BaseDatabaseModel::getInstance('Cluster', 'ClusterModel', array("ignore_request" => 1));
				$data['name'] = $multiagency['title'];
				$data['description'] = $multiagency['title'];
				$data['params'] = $multiagency['title'];
				$data['client'] = 'com_multiagency';
				$data['client_id'] = $multiagency['id'];
				$data['ordering'] = '0';
				$data['state'] = '1';
				$data['created_by'] = $multiagency['created_by'];
				$data['modified_by'] = $multiagency['created_by'];

				// Add enrty ing cluster table
				if (!$clusterModel->save($data))
				{
					$msg = Text::_('COM_DPE_ENTRY_NOT_UPDATED') . $clusterModel->getError() .
					Text::_('COM_DPE_FORM_LBL_MULTIAGENCY_ID') . "--" . $multiagency['id'];
					$clusterEntry = new LogEntry($msg);
					$logger->addEntry($clusterEntry);
				}
				else
				{
					$msg1 = Text::_('COM_DPE_ENTRY_UPDATED') .
					Text::_('COM_DPE_FORM_LBL_MULTIAGENCY_ID') . "--" . $clusterModel->getState('cluster.id');
					$clusterSuccessEntry = new LogEntry($msg1);
					$clusterEnterySuccessLogger->addEntry($clusterSuccessEntry);
				}
			}
		}
		else
		{
			$loginUrlWithReturn = Route::_('index.php?option=com_users');
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect($loginUrlWithReturn, 403);
		}
	}

	/**
	 * Add manager entry to subusers and cluster nodes
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function addManagerToTjsu()
	{
		$app = Factory::getApplication();
		$params = ComponentHelper::getParams('com_dpe');
		$key = $params->get('migrationKey');

		$userKey = $app->input->get('key');

		$user = Factory::getUser();

		// Allow only admin to run the script
		if ($key === $userKey)
		{
			$subUserRoles = array('com_multiagency','com_cluster');
			$params = ComponentHelper::getParams('com_multiagency');

			$managerRoleId = $params->get('manager_role_id', '0', 'INT');

			// Config Error log file
			$config = array(
					'text_file' => 'addManagerToTjsuError.log'
			);

			$logger = new FormattedtextLogger($config);

			$cluster_NodeConfig = array(
					'text_file' => 'addManagerToclusterNodeError.log'
			);
			$addManagerToclusterNode = new FormattedtextLogger($config);

			// Config Success log file
			$addManagerToTjsuSuccessConfig = array(
					'text_file' => 'addManagerToTjsuSuccess.log'
			);

			$addManagerToTjsuSuccessLogger = new FormattedtextLogger($addManagerToTjsuSuccessConfig);

			$addManagerToclusterNodeSuccess = array(
					'text_file' => 'addManagerToclusterNodeSuccess.log'
			);

			$addManagerToclusterNodeSuccessLogger = new FormattedtextLogger($addManagerToclusterNodeSuccess);

			$db    = Factory::getDbo();

			$query = $db->getQuery(true);

			// Get the agency data
			$query->select('*');
			$query->from($db->quoteName('#__tjmultiagency_multiagency'));
			$db->setQuery($query);
			$multiagencies = $db->loadObjectList();

			$tjsuObject = new stdClass;

			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_subusers/tables');
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/models');

			foreach ($multiagencies as $multiagency)
			{
				if ($multiagency->manager_id)
				{
				foreach ($subUserRoles as $roles)
				{
					// Add entry in subuser
					$tjsuSingleObject = new stdClass;

					$tableInstance = Table::getInstance('user', 'SubusersTable');

					// Insert the record into subuser  table.
					$tjsuSingleObject->user_id = $multiagency->manager_id;
					$tjsuSingleObject->role_id = $managerRoleId;
					$tjsuSingleObject->client = $roles;
					$tjsuSingleObject->client_id = $multiagency->id;
					$tjsuSingleObject->created_by = Factory::getUser()->id;
					$tjsuSingleObject->created_date = Factory::getDate()->toSql();
					$tjsuSingleObject->modified_by = Factory::getUser()->id;
					$tjsuSingleObject->modified_date = Factory::getDate()->toSql();
					$tjsuSingleObject->checked_out = 0;
					$tjsuSingleObject->checked_out_time = Factory::getDate()->toSql();
					$tjsuSingleObject->ordering = 0;
					$tjsuSingleObject->state = 0;

					if (!empty($tjsuSingleObject->user_id))
					{
						$tableInstance->load(
						array('user_id' => $tjsuSingleObject->user_id, 'client_id' => $multiagency->id,'client' => $roles,'role_id' => $managerRoleId)
						);

						if ($tableInstance->id)
						{
							$tjsuSingleObject->id = $tableInstance->id;
						}

						if (!$tableInstance->save($tjsuSingleObject))
						{
							$msgError = Text::_('COM_DPE_ENTRY_NOT_UPDATED') . $tableInstance->getError() .
							Text::_('COM_DPE_MULTIAGENCES_MANAGER_ID') . "--" . $tjsuSingleObject->user_id .
							Text::_('COM_DPE_ENROLMENT_ROLE') . "--" . $roles;
							$subuserEntry = new LogEntry($msgError);
							$logger->addEntry($subuserEntry);
						}
						else
						{
							$msgSuccess = Text::_('COM_DPE_ENTRY_UPDATED') .
							Text::_('COM_DPE_MULTIAGENCES_ID') . "--" . $tableInstance->id .
							Text::_('COM_DPE_ENROLMENT_ROLE') . "--" . $roles;
							$subuserEntrySuccess = new LogEntry($msgSuccess);
							$addManagerToTjsuSuccessLogger->addEntry($subuserEntrySuccess);
						}
					}
				}

				$clusterInstance = Table::getInstance('Clusters', 'ClusterTable');

				// Get cluster Id
				$clusterInstance->load(array('client_id' => $multiagency->id));
				$clusterID = $clusterInstance->id;

				$clusterNodeModel = BaseDatabaseModel::getInstance('ClusterUser', 'ClusterModel', array("ignore_request" => 1));

				$clusterNodeData = array();

				$clusterNodeData['cluster_id'] = $clusterID;
				$clusterNodeData['user_id'] = $multiagency->manager_id;
				$clusterNodeData['state'] = 1;
				$clusterNodeData['created_on'] = Factory::getDate()->toSql();
				$clusterNodeData['created_by'] = Factory::getUser()->id;

				// Add entry in cluster Node
				if (!$clusterNodeModel->save($clusterNodeData))
				{
					$msg = Text::_('COM_DPE_ENTRY_NOT_UPDATED') . $clusterNodeModel->getError() .
					Text::_('COM_DPE_MULTIAGENCES_MANAGER_ID') . "--" . $multiagency->manager_id .
					Text::_('COM_DPE_FORM_LBL_MANAGER_CLUSTER') . "--" . $clusterID;
					$clusterEntry = new LogEntry($msg);
					$addManagerToclusterNode->addEntry($clusterEntry);
				}
				else
				{
					$msg1 = Text::_('COM_DPE_ENTRY_UPDATED') .
					Text::_('COM_DPE_MULTIAGENCES_MANAGER_ID') . "--" . $multiagency->manager_id .
					Text::_('COM_DPE_FORM_LBL_MANAGER_CLUSTER') . "--" . $clusterID;
					$clusterEntrySuccess = new LogEntry($msg1);
					$addManagerToclusterNodeSuccessLogger->addEntry($clusterEntrySuccess);
				}
				}
			}
		}
		else
		{
			$loginUrlWithReturn = Route::_('index.php?option=com_users');
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect($loginUrlWithReturn, 403);
		}
	}

	/**
	 * Add staff entry to subusers and cluster nodes
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function addStaffToTjsu()
	{
		$app = Factory::getApplication();
		$params = ComponentHelper::getParams('com_dpe');
		$key = $params->get('migrationKey');

		$userKey = $app->input->get('key');

		$user = Factory::getUser();

		// Allow only admin to run the script
		if ($key === $userKey)
		{
				$params = ComponentHelper::getParams('com_multiagency');

				$memberRoleId = $params->get('member_role_id', '0', 'INT');

				// Config log file
				$config = array(
						'text_file' => 'addStaffToTjsuError.log'
				);

				$logger = new FormattedtextLogger($config);

				$cluster_NodeConfig = array(
						'text_file' => 'addStaffToclusterNodeError.log'
				);

				$cluster_NodeConfigLogger = new FormattedtextLogger($cluster_NodeConfig);

				// Config Success Log
				$addStaffToTjsuSuccess = array(
						'text_file' => 'addStaffToTjsuSuccess.log'
				);

				$addStaffToTjsuSuccessLogger = new FormattedtextLogger($addStaffToTjsuSuccess);

				$addStaffToclusterNodeSuccess = array(
						'text_file' => 'addStaffToclusterNodeSuccess.log'
				);

				$addStaffToclusterNodeSuccessLogger = new FormattedtextLogger($addStaffToclusterNodeSuccess);

				$db    = Factory::getDbo();

				$query = $db->getQuery(true);

				$query->select('hu.*,tmm.id as agency_id');
				$query->from($db->quoteName('#__hierarchy_users', 'hu'));
				$query->join('INNER', $db->quoteName('#__tjmultiagency_multiagency', 'tmm') . ' ON (' . $db->quoteName('tmm.manager_id') . ' = ' .
						$db->quoteName('hu.reports_to') . ')');
				$db->setQuery($query);
				$subUsers = $db->loadObjectList();

				$tjsuObject = new stdClass;

				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_subusers/tables');
				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/models');

				if (count($subUsers))
				{
					foreach ($subUsers as $subUser)
					{
						$tjsuSingleObject = new stdClass;

						$tableInstance = Table::getInstance('user', 'SubusersTable');

						// Insert the record into subuser  table.
						$tjsuSingleObject->user_id = $subUser->user_id;
						$tjsuSingleObject->role_id = $memberRoleId;
						$tjsuSingleObject->client = 'com_multiagency';
						$tjsuSingleObject->client_id = $subUser->agency_id;
						$tjsuSingleObject->created_by = $subUser->reports_to;
						$tjsuSingleObject->created_date = Factory::getDate()->toSql();
						$tjsuSingleObject->modified_by = Factory::getUser()->id;
						$tjsuSingleObject->modified_date = Factory::getDate()->toSql();
						$tjsuSingleObject->checked_out = 0;
						$tjsuSingleObject->checked_out_time = Factory::getDate()->toSql();
						$tjsuSingleObject->ordering = 0;
						$tjsuSingleObject->state = 0;

						if (!empty($tjsuSingleObject->user_id))
						{
							$tableInstance->load(
							array('user_id' => $subUser->user_id, 'client_id' => $subUser->agency_id,'client' => 'com_multiagency','role_id' => $memberRoleId)
							);

							if ($tableInstance->id)
							{
								$tjsuSingleObject->id = $tableInstance->id;
							}

							if (!$tableInstance->save($tjsuSingleObject))
							{
								$subusermsg = Text::_('COM_DPE_ENTRY_NOT_UPDATED') . $tableInstance->getError() .
								Text::_('COM_DPE_FORM_LBL_MEMBER_ID') . "--" . $tjsuSingleObject->user_id .
								Text::_('COM_DPE_FORM_LBL_MEMBER_ID') . "--" . 'com_multiagency';
								$subuserEntry = new LogEntry($subusermsg);
								$logger->addEntry($subuserEntry);
							}
							else
							{
								$subuserSuccessmsg = Text::_('COM_DPE_UPDATED') .
								Text::_('COM_DPE_FORM_LBL_MEMBER_ID') . "--" . $tableInstance->id .
								Text::_('COM_DPE_FORM_LBL_MEMBER_ID') . "--" . 'com_multiagency';
								$subuserSuccessEntry = new LogEntry($subuserSuccessmsg);
								$addStaffToTjsuSuccessLogger->addEntry($subuserSuccessEntry);
							}
						}

						$clusterInstance = Table::getInstance('Clusters', 'ClusterTable');

						$clusterInstance->load(array('client_id' => $subUser->agency_id));

						$clusterID = $clusterInstance->id;

						$clusterNodeModel = BaseDatabaseModel::getInstance('ClusterUser', 'ClusterModel', array("ignore_request" => 1));

						$clusterNodeData = array();

						$clusterNodeData['cluster_id'] = $clusterID;
						$clusterNodeData['user_id'] = $subUser->user_id;
						$clusterNodeData['state'] = 1;
						$clusterNodeData['created_on'] = Factory::getDate()->toSql();
						$clusterNodeData['created_by'] = $subUser->reports_to;

						if (!$clusterNodeModel->save($clusterNodeData))
						{
							$clustermsg = Text::_('COM_DPE_ENTRY_NOT_UPDATED') . $clusterNodeModel->getError() .
							Text::_('COM_DPE_FORM_LBL_USER_ID') . "--" . $subUser->user_id .
							Text::_('COM_DPE_FORM_LBL_MANAGER_CLUSTER') . "--" . $clusterID;
							$clusterEntry = new LogEntry($clustermsg);
							$clusterNodeConfigLogger->addEntry($clusterEntry);
						}
						else
						{
							$clusterSuccessmsg = Text::_('COM_DPE_ENTRY_UPDATED') .
							Text::_('COM_DPE_FORM_LBL_USER_ID') . "--" . $subUser->user_id .
							Text::_('COM_DPE_FORM_LBL_MANAGER_CLUSTER') . "--" . $clusterID;
							$clusterSuccessEntry = new LogEntry($clusterSuccessmsg);
							$addStaffToclusterNodeSuccessLogger->addEntry($clusterSuccessEntry);
						}
					}
				}
			else
			{
				$clusterSuccessmsg = Text::_('COM_DPE_HIERARCHY_ALL_USERS_ALREADY_MEMBERS');
				$clusterSuccessEntry = new LogEntry($clusterSuccessmsg);
				$addStaffToclusterNodeSuccessLogger->addEntry($clusterSuccessEntry);
			}
		}
		else
		{
			$loginUrlWithReturn = Route::_('index.php?option=com_users');
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect($loginUrlWithReturn, 403);
		}
	}

	/**
	 * Update shika enrollment Dates
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function updateShikaEnrollment()
	{
		$app = Factory::getApplication();
		$params = ComponentHelper::getParams('com_dpe');
		$key = $params->get('migrationKey');

		$userKey = $app->input->get('key');

		$user = Factory::getUser();

		// Allow only admin to run the script
		if ($key === $userKey)
		{
			// Config Error log file
				$config = array(
						'text_file' => 'updateShikaEntryError.log'
				);

				$logger = new FormattedtextLogger($config);

			// Config Success log file
				$updateShikaEntrySuccessConfig = array(
						'text_file' => 'updateShikaEntrySuccess.log'
				);

				$updateShikaEntrySuccessLogger = new FormattedtextLogger($updateShikaEntrySuccessConfig);

				$db    = Factory::getDbo();
				$query = $db->getQuery(true);

				// Get All the enrolled users
				$query->select('*');
				$query->from($db->quoteName('#__tjlms_enrolled_users'));
				$db->setQuery($query);
				$enrolledUsers = $db->loadObjectList();

				BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/models');
				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');

				foreach ($enrolledUsers as $enrolleduser)
				{
					$query1 = $db->getQuery(true);
					$query1->select('*');
					$query1->from($db->quoteName('#__tj_cluster_nodes'));
					$query1->where($db->quoteName('user_id') . '=' . (int) $enrolleduser->user_id);
					$db->setQuery($query1);
					$clusterNodeusers = $db->loadObjectList();

					foreach ($clusterNodeusers as $clusterNode)
					{
						$cluster = BaseDatabaseModel::getInstance('Cluster', 'ClusterModel', array("ignore_request" => 1));

						// Get agency id using cluster node
						$clusterData = $cluster->getItem($clusterNode->cluster_id);

						$licenceTableInstance = Table::getInstance('licence', 'MultiagencyTable');

						// Get licence id
						$licenceTableInstance->load(array('multiagency_id' => $clusterData->client_id));

						if ($licenceTableInstance->id)
						{
							$enrolleduser->end_time = $licenceTableInstance->end_date;

							$enrolledusersTableInstance = Table::getInstance('Enrolledusers', 'TjlmsTable');

							// Update the end date
							if (!$enrolledusersTableInstance->save($enrolleduser))
							{
								$enrollmenrmsg = Text::_('COM_DPE_ENTRY_NOT_UPDATED') . $enrolledusersTableInstance->getError() .
								Text::_('COM_DPE_FORM_LBL_ENROLLMENT_ID') . "--" . $enrolleduser->id;
								$enrollEntry = new LogEntry($enrollmenrmsg);
								$logger->addEntry($enrollEntry);
							}
							else
							{
								$enrollmenrSuccessmsg = Text::_('COM_DPE_ENTRY_NOT_UPDATED') .
								Text::_('COM_DPE_FORM_LBL_ENROLLMENT_ID') . "--" . $enrolledusersTableInstance->id;
								$enrollSuccessEntry = new LogEntry($enrollmenrSuccessmsg);
								$updateShikaEntrySuccessLogger->addEntry($enrollSuccessEntry);
							}
						}
					}
				}
		}
		else
		{
			$loginUrlWithReturn = Route::_('index.php?option=com_users');
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect($loginUrlWithReturn, 403);
		}
	}

	/**
	 * Update multi agency enrollment Dates
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function addMultiagencyEnrollment()
	{
		$app = Factory::getApplication();
		$params = ComponentHelper::getParams('com_dpe');
		$key = $params->get('migrationKey');

		$userKey = $app->input->get('key');

		$user = Factory::getUser();

		// Allow only admin to run the script
		if ($key === $userKey)
		{
			// Config Error log file
			$config = array(
					'text_file' => 'updateMultiagencyEntryError.log'
			);

			$logger = new FormattedtextLogger($config);

			// Config Success log file
			$updateMultiagencyEntrySuccessConfig = array(
					'text_file' => 'updateMultiagencyEntrySuccess.log'
			);

			$updateMultiagencyEntrySuccessLogger = new FormattedtextLogger($updateMultiagencyEntrySuccessConfig);

			// Get All the enrolled users
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->select('*');
			$query->from($db->quoteName('#__tjlms_enrolled_users'));
			$db->setQuery($query);
			$enrolledUsers = $db->loadObjectList();

			foreach ($enrolledUsers as $enrolleduser)
			{
				$query1 = $db->getQuery(true);
				$query1->select('*');
				$query1->from($db->quoteName('#__tj_cluster_nodes'));
				$query1->where($db->quoteName('user_id') . '=' . (int) $enrolleduser->user_id);
				$db->setQuery($query1);
				$clusterNodeusers = $db->loadObjectList();

				// Get order id for the enrollments
				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
				$orderTableInstance = Table::getInstance('Orders', 'TjlmsTable');

				$orderTableInstance->load(array('enrollment_id' => $enrolleduser->id));
				$orderId = $orderTableInstance->id;

				if ($orderId)
				{
					BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/models');

					foreach ($clusterNodeusers as $clusterNode)
					{
						$cluster = BaseDatabaseModel::getInstance('Cluster', 'ClusterModel', array("ignore_request" => 1));

						// Get agency id using cluster node
						$clusterData = $cluster->getItem($clusterNode->cluster_id);

						Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
						$licenceTableInstance = Table::getInstance('licence', 'MultiagencyTable');

						// Get licence id
						$licenceTableInstance->load(array('multiagency_id' => $clusterData->client_id));

						if ($licenceTableInstance->id)
						{
							$enrolleduser->end_time = $licenceTableInstance->end_date;

							$enrollmentTableInstance = Table::getInstance('Enrollment', 'MultiagencyTable');

							$agencyEnrollment = new stdClass;
							$agencyEnrollment->license_id = (int) $licenceTableInstance->id;
							$agencyEnrollment->course_id = (int) $orderTableInstance->course_id;
							$agencyEnrollment->order_id = (int) $orderId;
							$agencyEnrollment->user_id = $enrolleduser->user_id;
							$agencyEnrollment->client = 'com_multiagency';

							// Add entry in multy agency enrollment
							if (!$enrollmentTableInstance->save($agencyEnrollment))
							{
								$enrollmenrmsg = Text::_('COM_DPE_NOT_UPDATED') . "--" . $enrollmentTableInstance->getError() .
								Text::_('COM_DPE_FORM_LBL_ENROLLMENT_ID') . "--" . $enrollmentTableInstance->license_id;
								$enrollEntry = new LogEntry($enrollmenrmsg);
								$logger->addEntry($enrollEntry);
							}
							else
							{
								$enrollmenrSuccessmsg = Text::_('COM_DPE_ENTRY_UPDATED') .
								Text::_('COM_DPE_FORM_LBL_LICENCE_ID') . "--" . $enrollmentTableInstance->license_id;
								$enrollSuccesEntry = new LogEntry($enrollmenrSuccessmsg);
								$updateMultiagencyEntrySuccessLogger->addEntry($enrollSuccesEntry);
							}
						}
					}
				}
			}
		}
		else
		{
			$loginUrlWithReturn = Route::_('index.php?option=com_users');
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect($loginUrlWithReturn, 403);
		}
	}

	/**
	 * Function for certificate migration
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function certitifcateMigration()
	{
		$app        = Factory::getApplication();
		$input      = $app->input;
		$courseId   = $input->getInt('course_id', 0);
		$batchCount = $input->getInt('batchCount', 0);
		$count      = 0;

		if (!$courseId && !$batchCount)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');

			return;
		}

		// Create a new query object.
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$subQuery = $db->getQuery(true);
		$subQuery->select('user_id') ->from($db->quoteName('#__tjlms_certificate'));
		$subQuery->where($db->quoteName('course_id') . ' = ' . (int) $courseId);

		$query->select(array('ct.user_id','ct.timeend'));

		$query->from($db->quoteName('#__tjlms_course_track', 'ct'));
		$query->where($db->quoteName('ct.status') . ' = ' . $db->quote('C'));
		$query->where($db->quoteName('ct.course_id') . ' = ' . (int) $courseId);
		$query->join('INNER', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('ct.user_id') . ' = ' . $db->quoteName('u.id') . ')');

		$query->where($db->qn('ct.user_id') . '  Not IN (' . $subQuery . ')');

		$db->setQuery($query);
		$query->setLimit($batchCount);

		$users = $db->loadObjectList();

		foreach ($users as $user)
		{
			$data = array('course_id' => (int) $courseId, 'user_id' => (int) $user->user_id);

			if (empty($data['course_id']))
			{
				return false;
			}

			if (empty($data['user_id']))
			{
				$data['user_id'] = $user->user_id;
			}

			$userId = $data['user_id'];

			JLoader::import('components.com_tjlms.helpers.courses', JPATH_SITE);
			$TjlmsCoursesHelper = new TjlmsCoursesHelper;
			$courseProgress     = $TjlmsCoursesHelper->getCourseProgress($courseId, $userId);

			if ($courseProgress['status'] == 'C')
			{
				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjlms/tables');
				$result = Table::getInstance('course', 'TjlmsTable');
				$result->load(array('id' => $courseId));

				// Certificate table object
				$certTable = Table::getInstance('Certificate', 'TjlmsTable');
				$isPresent = $certTable->load(array('user_id' => (int) $userId, 'course_id' => (int) $courseId));

				if (empty($data['exp_date']))
				{
					if (empty($result->expiry))
					{
						$data['exp_date'] = '000-00-00 00:00:00';
					}
					else
					{
						// Set expriry date as expiry date
						if ($user->timeend === '0000-00-00 00:00:00')
						{
							// Shika uses this
							$data['exp_date'] = Factory::getDate("now +" . $result->expiry . "day")->format("Y-m-d H:i:s");
						}
						else
						{
							$data['exp_date'] = Factory::getDate($user->timeend . " +" . $result->expiry . "day")->format("Y-m-d H:i:s");
						}
					}
				}

				if (!$isPresent)
				{
					$certificateTable = Table::getInstance('Certificate', 'TjlmsTable');

					if (empty($data['cert_id']))
					{
						$certPrefix = ComponentHelper::getParams('com_tjlms')->get('certificate_prefix', 'LMS-CERT');
						$data['cert_id']    = $certPrefix . '-' . rand() . "-" . $result->certificate_id;
					}

					if (empty($data['grant_date']))
					{
						// Grant date as today's date $data['grant_date'] = JFactory::getDate()->toSql();
						if ($user->timeend === '0000-00-00 00:00:00')
						{
							// Shika uses this
							$data['grant_date'] = Factory::getDate()->toSql();
						}
						else
						{
							$data['grant_date'] = $user->timeend;
						}
					}

					$res = $certificateTable->save($data);

					if ($res)
					{
						$count++;
					}
				}
			}
		}

		echo "Total users migrated : " . $count;
		jexit();
	}

	/**
	 * Function to delete Scorm Tracking of 2018 users
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function deleteScormTracking()
	{
		$app        = Factory::getApplication();
		$input      = $app->input;
		$courseId   = $input->getInt('course_id', 0);
		$startLimit = $input->getInt('startLimit', 0);
		$limit      = $input->getInt('limit', 50);
		$year       = $input->getInt('year', 2018);
		$count      = 0;

		if (!$courseId && !$batchCount)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');

			return;
		}

		// Create a new query object.
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array('eu.user_id'));
		$query->from($db->quoteName('#__tjlms_enrolled_users', 'eu'));
		$query->where($db->quoteName('eu.course_id') . ' = ' . (int) $courseId);
		$query->join('INNER', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('eu.user_id') . ' = ' . $db->quoteName('u.id') . ')');
		$query->where($db->quoteName('eu.enrolled_on_time') . ' LIKE ' . $db->quote("%{$year}%"));
		$db->setQuery($query);
		$query->setLimit($limit, $startLimit);
		$users = $db->loadColumn();

		// Create a new query object.
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array('l.id'));
		$query->from($db->quoteName('#__tjlms_lessons', 'l'));
		$query->where($db->quoteName('l.course_id') . ' = ' . (int) $courseId);
		$db->setQuery($query);
		$lessons = $db->loadColumn();

		JLoader::import('helpers.lesson', JPATH_SITE . '/components/com_tjlms');
		JLoader::import('models.attemptreport', JPATH_ADMINISTRATOR . '/components/com_tjlms');
		$TjlmsLessonHelper = new TjlmsLessonHelper;
		$TjlmsModelAttemptreport = new TjlmsModelAttemptreport;

		foreach ($users as $user)
		{
			foreach ($lessons as $lesson)
			{
				$last_attempt = $TjlmsLessonHelper->getlesson_total_attempts_done($lesson, $user);

				// Get Last attempt lesson track details
				Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
				$lessonTrack = Table::getInstance('lessontrack', 'TjlmsTable', array('dbo', $db));
				$lessonTrack->load(array('lesson_id' => $lesson, 'user_id' => $user, 'attempt' => $last_attempt));

				if ($lessonTrack->last_accessed_on <= ($year . '-12-31 23:59:59') )
				{
					// Delete data from tmt_test_answer and tmt_test_atendees
					$deleteTestAttemptDataScorm = $TjlmsModelAttemptreport->deleteTestAttemptDataScorm($lessonTrack->id, $lesson, $user, $last_attempt);
				}
			}
		}
	}

	/**
	 * DPE email notification template migrations
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function migrateTemplates()
	{
		$result = array();

		try
		{
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjnotifications/tables');

			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select("temp.id as tempId");
			$query->select("conf.id as confId");
			$query->select("conf.subject");
			$query->select("conf.body");
			$query->from($db->qn('#__tj_notification_templates', 'temp'));
			$query->join('LEFT', $db->qn('#__tj_notification_template_configs', 'conf') .
			' ON (' . $db->qn('temp.id') . ' = ' . $db->qn('conf.template_id') . ')');
			$query->where(
				$db->qn('temp.client') . ' IN(' . $db->quote("com_dpe") . ',' . $db->quote("com_multiagency") . ',' . $db->quote("com_sla") . ','
				. $db->quote("com_tjucm") . ')'
			);
			$query->where($db->qn('conf.backend') . ' = ' . $db->quote("email"));
			$db->setQuery($query);
			$emailArray = $db->loadAssocList();

			if (!empty($emailArray))
			{
				foreach ($emailArray as $key => $value)
				{
					if ((!empty($value['subject'])) || (!empty($value['body'])))
					{
						$table   = Table::getInstance('Template', 'TjnotificationTable', array());
						$obj     = new stdClass;
						$obj->id = $value['confId'];

						if (strpos($value['subject'], '{{') === false)
						{
							$subjectBracketReplace  = str_replace("{", "{{", $value['subject']);
							$subjectBracketReplace  = str_replace("}", "}}", $subjectBracketReplace);
							$obj->subject           = $subjectBracketReplace;
						}

						if (strpos($value['body'], '{{') === false)
						{
							$bodyBracketReplace  = str_replace("{", "{{", $value['body']);
							$bodyBracketReplace  = str_replace("}", "}}", $bodyBracketReplace);
							$obj->body           = $bodyBracketReplace;
						}

						$table->save($obj);
					}
				}

				$result['status']  = true;
				$result['message'] = "Migration is done successfully";

				return $result;
			}
		}
		catch (Exception $e)
		{
			$result['err_code'] = '';
			$result['status']   = false;
			$result['message']  = $e->getMessage();
		}

		return $result;
	}

	/**
	 * Temporary function to excute sql
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function runSQL()
	{
		$db = Factory::getDbo();

		$sqlfile = JPATH_ADMINISTRATOR . '/components/com_dpe/sql/updates/mysql/1.4.2.sql';

		if (file_exists($sqlfile))
		{
			$buffer = file_get_contents($sqlfile);

			if ($buffer !== false)
			{
				$queries = $db->splitSql($buffer);

				$i = 0;

				foreach ($queries as $query)
				{
					$query = trim($query);

					if ($query != '')
					{
						$db->setQuery($query)->execute();
						$i++;
					}
				}
			}

			echo $i . 'queries executed';
		}
	}

	/**
	 * Migrations script for add data in #__tjlms_certificate table
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function migrateCertificateData()
	{
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		$app    = Factory::getApplication();
		$jinput = $app->input;
		$course_id = $jinput->get("course_id", 0, "INT");

		if ($course_id)
		{
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

			$subquery = $db->getQuery(true);

			$subquery->select("user_id");
			$subquery->from($db->qn('#__tj_certificate_issue'));
			$subquery->where($db->qn('client_id') . ' = ' . (int) $course_id);
			$subquery->where($db->qn('client') . ' = "com_tjlms.course"');

			$query->select("c.id as course_id, d.user_id, d.last_accessed_date as grant_date, c.expiry");
			$query->from($db->qn('#__tjlms_course_track', 'd'));
			$query->join('INNER', $db->qn('#__tjlms_courses', 'c') .
			' ON (' . $db->qn('c.id') . ' = ' . $db->qn('d.course_id') . ')');
			$query->where($db->qn('d.status') . ' = "C"');
			$query->where($db->qn('d.course_id') . ' = ' . (int) $course_id);
			$query->where($db->qn('c.id') . ' = ' . (int) $course_id);
			$query->where(
				$db->qn('d.user_id') . ' NOT IN(' . $subquery . ')'
			);
			$query->group('d.id');
			$db->setQuery($query);

			$courseData = $db->loadObjectList();

			$certificateExpiryDate = "";

			$i = 0;

			foreach ($courseData as $course)
			{
				$certificateExpiryDate = Factory::getDate($course->grant_date, 'UTC');
				$certificateExpiryDate->modify("+" . $course->expiry . " days");
				$certificateExpiryDate = $certificateExpiryDate->toSql();

				$table   		 = Table::getInstance('Certificate', 'TjlmsTable', array());
				$obj     		 = new stdClass;
				$obj->user_id 	 = $course->user_id;
				$obj->course_id  = $course->course_id;
				$obj->grant_date = $course->grant_date;
				$obj->exp_date   = $certificateExpiryDate;

				$table->save($obj);
				$i++;
			}

			echo $i . ' entries inserted';
		}
	}

	/**
	 * This is to allocate all tools to licence
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function migrateLicenceTools()
	{
		$app        = Factory::getApplication();
		$input      = $app->input;
		$batchCount = $input->getInt('batchCount', 0);

		if (!$batchCount)
		{
			return false;
		}

		$db       = Factory::getDbo();
		$query    = $db->getQuery(true);
		$subquery = $db->getQuery(true);

		$subquery->select('DISTINCT licence_id');
		$subquery->from($db->qn('#__tjmultiagency_licences_xref'));

		// Query to get activate licence who has not assigned any Tools
		$query->select('ml.id');
		$query->from($db->qn('#__tjmultiagency_licences', 'ml'));
		$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'tm') . ' ON (' . $db->qn('tm.id') . ' = ' . $db->qn('ml.multiagency_id') . ')');

		// Licences is in upcoming state
		$query->where($db->quoteName('ml.state') . ' = 1');
		$query->where($db->qn('ml.id') . ' NOT IN(' . $subquery . ')');
		$query->setLimit($batchCount);
		$db->setQuery($query);

		$activeLicences = $db->loadObjectList();

		$params       = ComponentHelper::getParams('com_dpe');
		$allTools     = new Registry($params->get('allTools'));
		$allToolsdata = $allTools->get('tools');
		$tools        = array('com_tjlms.eLearning', 'com_tjlms.compliancemanager', 'com_tjucm.phishingsimulation', 'com_tjucm.informationclassification',
		'com_tjucm.rop','com_tjucm.sarlog','com_tjucm.breachlog', 'com_tjucm.FOIlog');

		foreach ($activeLicences as $key => $activeLicenceId )
		{
			foreach ($tools as $toolClient)
			{
				// Build array to store data into xref for tool client configured in SLA params
				$toolData                   = $allToolsdata->$toolClient;
				$licenceXref                = array();
				$licenceXref['licence_id']  = $activeLicenceId->id;
				$licenceXref['tool_client'] = $toolData->tool_client;
				$licenceXref['type']        = 'all';

				$this->addEntryToLicenceXref($licenceXref);

				foreach ($toolData->supporting_clients as $client)
				{
					// Build array to store data into xref for supporting clients configured in SLA params
					$licenceXref                = array();
					$licenceXref['licence_id']  = $activeLicenceId->id;
					$licenceXref['tool_client'] = $client;
					$licenceXref['type']        = 'all';

					$this->addEntryToLicenceXref($licenceXref);
				}
			}
		}

		echo "<pre>";
		echo 'Licence IDs';
		print_r($activeLicences);
		die;
	}

	/**
	 * Method to add entry into lesson xref
	 *
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function addEntryToLicenceXref($data)
	{
		// Get the table of licence xref
		$licenceXrefTable = Multiagency::table("licencexref");
		$licenceXrefTable->load(array('licence_id' => $data['licence_id'], 'client' => $data['tool_client']));

		// Insert licence-Tool Saving data into licence xref
		$licenceXref = Multiagency::table("licencexref");

		if ($licenceXrefTable->id)
		{
			$licenceXref->id = $licenceXrefTable->id;
		}

		// Build object to save record into xref table
		$licenceXref->licence_id  = $data['licence_id'];
		$licenceXref->client      = $data['tool_client'];
		$licenceXref->type        = 'all';

		if ($data['is_tjcum'] === "yes")
		{
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjucm/tables');
			$ucmTable = Table::getInstance('type', 'TjucmTable');
			$ucmTable->load(array('unique_identifier' => $data['tool_client']));

			// If Ucm id present then add ucm id as tool id else it will 0
			if ($ucmTable->id)
			{
				$licenceXref->client_id = $ucmTable->id;
			}
			else
			{
				$licenceXref->client_id = 0;
			}
		}
		else
		{
			$licenceXref->client_id = $data['client_id'];
		}

		// Save into licence xref
		$licenceXref->store();
	}

	/**
	 * Function used for single tool migration
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function migrateSingleTool()
	{
		$app        = Factory::getApplication();
		$input      = $app->input;
		$batchCount = $input->getInt('batchCount', 0);

		if (!$batchCount)
		{
			return false;
		}

		$params         = ComponentHelper::getParams('com_dpe');
		$singleTool     = new Registry($params->get('singleTool'));
		$singleToolData = $singleTool->get('tool');
		$db             = Factory::getDbo();
		$query          = $db->getQuery(true);
		$subquery       = $db->getQuery(true);

		$subquery->select('DISTINCT licence_id');
		$subquery->from($db->qn('#__tjmultiagency_licences_xref'));
		$subquery->where($db->qn('client') . ' = ' . $db->q($singleToolData->tool_client));

		// Query to get activate licence who has not assigned any Tools
		$query->select('ml.id');
		$query->from($db->qn('#__tjmultiagency_licences', 'ml'));
		$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'tm') . ' ON (' . $db->qn('tm.id') . ' = ' . $db->qn('ml.multiagency_id') . ')');

		// Licences is not in archived state and delete 0 state
		$query->where($db->qn('ml.state') . ' NOT IN (0,2)');
		$query->where($db->qn('tm.state') . '= 1');
		$query->where($db->qn('ml.id') . ' NOT IN(' . $subquery . ')');
		$query->setLimit($batchCount);
		$db->setQuery($query);

		$licences         = $db->loadObjectList();
		$migratedlicences = array();

		foreach ($licences as $key => $licence)
		{
			// Build array to store data into xref for supporting clients configured in SLA params
			$licenceXref                = array();
			$licenceXref['licence_id']  = $licence->id;
			$licenceXref['tool_client'] = $singleToolData->tool_client;
			$licenceXref['type']        = 'all';
			$licenceXref['client_id']   = $singleToolData->client_id;
			$licenceXref['is_tjcum']    = $singleToolData->is_tjcum;

			$this->addEntryToLicenceXref($licenceXref);

			if (!empty($singleToolData->supporting_clients))
			{
				foreach ($singleToolData->supporting_clients as $client)
				{
					// Build array to store data into xref for supporting clients configured in SLA params
					$licenceXref                = array();
					$licenceXref['licence_id']  = $licence->id;
					$licenceXref['tool_client'] = $client;
					$licenceXref['type']        = 'all';
					$licenceXref['client_id']   = $singleToolData->client_id;
					$licenceXref['is_tjcum']    = $singleToolData->is_tjcum;
					$this->addEntryToLicenceXref($licenceXref);
				}
			}

			$migratedlicences[] = $licence->id;
		}

		echo 'Migrated Licences: Total(' . count($migratedlicences) . ') =>' . implode(', ', $migratedlicences);
	}

	/**
	 * Function used to migrate user with new role
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function migrateUsers()
	{
		$app        = Factory::getApplication();
		$input      = $app->input;
		$limit      = $input->getInt('limit', 50);
		$limitstart = $input->getInt('limitstart', 0);

		$params         = ComponentHelper::getParams('com_dpe');
		$singleTool     = new Registry($params->get('singleTool'));
		$singleToolData = $singleTool->get('tool');
		$roleIds        = $singleToolData->role_ids;
		$users          = $this->getUsersForMigrate($limit, $limitstart);
		$userFormModel  = Multiagency::model('userform', array('ignore_request' => true));
		$migratedUsers  = array();

		if (empty($roleIds))
		{
			return false;
		}

		// Foreach for distinct users
		foreach ($users as $user)
		{
			// Get distinct user's clusters
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('user_id, client, client_id');
			$query->from($db->qn('#__tjsu_users'));
			$query->where($db->qn('role_id') . ' IN(' . implode(",", $singleToolData->migrate_user_role_ids) . ')');
			$query->where($db->qn('user_id') . ' = ' . (INT) $user->user_id);
			$query->where($db->qn('client') . ' = ' . $db->q('com_cluster'));
			$db->setQuery($query);
			$usersClusters = $db->loadObjectList();

			// Add entry for cluster
			foreach ($usersClusters as $usersCluster)
			{
				// Add entry for com_cluster
				$asssignUserToCluster              = array();
				$asssignUserToCluster['user_id']   = $usersCluster->user_id;
				$asssignUserToCluster['content']   = $usersCluster->client;
				$asssignUserToCluster['client_id'] = $usersCluster->client_id;

				foreach ($roleIds as $roleId)
				{
					$asssignUserToCluster['role_id'] = $roleId;

					// Assign user to role
					$userFormModel->assignRoleToUser($asssignUserToCluster);
				}

				// Add entry for multiagency
				$clustertable = ClusterFactory::table('Clusters');
				$clustertable->load(array('id' => $usersCluster->client_id));

				$asssignUserToAgency              = array();
				$asssignUserToAgency['user_id']   = $user->user_id;
				$asssignUserToAgency['content']   = 'com_multiagency';
				$asssignUserToAgency['client_id'] = $clustertable->client_id;

				foreach ($roleIds as $roleId)
				{
					$asssignUserToAgency['role_id'] = $roleId;

					// Assign user to role
					$userFormModel->assignRoleToUser($asssignUserToAgency);
				}
			}

			$migratedUsers[] = $user->user_id;
		}

		echo 'Migrated Users: Total(' . count($migratedUsers) . ') =>' . implode(', ', $migratedUsers);
	}

	/**
	 * Function used to get users for migration
	 *
	 * @param   Integer  $limit       total limit
	 *
	 * @param   Integer  $limitstart  start limit
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function getUsersForMigrate($limit, $limitstart)
	{
		$params         = ComponentHelper::getParams('com_dpe');
		$singleTool     = new Registry($params->get('singleTool'));
		$singleToolData = $singleTool->get('tool');
		$db             = Factory::getDbo();
		$query          = $db->getQuery(true);

		$query->select('DISTINCT su.user_id');
		$query->from($db->qn('#__tjsu_users', 'su'));
		$query->join('INNER', '#__users AS u ON su.user_id = u.id');
		$query->where($db->qn('su.client') . ' = ' . $db->q('com_cluster'));
		$query->where($db->qn('u.block') . ' = 0');
		$query->where($db->qn('role_id') . ' IN(' . implode(",", $singleToolData->migrate_user_role_ids) . ')');
		$query->order('su.user_id');
		$query->setLimit($limit, $limitstart);
		$db->setQuery($query);

		$tjsuData = $db->loadObjectList();

		return $tjsuData;
	}

	/**
	 * Function used to get sla and its list of tool for adition of tool 
	 *
	 * @param   Integer  $limit       total limit
	 *
	 * @param   Integer  $limitstart  start limit
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function addSupportedTools()
	{
		$db             = Factory::getDbo();
		$query          = $db->getQuery(true);

		$query->select('DISTINCT su.licence_id');
		$query->from($db->qn('#__tjmultiagency_licences_xref', 'su'));
		$query->where($db->qn('su.client') . ' = ' . $db->q('com_tjucm.rop'));
		$db->setQuery($query);
		$tjliData = $db->loadObjectList();

		foreach ($tjliData as $licence)
		{
			$licenceXref               = new stdClass();
			$licenceXref->licence_id  = $licence->licence_id;
			$licenceXref->client = 'com_tjucm.ropvendors';
			$licenceXref->type        = 'all';
			$licenceXref->client_id   = '30';
			$licenceXref->total_seats =  '0';
			$licenceXref->used_seats  =  '0';

			$db             = Factory::getDbo();
			$query          = $db->getQuery(true);
			$query->select('DISTINCT id');
			$query->from($db->qn('#__tjmultiagency_licences_xref'));
			$query->where($db->qn('licence_id') . ' = ' . $db->q($licenceXref->licence_id));
			$query->where($db->qn('client') . ' = ' . $db->q('com_tjucm.ropvendors'));
			$db->setQuery($query);
			$tj = $db->loadObjectList();

			if (empty($tj))
			{
				$result = $db->insertObject('#__tjmultiagency_licences_xref', $licenceXref);
				$lastRowId = $db->insertid();
				if ($result) 
				{
					echo $lastRowId.'\n';
				}
			}	
		}
	}

	/**
	 * Function used to update tool in param field
	 *
	 * @param   Integer  $limit       total limit
	 *
	 * @param   Integer  $limitstart  start limit
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function addRopInSla()
	{
		$db       = Factory::getDbo();
		$query    = $db->getQuery(true);
		$query->select('DISTINCT *');
		$query->from($db->qn('#__tj_slas'));
		$db->setQuery($query);
		$licenseData  = $db->loadObjectList();

		foreach($licenseData as $licenseData)
		{
			$data = json_decode($licenseData->params);
			foreach ($data as $key => $data)
			{
				
				$ropLicense[$key]= (array)$data;

				// if Rop present then only assign 
				if ($ropLicense[$key]['com_tjucm.rop']->supporting_clients)
				{
					$ropLicense[$key]['com_tjucm.rop']->supporting_clients['7'] = "com_tjucm.ropvendors";
					$licenseData->params = json_encode($ropLicense);
				} 
				
		   	}
		   	// Update the param field 
		   		$subquery    = $db->getQuery(true);
				$fields = array(
					    $db->quoteName('params') . ' = ' . $db->quote($licenseData->params),
					);
					// Conditions for which records should be updated.
					$conditions = array(
					    $db->quoteName('id') . ' = '.$licenseData->id, 
					);

					$subquery->update($db->quoteName('#__tj_slas'))->set($fields)->where($conditions);
					$db->setQuery($subquery);

					$result = $db->execute();
					if ($db->getAffectedRows())
					{
					echo "done \n";
					echo 'id = '.$licenseData->id;
					}
		  			

		}
}


}
