<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

jimport('techjoomla.tjnotifications.tjnotifications');
/**
 * DPE RsTicketsPro controller
 *
 * @since  __DEPLOY_VERSION__
 */
class DpeControllerRsticketspro extends \Joomla\CMS\MVC\Controller\BaseController
{
	/**
	 * Method to redirect to submit ticket form
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function createTicket()
	{
		JLoader::import('ComtjlmsHelper', JPATH_SITE . '/components/com_tjlms/helpers');
		$app              = Factory::getApplication();
		$comtjlmsHelper   = new ComtjlmsHelper;
		$itemId           = $comtjlmsHelper->getitemid('index.php?option=com_rsticketspro&view=submit');
		$submitTicketLink = Route::_('index.php?option=com_rsticketspro&view=submit&Itemid=' . $itemId, false);
		$app->redirect($submitTicketLink);
	}

/**
 * Retrieves the SMTP connection report for ticketing.
 *
 * This method is used to gather diagnostic or status information
 * about the SMTP connection used in the ticketing system.
 *
 * @return Void
 *
 **/
	public function getTicketSmtpConnectionReport()
	{
		$id = 1;
		require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/cron.php';
		$cron 	= new RSTicketsProCron();
		$ticketReport = $cron->test($id);

		JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
			$params = DPE::config();
			$userDatas = $params->get('dpeadminforticket');
			
			foreach($userDatas as $userData)
			{
				$user  = Factory::getUser($userData);
				$recipients = array(
				'email' => array(
					'to' => array($user->email)
				)
			);
			$key    = "ticketConnectionReport";
			$options = new Registry;
			$replacements = new stdClass;
			$replacements->user = new stdClass;
			$replacements->user->name = $user->name;
			$replacements->user->msg = $ticketReport[0];

			// Check if send a mail is failuer or Success mail	
			$result =  Tjnotifications::send("com_dpe", $key, $recipients, $replacements, $options);
			}
	}
}
