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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Model class for JMailAlerts control panel.
 *
 * @package  JMailAlerts
 *
 * @since    3.0
 */
class JmailalertsModelDashboard extends BaseDatabaseModel
{
	/**
	 * Constructor
	 *
	 * @since  3.0
	 */
	public function __construct()
	{
		// Get download id
		$params           = ComponentHelper::getParams('com_jmailalerts');
		$this->downloadid = $params->get('downloadid');

		// Setup vars
		$this->updateStreamName = 'JMailAlerts';
		$this->updateStreamType = 'extension';
		$this->updateStreamUrl  = "https://techjoomla.com/updates/stream/jmailalerts.xml?format=xml";
		$this->extensionElement = 'com_jmailalerts';
		$this->extensionType    = 'component';

		// Call the parents constructor
		parent::__construct();
	}

	/**
	 * Get extension id for tis extension
	 *
	 * @return  string
	 *
	 * @since   3.2.5
	 */
	public function getExtensionId()
	{
		$db = $this->getDbo();

		// Get current extension ID
		$query = $db->getQuery(true)
			->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q($this->extensionType))
			->where($db->qn('element') . ' = ' . $db->q($this->extensionElement));
		$db->setQuery($query);

		$extensionId = $db->loadResult();

		if (empty($extensionId))
		{
			return 0;
		}
		else
		{
			return $extensionId;
		}
	}

	/**
	 * Refreshes the Joomla! update sites for this extension as needed
	 *
	 * @return  void
	 */
	public function refreshUpdateSite()
	{
		// Extra query for Joomla 3.0 onwards
		$extraQuery = null;

		if (preg_match('/^([0-9]{1,}:)?[0-9a-f]{32}$/i', $this->downloadid))
		{
			$extraQuery = 'dlid=' . $this->downloadid;
		}

		// Setup update site array for storing in database
		$updateSite = array(
			'name' => $this->updateStreamName,
			'type' => $this->updateStreamType,
			'location' => $this->updateStreamUrl,
			'enabled'  => 1,
			'last_check_timestamp' => 0,
			'extra_query'          => $extraQuery
		);

		$db = $this->getDbo();

		// Get current extension ID
		$extensionId = $this->getExtensionId();

		if (!$extensionId)
		{
			return;
		}

		// Get the update sites for current extension
		$query = $db->getQuery(true)
			->select($db->qn('update_site_id'))
			->from($db->qn('#__update_sites_extensions'))
			->where($db->qn('extension_id') . ' = ' . $db->q($extensionId));
		$db->setQuery($query);

		$updateSiteIDs = $db->loadColumn(0);

		if (!count($updateSiteIDs))
		{
			// No update sites defined. Create a new one.
			$newSite = (object) $updateSite;
			$db->insertObject('#__update_sites', $newSite);

			$id = $db->insertid();

			$updateSiteExtension = (object) array(
				'update_site_id' => $id,
				'extension_id'   => $extensionId,
			);

			$db->insertObject('#__update_sites_extensions', $updateSiteExtension);
		}
		else
		{
			// Loop through all update sites
			foreach ($updateSiteIDs as $id)
			{
				$query = $db->getQuery(true)
					->select('*')
					->from($db->qn('#__update_sites'))
					->where($db->qn('update_site_id') . ' = ' . $db->q($id));
				$db->setQuery($query);
				$aSite = $db->loadObject();

				// Does the name and location match?
				if (($aSite->name == $updateSite['name']) && ($aSite->location == $updateSite['location']))
				{
					// Do we have the extra_query property (J 3.2+) and does it match?
					if (property_exists($aSite, 'extra_query'))
					{
						if ($aSite->extra_query == $updateSite['extra_query'])
						{
							continue;
						}
					}
					else
					{
						// Joomla! 3.1 or earlier. Updates may or may not work.
						continue;
					}
				}

				$updateSite['update_site_id'] = $id;
				$newSite = (object) $updateSite;
				$db->updateObject('#__update_sites', $newSite, 'update_site_id', true);
			}
		}
	}

	/**
	 * Get latest version fetched by joomla updater
	 *
	 * @return  string
	 *
	 * @since   3.2.5
	 */
	public function getLatestVersion()
	{
		// Get current extension ID
		$extensionId = $this->getExtensionId();

		if (!$extensionId)
		{
			return 0;
		}

		$db = $this->getDbo();

		// Get current extension ID
		$query = $db->getQuery(true)
			->select($db->qn(array('version', 'infourl')))
			->from($db->qn('#__updates'))
			->where($db->qn('extension_id') . ' = ' . $db->q($extensionId));
		$db->setQuery($query);

		$latestVersion = $db->loadObject();

		if (empty($latestVersion))
		{
			return 0;
		}
		else
		{
			return $latestVersion;
		}
	}
}
