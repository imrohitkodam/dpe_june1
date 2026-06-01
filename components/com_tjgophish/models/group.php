<?php
/**
 * @package     TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Factory;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Http\Http;
use Joomla\CMS\User\User;
use Joomla\CMS\Language\Text;

/**
 * TjGoPhish - Group Model
 *
 * @since  1.0.0
 */
class TjGoPhishModelGroup extends AdminModel
{
	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $type    The table name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table  A Table object
	 *
	 * @since   1.0.0
	 */
	public function getTable($type = 'Group', $prefix = 'TjGoPhishTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed    A Form object on success, false on failure
	 *
	 * @since   1.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm(
			'com_tjgophish.group',
			'group',
			array(
				'control' => 'jform',
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
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.0.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState(
			'com_tjgophish.edit.group.data',
			array()
		);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to save groups data
	 *
	 * @param   array  $data  Data for the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.0.0
	 */
	public function save($data)
	{
		$user = Factory::getUser();

		// Guest users are not allowed to add the records
		if (empty($user->id))
		{
			$this->setError(Text::_('COM_TJGOPHISH_FORM_SAVE_FAILED_AUTHORIZATION_ERROR'));

			return false;
		}

		if ($data['all_cluster_users'] != 1 && empty($data['targets']))
		{
			$this->setError(Text::_('COM_TJGOPHISH_GROUP_SAVE_FAILED_EMPTY_TARGETS_ERROR'));

			return false;
		}

		if (isset($data['id']) && empty($data['id']))
		{
			unset($data['id']);
		}

		$requestData = new stdClass;
		$requestData->name = $data['gophish_group_title'];
		$targets = array();

		if ($data['all_cluster_users'] == 1)
		{
			JLoader::import('components.com_cluster.models.clusterusers', JPATH_ADMINISTRATOR);
			$clusterObj = BaseDatabaseModel::getInstance('ClusterUsers', 'ClusterModel', array('ignore_request' => true));
			$clusterObj->setState('filter.block', 0);
			$clusterObj->setState('filter.cluster_id', $data['cluster_id']);
			$clusterObj->setState('list.group_by_user_id', 1);
			$clusterUsers = $clusterObj->getItems();

			if (empty($clusterUsers))
			{
				$this->setError(Text::_('COM_TJGOPHISH_GROUP_SAVE_FAILED_NO_USERS_IN_CLUSTER_ERROR'));

				return false;
			}

			// Get all users from cluster
			foreach ($clusterUsers as $target)
			{
				$tmpTarget = new stdClass;
				$tmpTarget->email = $target->uemail;
				$tmpTarget->first_name = $target->name;
				$tmpTarget->last_name = '';
				$tmpTarget->position = '';
				$targets[] = $tmpTarget;
			}
		}
		else
		{
			foreach ($data['targets'] as $target)
			{
				$tmpTarget = new stdClass;
				$user = Factory::getUser($target);
				$tmpTarget->email = $user->email;
				$tmpTarget->first_name = $user->name;
				$tmpTarget->last_name = '';
				$tmpTarget->position = '';
				$targets[] = $tmpTarget;
			}
		}

		$requestData->targets = $targets;

		$params = ComponentHelper::getParams('com_tjgophish');
		$goPhishApiEnd = $params->get('api_base_url');
		$goPhishApiKey = $params->get('api_key');

		$Http = new Http;

		if (isset($data['gophish_group_id']) && !empty($data['gophish_group_id']))
		{
			$requestData->id = (INT) $data['gophish_group_id'];
			$url = $goPhishApiEnd . 'api/groups/' . $data['gophish_group_id'] . '?api_key=' . $goPhishApiKey;
			$response = $Http->put($url, json_encode($requestData), array('Content-Type' => 'application/json'));
		}
		else
		{
			$url = $goPhishApiEnd . 'api/groups/' . '?api_key=' . $goPhishApiKey;
			$response = $Http->post($url, json_encode($requestData), array('Content-Type' => 'application/json'));
		}

		$response = json_decode($response->body);

		if (!isset($response->id))
		{
			$this->setError(Text::_($response->message));

			return false;
		}

		$data['gophish_group_id'] = $response->id;
		$data['checked_out'] = empty($data['checked_out'])?0:$data['checked_out'];
		$data['modified_by'] = empty($data['modified_by'])?Factory::getUser()->id:$data['modified_by'];


		if (parent::save($data))
		{
			return true;
		}
	}

	/**
	 * Method to get group data
	 *
	 * @param   integer  $pk  An optional ID
	 *
	 * @return  object
	 *
	 * @since   1.0.0
	 */
	public function getItem($pk = null)
	{
		$this->item = parent::getItem($pk);
		$user = Factory::getUser();

		/* Validate if a user can access the campaign report

		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters = $clusterUserModel->getUsersClusters($user->id);

		if (!empty($clusters) && $this->item->cluster_id)
		{
			$clusterIds = array();

			foreach ($clusters as $cluster)
			{
				$clusterIds[] = $cluster->cluster_id;
			}

			if (!in_array($this->item->cluster_id, $clusterIds))
			{
				$this->setError(Text::_("JERROR_ALERTNOAUTHOR"));

				return false;
			}
		}
		*/

		// Get com_cluster component status
		if (ComponentHelper::getComponent('com_cluster', true)->enabled)
		{
			// Get com_subusers component status
			$subUserExist = ComponentHelper::getComponent('com_subusers', true)->enabled;

			// Check user have permission to edit record of assigned cluster
			if ($subUserExist && !empty($this->item->cluster_id) && !$user->authorise('core.manageall', 'com_cluster'))
			{
				JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

				/*
				 *  @Todo migration for client 'com_cluster' in dpe
				 *  Com_dpe - Hack - start
				 *  Original Code : RBACL::check($user->id, 'com_cluster', 'core.addItem', 'com_tjucm', $clusterId)
				 */

				// Check user has permission for mentioned cluster
				if (!RBACL::check($user->id, 'com_cluster', 'core.deleteGroup', 'com_tjgophish', $this->item->cluster_id))
				{
					$this->setError(Text::_("JERROR_ALERTNOAUTHOR"));

					return false;
				}
			}
		}

		if ($this->item->id)
		{
			$params = ComponentHelper::getParams('com_tjgophish');
			$goPhishApiEnd = $params->get('api_base_url');
			$goPhishApiKey = $params->get('api_key');

			$Http = new Http;
			$url = $goPhishApiEnd . 'api/groups/' . $this->item->gophish_group_id . '?api_key=' . $goPhishApiKey;
			$response = $Http->get($url);
			$response = json_decode($response->body);

			$this->item->gophish_group_title = $response->name;

			$db = Factory::getDbo();
			$this->item->targets = array();

			foreach ($response->targets as $target)
			{
				$query = $db->getQuery(true);
				$query->select($db->qn('id'))->from($db->qn('#__users'))->where($db->qn('email') . ' = ' . $db->q($target->email));
				$db->setQuery($query);
				$uId = $db->loadResult();

				if (!empty($uId))
				{
					$this->item->targets[] = $uId;
				}
			}
		}

		return $this->item;
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   1.0.0
	 */
	public function delete(&$pks)
	{
		if (empty($pks))
		{
			return false;
		}

		$user = Factory::getuser();

		foreach ($pks as $pk)
		{
			$item = $this->getItem($pk);

			// DPE hack to allow delete to DPE admin
			if (!$user->authorise('core.manageall', 'com_cluster'))
			{
				// Check user has permission for mentioned cluster
				if (!RBACL::check($user->id, 'com_cluster', 'core.deleteGroup', 'com_tjgophish', $item->cluster_id))
				{
					$this->setError(Text::_("JERROR_ALERTNOAUTHOR"));

					return false;
				}
			}

			// Check if user can delete the record
			JLoader::import("components.com_cluster.includes.cluster", JPATH_ADMINISTRATOR);
			$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
			$clusters = $clusterUserModel->getUsersClusters($user->id);

			if (!empty($clusters))
			{
				$clusterIds = array();

				foreach ($clusters as $cluster)
				{
					$clusterIds[] = $cluster->cluster_id;
				}

				if (!in_array($item->cluster_id, $clusterIds))
				{
					$this->setError(Text::sprintf("COM_TJGOPHISH_UNABLE_TO_DELETE_GROUP", $item->gophish_group_title));

					return false;
				}
			}

			$params = ComponentHelper::getParams('com_tjgophish');
			$goPhishApiEnd = $params->get('api_base_url');
			$goPhishApiKey = $params->get('api_key');

			$Http = new Http;
			$url = $goPhishApiEnd . 'api/groups/' . $item->gophish_group_id . '?api_key=' . $goPhishApiKey;
			$response = $Http->delete($url);
			$response = json_decode($response->body);

			if ($response->success)
			{
				if (!parent::delete($pk))
				{
					$this->setError(Text::sprintf("COM_TJGOPHISH_UNABLE_TO_DELETE_GROUP", $item->gophish_group_title));

					return false;
				}
			}
			else
			{
				$this->setError(Text::_($response->message));

				return false;
			}
		}

		return true;
	}
}
