<?php
/**
 * @package    Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Form\Form;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Item Model for an Cluster.
 *
 * @since  1.0.0
 */
class ClusterModelClusterUser extends AdminModel
{
	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		$form = $this->loadForm('com_cluster.clusteruser', 'clusteruser', array('control' => 'jform', 'load_data' => $loadData));

		return empty($form) ? false : $form;
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 */
	public function getTable($type = 'ClusterUsers', $prefix = 'ClusterTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');

		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	$data  The data for the form.
	 *
	 * @since	1.0.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState('com_cluster.edit.clusteruser.data', array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return   void
	 *
	 * @since    1.0.0
	 */
	protected function populateState()
	{
		$jinput = Factory::getApplication()->input;
		$id = ($jinput->get('id')) ? $jinput->get('id') : $jinput->get('id');
		$this->setState('clusteruser.id', $id);
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   1.0.0
	 */
	public function save($data)
	{
		$table = $this->getTable();

		// Avoid the duplicate record for the same cluster
		$table->load(array("user_id" => $data['user_id'], "cluster_id" => $data['cluster_id']));

		// Record exist do not create new record
		$data['id'] = $table->id;

		return parent::save($data);
	}

	/**
	 * Method to get the list of clusters to which user have access
	 *
	 * @param   INT  $userId  Users Id.
	 *
	 * @return  ARRAY  List of clusters.
	 *
	 * @since   1.0.0
	 */
	public function getUsersClusters($userId = null)
	{
		$user = empty($userId) ? Factory::getUser() : Factory::getUser($userId);

		$clusters = array();

		// Load cluster library file
		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

		// If user is not allowed to view all the clusters then return the clusters in which user is a part else return al cluster
		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			$clusterUsersModel = ClusterFactory::model('ClusterUsers', array('ignore_request' => true));
			$clusterUsersModel->setState('list.group_by_client_id', 1);
			$clusterUsersModel->setState('filter.published', 1);
			$clusterUsersModel->setState('list.ordering', 'cl.name');
			$clusterUsersModel->setState('list.direction', 'ASC');
			$clusterUsersModel->setState('filter.user_id', $user->id);

			// Get all assigned cluster entries
			$clusters = $clusterUsersModel->getItems();
		}
		else
		{
			$clusterModel = ClusterFactory::model('Clusters', array('ignore_request' => true));

			// Get all cluster entries
			$clusterModel->setState('filter.state', 1);
			$clusterModel->setState('list.ordering', 'cl.name');
			$clusterModel->setState('list.direction', 'ASC');
			$clusters = $clusterModel->getItems();
		}

		// Get com_subusers component status
		$subUserExist = ComponentHelper::getComponent('com_subusers', true)->enabled;

		if ($subUserExist)
		{
			JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
		}

		$usersClusters = array();

		if (!empty($clusters))
		{
			if ($subUserExist && (!$user->authorise('core.manageall', 'com_cluster')))
			{
				foreach ($clusters as $cluster)
				{
					// Check user has permission for mentioned cluster

					/*
					 *  @Todo migration for client 'com_cluster' in dpe
					 *  Com_dpe - Hack - start
					 *  Original Code : RBACL::authorise($user->id, 'com_cluster', 'core.manage.cluster', 'com_cluster', $cluster->id)
					 */

					// Get cluster also for staff users
					if (RBACL::authorise($user->id, 'com_cluster', 'core.view.all', 'com_multiagency', $cluster->cluster_id)
						|| RBACL::authorise($user->id, 'com_cluster', 'core.adduser', 'com_multiagency', $cluster->cluster_id)
						|| RBACL::authorise($user->id, 'com_cluster', 'core.view.own', 'com_tjdashboard', $cluster->cluster_id))
					{
						$usersClusters[] = $cluster;
					}
					// Com_dpe - Hack - end
				}
			}
			else
			{
				$usersClusters = $clusters;
			}
		}

		return $usersClusters;
	}
}
