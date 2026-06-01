<?php
/**
 * @package    Techjoomla.Libraries
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2024 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;

use \Joomla\Registry\Registry;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;


JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

JLoader::import("/components/com_multiagency/includes/multiagency", JPATH_SITE);

/**
 * TjQueue
 *
 * @package     Techjoomla.Libraries
 * @subpackage  Tjqueue
 * @since       __DEPLOY_VERSION__
 */
class TjqueueUcmnotificationSendemail
{
	/**
	 * Plugin method with the same name as the event will be called automatically.
	 *
	 * @param   string  $message  A Message
	 *
	 * @return  boolean  This method should return acknowledgement flag
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function consume($message)
	{
		$messageBody      = $message->getBody();
		$messageData      = new Registry($messageBody);
		$messageData      = $messageData->toArray();
		$messageData['ucmnotification'] = 1;

		if (is_array($messageData['status']))
		{
			$messageData['status'] = implode(', ', $messageData['status']);
		}

		PluginHelper::importPlugin('tjucmdpe');
	    $result = Factory::getApplication()->triggerEvent('onSendTjucmEmails', array($messageData, $messageData['status'], $messageData['notificationKey'], $messageData['userIdToEmail']));

		if ($result[0]['success'])
		{
			return true;
		}
	}
}
