<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_admin
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
/**
 * Script file of Joomla CMS
 *
 * @since  1.6.4
 */
class MigrationSla
{
	/**
	 * This method used to add data in below table associated with already existed school which doesn't have activity
	 * #_tj_sla_activities
	 * #_jlike_todos
	 * #_tjmultiagency_licences
	 * #_tj_sla_cluster_xref
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function migrateLicenceActivityData()
	{
		// While running cli add User id hardcoded.
		$user = Factory::getUser(329);

		// Validate user login.
		if ($user->id && $user->authorise('core.admin'))
		{
			// Reading file here for getting limit start and end
			$fileData = file(JPATH_ROOT . '/media/com_dpe/migrationLimit.txt');
			$limitstart = $limitend = 0;

			foreach ($fileData as $line)
			{
				if (strpos($line, 'limitstart') !== false)
				{
					$limitstart = explode(":", $line)[1];
				}
				elseif (strpos($line, 'limitend') !== false)
				{
					$limitend = explode(":", $line)[1];
				}
			}

			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
			JLoader::register('MultiagencyModelLicences', JPATH_SITE . '/components/com_multiagency/models/licences.php');
			JLoader::register('MultiagencyModelLicenceForm', JPATH_SITE . '/components/com_multiagency/models/licenceform.php');
			$multiagencyModelLicenceForm = BaseDatabaseModel::getInstance('LicenceForm', 'MultiagencyModel', array('ignore_request' => true));
			$multiagencyModelLicences    = BaseDatabaseModel::getInstance('Licences', 'MultiagencyModel', array('ignore_request' => true));

			$multiagencyModelLicences->setState('list.start', $limitstart);
			$multiagencyModelLicences->setState('list.limit', $limitend);

			$multiagencyLicencesData = $multiagencyModelLicences->getItems();
			$licenceActivityData     = array();

			if (!empty($multiagencyLicencesData))
			{
				foreach ($multiagencyLicencesData as $data)
				{
					$licenceActivityData = (array) $data;

					// In db table the column name is licence_type hence need this change
					$licenceActivityData['licence_type'] = $licenceActivityData['type'];
					unset($licenceActivityData['type']);

					// This is added extra field data as a flag to identify that is this migrated data.
					$licenceActivityData['migrationDb']        = 1;

					// This is newly added field, have added this for migrating data
					$licenceActivityData['sla_id']             = 1;
					$licenceActivityData['lead_consultant_id'] = $user->id;
					$result = $multiagencyModelLicenceForm->save($licenceActivityData);
				}

				// Update limitstart and limitend value
				$filePath    = fopen(JPATH_ROOT . '/media/com_dpe/migrationLimit.txt', 'w+');

				if ($filePath !== false)
				{
					$newContents = "limitstart:" . ($limitstart + 1) . "\n";
					$newContents .= "limitend:" . ($limitend + 1);
					fwrite($filePath, $newContents);
					fclose($filePath);
				}
			}
		}
	}
}
