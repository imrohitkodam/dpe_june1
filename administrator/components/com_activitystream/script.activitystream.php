<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Activitystream
 * @author     Parth Lawate <contact@techjoomla.com>
 * @copyright  2016 Parth Lawate
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Installer\Installer;
use Joomla\Data\DataObject;
use Joomla\CMS\Language\Text;

/**
 * Script file of activitystream component
 *
 * @since  0.0.1
 */
class Com_ActivityStreamInstallerScript
{
	/** @var array The list of extra modules and plugins to install */
	private $queue = array(
		// Plugins => { (folder) => { (element) => (published) }* }*
		'plugins' => array(
			'privacy' => array(
				'activitystream' => 1
			),
			'api' => array(
				'tjactivity' =>0
				)
		),
	);

	/**
	 * method to install the component
	 *
	 * @param   STRING  $parent  parent
	 *
	 * @return void
	 */
	public function install($parent)
	{
	}

	/**
	 * method to uninstall the component
	 *
	 * @param   STRING  $parent  parent
	 *
	 * @return object
	 */
	public function uninstall($parent)
	{
		jimport('joomla.installer.installer');
		$db              = Factory::getDbo();
		$status          = new CMSObject;
		$status->plugins = array();

		// Plugins uninstallation
		if (count($this->queue['plugins']))
		{
			foreach ($this->queue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$sql = $db->getQuery(true)->select($db->qn('extension_id'))
						->from($db->qn('#__extensions'))
						->where($db->qn('type') . ' = ' . $db->q('plugin'))
						->where($db->qn('element') . ' = ' . $db->q($plugin))
						->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($sql);

						$id = $db->loadResult();

						if ($id)
						{
							$installer         = new Installer;
							$result            = $installer->uninstall('plugin', $id);
							$status->plugins[] = array(
								'name' => 'plg_' . $plugin,
								'group' => $folder,
								'result' => $result
							);
						}
					}
				}
			}
		}

		return $status;
	}

	/**
	 * method to update the component
	 *
	 * @param   STRING  $parent  parent
	 *
	 * @return void
	 */
	public function update($parent)
	{
		$this->installSqlFiles($parent);
	}

	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @param   STRING  $type    type
	 * @param   STRING  $parent  parent
	 *
	 * @return void
	 */
	public function preflight($type, $parent)
	{
	}

	/**
	 * method to run after an install/update/uninstall method
	 *
	 * @param   STRING  $type    type
	 * @param   STRING  $parent  parent
	 *
	 * @return void
	 */
	public function postflight($type, $parent)
	{
		$src = $parent->getParent()->getPath('source');
 		$db = Factory::getDbo();
 		$status = new CMSObject;
 		$status->plugins = array();

		// Plugins installation
 		if (count($this->queue['plugins']))
 		{
			foreach ($this->queue['plugins'] as $folder => $plugins)
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
 						$query = $db->getQuery(true)
 							->select('COUNT(*)')
 							->from($db->qn('#__extensions'))
 							->where($db->qn('element') . ' = ' . $db->q($plugin))
 							->where($db->qn('folder') . ' = ' . $db->q($folder));
 						$db->setQuery($query);
 						$count = $db->loadResult();

 						$installer = new Installer;
 						$result = $installer->install($path);

						$status->plugins[] = array('name' => 'plg_' . $plugin, 'group' => $folder, 'result' => $result);

 						if ($published && !$count)
 						{
 							$query = $db->getQuery(true)
 								->update($db->qn('#__extensions'))
 								->set($db->qn('enabled') . ' = ' . $db->q('1'))
 								->where($db->qn('element') . ' = ' . $db->q($plugin))
 								->where($db->qn('folder') . ' = ' . $db->q($folder));
 							$db->setQuery($query);
 							$db->execute();
 						}

					}
				}
			}

		}

		// Install SQL FIles
		$this->installSqlFiles($parent);
	}

	/**
	 * installSqlFiles
	 *
	 * @param   JInstaller  $parent  parent
	 *
	 * @return  void
	 */
	public function installSqlFiles($parent)
	{
		$db = Factory::getDBO();

		// Obviously you may have to change the path and name if your installation SQL file ;)
		if (method_exists($parent, 'extension_root'))
		{
			$sqlfile = $parent->getPath('extension_root') . '/admin/sql/install.mysql.utf8.sql';
		}
		else
		{
			$sqlfile = $parent->getParent()->getPath('extension_root') . '/sql/install.mysql.utf8.sql';
		}

		// Don't modify below this line
		$buffer = file_get_contents($sqlfile);

		if ($buffer !== false)
		{
			$queries = \JDatabaseDriver::splitSql($buffer);

			if (count($queries) != 0)
			{
				foreach ($queries as $query)
				{
					$query = trim($query);

					if ($query != '' && $query[0] != '#')
					{
						$db->setQuery($query);

						if (!$db->execute())
						{
							JError::raiseWarning(1, Text::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));

							return false;
						}
					}
				}
			}
		}
	}
}
