<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Table\Table;

/**
 * Multiagency user list controller class.
 *
 * @since  __DEPLOY__VERSION__
 */
class MultiagencyControllerUsers extends AdminController
{
	/**
	 * This function validate the license of school
	 *
	 * @return  string 
	 *
	 * @since   __DEPLOY__VERSION__
	 */
	public function validateLicense()
	{
		$app = Factory::getApplication();

		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$data = $app->input->getArray();

		if (!$data['agencyId'])
		{
			echo new JsonResponse(null, Text::_('COM_MULTIAGENCY_NO_SCHOOL_SELECTED'), true);
			$app->close();
		}

		// Get cluster ID
		$client_id      = $data['agencyId'];
		$isAgencyValue  = $data['isAgencyValue'];
		$agencyValueId  = $data['agencyId'];

		// IF cluster Id is sent calculate agency ID
		if (!$isAgencyValue)
		{
			// Get agency ID using cluster details
			$clusterTable = ClusterFactory::table("Clusters");
			$clusterTable->load(array('id' => $client_id));
			$agencyValueId = $clusterTable->client_id;
		}

		// Check license is available for school
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_multiagency/tables');
		$licenceTable = Table::getInstance('licence', 'MultiagencyTable');
		$licenceTable->load(array('multiagency_id' => $agencyValueId, 'state' => 1));

		if (!$licenceTable->id)
		{
			echo new JsonResponse(null, Text::sprintf('COM_MULTIAGENCY_NO_LICNESE_ADDED', Text::_('COM_MULTIAGENCY_ORGANISATION')), true);
			$app->close();
		}

		echo new JsonResponse($licenceTable->id, Text::_('COM_MULTIAGENCY_LICNESE_ADDED'));
		$app->close();
	}
}
