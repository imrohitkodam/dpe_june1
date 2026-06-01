<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;

/**
 * Sync controller class.
 *
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 * @since       2.6.1
 */
class JmailalertsControllerSync extends FormController
{
	/**
	 * Load frequencies
	 *
	 * @return void
	 */
	public function loadFrequencies()
	{
		$input   = Factory::getApplication()->input;
		$alertid = $input->get('alertid');
		$model   = $this->getModel();

		$alertDefaultFreq = $model->getDefaultFreq($alertid);
		echo json_encode($alertDefaultFreq);
		jexit();
	}

	/**
	 * Get subscribers count
	 *
	 * @return void
	 */
	public function getSubscribesCount()
	{
		$input   = Factory::getApplication()->input;
		$alertid = $input->get('alertid');

		// Get the number of users subscribe for alerts
		$jmaHelperPath = JPATH_ADMINISTRATOR . '/components/com_jmailalerts/helpers/jmailalerts.php';

		if (!class_exists('JmailalertsHelper'))
		{
			JLoader::register('JmailalertsHelper', $jmaHelperPath);
			JLoader::load('JmailalertsHelper');
		}

		$jmailAlertsHelper = new JmailalertsHelper;

		if ($alertid >= 0)
		{
			$subsreport = $jmailAlertsHelper->getSubscribesCount($alertid);
		}

		echo json_encode($subsreport);
		jexit();
	}
}
