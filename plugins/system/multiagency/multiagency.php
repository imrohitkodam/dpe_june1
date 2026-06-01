<?php
/**
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2020 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Table\Table;

jimport('joomla.filesystem.file');
jimport('joomla.html.parameter');
jimport('joomla.plugin.plugin');

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

// Load language file for plugin.
$lang = Factory::getLanguage();
$lang->load('plg_system_multiagency', JPATH_ADMINISTRATOR);
$lang->load('com_tjlms', JPATH_SITE);

/**
 * Methods supporting a list of Tjlms action.
 *
 * @since  1.0.0
 */
class PlgSystemMultiagency extends CMSPlugin
{
	/**
	 * Function used as a trigger after user successfully enrolled  for a course.
	 *
	 * @param   INT  $actorId          user has been enrolled
	 * @param   INT  $courseId         Course id
	 * @param   INT  $licenseId        License ID
	 * @param   INT  $agencyId         Agency Id
	 * @param   INT  $enrollmentCount  Enrollment count
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterAgencyEnrol($actorId, $courseId, $licenseId, $agencyId, $enrollmentCount = 1)
	{
		if ($licenseId && $agencyId)
		{
			$userId = Factory::getUser()->id;
			$db = Factory::getDbo();
			$query = $db->getQuery(true);

			$query->UPDATE($db->quoteName('#__tjmultiagency_licences'));
			$query->SET($db->quoteName('used_seats') . '=' . $db->quoteName('used_seats') . '+ ' . $enrollmentCount);
			$query->WHERE($db->quoteName('course_id') . '=' . $courseId);
			$query->WHERE($db->quoteName('id') . '=' . $licenseId);
			$query->WHERE($db->quoteName('multiagency_id') . '=' . $agencyId);

			$db->setQuery($query);
			$db->execute();

			return true;
		}

		return false;
	}

	/**
	 * Function is triggered when enrollements are deleted from manageenrollments
	 *
	 * @param   INT  $enrolmentIds      array of primary keys of the enrolment table
	 * @param   INT  $enrolmentDetails  array([enrolmentid]= object(course_id,user_id))
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onAfterEnrolementsDelete($enrolmentIds, $enrolmentDetails)
	{
		// Blank
	}

	/**
	 * Function is triggered when agency is update
	 *
	 * @param   INT  $agencyId  Agency Id
	 *
	 * @return  null
	 *
	 * @since  1.0.0
	 */
	public function onAfterUpdateAgency($agencyId)
	{
		$ClusterModel = ClusterFactory::model('Cluster');
		$clusterInfo  = $ClusterModel::getClusterByClient('com_multiagency', $agencyId);
		$clusterData  = array();

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$MultiagencyModel = BaseDatabaseModel::getInstance('MultiagencyForm', 'MultiagencyModel', array('ignore_request' => true));
		$agencyData       = $MultiagencyModel->getData($agencyId);

		if (!empty($clusterInfo->id))
		{
			$clusterData['id'] = $clusterInfo->id;
		}

		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

		// Create cluster or edit cluster if user change school name it should change cluster name and description also.
		$clusterData['client']      = 'com_multiagency';
		$clusterData['client_id']   = $agencyId;
		$clusterData['name']        = $agencyData->title;
		$clusterData['description'] = $agencyData->title;
		$clusterData['state']       = (int) 1;

		$ClusterModel->save($clusterData);
	}

	/**
	 * Function is triggered when agency is deleted
	 *
	 * @param   INT    $agencyId     Agency Id
	 * @param   ARRAY  $managerList  Agency manager list
	 *
	 * @return  null
	 *
	 * @since  1.0.0
	 */
	public function onAfterDeleteAgency($agencyId, $managerList)
	{
		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
		$ClusterModel = ClusterFactory::model('Cluster');
		$clusterInfo = $ClusterModel::getClusterByClient('com_multiagency', $agencyId);
		$clusterId = $clusterInfo->id;
		$user = Factory::getUser();

		if (!empty($clusterId))
		{
			$ClusterUsersModel = ClusterFactory::model('ClusterUsers');
			$ClusterUsersModel->setState('filter.cluster_id', $clusterId);
			$clusterUserList = $ClusterUsersModel->getItems();
			$clusterUsers[] = array();

			foreach ($clusterUserList as $node)
			{
				$clusterUsers[$node->id] = $node->user_id;
			}

			if ($clusterId)
			{
				JLoader::import("/components/com_cluster/libraries/cluster", JPATH_ADMINISTRATOR);
				$cluster           = ClusterCluster::getInstance($clusterId);
				$cluster->state    = 0;
				$isClusterDisabled = $cluster->save();
			}

			// Discussion pending on delete, as per latest discussion discussed to comment code
			// $Cluster = $ClusterModel->delete($clusterId);

			/*
			if ($Cluster)
			{
				if (count($managerList) > 0)
				{
					$ClusterUserModel = ClusterFactory::model('ClusterUser');
					$clusterNodeIds = array_flip($clusterUsers);

					foreach ($managerList as $rmMgr)
					{
						if (in_array($rmMgr, $clusterUsers) &&  !empty($clusterNodeIds[$rmMgr]))
						{
							$ClusterUserModel->delete($clusterNodeIds[$rmMgr]);
						}
					}
				}
			}*/
		}

		// Delete Licence of agency
		if (!empty($agencyId))
		{
			if ($licenceTable->id)
			{
				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
				$licenceModel = BaseDatabaseModel::getInstance('Licence', 'MultiagencyModel', array('ignore_request' => true));
				$licenceTable = $licenceModel->getTable();
				$licenceTable->load(array('multiagency_id' => $agencyId));

				if ($licenceTable->id)
				{
					// $licenceTable->delete($licenceTable->id);
				}
			}
		}
	}

	/**
	 * Function is triggered when agency is create
	 *
	 * @param   INT    $clusterId  Cluster Id
	 * @param   ARRAY  $userList   Newely created Manager ids
	 *
	 * @return  null
	 *
	 * @since  1.0.0
	 */
	public function onAfterAddUser($clusterId, $userList)
	{
		if ($clusterId && count($userList) > 0)
		{
			foreach ($userList as $uId)
			{
				$clusterUserData['cluster_id'] = $clusterId;
				$clusterUserData['user_id'] = $uId;
				$clusterUserData['state'] = (int) 1;
				$clusterUserData['modified_by'] = Factory::getUser()->get('id');

				$ClusterUserModel = ClusterFactory::model('ClusterUser');
				$ClusterUserModel->save($clusterUserData);
			}
		}
	}

	/**
	 * Function is triggered when agency is create
	 *
	 * @param   INT  $agencyId  Agency Id
	 *
	 * @return  null
	 *
	 * @since  1.0.0
	 */
	public function onAfterCreateAgency($agencyId)
	{
		$ClusterModel = ClusterFactory::model('Cluster');
		$clusterInfo = $ClusterModel::getClusterByClient('com_multiagency', $agencyId);
		$clusterId = $clusterInfo->id;
		$user = Factory::getUser();

		if (empty($clusterId))
		{
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
			$MultiagencyModel = BaseDatabaseModel::getInstance('MultiagencyForm', 'MultiagencyModel', array('ignore_request' => true));
			$agencyData = $MultiagencyModel->getData($agencyId);

			JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

			// Create cluster
			$clusterData['client'] = 'com_multiagency';
			$clusterData['client_id'] = $agencyId;
			$clusterData['name'] = $agencyData->title;
			$clusterData['description'] = $agencyData->title;
			$clusterData['state'] = (int) 1;
			$clusterData['modified_by'] = $user->id;

			$ClusterModel->save($clusterData);
		}
	}

	/**
	 * Adds additional fields to the user editing form
	 *
	 * @param   Form  $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		$app = Factory::getApplication();

		if (!$app->isClient('site'))
		{
			return;
		}

		if (!($form instanceof Form))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		// Check we are manipulating a valid form - we only display this on license form while editing.
		$name = $form->getName();

		$allowedForms = array ('com_multiagency.licence', 'com_sla.slaactivity', 'com_timelog.activityform');

		if (!in_array($name, $allowedForms))
		{
			return true;
		}

		// Get com_sla component status
		if (ComponentHelper::getComponent('com_sla', true)->enabled)
		{
			$licenseId = $app->input->getInt('licence_id', 0);

			// We only display this if user has not consented before
			if (is_object($data) && $name == 'com_multiagency.licence')
			{
				$licenseId = isset($data->id) ? $data->id : 0;

				if (empty($licenseId))
				{
					$licenseId = $app->input->getInt('id', 0);
				}

				if (empty($licenseId))
				{
					return true;
				}

				$table = SlaFactory::table("slaclusterxrefs");
				$table->load(array('license_id' => $licenseId));

				$data->sla_id             = $table->sla_id;
				$data->lead_consultant_id = $table->lead_consultant_id;
				$data->notify_dpe_admin   = $table->notify_dpe_admin;
				$data->dpeadmins          = $table->dpeadmins;

				if (!empty($table->id))
				{
					$form->setFieldAttribute('sla_id', 'readonly', 'true');
				}
			}
			elseif (is_object($data) && $name == 'com_sla.slaactivity')
			{
				// Add Record
				if (!$data->id && $licenseId)
				{
					$table = SlaFactory::table("slaclusterxrefs");
					$table->load(array('license_id' => $licenseId));

					$data->lead_consultant_id = $table->lead_consultant_id;
					$data->license_id         = $licenseId;
				}
				// Edit Record
				elseif ($data->id)
				{
					JLoader::import('components.com_jlike.tables.todos', JPATH_ADMINISTRATOR);
					$todoTable = Table::getInstance('Todos', 'JlikeTable');
					$todoTable->load($data->todo_id);

					$data->lead_consultant_id = $todoTable->assigned_to;
					$data->activity_name      = $todoTable->title;
					$data->activity_desc      = $todoTable->sender_msg;
					$data->ideal_time         = $todoTable->ideal_time;
					$data->start_date         = $todoTable->start_date;
					$data->due_date           = $todoTable->due_date;
				}
			}
			elseif (is_object($data) && $name == 'com_timelog.activityform')
			{
				$slaActivity = $app->input->getInt('sla_activity', 0);

				if (!empty($licenseId))
				{
					$form->setFieldAttribute('license_id', 'readonly', 'true');
					$data->license_id = $licenseId;
				}

				if (!empty($slaActivity))
				{
					$data->client_id = $slaActivity;
				}
			}
		}
	}
}
