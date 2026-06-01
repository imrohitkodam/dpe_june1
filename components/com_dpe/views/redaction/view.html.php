<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;


JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);
JLoader::import('components.com_subusers.includes.rbacl', JPATH_ADMINISTRATOR);

/**
 * Control Panel controller class.
 *
 * @package  DPE
 * @since    __DEPLOY_VERSION__
 */
class DPEViewRedaction extends BaseHtmlView
{
	/**
	 * Function to display.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths
	 *
	 * @return  mixed  A string.
	 *
	 * @since	__DEPLOY_VERSION__
	 */
	public function display($tpl = null)
	{
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		if (!$user->id)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

			return;
		}

		// Check action for PDF reduction tool
		$clusterUserModel = ClusterFactory::model('ClusterUser', array('ignore_request' => true));
		$clusters         = $clusterUserModel->getUsersClusters($user->id);

		if (!$user->authorise('core.manageall', 'com_cluster'))
		{
			$mangeDocumenReduction = array();

			foreach ($clusters as $cluster)
			{
				$mangeDocumenReduction[] = RBACL::check($user->id, 'com_cluster', 'core.manageDocumentRedaction', 'com_dpe', $cluster->cluster_id);
			}

			$mangeDocumenReduction = array_filter($mangeDocumenReduction);

			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModelStaffDashboard');
			$redactionModel = BaseDatabaseModel::getInstance('StaffDashboard', 'DpeModel');
			$redactionAccess = $redactionModel->getRedactionFormattedData();

			if (empty($mangeDocumenReduction) && !$redactionAccess['data']['titleLink'])
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
				$app->setHeader('status', 403, true);

				return;
			}
		}

		parent::display($tpl);
	}
}
