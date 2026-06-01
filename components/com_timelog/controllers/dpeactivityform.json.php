<?php
/**
 * @package    Com_Timelog
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

defined('_JEXEC') or die;

JLoader::import('components.com_timelog.controllers.activityform', JPATH_SITE);

/**
 * TimelogControllerDpeActivityForm class
 *
 * @since  __DEPLOY_VERSION__
 */
class TimelogControllerDpeActivityForm extends TimelogControllerActivityForm
{
	/**
	 * Method to load the activity according to the license key
	 *
	 * @return string
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	public function loadActivity()
	{
		$app  = Factory::getApplication();

		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN_NOTICE'), true);
			$app->close();
		}

		$user = Factory::getUser();

		$options   = $activities = array();

		JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);

		$slaModel = SlaFactory::model('SlaActivities', array('ignore_request' => true));

		$licenseId = $app->input->getInt('licence_id', 0);

		if (empty($licenseId))
		{
			echo new JsonResponse(null, Text::_("COM_TIMELOG_ITEM_NOT_LOADED"), true);
		}

		$slaModel->setState('filter.license_id', $licenseId);
		$slaModel->setState('list.ordering', 'todo.title');
		$slaModel->setState('list.direction', 'asc');

		if ($user->id)
		{
			$activities = $slaModel->getItems();
		}

		if (count($activities) > 0)
		{
			$options[] = HTMLHelper::_('select.option', "", Text::_('COM_TIMELOG_FORM_LBL_ACTIVITY_OPTION'));

			foreach ($activities as $activity)
			{
				$options[] = HTMLHelper::_('select.option', $activity->id, $activity->sla_service_title);
			}
		}

		echo new JsonResponse($options);
		$app->close();
	}
}
