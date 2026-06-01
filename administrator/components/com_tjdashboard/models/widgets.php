<?php
/**
 * @package     TJDashboard
 * @subpackage  com_tjdashboard
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
/**
 * Tjdashboard model class for widgets
 *
 * @since  1.0.0
 */
class TjdashboardModelWidgets extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JModelList
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'dashboard_widget_id', 'wid.dashboard_widget_id',
				'dashboard_id', 'dash.dashboard_id',
				'state', 'wid.state',
				'title', 'wid.title',
				'dashboard_name','dashboard_name',
				'renderer_plugin','wid.renderer_plugin',
				'data_plugin','wid.data_plugin',
				'created_by', 'wid.created_by',
				'ordering', 'wid.ordering',
				'size','wid.size'
				);
		}

		// Set the filters as default filters
		parent::__construct($config);
	}

	/**
	 * Get the master query for retrieving a list of requests to the senior.
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   1.0.0
	 */
	protected function getListQuery()
	{
		// Initialize variables.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Create the base select statement.
		$query->select('wid.*');
		$query->select(array($db->quoteName('dash.title', 'dashboard_name'), $db->quoteName('users.name')));
		$query->from($db->quoteName('#__tj_dashboard_widgets', 'wid'));
		$query->join('LEFT',
			$db->quoteName('#__tj_dashboards', 'dash') . ' ON (' . $db->quoteName('wid.dashboard_id') . ' = ' . $db->quoteName('dash.dashboard_id') . ')');
		$query->join('LEFT', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('wid.created_by') . ' = ' . $db->quoteName('users.id') . ')');

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where($db->quoteName('wid.dashboard_widget_id') . ' = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('(wid.title LIKE ' . $search . ')');
			}
		}

		// Filter by dashboard_id
		$dashboard_id = $this->getState('filter.dashboard_id');

		if (!empty($dashboard_id))
		{
			$query->where($db->quoteName('wid.dashboard_id') . ' = ' . (int) $dashboard_id);
		}

		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where($db->quoteName('wid.state') . ' = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where($db->quoteName('wid.state') . ' IN (0,1) ');
		}

		// Filter by size
		$size  = $this->getState('filter.size');

		if (!empty($size))
		{
			$query->where($db->quoteName('wid.size') . ' = ' . (int) $size);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}
		else
		{
			$query->order(array($db->quoteName('wid.dashboard_id'),$db->quoteName('wid.ordering')));
		}

		return $query;
	}

	/**
	 * DPE Hack for Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getItems()
	{
		$items     = parent::getItems();
		$app       = Factory::getApplication();

		if ($app->isClient('site'))
		{
			if (!empty($items))
			{
				$user      = Factory::getUser();
				$filter    = $app->input->get('filter', 0, 'INT');
				$clusterId = $filter['cluster_id'];

				JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
				JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

				$params                   = ComponentHelper::getParams('com_multiagency');
				$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');

				if (in_array($multiagencyTrusteeRoleId, $user->groups))
				{
					$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
					$clusters = $clusterUserModel->getUsersClusters($user->id);

					if (empty($clusterId))
					{
						$clusterId = $clusters[0]->cluster_id;
					}
				}

				if ((in_array($multiagencyTrusteeRoleId, $user->groups) || $user->authorise('core.manageall', 'com_cluster')) && !empty($clusterId))
				{ 
					$clusterModel = ClusterFactory::model('Clusters', array('ignore_request' => true));
					$clusterModel->setState('filter.id', $clusterId);
					$clusterModel->setState('filter.state', 1);
					$clustersItems = $clusterModel->getItems();

					// DPE HACK Check license is available for school
					Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
					$licenceTable = Table::getInstance('licence', 'MultiagencyTable');

					$licenceTable->load(array('multiagency_id' => $clustersItems[0]->client_id, 'state' => 1));

					$licenceModel = Multiagency::model('licence', array('ignore_request' => true));

					$licenceId = 0;

					if (property_exists($licenceTable, 'id'))
					{
						$licenceId = $licenceTable->id;
					}

					if ($licenceId)
					{
						// Get the saved tool for licence xref
						$savedClients = $licenceModel->getSavedClientsFromLicenceXref($licenceId);

						// DPE HACK  To add ucm tools if not present in the json format of old license
						if (array_search('com_tjucm.rop', $savedClients))
						{
							$params        = ComponentHelper::getParams('com_dpe');
							$relatedTools = json_decode($params->get("tjucmrelatedtools"));
							$counter = count($relatedTools->supporting_clients);

							for ($i = 0; $i < $counter; $i++)
							{
								if (!in_array($relatedTools->supporting_clients[$i], $savedClients))
								{
									array_push($savedClients, $relatedTools->supporting_clients[$i]);
								}
							}
						}
					}

					$allowedWidgets = array();

					foreach ($items as $item)
					{
						$params = new Registry($item->params);

						// Check if the client is configured in widget list or free licence
						if (in_array($params->get('client'), (array) $savedClients) || $params->get('client') == Text::_('COM_DASHBOARD_FREE_WIDGET_CLIENT'))
						{
							$allowedWidgets[] = $item;
						}
					}
					return $allowedWidgets;
				}
				elseif (!$user->authorise('core.manageall', 'com_cluster') && !in_array($multiagencyTrusteeRoleId, $user->groups))
				{ 
					if (empty($clusterId))
					{ 
						$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
						$clusters = $clusterUserModel->getUsersClusters($user->id);

						foreach ($clusters as $cluster)
						{
							// Check user having permission to add staff
							if (RBACL::check($user->id, 'com_cluster', 'core.view.own', 'com_tjdashboard', $cluster->cluster_id))
							{
								$clusterIds[] = $cluster->cluster_id;
							}
						}

				  		// Commented to dont check the cluster To show all the widgets on first view

						  // array_splice($clusterIds, 0, 0, '' );
						  // $clusterId = $clusterIds[0];
					}

					$allowedWidgets = array();

					if (!empty($clusterId) || count((array)$clusterIds) == 1)
					{
						foreach ($items as $item)
						{
							// Get part of data_plugin string to form a RBACL action name
							$action = strtolower(substr($item->data_plugin, strrpos($item->data_plugin, '.') + 1));

							if (RBACL::check($user->id, 'com_cluster', 'view.' . $action . 'Dashboard', 'com_multiagency', $clusterId))
							{
								$allowedWidgets[] = $item;
							}

						}
					}
					else{ // Show all the widgets for the first view fro Admin users having multiple organistions.

						foreach ($items as $item)
						{
							$allowedWidgets[] = $item;	
							
						}
					}
					

					return $allowedWidgets;
				}
			}
		}

		return $items;
	}
}
