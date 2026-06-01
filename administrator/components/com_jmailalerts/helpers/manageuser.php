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

/**
 * ManageUserHelper helper.
 *
 * @since  2.5.0
 */
class ManageUserHelper
{
	/**
	 * Method to subscribe new user when it added in manage user Subscriber
	 *
	 * @param   mixed  $user  Subscriber id
	 *
	 * @return  array
	 */
	public function subscribeUser($user)
	{
		$db = Factory::getDBO();

		// $userid = $user['user_id'];

		// Recieve array of alert id where set to defaoult
		$query = 'SELECT id  FROM #__jma_alerts WHERE is_default = 1';
		$db->setQuery($query);
		$alertid       = $db->loadColumn();
		$alertidString = $alertid;

		$alertqry = "";

		for ($i = 0; $i < count($alertidString); $i++)
		{
			$alertqry .= "id=" . $alertidString[$i];

			if ($i != (count($alertidString) - 1))
			{
				$alertqry .= " OR ";
			}
		}

		$query = 'SELECT element FROM #__extensions WHERE folder = \'emailalerts\'  AND enabled = 1';
		$db->setQuery($query);
		$plugnamecompair = $db->loadColumn();
		$plugnamesend    = implode(',', $plugnamecompair);
		$plugnamecompair = explode(',', $plugnamesend);

		$cnt   = 0;
		$rnt   = 99;
		$query = "SELECT id, default_freq, template FROM #__jma_alerts WHERE $alertqry";
		$db->setQuery($query);
		$result = $db->loadObjectList();

		foreach ($result as $key)
		{
			$date  = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - $key->default_freq, date("Y")));
			$entry = "";

			for ($i = 0; $i < count($plugnamecompair); $i++)
			{
				if (strstr($key->template, $plugnamecompair[$i]))
				{
					$pluginNameString[] = $plugnamecompair[$i];
				}
			}

			foreach ($pluginNameString as $plug)
			{
				$query = "select params from #__extensions where element='" . $plug . "' && folder='emailalerts'";
				$db->setQuery($query);
				$plugParams = $db->loadResult();

				if (preg_match_all('/\[(.*?)\]/', $plugParams, $match))
				{
					foreach ($match[1] as $mat)
					{
						$match       = str_replace(',', '|', $mat);
						$plugParams = str_replace($mat, $match, $plugParams);
					}
				}

				$newlin = explode(",", $plugParams);

				foreach ($newlin as $v)
				{
					if (!empty($v))
					{
						$v = str_replace('{', '', $v);
						$v = str_replace(':', '=', $v);
						$v = str_replace('"', '', $v);
						$v = str_replace('}', '', $v);
						$v = str_replace('[', '', $v);
						$v = str_replace(']', '', $v);
						$v = str_replace('|', ',', $v);

						/*
						if ($plug == 'jma_latestnews_js')
						{
							$cnt++;
						}
						*/

						if (!($cnt > $rnt))
						{
							$entry .= $plug . '|' . $v . "\n";
						}
					}

					/*
					if ($plug == 'jma_latestnews_js')
					{
						$entry = str_replace('category', 'catid', $entry);
						$entry = str_replace('sections', 'secid', $entry);
					}
					*/
				}

				$cnt = 0;
			}

			unset($pluginNameString);
			unset($match);

			$userData = array();

			// Plugins paramenter
			$userData['plugins_subscribed_to'] = $entry;

			// Date of subscription -(minus) frequency
			$userData['date'] = $date;

			return $userData;
		}
	}
}
