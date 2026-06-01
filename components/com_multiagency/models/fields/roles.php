<?php
/**
 * @package     Multiagency
 * @subpackage  Subusers
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of allocated agencies
 *
 * @since  __DEPLOY_VERSION__
 */
class JFormFieldRoles extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.0.0
	 */
	protected $type = 'roles';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$app = Factory::getApplication();
		$user = Factory::getUser();
		$params = $app->getParams('com_multiagency');
		$agencyId = 0;

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));

		$params               = ComponentHelper::getParams('com_multiagency');
		$memberRole           = (int) $params->get('member_role_id', '0', 'INT');
		$leadConsultantRoleId = (int) $params->get('organization_lead_consultant_role_id', '0', 'INT');
		$trusteeRoleId        = (int) $params->get('organization_trustee_role_id', '0', 'INT');
		$orgAdminRoleId       = (int) $params->get('school_admin_role_id', '0', 'INT');
		$orgTrusteeGroup      = (int) $params->get('multiagency_trustee_group', '0', 'INT');

		// Get logged-in user associated agencies
		$allowedAgencies = $MultiagencyModel->getAllocatedAgencies($user->id, array($memberRole, $leadConsultantRoleId));
		$agencies = array_column($allowedAgencies, 'id');

		if (count($agencies) == 1)
		{
			$agencyId = $agencies[0];
		}

		$userId      = $app->input->getInt('id', 0);
		$usersGroups = Factory::getUser($userId)->groups;

		if (!$user->authorise('core.manageall', 'com_cluster')
			&& $app->input->get('view') == 'userform' && $userId)
		{
			$allowedAgency = $sortedAgencies = array();

			if (!empty($allowedAgencies))
			{
				if (in_array($orgTrusteeGroup, $usersGroups))
				{
					foreach ($allowedAgencies as $agency)
					{
						// If logged in user id is org admin or DPE admin
						if ($orgAdminRoleId == $agency->role_id)
						{
							$allowedAgency[$agency->id] = $agency->id;
						}
					}
				}
				else
				{
					foreach ($allowedAgencies as $agency)
					{
						$allowedAgency[$agency->id] = $agency->id;
					}
				}
			}

			if (! class_exists('MultiagencyFrontendHelpers'))
			{
				// Require_once $path;
				JLoader::register('MultiagencyFrontendHelpers', JPATH_COMPONENT_SITE . '/helpers/multiagency.php');
				JLoader::load('MultiagencyFrontendHelpers');
			}

			$helperObject = new MultiagencyFrontendHelpers;
			$userAgencies = $helperObject->getRoleClient($userId, 'com_multiagency');

			foreach ($userAgencies as $k => $v)
			{
				if ($allowedAgency[$v['client_id']])
				{
					$sortedAgencies[] = $v['client_id'];
				}
			}

			$recordNumber = preg_replace('/[^0-9]/', '', $this->name);

			foreach ($sortedAgencies as $key => $agency)
			{
				if ($recordNumber == $key)
				{
					$agencyId = $agency;
					break;
				}
			}
		}

		// Initialize array to store dropdown options
		$options = array();

		if ($agencyId)
		{
			$userFormModel = BaseDatabaseModel::getInstance('UserForm', 'MultiagencyModel', array('ignore_request' => true));
			$roles = $userFormModel->getUserAgencyRole($agencyId);
		}
		else
		{
			$db = Factory::getDBO();
			$subInQuery = $db->getQuery(true);
			$subInQuery->select('id as role_id, name')
			->from($db->quoteName('#__tjsu_roles'))
			->where($db->quoteName('state') . ' = 1')
			->where($db->quoteName('client') . ' = "com_multiagency"')
			->order($db->quoteName('ordering') . ' ASC');

			$db->setQuery($subInQuery);
			$roles = $db->loadAssocList();
		}

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			/*
				Allow to create users with Role Trustee to whom of have manage all permission to cluster
				And to org Admin
				GetUserAgencyRole Method cant handle this as Trustee is higher role with Less access i.e actions
			*/
			/*
			$key = array_search($trusteeRoleId, array_column($roles, 'role_id'));

			if (is_numeric($key) && !in_array($orgAdminRoleId, array_column($roles, 'role_id')))
			{
				unset($roles[$key]);
			}
			*/
		}

		/*
		if (in_array($orgTrusteeGroup, $usersGroups))
		{
			// Show only Trustee role while editing any trsutee
			$roleTable = RBACL::table('role');
			$roleTable->load(array('id' => $trusteeRoleId));
			$roles = array('0' => array('role_id' => $trusteeRoleId, 'name' => $roleTable->name));
		}
		*/

		$options[] = HTMLHelper::_('select.option', "", Text::_('COM_MULTIAGENCY_SELECT_ROLE_OPTION'));

		foreach ($roles as $role)
		{
			$options[] = HTMLHelper::_('select.option', $role['role_id'], $role['name']);
		}

		return $options;
	}
	
	/**
	 * Method to get a list of Agency options for a list input externally and not from xml.
	 *
	 * @return array  An array of HTMLHelper options.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getOptionsExternally()
	{
		$this->loadExternally = 1;

		return $this->getOptions();
	}
}
