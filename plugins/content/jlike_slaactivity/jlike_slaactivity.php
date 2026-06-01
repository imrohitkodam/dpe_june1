<?php
/**
 * @version    SVN: <svn_id>
 * @package    Sla_Activity
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2019-2019 DPE. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Table\Table;
use Joomla\CMS\Plugin\CMSPlugin;

JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);

/**
 * Sla Activity
 *
 * @package     Sla_Activity
 * @subpackage  site
 * @since       __DEPLOY_VERSION__
 */
class PlgContentJLike_Slaactivity extends CMSPlugin
{
	/**
	 * check selected content follows criteria to send reminder
	 *
	 * @param   INT     $user_id     user_id ID
	 * @param   INT     $element_id  lesson ID
	 * @param   String  &$title      title
	 *
	 * @return  Integer
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onAfterJlikeslaactivityContentCheckforReminder($user_id, $element_id, &$title)
	{
		// Get SLA details
		$slaActivitiesTable = SlaFactory::table("SlaActivities");

		// Send reminder only for active activity
		$slaActivitiesTable->load(array('id' => $element_id, 'state' => 1));

		if ($slaActivitiesTable->id)
		{
			$clusterTable = ClusterFactory::table("Clusters");
			$clusterTable->load(array('id' => $slaActivitiesTable->cluster_id));

			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/tables');
			$todosTable            = Table::getInstance('Todos', 'JlikeTable', array());
			$todosTable->load(array('id' => $slaActivitiesTable->todo_id));

			if (property_exists($todosTable, 'id'))
			{
				if ($todosTable->id)
				{
					// 'C' is complete status and 'CN' stands for Cancelled status which is saved after cancel the activity
					if ($todosTable->status != 'C' && $todosTable->status != 'CN')
					{
						$title = $clusterTable->name;
						return 1;
					}
				}
			}
		}

		return 0;
	}
}
