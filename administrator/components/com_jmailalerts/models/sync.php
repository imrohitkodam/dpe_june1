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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Helper\UserGroupsHelper;


/**
 * Model for sync
 *
 * @since  1.0.0
 */
class JmailalertsModelsync extends BaseDatabaseModel
{
	/**
	 * Function to get the plugin data(names, elements) related to ejmailalerts
	 *
	 * @return object
	 */
	public function getPluginData()
	{
		$this->_db->setQuery("SELECT name,element FROM #__extensions WHERE enabled=1 AND folder='emailalerts'");

		return $this->_db->loadObjectList();
	}

	/**
	 * Function called from view.html.php of sync. It returns the alert name with default selected alert id
	 *
	 * @return object
	 */
	public function getAlertnames()
	{
		// Get default alert ids
		$this->_db->setQuery("SELECT alert.* FROM #__jma_alerts as alert WHERE state=1");
		$alertnames = $this->_db->loadObjectList();
		$options    = array();

		foreach ($alertnames as $alertname)
		{
			$options[] = HTMLHelper::_('select.option', $alertname->id, $alertname->title);
		}

		return $options;
	}

	/**
	 * Get the default preferences from jmail alerts table
	 *
	 * @return object
	 */
	public function getPluginNames()
	{
		// FIRST GET THE EMAIL-ALERTS RELATED PLUGINS FRM THE `jos_plugins` TABLE
		$this->_db->setQuery('SELECT element FROM #__extensions WHERE folder = \'emailalerts\'  AND enabled = 1');

		// Get the plugin names and store in an array
		return $this->_db->loadColumn();
	}

	/**
	 * Method to get the frequencies according to the atert id
	 *
	 * @param   int  $alertid  Alert id
	 *
	 * @return array
	 */
	public function getFrequencies($alertid)
	{
		$this->_db->setQuery("
			SELECT alert.allowed_freq, alert.default_freq
			FROM #__jma_alerts AS alert
			WHERE alert.id=" . $alertid
		);

		$alertDetails = $this->_db->loadObject();

		$allowedFreqs = $alertDetails->allowed_freq;

		// Build array to replace ["1","3"] & make 1,3
		$search       = array('[', ']', '"');
		$allowedFreqs = str_replace($search, '', $allowedFreqs);

		$this->_db->setQuery("
			SELECT freq.id, freq.name as freq_name
			FROM #__jma_frequencies as freq
			WHERE freq.id IN (" . $allowedFreqs . ")"
		);

		if (count($this->_db->loadAssocList()))
		{
			$frequencies = array();

			foreach ($this->_db->loadAssocList() as $f)
			{
				$i = 0;

				$frequencies[$i]['id']        = $f['id'];
				$frequencies[$i]['freq_name'] = Text::_($f['freq_name']);
			}
		}

		return $frequencies;
	}

	/**
	 * Method to get the alert default freq
	 *
	 * @param   int  $alertId  Alert id
	 *
	 * @return array
	 */
	public function getDefaultFreq($alertId)
	{
		if (empty($alertId) || $alertId == 'null')
		{
			return array();
		}

		$this->_db->setQuery("
			SELECT alert.id as alertid, alert.default_freq, freq.name, freq.time_measure, freq.duration
			 FROM #__jma_alerts as alert
			 LEFT JOIN #__jma_frequencies as freq ON freq.id=alert.default_freq
			 WHERE alert.id = " . $alertId
		);

		$alertDetails = $this->_db->loadAssocList();

		if (isset($alertDetails))
		{
			if ($alertDetails['0']['time_measure'] == 'days')
			{
				$alertDetails['0']['last_email_date'] = date(
					Text::_('COM_JMAILALERTS_DATE_FORMAT_PHP'),
					strtotime(date(Text::_('COM_JMAILALERTS_DATE_FORMAT_PHP')) . ' - ' . $alertDetails['0']['duration'] . ' days')
				);
			}
			else
			{
				$alertDetails['0']['last_email_date'] = date(
					Text::_('COM_JMAILALERTS_DATE_FORMAT_PHP'),
					strtotime(date(Text::_('COM_JMAILALERTS_DATE_FORMAT_PHP')) . '- 1 days')
				);
			}

			$alertDetails['0']['name'] = Text::_($alertDetails['0']['name']);
		}

		return $alertDetails;
	}

	/**
	 * Function called from view.html.php of sync. It returns the UserGroups name with default selected alert id.
	 *
	 * @return object
	 */
	public function getUserGroups()
	{
		$userGroups = UserGroupsHelper::getInstance()->getAll();

		foreach ($userGroups as $userGroup)
		{
			$options[] = HTMLHelper::_('select.option', $userGroup->id, $userGroup->title);
		}

		return $options;
	}
}
