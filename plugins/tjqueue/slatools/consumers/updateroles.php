<?php
/**
 * @package    Techjoomla.Libraries
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;

use \Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

JLoader::import("/components/com_multiagency/includes/multiagency", JPATH_SITE);

/**
 * TjQueue
 *
 * @package     Techjoomla.Libraries
 * @subpackage  Tjqueue
 * @since       __DEPLOY_VERSION__
 */
class TjqueueSlaToolsUpdateRoles
{
	/**
	 * Plugin method with the same name as the event will be called automatically.
	 *
	 * @param   string  $message  A Message
	 *
	 * @return  boolean  This method should return acknowledgement flag
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function consume($message)
	{
		$messageBody      = $message->getBody();
		$messageData      = new Registry($messageBody);
		$messageData      = $messageData->toArray();

		// Create Helper Object
		JLoader::register('MultiagencyFrontendHelpers', JPATH_SITE . '/components/com_multiagency/helpers/multiagency.php');
		$multiAgencyHelper = new MultiagencyFrontendHelpers;

		// Get Licence assigned Role Ids
		$licenceAssignedRoleIds = $multiAgencyHelper->getLicenceAssignedRoleIds($messageData['agencyId']);

		// Get user assigned role ids
		$userAssignedRoleIds = $multiAgencyHelper->getRoleClient($messageData['userId'], 'com_multiagency', $messageData['agencyId']);

		// Get User assigned Core role id
		$userAssignedCoreRoleId = $multiAgencyHelper->getRoleClient($messageData['userId'], 'com_multiagency', $messageData['agencyId'], 1);

		if (is_array($userAssignedRoleIds) && !empty($userAssignedRoleIds))
		{
			$userAssignedRoleIds = array_column($userAssignedRoleIds, 'role_id');
		}

		$params              = ComponentHelper::getParams('com_dpe');
		$multiagencyParams   = ComponentHelper::getParams('com_multiagency');
		$dpeTools            = new Registry($params->get('dpe_role_ids'));
		$dpeTools            = $dpeTools->toArray();
		$savedToolsRoleIds   = $dpeTools[$userAssignedCoreRoleId[0]['role_id']];
		$memberRoleId        = $multiagencyParams->get('member_role_id', 'INT');
		$trusteeRoleId       = $multiagencyParams->get('organization_trustee_role_id', 'INT');

		// Tools role ids saved into tjsu table
		$roleIds = array_intersect($savedToolsRoleIds, $userAssignedRoleIds);

		// Licence role id as per core role
		$licenceRoleIds = array_intersect($savedToolsRoleIds, (array)$licenceAssignedRoleIds);

		// Code to check the Staff and Trustee related Role
		if ($userAssignedCoreRoleId[0]['role_id'] == $memberRoleId || $userAssignedCoreRoleId[0]['role_id'] == $trusteeRoleId)
		{
			$prevRelatedRoleData = $multiAgencyHelper->getRelatedRoleUserData(
			$messageData['userId'], 'com_multiagency', $messageData['agencyId'], $userAssignedCoreRoleId[0]['role_id']
			);
			$prevRelatedRoleData = array_column($prevRelatedRoleData, 'role_id');
			$removeRelatedRoles  = array_diff($prevRelatedRoleData, (array)$licenceAssignedRoleIds);

			// If configured core rols is not available in the assigned tools then remove
			if (empty($licenceRoleIds))
			{
				$removeRelatedRoles = array_merge($removeRelatedRoles, $savedToolsRoleIds);
			}

			// Remove user from tjsu users
			foreach ($removeRelatedRoles as $removeRelatedRole)
			{
				$mappedData = $multiAgencyHelper->getIdsUserAgencyRoleMap($messageData['userId'], $removeRelatedRole, $messageData['agencyId']);

				// Remove mappedData of users based on multiagency client_id
				foreach ($mappedData as $suid)
				{
					$tableInstance = RBACL::table('user');
					$tableInstance->delete($suid);
				}

				$mappedData = $multiAgencyHelper->getIdsUserAgencyRoleMap($messageData['userId'], $removeRelatedRole, $messageData['clusterId'], 'com_cluster');

				// Remove mappedData of users based on cluster client_id
				foreach ($mappedData as $suid)
				{
					$tableInstance = RBACL::table('user');
					$re            = $tableInstance->delete($suid);
				}
			}

			// Code for add roles to staff
			$additionalRole    = new Registry($params->get('additional_role'));
			$additionalRoleIds = $additionalRole[$userAssignedCoreRoleId[0]['role_id']];

			// Get current saved roles
			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
			$currentRoleIds = RBACL::getRoleByUser($messageData['userId'], 'com_multiagency', $messageData['agencyId']);

			// Check additional role is available in currentroles or not
			$isAdditionalRoleAvailable = array_intersect($currentRoleIds, $additionalRoleIds);

			// If additional role not available then add
			if (empty($isAdditionalRoleAvailable))
			{
				$licenceRoleIds = array_merge($additionalRoleIds, $licenceRoleIds);
			}

			sort($roleIds);
			sort($licenceRoleIds);

			if ($roleIds != $licenceRoleIds)
			{
				// Add subusers entries for below client context
				$subUserRoles  = array('com_multiagency','com_cluster');
				$userFormModel = Multiagency::model('userform', array('ignore_request' => true));

				// Add user to tjsu users table for additional roles
				foreach ($licenceRoleIds as $licenceRoleId)
				{
					foreach ($subUserRoles as $content)
					{
						$asssignUserData            = array();
						$asssignUserData['user_id'] = $messageData['userId'];

						if ($content == 'com_multiagency')
						{
							$asssignUserData['client_id'] = $messageData['agencyId'];
						}
						else
						{
							$asssignUserData['client_id'] = $messageData['clusterId'];
						}

						$asssignUserData['content'] = $content;
						$asssignUserData['role_id'] = $licenceRoleId;

						// Assign user to role
						$userFormModel->assignRoleToUser($asssignUserData);
					}
				}
			}
		}
		else
		{
			// Sort the array role ids
			sort($roleIds);
			sort($licenceRoleIds);

			if ($roleIds != $licenceRoleIds)
			{
				$userFormModel       = Multiagency::model('userform', array('ignore_request' => true));
				$oldRoleIdsForRemove = array_diff($roleIds, $licenceRoleIds);
				$newRoleIdsForAdd    = array_diff($licenceRoleIds, $roleIds);

				$additionalRole = new Registry($params->get('additional_role'));

				if (count($additionalRole[$userAssignedCoreRoleId[0]['role_id']]))
				{
					JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
					$currentRoleIds = RBACL::getRoleByUser($messageData['userId'], 'com_multiagency', $messageData['agencyId']);

					// Check additional roles are available or not
					$isAdditionalAvailable = array_intersect($currentRoleIds, $additionalRole[$userAssignedCoreRoleId[0]['role_id']]);

					// If additional roles are not available to the user then add
					if (empty($isAdditionalAvailable))
					{
						$newRoleIdsForAdd = array_merge($newRoleIdsForAdd, $additionalRole[$userAssignedCoreRoleId[0]['role_id']]);
					}
				}

				JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
				$result = RBACL::getRoleByUser($messageData['userId'], 'com_multiagency', $messageData['agencyId']);

				// Remove user from tjsu users
				foreach ($oldRoleIdsForRemove as $oldRoleIdForRemove)
				{
					$mappedData = $multiAgencyHelper->getIdsUserAgencyRoleMap($messageData['userId'], $oldRoleIdForRemove, $messageData['agencyId']);

					// Remove mappedData of users based on multiagency client_id
					foreach ($mappedData as $suid)
					{
						$tableInstance = RBACL::table('user');
						$tableInstance->delete($suid);
					}

					$mappedData = $multiAgencyHelper->getIdsUserAgencyRoleMap($messageData['userId'], $oldRoleIdForRemove, $messageData['clusterId'], 'com_cluster');

					// Remove mappedData of users based on cluster client_id
					foreach ($mappedData as $suid)
					{
						$tableInstance = RBACL::table('user');
						$re            = $tableInstance->delete($suid);
					}
				}

				// Add subusers entries for below client context
				$subUserRoles = array('com_multiagency','com_cluster');

				// Add user to tjsu users table for new roles
				foreach ($newRoleIdsForAdd as $newRoleIdForAdd)
				{
					foreach ($subUserRoles as $content)
					{
						$asssignUserData            = array();
						$asssignUserData['user_id'] = $messageData['userId'];

						if ($content == 'com_multiagency')
						{
							$asssignUserData['client_id'] = $messageData['agencyId'];
						}
						else
						{
							$asssignUserData['client_id'] = $messageData['clusterId'];
						}

						$asssignUserData['content'] = $content;
						$asssignUserData['role_id'] = $newRoleIdForAdd;

						// Assign user to role
						$userFormModel->assignRoleToUser($asssignUserData);
					}
				}
			}
		}

		return true;
	}
}
