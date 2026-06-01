<?php
/**
 * @package    Techjoomla.Libraries
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;

use \Joomla\Registry\Registry;
use \Joomla\CMS\Component\ComponentHelper;

JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

/**
 * TjQueue
 *
 * @package     Techjoomla.Libraries
 * @subpackage  Tjqueue
 * @since       __DEPLOY_VERSION__
 */
class TjqueueSlaActivitiesCreate
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

		$activityLimit      = ComponentHelper::getParams('com_sla')->get('activityLimit');
		$slaActivitiesModel = SlaFactory::model('SlaActivities', array('ignore_request' => true));

		foreach ($messageData['activity'] as $activityType => $activityCount)
		{
			// Server side validation for count
			$activityCount = ($activityCount > $activityLimit) ? $activityLimit : $activityCount;

			if ($activityCount)
			{
				$slaActivitiesModel->createActivities($messageData, $activityType, $activityCount);
			}
		}

		return true;
	}
}
