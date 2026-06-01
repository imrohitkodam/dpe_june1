<?php
/**
 * @package    Com_Cluster
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('/components/com_subusers/includes/rbacl', JPATH_ADMINISTRATOR);
JLoader::import('/components/com_cluster/includes/cluster', JPATH_ADMINISTRATOR);
JLoader::import('/components/com_tjfields/tables', JPATH_ADMINISTRATOR);
JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

/**
 * Get Cluster Users controller class.
 *
 * @since  1.0.0
 */
class ClusterControllerDporesponding extends BaseController
{
	/**
	 * Method to get user list depending on the client chosen.
	 *
	 * @return   null
	 *
	 * @since    1.0.0
	 */
	public function getDpoResponding()
	{
		$app                     = Factory::getApplication();
		$params                  = ComponentHelper::getParams('com_multiagency');
		$organisationAdminRoleId = $params->get('school_admin_role_id', '0', 'INT');

		// Check for request forgeries.
		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$clusterUserData        = $app->input->get('clusterUserData', array(), 'ARRAY');
		$dpoRespondingFieldName = str_replace('jform_', '', $app->input->get('dporespondingFieldId', ''));

		if ($clusterUserData['cluster_id'])
		{
			$clusterId = $clusterUserData['cluster_id'];
		}

		$userOptions = $allUsers = array();

		// Get SLA details
		$slaClusterXrefTable = SlaFactory::table('slaclusterxrefs');
		$slaClusterXrefTable->load(array('cluster_id' => $clusterId));

		// Get SLA details
		if (! property_exists($slaClusterXrefTable, 'license_id') || (!$clusterId))
		{
			echo new JsonResponse($userOptions);
			$app->close();
		}

		$slaTable = SlaFactory::table('slas');
		$slaTable->load(array('id' => $slaClusterXrefTable->sla_id));

		if (!property_exists($slaTable, 'id'))
		{
			echo new JsonResponse($userOptions);
			$app->close();
		}

		$params = json_decode($slaTable->params);

		if (is_object($params) && property_exists($params, 'kbonly'))
		{
			// Get users by roles
			$subusersModelUsers = RBACL::model('users', array('ignore_request' => true));
			$subusersModelUsers->setState('filter.client_id', $clusterId);
			$subusersModelUsers->setState('filter.role_id', $organisationAdminRoleId);
			$subusersModelUsers->setState('filter.client', 'com_cluster');
			$subusersModelUsers->setState('group_by', 'user_id');
			$subusersModelUsers->setState('filter.state', 0);
			$clusterUsers = $subusersModelUsers->getItems();

			if (!empty($clusterUsers))
			{
				foreach ($clusterUsers as $user)
				{
					$userOptions[] = HTMLHelper::_('select.option', trim($user->name), trim($user->name));
				}
			}
		}
		else
		{
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjfields/tables');
			$fieldTbl = Table::getInstance('Field', 'TjfieldsTable');
			$fieldTbl->load(array('name' => $dpoRespondingFieldName));
			$options = array();

			if (property_exists($fieldTbl, 'id'))
			{
				$options = $this->getOptions($fieldTbl->id);
			}

			foreach ($options as $option)
			{
				$userOptions[] = HTMLHelper::_('select.option', trim($option->value), trim($option->options));
			}
		}

		echo new JsonResponse($userOptions);
		$app->close();
	}

	/** Get option which are stored in field option table.
	 *
	 * @param   INT  $fieldId  field id
	 *
	 * @return array of option for the particular field
	 */
	public function getOptions($fieldId)
	{
		if ($fieldId)
		{
			$db = Factory::getDbo();

			$query = $db->getQuery(true);

			$query
				->select(array('options', 'value'))
				->from($db->quoteName('#__tjfields_options'))
				->where('field_id=' . (int) $fieldId)
				->order('ordering', 'ASC');

			$db->setQuery($query);

			return $db->loadObjectList();
		}
	}
}
