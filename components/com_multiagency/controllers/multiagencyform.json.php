<?php
/**
 * @package     Multiagency
 * @subpackage  Com_Multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Response\JsonResponse;

/**
 * Multiagencyform controller class
 *
 * @since  __DEPLOY_VERSION__
 */
class MultiagencyControllerMultiagencyForm extends FormController
{
	protected $comMultiAgency = 'com_multiagency';

	/**
	 * Function to get agency info
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function getAgencyInfo()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			$app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
			echo new JsonResponse(null, null, true);
			$app->close();
		}

		$clusterId  = $app->input->post->get('clusterId', 0, 'INT');
		$clusterTbl = ClusterFactory::table('Clusters');
		$clusterTbl->load(array('id' => $clusterId));

		if (!$clusterTbl->client_id)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

			return;
		}

		$agencyObj      = new StdClass;
		$urlParam       = '&clusterId=' . $clusterTbl->id;
		$agencyObj->url = 'index.php?option=com_multiagency&tmpl=component&view=multiagencyform&layout=agencyinfo&id=' . $clusterTbl->client_id . $urlParam;

		echo new JsonResponse($agencyObj);
		$app->close();
	}
}
