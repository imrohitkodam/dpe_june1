<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Simulate model class
 *
 * @package  JMailAlerts
 *
 * @since    2.5.0
 */
class JMailalertsModelMailSimulate extends BaseDatabaseModel
{
	/**
	 * Send simulated email
	 *
	 * @return  integer
	 *
	 * @since  2.5.0
	 */
	public function simulate()
	{
		$input = Factory::getApplication()->input;

		require_once JPATH_SITE . '/components/com_jmailalerts/models/emails.php';
		require_once JPATH_SITE . '/components/com_jmailalerts/helpers/emailhelper.php';

		$jmaEmailhelper = new jmailalertsemailhelper;
		$jmaModelEmails = new jmailalertsModelEmails;

		// Get date selected in simulate
		$today              = $input->get('select_date_box', '', 'STRING');
		$targetUserId       = $input->get('user_id_box', '', 'INT');
		$alertTypeId        = $input->get('altypename', '', 'INT');
		$destinationEmailId = ($input->get('send_mail_to_box', '', 'STRING'))? $input->get('send_mail_to_box', '', 'STRING'):Factory::getUser($targetUserId)->get('email');
		$flag               = $input->get('flag', '', 'INT');

		if (!$alertTypeId || !$targetUserId || !$destinationEmailId)
		{
			return 2;
		}

		$query = "SELECT u.id as user_id, u.name, u.email as email_id, u.params,
		 a.template, a.email_subject,
		 e.date, e.alert_id,
		 a.template_css,
		 e.plugins_subscribed_to,
		 a.respect_last_email_date,e.state
		 FROM #__users AS u,
		 #__jma_subscribers AS e,
		 #__jma_alerts AS a
		 WHERE e.user_id = " . $targetUserId . "
		 AND e.alert_id = " . $alertTypeId . "
		 AND u.id = e.user_id
		 AND e.state = 1
		 AND a.id = e.alert_id";

		$this->_db->setQuery($query);
		$targetUserData = $this->_db->loadObjectList();

		$i = 0;

		foreach ($targetUserData as $data)
		{
			if ($data->date)
			{
				// $data[$i]->date = $today;
				$data->date = ($today) ? $today : $data->date;
			}
			else
			{
				$data->date = ($today) ? $today : $data->date;
			}

			$i++;
		}

		if ($targetUserData)
		{
			// @echo $destinationEmailId;
			$targetUserData[0]->email_id = $destinationEmailId;

			// Get template from alert type
			$query = "SELECT template FROM #__jma_alerts WHERE id =" . $alertTypeId;
			$this->_db->setQuery($query);
			$msgBody = $this->_db->loadResult();

			$skipTags     = array('[SITENAME]', '[NAME]', '[SITELINK]', '[PREFRENCES]', '[mailuser]');
			$tmplTags     = $jmaModelEmails->get_tmpl_tags($msgBody, $skipTags);
			$rememberTags = $jmaModelEmails->get_original_tmpl_tags($msgBody, $skipTags);

			$response = $jmaEmailhelper->getMailcontent($targetUserData[0], $flag, $tmplTags, $rememberTags);

			if (isset($response))
			{
				return $response[1];
			}
		}
		else
		{
			return 2;
		}
	}

	/**
	 * Function to call plugins and return the output. This function is called from the addtomailq() function above
	 *
	 * @param   int     $id    User id
	 * @param   string  $date  Date
	 *
	 * @return  array
	 *
	 * @since   2.5.0
	 */
	public function getPlugins($id, $date)
	{
		PluginHelper::importPlugin('emailalerts');

		return Factory::getApplication()->triggerEvent('onBeforeJmaAlertEmail', array($id, $date));
	}

	/**
	 * Get published alerts
	 *
	 * @return  string
	 *
	 * @since  2.5.0
	 */
	public function getAlertypename()
	{
		$db = Factory::getDBO();
		$db->setQuery("SELECT id  AS value, title AS text FROM #__jma_alerts WHERE state=1");
		$altypename	 = $db->loadObjectList();

		return HTMLHelper::_(
			'select.genericlist',
			$altypename,
			'altypename',
			'class="form-select inputbox"',
			'value',
			'text',
			'',
			'altypename'
		);
	}
}
