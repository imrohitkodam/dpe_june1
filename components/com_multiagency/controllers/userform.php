<?php

/**
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

require_once JPATH_COMPONENT . '/controller.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Plugin\PluginHelper;


/**
 * User controller class.
 *
 * @since  1.6
 */
class MultiagencyControllerUserForm extends FormController
{
	/**
	 * Method to check out an item for editing and redirect to the edit form.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	public function edit($key = null, $urlVar = null)
	{
		$app = Factory::getApplication();

		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $app->getUserState('com_multiagency.edit.userform.data.id');
		$editId     = $app->input->getInt('id', 0);

		// Set the user id for the user to edit in the session.
		$app->setUserState('com_multiagency.edit.userform.data.id', $editId);

		// Get the model.
		$model = $this->getModel('UserForm', 'MultiagencyModel');

		// Check out the item
		if ($editId)
		{
			$model->checkout($editId);
		}

		// Check in the previous user.
		if ($previousId)
		{
			$model->checkin($previousId);
		}

		$app->setUserState('com_multiagency.edit.userform.data', null);

		// Redirect to the edit screehttp://ttpl62-php7.local/dpe/index.php/component/multiagency/usersn.
		$this->setRedirect(Route::_('index.php?option=com_multiagency&view=userform&layout=edit&id=' . $editId, false));
	}

	/**
	 * Method to save a user's profile data.
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since  1.6
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app   = Factory::getApplication();
		$itemId = $app->getMenu()->getActive()->id;
		$user  = Factory::getUser();

		$isDpeAdmin = $user->authorise('core.manageall', 'com_cluster');
		$view       = $isDpeAdmin ? 'edit' : 'default_userform';

		$model = $this->getModel('UserForm', 'MultiagencyModel');
		$data = $app->input->get('jform', array(), 'array');

		// DPE Hack Start
		// If "User created" is checked, find existing user by email
		if (!empty($data['user_created']) && !empty($data['email']))
		{
			$db = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('id')
				->from('#__users')
				->where('email = ' . $db->quote($data['email']));
			$db->setQuery($query);
			$foundUserId = (int) $db->loadResult();

			if ($foundUserId)
			{
				$foundUser = Factory::getUser($foundUserId);
				$data['id'] = $foundUserId;
				$data['name'] = $foundUser->name;
				$data['username'] = $foundUser->username;
				// Also update the session state so model knows it's an edit
				$app->setUserState('com_multiagency.edit.userform.data.id', $foundUserId);
			}
			else
			{
				$app->setUserState('com_multiagency.edit.userform.data', $data);
				$app->enqueueMessage(Text::_('COM_MULTIAGENCY_ERROR_USER_NOT_FOUND_FOR_TAGS'), 'error');
				$this->setRedirect(Route::_('index.php?option=com_multiagency&view=userform&layout=' . $view . '&Itemid=' . $itemId, false));

				return false;
			}
		}
		// DPE Hack End

		$id = (!empty($data['id'])) ? $data['id'] : (int) $app->getUserState('com_multiagency.edit.userform.data.id');


		// Get user groups as per name
		$leadConsultantGroupId = 0;
		$leadConsultantGroup = Table::getInstance('Usergroup', 'JTable');
		$leadConsultantGroup->load(array('title' => 'External Lead Consultant'));
		$usersGroups = Factory::getUser($id)->groups;

		// Get component prams
		$params                   = ComponentHelper::getParams('com_multiagency');
		$MultiagencyTrusteeRoleId = (INT) $params->get('organization_trustee_role_id');
		$orgTrusteeGroup          = (int) $params->get('multiagency_trustee_group', '0', 'INT');
		$orgAdminRoleId           = (int) $params->get('school_admin_role_id', '0', 'INT');

		if (property_exists($leadConsultantGroup, 'id'))
		{
			$leadConsultantGroupId = $leadConsultantGroup->id;
		}


		if (in_array($leadConsultantGroupId, $usersGroups))
		{
			$app->setUserState('com_multiagency.edit.userform.data', $data);
			$app->enqueueMessage(Text::_('COM_MULTIAGENCY_LC_CANT_EDIT'), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_multiagency&view=userform&layout=' . $view . '&id=' . $id . '&Itemid=' . $itemId, false));

			return false;
		}

		// Redirect back to the edit screen.
		if ($id == $user->id)
		{
			$app->setUserState('com_multiagency.edit.userform.data', $data);
			$app->enqueueMessage(Text::sprintf('COM_MULTIAGENCY_EDIT_OWN_DETAILS', Text::_('COM_MULTIAGENCY_ORGANISATION')), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_multiagency&view=userform&layout=' . $view . '&id=' . $id . '&Itemid=' . $itemId, false));

			return false;
		}

		// Allow to users to create Trustee who have access to all cluseter
		$postDataRoleList = array_column($data['agency_role_map'], 'rolelist');

		// Get subusers actions mapp
		$userRoleId = RBACL::getRoleByUser($userId, 'com_multiagency', 0);
		
		if (empty($userRoleId))
		{
			$userRoleId = RBACL::getRoleByUser($userId, 'com_multiagency', $agencyId);
		}

		$userRoleIds = RBACL::getRoleByUser($id, 'com_multiagency');

		if (in_array($MultiagencyTrusteeRoleId, $userRoleIds))
		{
			/*
			if ($user->authorise('core.manageall', 'com_cluster') && count(array_unique($postDataRoleList)) > 1)
			{
				$app->enqueueMessage(Text::_('COM_MULTIAGENCY_ADD_TRSUTEE_USER_MULTIPLE_ROLES'), 'warning');
				$this->setRedirect(Route::_('index.php?option=com_multiagency&view=userform&layout=edit&id=' . $id . '&Itemid=' . $itemId, false));

				return false;
			}
			*/
			$postDataRoleList   = array_diff($postDataRoleList, $userRoleIds);

			if (!in_array($MultiagencyTrusteeRoleId, $postDataRoleList) && in_array($MultiagencyTrusteeRoleId, $userRoleIds) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				// Get all roles in system for users
				$currentUserAgencyIds = RBACL::getAgencyByUser($id, 'com_multiagency');
				$postDataAgencyList   = array_column($data['agency_role_map'], 'client_id');
				$otherAgencyList      = array_diff($currentUserAgencyIds, $postDataAgencyList);

				if (!empty($otherAgencyList))
				{
					$otherAgencyRoleIds   = RBACL::getRoleByUser($id, 'com_multiagency', $otherAgencyList);
					$postDataRoleList     = array_diff($postDataRoleList, $otherAgencyRoleIds);

					if (count(array_unique($postDataRoleList)) && in_array($MultiagencyTrusteeRoleId, $otherAgencyRoleIds))
					{
						$app->enqueueMessage(Text::_('COM_MULTIAGENCY_ADD_TRSUTEE_USER_MULTIPLE_ROLES_MUL_ORG'), 'warning');
						$this->setRedirect(Route::_('index.php?option=com_multiagency&view=userform&layout=' . $view . '&id=' . $id . '&Itemid=' . $itemId, false));

						return false;
					}
				}
			}
		}

		/*
		if ((in_array($MultiagencyTrusteeRoleId, $postDataRoleList))
			&& (!$user->authorise('core.manageall', 'com_cluster'))
			&& (!in_array($orgAdminRoleId, $userRoleId)))
		{
			$app->setUserState('com_multiagency.edit.userform.data', $data);
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_multiagency&view=userform&layout=edit&id=' . $id . '&Itemid=' . $itemId, false));

			return false;
		}
		*/

		// Check license is available for school
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
		$licenceTable = Table::getInstance('licence', 'MultiagencyTable');

		foreach ($data['agency_role_map'] as $key => $agencyDetail)
		{
			$licenceTable->load(array('multiagency_id' => $agencyDetail['client_id']));

			if ($licenceTable->id)
			{
				$agenciesId[]     = $agencyDetail['client_id'];
			}
			elseif (!$id)
			{
				unset($data['agency_role_map'][$key]);
			}

			if (empty($agencyDetail['rolelist']))
			{
				unset($data['agency_role_map'][$key]);
			}
		}

		// Redirect back to the edit screen.
		if (count(array_unique((array) $agenciesId)) < count((array)$agenciesId))
		{
			$app->setUserState('com_multiagency.edit.userform.data', $data);
			$app->enqueueMessage(Text::sprintf('COM_MULTIAGENCY_ADD_USER_MULTIPLE_USER_ROLE', Text::_('COM_MULTIAGENCY_ORGANISATION')), 'warning'); 
			$this->setRedirect(Route::_('index.php?option=com_multiagency&view=userform&layout=' . $view . '&id=' . $id . '&Itemid=' . $itemId, false));

			return false;
		}

		// Validate the posted data.
		$form = $model->getForm();

		if (!$form)
		{
			throw new Exception($model->getError(), 500);
		}


	// DPE Hack Start
	// Check toggle value from your radio button
	$useTags = isset($data['use_tags']) ? $data['use_tags'] : '';
	
	// If toggle is off, clear the tags field and make it not required
	if($useTags && !empty($data['agency_role_map']))
	{
		$data['tags']= array();
		$data['jobtitle']=0;
		$data['rolelist']=0;
		$data['dpelead']=0;
	}
	elseif ($useTags == 0 || !empty($data['user_created']))
	{
		$clientIds = [];
		if (!empty($data['cluster_ids']))
		{
			$clientIds = explode(',', $data['cluster_ids']);
		}

		$existingAssignments = [];
		if (!empty($id))
		{
			$existingAssignments = $model->loadSubFormData($id);
		}

		$data['agency_role_map'] = [];
		$count = 0;
		$existingClientIds = [];

		if (!empty($existingAssignments))
		{
			foreach ($existingAssignments as $assignment)
			{
				$key = 'agency_role_map' . $count;
				$data['agency_role_map'][$key] = [
					'client_id' => $assignment['client_id'],
					'rolelist'  => $assignment['role_id']
				];
				$existingClientIds[] = (int) $assignment['client_id'];
				$count++;
			}
		}

		// Optimization: Move model instantiation outside the loop
		$clusterModel = null;
		$schoolModel = null;
		$clusterJobIdsCache = [];

		if (!empty($data['jobtitle']))
		{
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/models');
			$clusterModel = BaseDatabaseModel::getInstance('Cluster', 'ClusterModel');

			BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/components/com_dpe/models');
			$schoolModel = BaseDatabaseModel::getInstance('School', 'DpeModel');
		}

		foreach ($clientIds as $index => $clientId)
		{
			$clientId = (int) $clientId;

			// Skip if already assigned to prevent duplicates
			if (in_array($clientId, $existingClientIds))
			{
				continue;
			}

			$row = [];
			$row['client_id'] = $clientId;

			if (!empty($data['jobtitle']) && $clusterModel && $schoolModel)
			{
				$clusterIdResult = $clusterModel->getClusterByClient($client = null, $clientId);
				$clusterId = $clusterIdResult->id;

				if (!isset($clusterJobIdsCache[$clusterId]))
				{
					$jobTitle = $schoolModel->getJobTitlesByClusterId($clusterId);
					$clusterJobIdsCache[$clusterId] = array_column(json_decode(json_encode($jobTitle), true), 'id');
				}

				// Check if jobtitle exists
				if (in_array($data['jobtitle'], $clusterJobIdsCache[$clusterId]))
				{
					$row['jobtitle'] = $data['jobtitle'];
				}
			}

			if (!empty($data['relatedrole']))
			{
				// Get valid related role
				$licenceAssignedRoles    = $model->getUserAgencyRelatedRolesOptions($clientId);
				$licenceAssignedRolesIds = array_column((array) $licenceAssignedRoles, 'value');

				$row['relatedrole'] = array_intersect($licenceAssignedRolesIds, $data['relatedrole']);
			}

			$row['rolelist'] = $data['rolelist'];
			$row['dpelead']  = $data['dpelead'];

			$data['agency_role_map']['agency_role_map' . $count] = $row;
			$count++;
		}
	}
	else
	{

		$data['tags']= array();
		$data['rolelist']=0;
	}
	// DPE Hack End

		// Validate the posted data.
		$validData = $model->validate($form, $data);

		if ($validData == false)
		{
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			// Save the data in the session.
			$app->setUserState('com_multiagency.edit.userform.data', $data);

			$this->setRedirect(Route::_('index.php?option=com_multiagency&view=userform&layout=' . $view . '&id=' . $id . '&Itemid=' . $itemId, false));

			return false;
		}

		$validData['agency_role_map'] = $data['agency_role_map'];

		// Attempt to save the data.
		$return = $model->save($validData);

		if ($return == false)
		{

			$app->setUserState('com_multiagency.edit.userform.data', $data);
			$this->setMessage(Text::_($model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_multiagency&view=userform&layout=' . $view . '&id=' . $id . '&Itemid=' . $itemId, false));
		}

		// Check in the profile.
		if ($return)
		{
			// DPE jobtitle work
			BaseDatabaseModel::addIncludePath(JPATH_ROOT . '/components/com_dpe/models');
			$schoolModel = BaseDatabaseModel::getInstance('School', 'DpeModel');
			$userId     = empty($return) ? $data['id']: $return; 

			
			$listOfValidData = array_values($validData['agency_role_map']);

			for ($index = 0; $index < count($listOfValidData); $index++)
			{ 
				Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');
			    $clustertableInstance  = Table::getInstance('Clusters', 'ClusterTable');
				$clustertableInstance->load(array('client_id' => $listOfValidData[$index]['client_id']));
			    $clusterId = $clustertableInstance->id;
			    $schoolModels = BaseDatabaseModel::getInstance('School', 'DpeModel');
				$userJobTitle = $schoolModels->getJobTitlebyUserData($clusterId, $userId);

				$oldJobtitleStatus[$index] = $userJobTitle['ucm_id'];

				$key = 'agency_role_map' . $index;
				$usersJobtitleData[$index] = array('clusterId' => $listOfValidData[$index]['client_id'], 
												'user_id' => $return, 'ucm_id' => $listOfValidData[$index]['jobtitle'],'dpelead' =>$listOfValidData[$index]['dpelead']);
			}

			$saveResult = $schoolModel->saveUsersJobTitleDetails($usersJobtitleData, $userId);

			// DPE jobtitle work end;
			$validData['id'] = $return;

			//DPE UserOnboarding  start
			foreach($oldJobtitleStatus as $key => $jobtitle)
			{
				// If the Job title is not new then the Sets will not be assigned.
				if($jobtitle != $usersJobtitleData[$key]['ucm_id'])
				{
					PluginHelper::importPlugin('system', 'dpe');
	  			    $queryResult = Factory::getApplication()->triggerEvent('onUserOnboardAssignSet', array($usersJobtitleData[$key], $validData));
				}

			}

			
			// DPE Useronboarding End

			$model->checkin($return);
			$userData = Factory::getUser($userId);
			$userData = json_decode(json_encode($userData));
			$isNew = ($userData->requireReset)?$userData->requireReset:'';
//DPE UserOnboarding  start
			if($isNew)
			{
					$usersJobtitleData[$key]['isNew'] = $isNew;
					PluginHelper::importPlugin('system', 'dpe');
	  			    $queryResult = Factory::getApplication()->triggerEvent('onUserOnboardAssignSet', array($usersJobtitleData[$key], $validData));
				}
// DPE Useronboarding End
			$userString = json_encode($userData);
			$userDataArray = json_decode($userString, true);

			PluginHelper::importPlugin('plug_usr_mailalert');
			$results = Factory::getApplication()->triggerEvent('onUserAfterSave', array($userDataArray, $isNew, $return, $error));
		}

		// Clear the profile id from the session.
		$app->setUserState('com_multiagency.edit.userform.data', null);
		$message = Text::sprintf('COM_MULTIAGENCY_REGISTRATION_USER_ASSIGNED', Text::_('COM_MULTIAGENCY_ORGANISATION'));

		if ($data['id'])
		{
			$message = Text::_('COM_MULTIAGENCY_USER_UPDATED_SUCCESSFULLY');
		}

		$this->setRedirect(
			Route::_('index.php?option=com_multiagency&view=users', false),
			$message, 'message'
		);
	}

	/**
	 * Method to abort current operation
	 *
	 * $key is null 
	 * 
	 * @return void
	 *
	 * @throws Exception
	 */
	public function cancel($key = null)
	{
		$app = Factory::getApplication();

		// Get the current edit id.
		$editId = (int) $app->getUserState('com_multiagency.edit.userform.data.id');

		// Get the model.
		$model = $this->getModel('UserForm', 'MultiagencyModel');

		// Check in the item
		if ($editId)
		{
			$model->checkin($editId);
		}

		$menu = Factory::getApplication()->getMenu();
		$item = $menu->getActive();
		$url  = (empty($item->link) ? 'index.php?option=com_multiagency&view=users' : $item->link);
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Method to check out an email is already exist or not.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	public function validateEmail()
	{
		// Check for request forgeries.
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$user  = Factory::getUser();

		// Check the user can edit this item
		$authorised = $user->authorise('core.own.adduser', 'com_multiagency')
		|| $user->authorise('core.adduser', 'com_multiagency')
		|| $user->authorise('core.own.edituser', 'com_multiagency')
		|| $user->authorise('core.edituser', 'com_multiagency');

		if ($authorised !== true)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$app = Factory::getApplication();
		$emailId	= $app->input->get('email', '', 'STRING');

		// Get the model.
		$model = $this->getModel('UserForm', 'MultiagencyModel');

		if ( $model->validateEmail($emailId))
		{
			// If already present
			echo new JsonResponse("failure");
		}
		else
		{
			// New email id found
			echo new JsonResponse("success");
		}

		jexit();
	}

	/**
	 * Method to check out an username is already exist or not.
	 *
	 * @return void
	 *
	 * @since    1.6
	 */
	public function validateUserName()
	{
		$app		= Factory::getApplication();
		$userName	= $app->input->get('username', '', 'STRING');

		// Get the model.
		$model = $this->getModel('UserForm', 'MultiagencyModel');

		if ( $model->validateUserName($userName))
		{
			// If already present
			echo "failure";
			exit();
		}
		else
		{
			// New user name found
			echo "success";
			exit();
		}

		exit;
	}

	/**
	 * Method to Get roles of users again to selected agency.
	 *
	 * @param   integer  $agencyId  agency id
	 * @param   integer  $userId    user id
	 * @param   integer  $roleId    selected role id
	 *
	 * @return 	mixed
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function getUserAgencyRoleList($agencyId = null, $userId = null, $roleId = null)
	{
		$model = $this->getModel('UserForm', 'MultiagencyModel');
		$roles = $model->getUserAgencyRole();
		$options = '<option value="" selected="selected">' . Text::_('COM_MULTIAGENCY_SELECT_ROLE_OPTION') . '</option>';

		if ($roleId == null)
		{
			$input = Factory::getApplication()->input;
			$roleId = $input->get('role', '0', 'INT');
		}

		foreach ($roles as $role)
		{
			if ($roleId == $role['role_id'])
			{
				$options .= '<option selected="selected" value="' . $role['role_id'] . '">' . $role['name'] . '</option>';
			}
			else
			{
				$options .= '<option value="' . $role['role_id'] . '">' . $role['name'] . '</option>';
			}
		}

		echo json_encode($options);
		jexit();
	}

	/**
	 * Method to get agency user related role list
	 *
	 * @return void
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function getUserAgencyRelatedRoleList()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app      = Factory::getApplication();
		$agencyId = $app->input->get('agencyId', 0, 'INT');
		$model    = $this->getModel('UserForm', 'MultiagencyModel');

		// Create Helper Object
		JLoader::register('MultiagencyFrontendHelpers', JPATH_SITE . '/components/com_multiagency/helpers/multiagency.php');
		$multiAgencyHelper = new MultiagencyFrontendHelpers;

		// Get Licence Assigned role ids
		$licenceAssignedRoleIds = $multiAgencyHelper->getLicenceAssignedRoleIds($agencyId);

		// Get the related role list
		FormHelper::addFieldPath(JPATH_SITE . '/plugins/system/dpe/fields/');
		$relatedrole           = FormHelper::loadFieldType('relatedrole', false);
		$relatedroleList = $relatedrole->getOptionsExternally();

		$options = '';

		foreach ($relatedroleList as $role)
		{
			if (in_array($role->value, (array) $licenceAssignedRoleIds))
			{
				$options .= '<option value="' . $role->value . '">' . $role->text . '</option>';
			}
		}

		echo new JsonResponse($options);
		$app->close();
	}
}
