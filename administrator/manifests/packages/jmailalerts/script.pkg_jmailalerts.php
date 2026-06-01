<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Filesystem\File;

$tjInstallerPath = JPATH_ROOT . '/administrator/manifests/packages/jmailalerts/tjinstaller.php';

if (File::exists(__DIR__ . '/tjinstaller.php'))
{
	include_once __DIR__ . '/tjinstaller.php';
}
elseif (File::exists($tjInstallerPath))
{
	include_once $tjInstallerPath;
}

/**
 * JMailAlerts installer class
 *
 * @since  2.5.0
 */
class Pkg_JMailAlertsInstallerScript extends TJInstaller
{
	protected $extensionName = 'JMailAlerts';

	/** @var  array  The list of extra modules and plugins to install */
	protected $installationQueue = array (
		'postflight' => array(
			/*plugins => { (folder) => { (element) => (published) }}*/
			'plugins' => array (
				'system' => array (
					'tjassetsloader' => 1,
					'tjupdates'      => 1
				)
			)
		)
	);

	/** @var  array  The list of extra modules and plugins to uninstall */
	protected $uninstallQueue = array (
		/*plugins => { (folder) => { (element) => (published) }}*/
		'plugins' => array ()
	);

	/**
	 * A list of extensions (modules, plugins) to enable after installation. Each item has four values, in this order:
	 * type (plugin, module, ...), name (of the extension), status (0 - unpublish, 1 - publish),
	 * client (0=site, 1=admin), group (for plugins), position (for modules).
	 *
	 * @var array
	 */
	protected $extensionsToEnable = array (
		// JMA plugins
		array ('plugin', 'jmailalerts',        1, 1, 'privacy'),
		array ('plugin', 'plug_usr_mailalert', 1, 1, 'user')
	);

	/** @var  array  Obsolete files and folders to remove*/
	protected $removeFilesAndFolders = array (
		'files' => array (
			/*Since v2.6.0*/
			'administrator/components/com_jmailalerts/log.txt'
		),
		'folders' => array (
		)
	);

	/**
	 * Runs before install, update or discover_update
	 *
	 * @param   string      $type    install, update or discover_update
	 * @param   JInstaller  $parent  The class calling this method
	 *
	 * @return  void
	 */
	public function preflight($type, $parent)
	{
	}

	/**
	 * Runs after install
	 *
	 * @param   JInstaller  $parent  The class calling this method
	 *
	 * @return  void
	 */
	public function install($parent)
	{
		// Enable the extensions on fresh install
		$this->enableExtensions();

		// Create sample data for freqeuncies
		$this->runSQL(JPATH_ROOT . '/administrator/components/com_jmailalerts/sql/frequencies.mysql.utf8.sql');
	}

	/**
	 * Runs after update
	 *
	 * @param   Installer  $parent  The class calling this method
	 *
	 * @return  void
	 */
	public function update($parent)
	{
	}

	/**
	 * Method to uninstall the component
	 *
	 * @param   Installer  $parent  Class calling this method
	 *
	 * @return  void
	 */
	public function uninstall($parent)
	{
		// Uninstall subextensions
		$status = $this->uninstallSubextensions($parent);

		// Show the post-uninstallation page
		$this->renderPostUninstallation($status);
	}

	/**
	 * Runs after install, update or discover_update
	 *
	 * @param   string     $type    install, update or discover_update
	 * @param   Installer  $parent  The class calling this method
	 *
	 * @return  void
	 */
	public function postflight($type, $parent)
	{
		// Copy tjinstaller file into packages folder
		$this->copyInstaller($parent);

		// Install subextensions
		$status = $this->installSubextensions($parent, 'postflight');

		// Remove obsolete files and folders
		$this->removeObsoleteFilesAndFolders($this->removeFilesAndFolders);

		// Show the post-installation page
		$this->renderPostInstallation($status);
	}

	/**
	 * Method to copy installer file
	 *
	 * @param   Installer  $parent  Class calling this method
	 *
	 * @return  void
	 */
	protected function copyInstaller($parent)
	{
		$src  = $parent->getParent()->getPath('source') . '/tjinstaller.php';
		$dest = JPATH_ROOT . '/administrator/manifests/packages/jmailalerts/tjinstaller.php';

		File::copy($src, $dest);
	}
}
