<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

jimport('techjoomla.tjnotifications.tjnotifications');

/**
 * Multiagency UserForm model.
 *
 * @since  __DEPLOY__VERSION__
 */

class MultiagencyModelUserForm extends FormModel
{
	private $item = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return void
	 *
	 * @since  __DEPLOY__VERSION__
	 */
	protected function populateState()
	{
		$app = Factory::getApplication('com_multiagency');

		// Load state from the request userState on edit or from the passed variable on default
		$id = $app->input->get('id');
		$app->setUserState('com_multiagency.edit.user.id', $id);

		$this->setState('user.id', $id);

		// Load the parameters.
		$params       = $app->getParams();
		$params_array = $params->toArray();

		if (isset($params_array['item_id']))
		{
			$this->setState('user.id', $params_array['item_id']);
		}

		$this->setState('params', $params);
	}

	/**
	 * Method to get an ojbect.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return Object|boolean Object on success, false on failure.
	 *
	 * @throws Exception
	 */
	public function &getData($id = null)
	{
		if ($this->item === null)
		{
			$this->item = false;

			if (empty($id))
			{
				$id = $this->getState('user.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			// Attempt to load the row.
			if ($table !== false && $table->load($id))
			{
				$user = Factory::getUser();
				$id   = $table->id;
				$canEdit = $user->authorise('core.own.adduser', 'com_multiagency') || $user->authorise('core.adduser', 'com_multiagency');

				if (!$canEdit && $user->authorise('core.edit.own', 'com_multiagency'))
				{
					$canEdit = $user->id == $table->created_by;
				}

				// Check published state.
				if ($published = $this->getState('filter.published'))
				{
					if ($table->state != $published)
					{
						return $this->item;
					}
				}

				// Convert the JTable to a clean JObject.
				$properties  = $table->getProperties(1);
				$this->item = ArrayHelper::toObject($properties, 'JObject');
			}
		}

		return $this->item;
	}

	/**
	 * Method to get the table
	 *
	 * @param   string  $type    Name of the Table class
	 * @param   string  $prefix  Optional prefix for the table class name
	 * @param   array   $config  Optional configuration array for Table object
	 *
	 * @return  Table|boolean Table if found, boolean false on failure
	 */
	public function getTable($type = 'User', $prefix = 'MultiagencyTable', $config = array())
	{
		$this->addTablePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to check out an item for editing.
	 *
	 * @param   integer  $id  The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	public function checkout($id = null)
	{
		// Get the user id.
		$id = (!empty($id)) ? $id : (int) $this->getState('user.id');

		if ($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Get the current user object.
			$user = Factory::getUser();

			// Attempt to check the row out.
			if (method_exists($table, 'checkout'))
			{
				if (!$table->checkout($user->get('id'), $id))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to get the profile form.
	 *
	 * The base form is loaded from XML
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return    Form    A Form object on success, false on failure
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	public function getForm($data = array(), $loadData = true)
	{

		// Get the form.
		$form = $this->loadForm('com_multiagency.userform', 'userform', array(
			'control'   => 'jform',
			'load_data' => $loadData
			)
		);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the subform data that should be injected in the form.
	 *
	 * @param   int  $userId  User Id
	 *
	 * @return    mixed    The data for the form.
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	public function loadSubFormData($userId)
	{
		if (! class_exists('MultiagencyFrontendHelpers'))
		{
			// Require_once $path;
			JLoader::register('MultiagencyFrontendHelpers', JPATH_COMPONENT_SITE . '/helpers/multiagency.php');
			JLoader::load('MultiagencyFrontendHelpers');
		}

		$helperObject = new MultiagencyFrontendHelpers;

		return $helperObject->getRoleClient($userId, 'com_multiagency', null, 1);
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return    mixed    The data for the form.
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	protected function loadFormData()
	{
		$data = Factory::getApplication()->getUserState('com_multiagency.edit.userform.data');
		$user = Factory::getUser();

		if (empty($data))
		{
			$data = $this->getData();
		}

		// DPE Hack Start
		if (is_array($data))
		{
			$data = new CMSObject($data);
		}
		// DPE Hack End

		$allowedAgency = array();

		// Get com_subusers component status
		if (ComponentHelper::getComponent('com_subusers', true)->enabled)
		{
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
			$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));

			$allowedAgencies = $MultiagencyModel->getAllocatedAgencies(Factory::getUser()->id);

			if (!empty($allowedAgencies))
			{
				foreach ($allowedAgencies as $agency)
				{
					if (!$user->authorise('core.manageall', 'com_cluster'))
					{
						// Check user having permission to add staff
						if (RBACL::check($user->id, 'com_multiagency', 'core.adduser', 'com_multiagency', $agency->id))
						{
							$allowRoles = $this->getUserAgencyRole($agency->id);
							$allowedAgency[$agency->id] = array_column($allowRoles, 'role_id');
						}
					}
					else
					{
						$allowedAgency[$agency->id] = $agency->id;
					}
				}
			}
		}

		if (!empty($data->id))
		{
			// Load form data for extra fields (needed for editing).
			$dataExtra = $this->loadSubFormData((int) $data->id);

			if (!class_exists('MultiagencyFrontendHelpers'))
			{
				$helperPath = JPATH_COMPONENT_SITE . '/helpers/multiagency.php';

				// Require_once $path;
				JLoader::register('MultiagencyFrontendHelpers', $helperPath);
				JLoader::load('MultiagencyFrontendHelpers');
			}

			$helperObject = new MultiagencyFrontendHelpers;

			// Get com_cluster component status
			if (ComponentHelper::getComponent('com_cluster', true)->enabled)
			{
				$subFormArray = array();

				foreach ($dataExtra as $key => $agencyData)
				{
					if ($allowedAgency[$agencyData['client_id']])
					{
						$relatedRoleData = $helperObject->getRelatedRoleUserData($data->id, "com_multiagency", $agencyData['client_id'], $agencyData['role_id']);

						$relatedRole     = array();

						if (is_array($relatedRoleData) && !empty($relatedRoleData))
						{
							$relatedRole     = array_column($relatedRoleData, 'role_id');
						}

						if (in_array($agencyData['role_id'], (array) $allowedAgency[$agencyData['client_id']])
							|| $user->authorise('core.manageall', 'com_cluster'))
						{
							$subFormArray['agency_role_map' . $agencyData['client_id']] = [
								'client_id' => $agencyData['client_id'],
								'rolelist' => $agencyData['role_id'],
								'relatedrole' => $relatedRole,
							];
						}
					}
				}

				if (empty($subFormArray) && !$user->authorise('core.manageall', 'com_cluster'))
				{
					$app = Factory::getApplication();
					$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
					$app->redirect(Route::_('index.php?option=com_multiagency&view=users', false));

					return;
				}

				// DPE Hack
				$data->agency_role_map = $subFormArray;
			}

			// DPE Hack
			$data->password = '';
		}

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since __DEPLOY__VERSION__
	 */
	public function save($data)
	{
		$app = Factory::getApplication();
		$user  = Factory::getUser();
		$userId = $user->id;
		$data['username'] = str_replace( array( '\''), '', $data['email']);
		$data['name'] = $data['name'];
		$data['requireReset'] = 1;

		$randomPassword = (!empty($data['random_password'])) ? $data['random_password'] : false;

		$id    = (!empty($data['id'])) ? $data['id'] : (int) $this->getState('user.id');
		$state = (!empty($data['state'])) ? 1 : 0;

		if ($id)
		{
			// Check the user can edit this item
			$authorised = $user->authorise('core.edit', 'com_multiagency') || $authorised = $user->authorise('core.edit.own', 'com_multiagency');
		}
		else
		{
			// Check the user can create new items in this section
			$authorised = $user->authorise('core.create', 'com_multiagency');
		}

		if ($authorised !== true)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if (!class_exists('MultiagencyFrontendHelpers'))
		{
			$helperPath = JPATH_COMPONENT_SITE . '/helpers/multiagency.php';

			// Require_once $path;
			JLoader::register('MultiagencyFrontendHelpers', $helperPath);
			JLoader::load('MultiagencyFrontendHelpers');
		}

		$helperObject = new MultiagencyFrontendHelpers;

		
		PluginHelper::importPlugin('system');
		$params = ComponentHelper::getParams('com_multiagency');
		$groupManagerId = $params->get('multiagency_manager_group', '0', 'INT');
		$groupSAId = $params->get('multiagency_school_admin_group', '0', 'INT');
		$groupMultiagecnyAdminId = (INT) $params->get('multiagency_admin_group');
		$groupMultiagecnyTrusteeId = (INT) $params->get('multiagency_trustee_group');
		//$managerRoleId = $params->get('manager_role_id', '0', 'INT');
		$schoolAdminRoleId = $params->get('school_admin_role_id', '0', 'INT');
		$MultiagencyAdminRoleId = $params->get('multyagency_admin_role_id', '0', 'INT');
		$MultiagencyTrusteeRoleId = $params->get('organization_trustee_role_id', '0', 'INT');

		$memberRoleId = $params->get('member_role_id', '0', 'INT');

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models/userimport.php');
		$MultiagencyModelUserImport = BaseDatabaseModel::getInstance('UserImport', 'MultiagencyModel');

	// Check login user associated with school - start
		$clusterIds = array();

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);

			foreach ($clusters as $cluster)
			{
				// Check user having permission to add staff
				if (RBACL::check($user->id, 'com_cluster', 'core.adduser', 'com_multiagency', $cluster->cluster_id))
				{
					$allowRoles = $this->getUserAgencyRole($cluster->client_id);

					$allowedAgency[$cluster->client_id] = array_column($allowRoles, 'role_id');
					$clusterIds[] = $cluster->client_id;
				}
			}

			foreach ($data['agency_role_map'] as $key => $postData)
			{
				if (!in_array($postData['client_id'], (array) $clusterIds))
				{
					unset($data['agency_role_map'][$key]);
				}
			}
		}

		if (empty($data['agency_role_map']))
		{
			$this->setError(Text::_('COM_MULTIAGENCY_STAFF_CREATION_SOMETHING_IS_WRONG'));

			return false;
		}

	// Check login user associated with school - end

		if ($user->authorise('core.edit.state', 'com_multiagency') !== true && $state == 1)
		{
			// The user cannot edit the state of the item.
			$data['state'] = 0;
		}

	// Joomla user entry
		$formData = new Registry($app->input->get('jform', '', 'array'));
		$userexist = $this->getUserDetail($formData->get('email', '', 'STRING'));
		$userId = $userexist;

		if (!$userexist)
		{
			$data['reset_password'] = 1;
			$data['password'] = UserHelper::genRandomPassword(6);
		}
		else
		{
			if ($data['reset_password'] && $data['random_password'] != 1)
			{
				$data['password'] = UserHelper::genRandomPassword(6);
			}
		}

		switch ($params->get('social_integration', '', 'STRING'))
		{
			case "easysocial":
				$udateUser = $helperObject->createESuser($data);
				break;
			default:
				$udateUser = $helperObject->createnewuser($data, $randomPassword);

				if (!$udateUser->get('id'))
				{
					return false;
				}
		}

		$userId = $udateUser->id;
		$roleDetail  = $userRoles = array();
		$schoolTitle = '';

		if ($userId)
		{
			// Add subusers entries for below client context
			$subUserRoles = array('com_multiagency','com_cluster');

			// Assign role to users
			if ($id)
			{
				// On edit update and delete record
				$prevUserAgencyRolesData = $helperObject->getRoleClient($data['id'], 'com_multiagency', $clusterIds, 1);

				$i = 0;
				$roleUpdate = false;

				// Check if roles are added or removed
				if (count($prevUserAgencyRolesData) < count($data['agency_role_map']) || count($prevUserAgencyRolesData) > count($data['agency_role_map']))
				{
					$roleUpdate = true;
				}

				foreach ($prevUserAgencyRolesData as $preData)
				{
					foreach ($data['agency_role_map'] as $postData)
					{
						if ($preData['client_id'] == $postData['client_id'] && $preData['role_id'] == $postData['rolelist'])
						{
							// Check for Staff and Trustee that prev related roles and current role and if not changed then do not further process
							if ($postData['rolelist'] == $memberRoleId || $postData['rolelist'] == $MultiagencyTrusteeRoleId)
							{
								$prevRelatedRoleData = $helperObject->getRelatedRoleUserData($data['id'], 'com_multiagency', $postData['client_id'], $postData['rolelist']);
								$prevRelatedRole     = array();

								if (is_array($prevRelatedRoleData) && !empty($prevRelatedRoleData))
								{
									$prevRelatedRole     = array_column($prevRelatedRoleData, 'role_id');
								}

								if ($postData['relatedrole'] == $prevRelatedRole)
								{
									unset($prevUserAgencyRolesData[$i]);
									$userRoles[$preData['client_id']] = $preData;
								}
							}
							else
							{
								unset($prevUserAgencyRolesData[$i]);
								$userRoles[$preData['client_id']] = $preData;
							}
						}

						// Check user role have permission to update it
						if (!in_array($preData['role_id'], (array) $allowedAgency[$preData['client_id']])
							&& !$user->authorise('core.manageall', 'com_cluster'))
						{
							unset($prevUserAgencyRolesData[$i]);
							$userRoles[$preData['client_id']] = $preData;
						}
					}

					$i++;
				}

				$deletdNode = $prevUserAgencyRolesData;
				$ClusterModel = ClusterFactory::model('Cluster');

				// Remove associated user data
				foreach ($deletdNode as $deleteData)
				{
					$mappedData = $helperObject->getIdsUserAgencyRoleMap($userId, $deleteData['role_id'], $deleteData['client_id']);

					foreach ($mappedData as $suid)
					{
						$tableInstance = RBACL::table('user');
						$re = $tableInstance->delete($suid);
					}

					$clusterInfo = $ClusterModel::getClusterByClient('com_multiagency', $deleteData['client_id']);
					$clusterId = $clusterInfo->id;
					$mappedData = $helperObject->getIdsUserAgencyRoleMap($userId, $deleteData['role_id'], $clusterId, 'com_cluster');

					// Remove mappedData of users based on cluster client_id
					foreach ($mappedData as $suid)
					{
						$tableInstance = RBACL::table('user');
						$re = $tableInstance->delete($suid);
					}

					// Check user having any role for same entity
					if (!$userRoles[$deleteData['client_id']])
					{
						$ClusterUserTabel = ClusterFactory::table('ClusterUsers');
						$ClusterUserTabel->load(array('user_id' => $userId, 'cluster_id' => $clusterId));

						$ClusterUserModel = ClusterFactory::model('ClusterUser');
						$ClusterUserModel->delete($ClusterUserTabel->id);
					}

					/* This commented code will not provide empty data that is why removed from below if condition
						$groupAlreadyAssigned = $helperObject->getIdsUserAgencyRoleMap($userId, $deleteData['role_id']);
					*/

					if ($deleteData['role_id'])
					{
						
						switch ($deleteData['role_id'])
						{
							//case $managerRoleId:
							//	UserHelper::removeUserFromGroup($userId, $groupManagerId);
								//break;
							case $schoolAdminRoleId:
								UserHelper::removeUserFromGroup($userId, $groupSAId);
								break;
							case $MultiagencyAdminRoleId:
								UserHelper::removeUserFromGroup($userId, $groupMultiagecnyAdminId);
								break;
							case $MultiagencyTrusteeRoleId:
								UserHelper::removeUserFromGroup($userId, $groupMultiagecnyTrusteeId);
								break;
						}
					}
				}
			}

			$ClusterModel = ClusterFactory::model('Cluster', array('ignore_request' => true));

			// Code Start to add users entries into com_cluster and com_subusers extension tables
			foreach ($data['agency_role_map'] as $postData)
			{
				$multiagencyId = (int) $postData['client_id'];
				$mappedData = $helperObject->getIdsUserAgencyRoleMap($userId, $postData['rolelist'], $multiagencyId);

				// New entry
				if (count($mappedData) == 0)
				{
					$addCluster = $ClusterModel::getClusterByClient('com_multiagency', $multiagencyId);
					$clusterId = $addCluster->id;

					{
						if ($postData['rolelist'] == $MultiagencyAdminRoleId)
						{
							$multiagencyId = $clusterId = 0;
						}

						// TODO Start
						$roleIdstoSaved = array();
						$params         = ComponentHelper::getParams('com_dpe');

						// if ($postData['rolelist'] == $schoolAdminRoleId || $postData['rolelist'] == $managerRoleId)
						if ($postData['rolelist'] == $schoolAdminRoleId) 
						{
							$licenceAssignedRoleIds = $helperObject->getLicenceAssignedRoleIds($multiagencyId);
							$dpeTools               = new Registry($params->get('dpe_role_ids'));

							if (count((array) $licenceAssignedRoleIds))
							{
								// Add role as per tool allocated to licence
								$roleIdstoSaved = array_intersect($dpeTools->get($postData['rolelist']), $licenceAssignedRoleIds);
							}
						}

						$additionalRole = new Registry($params->get('additional_role'));

						if (count($additionalRole[$postData['rolelist']]))
						{
							$roleIdstoSaved = array_merge($roleIdstoSaved, $additionalRole[$postData['rolelist']]);
						}

						if ($postData['rolelist'] == $memberRoleId || $postData['rolelist'] == $MultiagencyTrusteeRoleId)
						{
							$licenceAssignedRoleIds = $helperObject->getLicenceAssignedRoleIds($multiagencyId);
							$dpeTools               = new Registry($params->get('dpe_role_ids'));

							// Check If core role available in assign tool role
							$additionRoles = array_intersect($dpeTools->get($postData['rolelist']), (array) $licenceAssignedRoleIds);

							// Add role
							if (!empty($additionRoles))
							{
								$roleIdstoSaved = array_merge($roleIdstoSaved, $additionRoles);
							}


							if (count((array) $postData['relatedrole']))
							{
								// Add related role
								$roleIdstoSaved = array_merge($roleIdstoSaved, $postData['relatedrole']);
							}
						}

						// Add core role Id
						array_push($roleIdstoSaved, $postData['rolelist']);

						foreach ($roleIdstoSaved as $roleId)
						{
							foreach ($subUserRoles as $content)
							{
								$asssignUserData            = array();
								$asssignUserData['user_id'] = $userId;

								if ($content == 'com_multiagency')
								{
									$asssignUserData['client_id'] = $multiagencyId;
								}
								else
								{
									$asssignUserData['client_id'] = $clusterId;
								}

								$asssignUserData['content'] = $content;
								$asssignUserData['role_id'] = $roleId;

								// Assign user to role
								$this->assignRoleToUser($asssignUserData);
							}
						}

						Factory::getApplication()->triggerEvent('onAfterAddUser', array($clusterId,array($userId)));

					}
				}

				if ($postData['rolelist'] == $MultiagencyTrusteeRoleId)
				{
					if (!in_array($groupMultiagecnyTrusteeId, Factory::getUser($userId)->groups))
					{
						// For manager assign joomla user group
						UserHelper::addUserToGroup($userId, $groupMultiagecnyTrusteeId);
					}
				}

				if ($postData['rolelist'] == $MultiagencyAdminRoleId)
				{
					if (!in_array($groupMultiagecnyAdminId, Factory::getUser($userId)->groups))
					{
						// For manager assign joomla user group
						UserHelper::addUserToGroup($userId, $groupMultiagecnyAdminId);
					}
				}

				// if ($postData['rolelist'] == $managerRoleId)
				// {
				// 	if (!in_array($groupManagerId, Factory::getUser($userId)->groups))
				// 	{
				// 		// For manager assign joomla user group
				// 		UserHelper::addUserToGroup($userId, $groupManagerId);
				// 	}
				// }

				if ($postData['rolelist'] == $schoolAdminRoleId)
				{
					// For school admin assign joomla user group
					if (!in_array($groupSAId, Factory::getUser($userId)->groups))
					{
						UserHelper::addUserToGroup($userId, $groupSAId);
					}
				}
			}

			// Code End - to add entries into com_cluster and com_subusers extension tables. Also, assign user group

			// Send emails as per data update
			if (ComponentHelper::getComponent('com_tjnotifications', true)->enabled)
			{
				if (!$userexist)
				{
					$key = 'newMultUserAdded';
				}
				elseif ($roleUpdate && $data['reset_password'])
				{
					$key = 'notifyRolePassword';
				}
				elseif ($data['reset_password'])
				{
					$key = 'notifyPassword';
				}
				elseif($roleUpdate)
				{
					$key = 'notifyRole';
				}
				else
				{
					$key = 'userDetailsUpdated';
				}

				$this->userProfileEmail($data, $key);
			}

			return $userId;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to assign role to user
	 *
	 * @param   Array  $data  data
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function assignRoleToUser($data)
	{
		$tableInstance       = RBACL::table('user');
		$sudata              = array();
		$sudata['user_id']   = $data['user_id'];
		$sudata['client_id'] = $data['client_id'];
		$sudata['client']    = $data['content'];
		$sudata['role_id']   = (int) $data['role_id'];

		if (!empty($sudata['user_id']) && !empty($sudata['role_id']))
		{
			// Insert the record into subuser  table.
			$re = $tableInstance->save($sudata);
		}
	}

	/**
	 * Check if data can be saved
	 *
	 * @return bool
	 */
	public function getCanSave()
	{
		$table = $this->getTable();

		return $table !== false;
	}

	/**
	 * To check if entered is valid email address
	 *
	 * @param   string  $emailId  Eamilid
	 *
	 * @return  boolean
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	public function validateEmail($emailId)
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__users'));
		$query->where($db->quoteName('email') . ' = ' . $db->quote($emailId));
		$this->_db->setQuery($query);
		$userexist = $this->_db->loadResult();

		if ($userexist)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * To check if entered is valid user name
	 *
	 * @param   string  $userName  Eamilid
	 *
	 * @return  boolean
	 *
	 * @since    __DEPLOY__VERSION__
	 */
	public function validateUserName($userName)
	{
		$query	= "SELECT id FROM #__users WHERE username = '" . $userName . "'";
		$this->_db->setQuery($query);
		$userexists = $this->_db->loadResult();
		$userid = "";

		if ($userexists)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * To check if entered is valid email address And get User Id
	 *
	 * @param   string   $emailId     Eamilid
	 * @param   boolean  $checkExist  True
	 *
	 * @return  boolean|int  Return user id
	 *
	 * @since    1.0
	 */
	public function getUserDetail($emailId, $checkExist = false )
	{
		$db = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__users'));
		$query->where($db->quoteName('email') . ' = ' . $db->quote($emailId) . 'OR' . $db->quoteName('username') . ' = ' . $db->quote($emailId));
		$this->_db->setQuery($query);
		$userexist = $this->_db->loadResult();

		if ($checkExist && $userexist)
		{
			return true;
		}
		elseif (!$checkExist && $userexist)
		{
			return $userexist;
		}

		return false;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   Form   $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  array|boolean  Array of filtered data if valid, false otherwise.
	 *
	 * @see     FormRule
	 * @see     InputFilter
	 * @since   __DEPLOY__VERSION__
	 */
	public function validate($form, $data, $group = null)
	{
		$emailValidate = $this->validateEmail($data['email']);

		if ($data['id'])
		{
			$user = Factory::getUser($data['id']);

			if ($user->email !== $data['email'] && $emailValidate)
			{
				$this->setError(Text::_('COM_MULTIAGENCY_EMAIL_MESSAGE'));

				return false;
			}
		}
		elseif ($emailValidate)
		{
			$this->setError(Text::_('COM_MULTIAGENCY_EMAIL_MESSAGE'));

			return false;
		}

		if (!$data['id'])
		{
			// Check user exist or not
			$userexist = $this->getUserDetail($data['email']);

			if ($userexist)
			{
				$this->setError(Text::_('COM_MULTIAGENCY_ADD_USER_USER_ALREADY_EXIST'));

				return false;
			}
		}

		$params                   = ComponentHelper::getParams('com_multiagency');
		$memberRoleId             = (int) $params->get('member_role_id', '0', 'INT');
		$MultiagencyTrusteeRoleId = (int) $params->get('organization_trustee_role_id', '0', 'INT');
		$orgTrusteeGroup          = (int) $params->get('multiagency_trustee_group', '0', 'INT');
		$usersGroups              = $user->groups;

		// Allow staff for multiple organisations $memberCount = 0;

		if ($data['id'])
		{
			if ($data['random_password'] == 1 && $data['reset_password'] != 1)
			{
				$this->setError(Text::_('COM_MULTIAGENCY_FORM_RESET_PASSWORD_EMPTY'));

				return false;
			}

			if ($data['random_password'] == 1)
			{
				if (empty($data['password']))
				{
					$this->setError(Text::_('COM_MULTIAGENCY_FORM_ERROR_PASSWORD_EMPTY'));

					return false;
				}

				if (empty($data['confirmPassword']))
				{
					$this->setError(Text::_('COM_MULTIAGENCY_FORM_ERROR_CONFIRM_PASSWORD_EMPTY'));

					return false;
				}

				if ($data['password'] !== $data['confirmPassword'])
				{
					$this->setError(Text::_('COM_MULTIAGENCY_FORM_ERROR_PASSWORD_NOT_MATCH'));

					return false;
				}
			}
		}

		$roleTable = RBACL::table('role');
		$subUserTable = RBACL::table('user');

		// Get all submitted roles
		$postDataRoleList = array_filter(array_column($data['agency_role_map'], 'rolelist'));

		// Check if Trustee have role more than one
		if ((in_array($MultiagencyTrusteeRoleId, $postDataRoleList))
			&& (!empty(array_diff(array_unique($postDataRoleList), array($MultiagencyTrusteeRoleId)))))
		{
			$roleTable->load(array('id' => $MultiagencyTrusteeRoleId));
			$errorMsg = Text::sprintf('COM_MULTIAGENCY_ADD_TRSUTEE_USER_WRONG_ROLE', $roleTable->name);
			$this->setError($errorMsg);

			return false;
		}

		JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
		$roles = RBACL::getRoleByUser($user->id, 'com_multiagency');

		// Get related roles and Trustee can not have more than one core role
		$subusersModelRole = RBACL::model('roles', array('ignore_request' => true));
		$subusersModelRole->setState('filter.client', 'com_multiagency');
		$subusersModelRole->setState('filter.state', 1);
		$coreRoles = $subusersModelRole->getItems();
		$coreRoles = array_column($coreRoles, 'id');
		$coreRoles = array_intersect($coreRoles, $roles);

		if (is_array($usersGroups))
		{
			if ((in_array($orgTrusteeGroup, $usersGroups)) && count(array_filter($coreRoles)) > 1)
			{
				// This user is a Trustee. You can not assign another role to a Trustee member.
				$errorMsg = Text::_('COM_MULTIAGENCY_ADD_TRSUTEE_USER_MULTIPLE_ROLES');
				$this->setError($errorMsg);

				return false;
			}
		}
		

		$roleList = array();

		foreach ($data['agency_role_map'] as $postData)
		{
			if ($postData['rolelist'])
			{
				$roleList[] = $postData['rolelist'];
			}

			if (empty($postData['client_id']))
			{
				$errorMsg = Text::_('COM_MULTIAGENCY_STAFF_CREATION_SOMETHING_IS_WRONG');
				$this->setError($errorMsg);

				return false;
			}

			if (($postData['rolelist'] == $memberRoleId || $postData['rolelist'] == $MultiagencyTrusteeRoleId) && (!empty($postData['relatedrole'])))
			{
				// Get valid related role
				$licenceAssignedRoles    = $this->getUserAgencyRelatedRolesOptions($postData['client_id']);
				$licenceAssignedRolesIds = array_column((array) $licenceAssignedRoles, 'value');
				$differentRelatedRoles   = array_diff($postData['relatedrole'], $licenceAssignedRolesIds);

				// If invalid related rolefound then throw error message
				if (count($differentRelatedRoles))
				{
					$errorMsg = Text::_('COM_MULTIAGENCY_INVALID_RELATED_ROLES');
					$this->setError($errorMsg);

					return false;
				}
			}

			$allowRoles = $this->getUserAgencyRole($postData['client_id']);

			if (!empty($allowRoles))
			{
				$roleArray = array();

				foreach ($allowRoles as $role)
				{
					$roleArray[] = $role['role_id'];
				}

				$roleTable->load(array('id' => $postData['rolelist']));

				$subUserTable->load(array('user_id' => $data['id'], 'client' => 'com_multiagency', 'client_id' => $postData['client_id']));

				if (!class_exists('MultiagencyFrontendHelpers'))
				{
					$helperPath = JPATH_COMPONENT_SITE . '/helpers/multiagency.php';

					// Require_once $path;
					JLoader::register('MultiagencyFrontendHelpers', $helperPath);
					JLoader::load('MultiagencyFrontendHelpers');
				}

				$helperObject     = new MultiagencyFrontendHelpers;

				// TODO - Update the method which return object instead of array
				$subuserTableData = $helperObject->getRoleClient($data['id'], 'com_multiagency', $postData['client_id'], 1);

				if ($subuserTableData[0]['role_id'] != $postData['rolelist'])
				{
					if (count($roleArray) == 1 && $roleArray[0] == $memberRoleId)
					{
						$errorMsg = Text::sprintf('COM_MULTIAGENCY_ADD_USER_WRONG_ROLE', $roleTable->name, Text::_('COM_MULTIAGENCY_ORGANISATION'));
						$this->setError($errorMsg);

						return false;
					}

					if (!in_array($postData['rolelist'], $roleArray))
					{
						$errorMsg = Text::sprintf('COM_MULTIAGENCY_ADD_USER_WRONG_ROLE', $roleTable->name, Text::_('COM_MULTIAGENCY_ORGANISATION'));
						$this->setError($errorMsg);

						return false;
					}
				}
			}
			elseif (!Factory::getUser()->authorise('core.manageall', 'com_cluster'))
			{
				$errorMsg = Text::_('COM_MULTIAGENCY_STAFF_CREATION_SOMETHING_IS_WRONG');
				$this->setError($errorMsg);

				return false;
			}

		/*
			Comment condition for staff for multiple organisations
			if ($memberRoleId == $postData['rolelist'])
			{
				$memberCount++;
			}
		*/
		}

		if (!count($roleList))
		{
			$this->setError(Text::_('COM_MULTIAGENCY_NO_ROLE_SELECTED'));

			return false;
		}

		/*
		if ($memberCount > 1)
		{
			Comment condition for staff for multiple organisations
			$this->setError(Text::_('COM_MULTIAGENCY_ALLOW_ADD_MEMEBER_ONLY_ONE'));

			return false;

		}
		*/

		$unqiueAgencyMap = array_map("unserialize", array_unique(array_map("serialize", $data['agency_role_map'])));

		if (count($unqiueAgencyMap) != count($data['agency_role_map']))
		{
			$this->setError(Text::_('COM_MULTIAGENCY_ADD_MEMEBER_DUPLICATION'));

			return false;
		}

		return parent::validate($form, $data, $group = null);
	}

	/**
	 * Method to send the user profile updation emails.
	 *
	 * @param   array    $data  data
	 * @param   boolean  $key   tj-notification key
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function userProfileEmail($data,$key)
	{
		$helperObject = new MultiagencyFrontendHelpers;
		$tableHtml = '';
		$tableHtml = '<table
		class="table table-bordered" style="border: 1px solid #ddd;text-align: left;padding: 15px;width: 50%;border-collapse: collapse;">
		<thead>
		  <tr>
			<th style="border: 1px solid #ddd;padding: 8px;">'
			. Text::sprintf('COM_MULTIAGENCY_SCHOOL_NAMES', Text::_('COM_MULTIAGENCY_ORGANISATION')) . '</th>
			<th style="border: 1px solid #ddd;padding: 8px;">'
			. Text::_('COM_MULTIAGENCY_USER_ROLES') . '</th>
		  </tr>
		</thead>
		<tbody>';

		foreach ($data['agency_role_map'] as $userData)
		{
			$schoolName = $helperObject->getmultiagency((int) $userData['client_id']);
			$roleDetail  = SubusersRole::getInstance($userData['rolelist']);

			if (!empty($schoolName))
			{
				$tableHtml .= '<tr><td style="border: 1px solid #ddd;padding: 8px;">' . $schoolName . '</td>
				<td style="border: 1px solid #ddd;padding: 8px;">' . $roleDetail->name . '</td></tr>';
			}
		}

		$tableHtml .= '</tbody></table>';

		$recipients = array (
			// Add specific to, cc (optional), bcc (optional)
			'email' => array (
				'to' => array ($data['email'])
			)
		);

		$options = new Registry;
		$replacements = new stdClass;
		$replacements->user = new stdClass;
		$replacements->user->username = $data['username'];
		$replacements->user->name     = $data['name'];
		$replacements->user->uname    = $data['email'];
		$replacements->user->password = $data['password'];
		$replacements->user->siteurl  = URI::root();
		$replacements->user->body = $tableHtml;

		Tjnotifications::send("com_multiagency", $key, $recipients, $replacements, $options);
	}

	/**
	 * Method to Get roles of users again to selected agency.
	 *
	 * @param   integer  $agencyId  agency id
	 * @param   integer  $userId    user id
	 *
	 * @return  mixed An array of data on success, false on failure.
	 *
	 * @since 1.6
	 */
	public function getUserAgencyRole($agencyId = null, $userId = null)
	{
		$user            = Factory::getUser();
		$params          = ComponentHelper::getParams('com_dpe');
		$allowedRoles    = new Registry($params->get('allowed_roles'));
		$input           = Factory::getApplication()->input;

		if ($agencyId == null)
		{
			$agencyId = $input->get('aid', '0', 'INT');
		}

		if ($userId == null)
		{
			$userId = Factory::getUser()->id;
		}

		if (ComponentHelper::getComponent('com_subusers', true)->enabled)
		{
			if (!class_exists('MultiagencyFrontendHelpers'))
			{
				$helperPath = JPATH_COMPONENT_SITE . '/helpers/multiagency.php';

				// Require_once $path;
				JLoader::register('MultiagencyFrontendHelpers', $helperPath);
				JLoader::load('MultiagencyFrontendHelpers');
			}

			$helperObject = new MultiagencyFrontendHelpers;

			$agencyRoleData = $helperObject->getRoleClient($userId, 'com_multiagency', $agencyId, 1);
			$roles          = array();

			if ($user->authorise('core.manageall', 'com_cluster'))
			{
				foreach ($allowedRoles as $allowedRole)
				{
					$roles = array_unique(array_merge($roles, $allowedRole));
				}
			}
			else
			{
				$roles = $allowedRoles[$agencyRoleData[0]['role_id']];
			}

			$roleList = array();
			$db       = Factory::getDbo();
			$query    = $db->getQuery(true);

			$query->select('tr.id as role_id, tr.name');
			$query->from($db->quoteName('#__tjsu_roles', 'tr'));

			$query->where($db->quoteName('tr.id') . 'IN (' . implode(',', (array) $db->quote($roles)) . ')');

			$db->setQuery($query);

			$roleList = array_merge($roleList, $db->loadAssocList());

			return $roleList;
		}
	}

	/**
	 * Allows preprocessing of the Form object.
	 *
	 * @param   Form   $form   The form object
	 * @param   array   $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function preprocessForm(Form $form, $data, $group = 'content')
	{
		$app    = Factory::getApplication();
		$layout = $app->input->get('layout');
		$user   = Factory::getUser();

		if (ComponentHelper::getComponent('com_subusers', true)->enabled)
		{
			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				$form->removeField('agency_role_map');
			}
		}

		parent::preprocessForm($form, $data, $group);
	}

	/**
	 * Get user agency allowed roles
	 *
	 * @param   integer  $agencyId  Agency Id
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getUserAgencyAllowedRolesOptions($agencyId)
	{
		$roles     = $this->getUserAgencyRole($agencyId);
		$options   = array();
		$options[] = HTMLHelper::_('select.option', "", Text::_('COM_MULTIAGENCY_SELECT_ROLE_OPTION'));

		foreach ($roles as $role)
		{
			$options[] = HTMLHelper::_('select.option', $role['role_id'], $role['name']);
		}

		return $options;
	}

	/**
	 * Get user agency related roles TODO: Need to move to relatedfield
	 *
	 * @param   integer  $agencyId  Agency Id
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getUserAgencyRelatedRolesOptions($agencyId)
	{
		// Create Helper Object
		JLoader::register('MultiagencyFrontendHelpers', JPATH_SITE . '/components/com_multiagency/helpers/multiagency.php');
		$multiAgencyHelper = new MultiagencyFrontendHelpers;

		// Get Licence Assigned role ids
		$licenceAssignedRoleIds = $multiAgencyHelper->getLicenceAssignedRoleIds($agencyId);

		// Get the related role list
		FormHelper::addFieldPath(JPATH_SITE . '/plugins/system/dpe/fields/');
		$relatedrole     = FormHelper::loadFieldType('relatedrole', false);
		$relatedroleList = $relatedrole->getOptionsExternally();

		$options = array();

		foreach ($relatedroleList as $role)
		{
			// Php 8.0 issue 
			
			if (in_array($role->value, (array)$licenceAssignedRoleIds))
			{
				$options[] = HTMLHelper::_('select.option', $role->value, $role->text);
			}
		}

		return $options;
	}
}
