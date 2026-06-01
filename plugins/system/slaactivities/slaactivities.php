<?php
/**
 * @package    Sla_Activities
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2021 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */
defined('_JEXEC') or die('Direct Access to this location is not allowed.');
use Joomla\CMS\Uri\Uri;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.plugin.plugin');
jimport('joomla.application.component.model');
jimport('techjoomla.jsocial');
$language = Factory::getLanguage();
$language->load('plg_system_slaactivities', JPATH_ADMINISTRATOR);
$language->load('com_activitystream', JPATH_SITE);

/**
 * Sla_Activities
 *
 * @package     Sla_Activities
 * @subpackage  site
 * @since       1.0
 */
class PlgSystemSlaActivities extends CMSPlugin
{
	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 *
	 * @param   array   $config    An array that holds the plugin configuration
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		require_once dirname(__FILE__) . '/helper.php';
		$this->PlgSystemSlaActivitiesHelper = new PlgSystemSlaActivitiesHelper;
	}

	/**
	 * Method to include required scripts for activity streams
	 *
	 * @return  null
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onAftergetActivityScript()
	{
		HTMLHelper::_('jquery.framework');
		JLoader::import('components.com_activitystream.helper', JPATH_SITE);
		$comActivityStreamHelper = new ComActivityStreamHelper;
		$comActivityStreamHelper->getLanguageConstantForJs();
		$document = Factory::getDocument();
		$document->addScriptDeclaration('var root_url = \'' . Uri::base() . '\'');
		$document->addScript(Uri::root(true) . '/media/com_activitystream/scripts/mustache.min.js');
		$document->addStyleSheet(Uri::root(true) . '/media/com_dpe/css/theme.css');

		HTMLHelper::script('media/com_activitystream/scripts/activities.jQuery.js');
	}

	/**
	 * Function onAfterMultiagencySave
	 *
	 * @param   Integer  $id           Licence Id
	 * @param   Array    $licenceData  licenceData
	 *
	 * @return  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAfterMultiagencySave($id, $licenceData)
	{ 
		$oldDetails = array();
		$result     = false;

		if (isset($licenceData['oldLicenceData']))
		{
			$oldDetails = $licenceData['oldLicenceData'];
		}

		if (!empty($licenceData['id']))
		{
			if (!empty($oldDetails))
			{
				if (!empty($licenceData['start_date']) && !empty($oldDetails->start_date))
				{
					$startDate    = Factory::getDate($licenceData['start_date'])->toSql();
					$oldStartDate = Factory::getDate($oldDetails->start_date)->toSql();

					if ($startDate != $oldStartDate)
					{
						$licenceData["template"] = 'updatestartdatesla.mustache';
						$licenceData["type"]     = 'multiagency.updatestartdatesla';
						$result = $this->PlgSystemSlaActivitiesHelper->addLicenceActivity($licenceData);
					}
				}

				if (!empty($licenceData['end_date']) && !empty($oldDetails->end_date))
				{
					/* If multiple licence available then don't add end date activity
					because end date will calculate automatically for mutiple licences
					*/

					JLoader::import('components.com_sla.includes.sla', JPATH_ADMINISTRATOR);
					$slaClusterXrefTable = SlaFactory::table("slaclusterxrefs");
					$slaClusterXrefTable->load(array('license_id' => $id));

					$registry         = new Registry($slaClusterXrefTable->params);
					$multiLicenceData = $registry->toArray();

					// No multiple licence then udpate end date change activity
					if (empty($multiLicenceData))
					{
						// Convert dates in sql format to compare
						$endDate    = Factory::getDate($licenceData['end_date'])->toSql();
						$oldEndDate = Factory::getDate($oldDetails->end_date)->toSql();

						// Check old and new end date
						if ($endDate != $oldEndDate)
						{
							$licenceData["template"] = 'updateenddatesla.mustache';
							$licenceData["type"]     = 'multiagency.updateenddatesla';
							$result = $this->PlgSystemSlaActivitiesHelper->addLicenceActivity($licenceData);
						}
					}
				}

				// Get tools config
				$params       = ComponentHelper::getParams('com_dpe');
				$allTools     = new Registry($params->get('allTools'));
				$allToolsdata = $allTools->get('tools');

				// Get current tool keys
				$toolClients  = array_keys((array)$licenceData['tools']);

				// Merge current tool client and supporting tool clients
				foreach ($toolClients as $toolClient)
				{
					$toolSupportingClients = $allToolsdata->$toolClient->supporting_clients;

					if (!empty($toolSupportingClients)?count($toolSupportingClients):0)
					{
						$toolClients = array_merge($toolClients, $toolSupportingClients);
					}
				}

				/*
				 Comapre both arrays against each othar, if both are empty then no tool changed
				 array1 = The array to compare from, array1 = An array to compare against
				 array_diff(array1,array2)
				*/
				$compareOldNewTools = array_diff($oldDetails->tools, $toolClients);
				$compareNewOldTools = array_diff($toolClients, $oldDetails->tools);

				// Check and assign difference of tools
				$differentTools = $compareOldNewTools ? $compareOldNewTools : $compareNewOldTools;

				// If tools difference found then tools are updated and need to add activity
				if (!empty($differentTools))
				{
					$licenceData["template"] = 'updateslatools.mustache';
					$licenceData["type"]     = 'multiagency.updateslatools';
					$result = $this->PlgSystemSlaActivitiesHelper->addLicenceActivity($licenceData);

					// Update log against upcoming licence after tool update
					if ($result)
					{
						$licenceTable = Multiagency::table('licence');
						$licenceTable->load(array('id' => $licenceData['id'], 'state' => 1));
						$parentId = $licenceTable->parent_id ? $licenceTable->parent_id : $licenceData['id'];

						JLoader::register('MultiagencyModelLicences', JPATH_ADMINISTRATOR . '/components/com_multiagency/models/licences.php');
						$multiagencyModelLicences = BaseDatabaseModel::getInstance('Licences', 'MultiagencyModel');

						// State 3 used for upcoming licence
						$multiagencyModelLicences->setState('filter.state', 3);
						$multiagencyModelLicences->setState('filter.parent_id', $parentId);
						$upcomingLicences = $multiagencyModelLicences->getItems();

						if (! empty($upcomingLicences))
						{
							foreach ($upcomingLicences as $upcomingLicence)
							{
								$licenceData['id'] = $upcomingLicence->id;
								$this->PlgSystemSlaActivitiesHelper->addLicenceActivity($licenceData);
							}
						}
					}
				}
			}
			else
			{
				$licenceData["template"] = 'addsla.mustache';
				$licenceData["type"]     = 'multiagency.addsla';
				$result = $this->PlgSystemSlaActivitiesHelper->addLicenceActivity($licenceData);
			}

			return $result;
		}
	}

	/**
	 * Function onAfterLicenceArchive
	 *
	 * @param   Integer  $id        Licence Id
	 * @param   Integer  $agencyId  Agency Id
	 *
	 * @return  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onAfterLicenceArchive($id, $agencyId)
	{
		if ($id && $agencyId)
		{
			$licenceData = array();
			$licenceData["multiagency_id"] = $agencyId;
			$licenceData["id"] = $id;
			$licenceData["template"] = 'archivesla.mustache';
			$licenceData["type"] = 'multiagency.archivesla';

			return $this->PlgSystemSlaActivitiesHelper->addLicenceActivity($licenceData);
		}
	}

	/**
	 * Function onUpdateUpcomingLicenceDate
	 *
	 * @param   array  $licenceData  Licence data
	 *
	 * @return  boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function onUpdateUpcomingLicenceDate($licenceData)
	{
		$licenceData["template"] = 'updatestartdatesla.mustache';
		$licenceData["type"]     = 'multiagency.updatestartdatesla';

		return $this->PlgSystemSlaActivitiesHelper->addLicenceActivity($licenceData);
	}
}
