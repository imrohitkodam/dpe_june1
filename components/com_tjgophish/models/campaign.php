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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Http\Http;
use Joomla\CMS\User\User;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;

/**
 * TjGoPhish - Campaign Model
 *
 * @since  1.0.0
 */
class TjGoPhishModelCampaign extends AdminModel
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
	public function getTable($type = 'Campaign', $prefix = 'TjGoPhishTable', $config = array())
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
			'com_tjgophish.campaign',
			'campaign',
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
			'com_tjgophish.edit.campaign.data',
			array()
		);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to save campaigns data
	 *
	 * @param   array  $data  Data for the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.0.0
	 */
	public function save($data)
	{
		$params = ComponentHelper::getParams('com_tjgophish');
		$user = Factory::getUser();

		// Guest users are not allowed to add the records
		if (empty($user->id))
		{
			$this->setError(Text::_('COM_TJGOPHISH_FORM_SAVE_FAILED_AUTHORIZATION_ERROR'));

			return false;
		}

		$campaignStartDate = Factory::getDate($data['gophish_campaign_launch_date']);

		$requestData = new stdClass;
		$requestData->name = $data['gophish_campaign_title'];

		$tmpTemplate = new stdClass;
		$tmpTemplate->name = $data['gophish_email_template'];
		$requestData->page = $requestData->smtp = $requestData->template = $tmpTemplate;
		$templateURLConfig = json_decode($params->get('templateURL'), true);

		if (!array_key_exists($requestData->template->name, (array) $templateURLConfig))
		{
			$this->setError(Text::_('COM_TJGOPHISH_FORM_CAMPAIGN_TEMPLATE_URL_NOT_CONFIGURED'));

			return false;
		}

		if (array_key_exists($requestData->template->name, $templateURLConfig))
		{
			$requestData->url = $templateURLConfig[$requestData->template->name];
		}

		$requestData->launch_date = $campaignStartDate->toISO8601();

		$groups = array();

		foreach ($data['gophish_groups'] as $group)
		{
			$tmpGroup = new stdClass;
			$tmpGroup->name = $group;
			$groups[] = $tmpGroup;
		}

		$requestData->groups = $groups;

		$params = ComponentHelper::getParams('com_tjgophish');
		$goPhishApiEnd = $params->get('api_base_url');
		$goPhishApiKey = $params->get('api_key');

		$Http = new Http;

		if (isset($data['gophish_campaign_id']) && !empty($data['gophish_campaign_id']))
		{
			$this->setError(Text::_('COM_TJGOPHISH_FORM_CAMPAIGN_UPDATE_NOT_ALLOWED'));

			return false;
		}

		$url = $goPhishApiEnd . 'api/campaigns/' . '?api_key=' . $goPhishApiKey;
		$response = $Http->post($url, json_encode($requestData), array('Content-Type' => 'application/json'));

		$response = json_decode($response->body);

		if (!isset($response->id))
		{
			$this->setError(Text::_($response->message));

			return false;
		}

		$data['gophish_campaign_id'] = $response->id;
		$data['gophish_campaign_status'] = $response->status;
		$data['gophish_groups'] = json_encode($data['gophish_groups']);
		$data['checked_out'] = empty($data['checked_out'])?0:$data['checked_out'];
		$data['modified_by'] = empty($data['modified_by'])?0:$data['modified_by'];
		
		if (parent::save($data))
		{
			$data['campaignId'] = 0;
			$data['campaignId'] = $this->getState('campaign.id');

			// Fire the onUserBeforeSave event.
			PluginHelper::importPlugin('system');
			Factory::getApplication()->triggerEvent('onAfterAddCampaign', array($data));

			return true;
		}
	}

	/**
	 * Method to get campaign data
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

		/* Validate if a user can access the campaign
		JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters = $clusterUserModel->getUsersClusters($user->id);

		if (!empty($clusters) && ($this->item->cluster_id))
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

				// Check user has create permission for mentioned cluster
				if (!RBACL::check($user->id, 'com_cluster', 'core.createCampaign', 'com_tjgophish', $this->item->cluster_id))
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
			$url = $goPhishApiEnd . 'api/campaigns/' . $this->item->gophish_campaign_id . '?api_key=' . $goPhishApiKey;
			$response = $Http->get($url);
			$response = json_decode($response->body);

			$this->item->gophish_campaign_title = $response->name;
			$this->item->gophish_email_template = $response->template->name;
			$this->item->gophish_landing_page = $response->page->name;
			$this->item->gophish_listener = $response->url;
			$this->item->gophish_campaign_launch_date = $response->launch_date;
			$this->item->gophish_sending_profile = $response->smtp->name;
			$this->item->gophish_campaign_status = $response->status;
			$this->item->gophish_groups = json_decode($this->item->gophish_groups);
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
				// Check user has delete permission for mentioned cluster
				if (!RBACL::check($user->id, 'com_cluster', 'core.deleteCampaign', 'com_tjgophish', $item->cluster_id))
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
					$this->setError(Text::sprintf("COM_TJGOPHISH_UNABLE_TO_DELETE_CAMPAIGN", $item->gophish_campaign_title));

					return false;
				}
			}

			$params = ComponentHelper::getParams('com_tjgophish');
			$goPhishApiEnd = $params->get('api_base_url');
			$goPhishApiKey = $params->get('api_key');

			$Http = new Http;
			$url = $goPhishApiEnd . 'api/campaigns/' . $item->gophish_campaign_id . '?api_key=' . $goPhishApiKey;
			$response = $Http->delete($url);
			$response = json_decode($response->body);

			if ($response->success)
			{
				if (!parent::delete($pk))
				{
					$this->setError(Text::sprintf("COM_TJGOPHISH_UNABLE_TO_DELETE_CAMPAIGN", $item->gophish_campaign_title));

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

	/**
	 * Method to delete one or more records.
	 *
	 * @param   INT  $pk  Campaign Id.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   1.0.0
	 */
	public function conclude($pk)
	{
		if (empty($pk))
		{
			return false;
		}

		$item = $this->getItem($pk);
		$user = Factory::getuser();

		// Check if user can perform the action
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
				$this->setError(Text::_("COM_TJGOPHISH_FORM_SAVE_FAILED_AUTHORIZATION_ERROR"));

				return false;
			}
		}

		$params = ComponentHelper::getParams('com_tjgophish');
		$goPhishApiEnd = $params->get('api_base_url');
		$goPhishApiKey = $params->get('api_key');

		$Http = new Http;
		$url = $goPhishApiEnd . 'api/campaigns/' . $item->gophish_campaign_id . '/complete?api_key=' . $goPhishApiKey;
		$response = $Http->get($url);
		$response = json_decode($response->body);

		if ($response->success)
		{
			$table = $this->getTable();
			$table->load(array('gophish_campaign_id' => $item->gophish_campaign_id));
			$table->gophish_campaign_status = "Completed";

			if ($table->store())
			{
				return true;
			}
		}

		return false;
	}
}
