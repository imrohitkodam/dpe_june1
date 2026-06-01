<?php

/**
 * @version     admin/controller.php 2020-11-20 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;
defined('WATCHFULLI_PATH') or die;

class watchfulliController extends BaseController
{
	/**
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  void
	 */
	public function display($cachable = false, $urlparams = [])
	{
		if (empty($this->input->get('view')))
		{
			$this->input->set('view', 'default');
		}
		parent::display($cachable);
	}

	/**
	 * Auto-Whitelist Watchful IPs after install/update
	 *
	 * @see install.watchfulli.php - whiteListWatchful()
	 */
	public function whitelist()
	{
		$app = \Joomla\CMS\Factory::getApplication();
		$redirect = Route::_('index.php?option=com_watchfulli');

		$response = WatchfulliConnection::getCurl(
			[
				'url'             => 'https://app.watchful.net/ip-v4.txt',
				'timeout'         => 300,
				"follow_location" => false,
			]
		);

		if (empty($response->data))
		{
			$app->enqueueMessage('Can\'t connect to watchful.net for get IPs (https://app.watchful.net/ip-v4.txt)', 'error');
			$app->redirect(JRoute::_('index.php?option=com_watchfulli'));
		}

		// Remove mask (Watchful will ever use /32)
		$watchfuIps = preg_replace("/\/32/", '', explode("\n", $response->data));

		try
		{
			$whiteList = new WatchfulliWhitelistIp(json_encode($watchfuIps), 'add', false);
		}
		catch (Exception $e)
		{
			$app->enqueueMessage('Error when whitelisting Watchful IPs : ' . $e->getMessage(), 'error');
			$app->redirect($redirect);
			exit();
		}

		$result = $whiteList->getResult();

		if (empty($result['provider']))
		{
			$app->enqueueMessage('No Joomla firewall find on your site', 'notice');
			$app->redirect($redirect);
			exit();
		}

		$app->enqueueMessage('watchful.net IP correctly white listed for ' . implode(', ', $result['provider']), 'message');
		$app->redirect($redirect);
	}
}
