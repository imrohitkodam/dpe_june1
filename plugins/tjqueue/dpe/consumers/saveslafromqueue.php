<?php
/**
 * @package    Techjoomla.Libraries
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * TjQueue Consumer to save SLA licences from queue
 */
class TjqueueDpeSaveSlaFromQueue
{
	/**
	 * Consume the message from queue
	 *
	 * @param   object  $message  The message object
	 *
	 * @return  boolean
	 */
	public function consume($message)
	{
		$messageBody = $message->getBody();
		$data = json_decode($messageBody, true);

		if (empty($data) || empty($data['cluster_ids']))
		{
			return true;
		}

		// Load the Dashboard model from com_dpe
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
		$dashboardModel = BaseDatabaseModel::getInstance('Dashboard', 'DpeModel');

		if ($dashboardModel)
		{
			$dashboardModel->saveSlaFromQueue($data);
		}

		return true;
	}
}