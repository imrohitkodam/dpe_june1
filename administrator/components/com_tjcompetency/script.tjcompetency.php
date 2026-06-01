<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * TjCompetencyInstallerScript class.
 *
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 * @since       1.0
 */
class Com_TjCompetencyInstallerScript
{
	/** @var array The list of extra modules and plugins to install */
	private $installation_queue = array(

		// Plugins => { (folder) => { (element) => (published) }* }*
		'plugins' => array(
				'tjcompetencycontenttype' => array(
					'course' => 1,
					'event' => 1,
					'manual' => 1,
				),
				'tjreports' => array(
					'userskillreport' => 1,
					'skillusermapsreport' => 1
				),
				'system' => array(
					'tjcompetency' => 1
				)
		),
		'applications' => array(
			'easysocial' => array(
				'tjcompetencyskills' => 1
			)
		),
	);

	/**
	 * Runs after install, update or discover_update
	 *
	 * @param   string      $type    install, update or discover_update
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  boolean
	 */
	public function postflight($type, $parent)
	{
		$this->installNotificationsTemplates();

		// Install subextensions
		$this->_installSubextensions($parent);

		return true;
	}

	/**
	 * Install Notifications Templates
	 *
	 * @return  void
	 */
	public function installNotificationsTemplates()
	{
		if (JComponentHelper::isEnabled('com_tjnotifications'))
		{
			jimport('joomla.application.component.model');
			JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjnotifications/tables');
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjnotifications/models');
			$notificationsModel = JModelLegacy::getInstance('Notification', 'TJNotificationsModel');

			$filePath = JPATH_ADMINISTRATOR . '/components/com_tjcompetency/emailTemplates.json';
			$str = file_get_contents($filePath);
			$json = json_decode($str, true);

			$existingKeys = $notificationsModel->getKeys('com_tjcompetency');

			if (count($json) != 0)
			{
				foreach ($json as $template => $array)
				{
					// If template doesn't exist then we add notification template.
					if (!in_array($array['key'], $existingKeys))
					{
						$notificationsModel->createTemplates($array);
					}
				}
			}
		}
	}

	/**
	 * Installs subextensions (modules, plugins) bundled with the main extension
	 *
	 * @param   JInstaller $parent
	 *
	 * @return JObject The subextension installation status
	 */
	private function _installSubextensions($parent)
	{
		$src = $parent->getParent()->getPath('source');

		$db = JFactory::getDbo();

		// Plugins installation
		if (count($this->installation_queue['plugins']))
		{
			foreach ($this->installation_queue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$path = "$src/plugins/$folder/$plugin";

						if (!is_dir($path))
						{
							$path = "$src/plugins/$folder/plg_$plugin";
						}

						if (!is_dir($path))
						{
							$path = "$src/plugins/$plugin";
						}

						if (!is_dir($path))
						{
							$path = "$src/plugins/plg_$plugin";
						}

						if (!is_dir($path))
						{
							continue;
						}

						// Was the plugin already installed?
						$query = $db->getQuery(true)->select('COUNT(*)')->from($db->qn('#__extensions'))->where($db->qn('element') . ' = ' . $db->q($plugin))->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($query);
						$count = $db->loadResult();

						$installer = new JInstaller;
						$result    = $installer->install($path);

						$status->plugins[] = array(
							'name' => 'plg_' . $plugin,
							'group' => $folder,
							'result' => $result
						);

						if ($published && !$count)
						{
							$query = $db->getQuery(true)->update($db->qn('#__extensions'))->set($db->qn('enabled') . ' = ' . $db->q('1'))->where($db->qn('element') . ' = ' . $db->q($plugin))->where($db->qn('folder') . ' = ' . $db->q($folder));
							$db->setQuery($query);
							$db->execute();
						}
					}
				}
			}
		}

		//Application Installations
		if (count($this->installation_queue['applications'])) {
			foreach ($this->installation_queue['applications'] as $folder => $applications) {
				if (count($applications)) {
					foreach ($applications as $app => $published) {
						$path = "$src/applications/$folder/$app";
						if (!is_dir($path)) {
							$path = "$src/applications/$folder/plg_$app";
						}
						if (!is_dir($path)) {
							$path = "$src/applications/$app";
						}
						if (!is_dir($path)) {
							$path = "$src/applications/plg_$app";
						}
						if (!is_dir($path)) continue;

						if (file_exists(JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/installer/installer.php'))
						{
							require_once JPATH_ADMINISTRATOR . '/components/com_easysocial/includes/installer/installer.php';

							$installer     = new SocialInstaller;
							// The $path here refers to your application path
							$installer->load($path);
							$installer->install();
							//$status->app_install[] = array('name'=>'easysocial_camp_plg','group'=>'easysocial_camp_plg', 'result'=>$plg_install,'status'=>'1');
							$status->applications[] = array('name'=>$app,'group'=>$folder, 'result'=>$result,'status'=>$published);
						}
					}
				}
			}
		}
	}
}
