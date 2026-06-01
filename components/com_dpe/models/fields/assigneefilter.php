<?php
/**
 * @package    DPE
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of custom field
 *
 * @since  __DEPLOY_VERSION__
 */

class JFormFieldAssigneeFilter extends \JFormFieldList

{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    __DEPLOY_VERSION__
	 */
	protected $type = 'assigneefilter';

	/**
	 * Fiedd to decide if options are being loaded externally and from xml
	 *
	 * @var		integer
	 * @since	__DEPLOY_VERSION__
	 */
	protected $loadExternally = 0;

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getOptions()
	{
		$app   = Factory::getApplication();
		$user  = Factory::getUser();
		$db    = Factory::getDBO();
		$query = $db->getQuery(true);
		$query->select('distinct(u.id) as value, trim(concat(u.name,"(",u.email,")")) as options');
		$query->from($db->quoteName('#__users', 'u'));
		$query->where($db->qn('u.block') . ' = 0');
		$query->join('INNER', $db->qn('#__tj_cluster_nodes', 'cn') .
				' ON cn.user_id = u.id');
		$query->join('INNER', $db->qn('#__tj_clusters', 'clusters') .
				' ON (' . $db->qn('clusters.id') . ' = ' . $db->qn('cn.cluster_id') .
				' AND ' . $db->qn('clusters.client') . " = 'com_multiagency' ) ");
		$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'ml') .
				' ON (' . $db->qn('ml.id') . ' = ' . $db->qn('clusters.client_id') . ')');
		$query->where($db->qn('ml.state') . '=' . 1);
		$query->where($db->qn('clusters.state') . '=' . 1);
		$query->order($db->escape('u.name' . ' ' . 'asc'));

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields');
			$clusterList = FormHelper::loadFieldType('Cluster', false);
			$clusters    = $clusterList->getOptionsExternally();

			foreach ($clusters as $cluster)
			{
				// Used core action to remove staff org and don't need to show assignee filter to staff org
				$manageCluster = RBACL::check($user->id, 'com_cluster', 'core.view.own', 'com_tjdashboard', $cluster->value);

				if ($manageCluster)
				{
					$clusterIds[] = $cluster->value;
				}
			}

			if (!empty($clusterIds))
			{
				$query->where("clusters.id  IN ('" . implode("','", $clusterIds) . "')");
			}
			else
			{
				return false;
			}
		}

		$clusterId = $app->input->get('cluster');

		if (!empty($clusterId) && $clusterId != "all")
		{
			$query->where($db->qn('clusters.id') . ' = ' . (int) $app->input->get('cluster'));
		}

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Method to get a list of options for a list input externally and not from xml.
	 *
	 * @return	array	An array of HTMLHelper options.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getOptionsExternally()
	{
		$this->loadExternally = 1;

		return $this->getOptions();
	}
}
