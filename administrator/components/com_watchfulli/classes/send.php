<?php

/**
 * @version     admin/classes/send.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Input\Input;

defined('_JEXEC') or die();
defined('WATCHFULLI_PATH') or die();

class WatchfulliSend
{
	/** @var array */
	public $_data;

	/** @var DatabaseDriver */
	public $db;

	/** @var CMSApplication */
	private $application;

	/** @var Input */
	private $input;

	/** @var array existing records in Joomla core updater table */
	private $update_records = [];

	/** @var array $update_sites list of update sites for Joomla core updater */
	private $update_sites = [];

	/** @var array */
	private $master_versions = [];

	/** @var bool */
	private $akeebaBackupInstalled = false;

	/** @var array */
	private $extensions = [];

	/** @var WatchfulliHelper */
	private $helper;

	/**
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->db          = Factory::getDBO();
		$this->application = Factory::getApplication();
		$this->input       = $this->application->input;
		$this->helper      = new WatchfulliHelper();
		if (!Watchfulli::checkToken())
		{
			$this->_data = ['status' => ['access' => false]];
			Watchfulli::debug("Invalid key");
			die(json_encode($this->_data));
		}

		$logPath = $this->application->get('log_path') . '/watchfulli.log.php';
		if (defined('WATCHFULLI_DEBUG') && file_exists($logPath))
		{
			unlink($logPath);
		}
	}

	/**
	 * Get current time and store it in a debug array with the given label
	 *
	 * @param   string  $label  a string to identify the current checkpoint
	 */
	private function timeLap($label)
	{
		if (!defined('WATCHFULLI_DEBUG'))
		{
			return;
		}
		global $debug;
		$debug->time[$label] = time();
	}

	/**
	 *    Return all client data separated into different array items
	 *
	 * @return     array of arrays
	 * @throws Exception
	 */
	public function getData()
	{
		Watchfulli::debug("getData - starting execution");
		$params      = ComponentHelper::getParams('com_watchfulli');
		$maintenance = $params->get('maintenance', 0) == 1;
		$status      = ['access' => true, 'maintenance' => $maintenance, 'can_update' => Watchfulli::canUpdate()];

		// Get versions info sent from the master
		$this->master_versions = [];
		foreach (json_decode($this->input->get('versions', '[]')) as $item)
		{
			$this->master_versions[$item->realname] = $item->version;
		}
		Watchfulli::debug("Master versions: " . print_r($this->master_versions, true));
		//compare local extension with given versions info
		$this->timeLap('2.1 Before watchfulliSend::getExtensions');
		Watchfulli::debug('2.1 Before watchfulliSend::getExtensions');
		$this->extensions = $this->getExtensions();
		$this->timeLap('2.2 After watchfulliSend::getExtensions');
		Watchfulli::debug('2.2 After watchfulliSend::getExtensions');

		$this->timeLap('2.5 Before building data');
		Watchfulli::debug('2.5 Before building data');

		$this->_data = [
			'status'          => $status,
			'versions'        => $this->getVersions(),
			'filesproperties' => $this->getFilesProperties(),
			'extensions'      => $this->extensions,
			'watchfulliApps'  => $this->getApps(),
			'latestBackup'    => $this->getLatestBackupInfo(),
			'joomlaMessages'  => Factory::getApplication()->getMessageQueue(),
			'adminUsersList'   => $this->getAdminUsers()
		];

		$this->timeLap('2.6 watchfulliSend::getData end');
		Watchfulli::debug('2.6 watchfulliSend::getData end');

		return $this->_data;
	}

	/**
	 * Get full list of extensions, separated by type (components, modules,
	 * plugins, libraries, other)
	 *
	 * @return  array of arrays
	 */
	public function getExtensions()
	{
		$lang               = Factory::getLanguage();
		$extensions         = [];
		$update_records     = $this->getUpdateRecords();
		$update_sites       = $this->getUpdateSites();
		$extensions_records = $this->getExtensionRecords();

		foreach ($extensions_records as $row)
		{
			// Set update fields
			$row->updateId = $row->updateVersion = $row->vUpdate = 0;
			if (isset($update_records[$row->extension_id]))
			{
				$row->updateId      = $update_records[$row->extension_id]->update_id;
				$row->updateVersion = $update_records[$row->extension_id]->version;
				$row->vUpdate       = 1;
			}

			// Set update servers
			$row->updateServer = '';
			if (isset($update_sites[$row->extension_id]))
			{
				$row->updateServer = $update_sites[$row->extension_id]->location;
			}

			$base_dir = ($row->client_id == '1') ? JPATH_ADMINISTRATOR : JPATH_SITE;

			if (empty($row->manifest_cache) || $row->manifest_cache == 'false')
			{
				$row->manifest = WatchfulliHelper::readManifest($row);
			}
			else
			{
				$row->manifest = json_decode($row->manifest_cache);
			}

			if (!is_object($row->manifest) || strpos($row->manifest->authorUrl, 'www.joomla.org') !== false)
			{
				if ($row->updateServer == "")
				{
					continue;
				}
			}

			$extension = [
				'version'       => (string) $row->manifest->version,
				'authorurl'     => (string) $row->manifest->authorUrl,
				'creationdate'  => (string) $row->manifest->creationDate,
				'vUpdate'       => (string) $row->vUpdate,
				'updateVersion' => (string) $row->updateVersion,
				'updateServer'  => (string) $row->updateServer,
				'extId'         => (string) $row->updateId,
				'enabled'       => (string) $row->enabled,
			];

			// Save JCE version for later
			if ($row->element == 'com_jce')
			{
				$jceVersion = $row->manifest->version;
			}

			// Save Akeeba info for later
			if ($row->element == 'com_akeeba')
			{
				$this->akeebaBackupInstalled = true;
			}

			$lang->load($row->element, $base_dir, 'en-GB', true);
			$lang->load($row->element . ".sys", $base_dir, 'en-GB', true);
			$lang->load($row->element, $base_dir . "/" . $row->type . "s/" . $row->element, 'en-GB', true);
			$lang->load($row->element . ".sys", $base_dir . "/" . $row->type . "s/" . $row->element, 'en-GB', true);

			switch ($row->type)
			{
				case 'sef_ext':
				case 'language':
				case 'xmap_ext':
					continue 2; // support for these types of extensions is not yet enabled on Watchful, so to avoid confusion...

				case 'component':
					$componentBaseDir = ($row->client_id == '1') ? JPATH_ADMINISTRATOR . '/components' : JPATH_SITE . '/components';
					if ($updateServer = $this->getLiveUpdateServer($componentBaseDir . "/" . $row->element))
					{
						$extension['updateServer'] = $updateServer;
					}
					break;

				case 'module':
					$extension['realname'] = (string) $row->element;
					break;

				case 'plugin':
					$lang->load('plg_' . $row->folder . '_' . $row->element, JPATH_ADMINISTRATOR, 'en-GB');
					$lang->load('plg_' . $row->folder . '_' . $row->element . ".sys", JPATH_ADMINISTRATOR, 'en-GB');
					$lang->load('plg_' . $row->folder . '_' . $row->element, JPATH_SITE . '/plugins/' . $row->folder . '/' . $row->element, 'en-GB');
					$extension['realname'] = 'plg_' . $row->folder . '_' . $row->element;
					break;

				case 'library':
                case 'template':
					break;

                case 'file':
                    $extension['realname'] = 'file_' . $row->element;
                    break;

				case 'package':
					// Languages are distributed as packages so we do an additional check
					if (!$extension['updateServer'])
					{
						$extension['updateServer'] = $this->getLanguageUpdateServer($row->element);
					}
					break;

				default:
					if ($row->name && $row->vUpdate == 1 && $row->name != 'files_joomla')
					{
						$extension = [
							'realname'      => (string) $row->name,
							'version'       => $row->manifest->version ?: "0",
							'type'          => (string) $row->type,
							'creationdate'  => '',
							'vUpdate'       => (string) $row->vUpdate,
							'updateVersion' => (string) $row->updateVersion,
							'extId'         => (string) $row->updateId,
							'enabled'       => (string) $row->enabled,
						];
					}
			}
			$extension['name'] = Text::_($row->name);

			if (empty($extension['name']))
			{
				$extension['name'] = $extension['realname'];
			}

			if (empty($extension['realname']))
			{
				$extension['realname'] = (string) $row->element;
			}

			$extension['variant'] = $this->getExtensionVariant($row);

			// Force UTF-8 encoding on extension name (json_encode needs this)
			if (function_exists('mb_detect_encoding') && mb_detect_encoding($extension['name']) != "UTF-8")
			{
				$extension['name'] = iconv(mb_detect_encoding($extension['name']), 'UTF-8', $extension['name']);
			}

			// add also to the complete array
			$extensions[$row->extension_id]         = $extension;
			$extensions[$row->extension_id]['type'] = $row->type;
		}

		$this->addJcePluginsToExtensionsList($extensions, $jceVersion);

		return ['extensions' => $extensions];
	}

	private function getExtensionVariant($row)
	{
		$extension = new WatchfulliExtension($row);

		return $extension->getVariant();
	}

	/**
	 * @param   array  $extensions
	 */
	private function addJcePluginsToExtensionsList(&$extensions, $jceVersion)
	{
		if (!$this->isJceinstalled() || !$this->isJceTargetVersion($jceVersion))
		{
			return;
		}
		// We DON'T use array_merge because we would lose the keys
		$extensions = $extensions + $this->getJCEplugins();
	}

	/**
	 * Check if JCE is installed
	 *
	 * @return bool
	 */
	private function isJceinstalled()
	{
		$jceBase = JPATH_ADMINISTRATOR . '/components/com_jce/includes/base.php';

		return file_exists($jceBase);
	}

	/**
	 * @param   string  $jceVersion
	 *
	 * @return bool
	 */
	private function isJceTargetVersion($jceVersion)
	{
		return version_compare($jceVersion, '2.3.0', '>=') && version_compare($jceVersion, '2.6.0', '<');
	}

	/**
	 * JCE are a custum way to manage his plugins
	 *
	 * @return array
	 */
	private function getJCEplugins()
	{
		// Removed from JCE 2.6 (no plugins)
		if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_jce/models/installer.php'))
		{
			return [];
		}

		require_once(JPATH_ADMINISTRATOR . '/components/com_jce/includes/base.php');
		require_once(JPATH_ADMINISTRATOR . '/components/com_jce/models/installer.php');

		//Get the list of JCE Plugins
		$WFModelInstaller = new WFModelInstaller();
		$jcePlugins       = [];
		foreach ($WFModelInstaller->getPlugins() as $plugin)
		{
			if (!$plugin->core)
			{
				if (!isset($plugin->id))
				{
					$plugin->id = '';
				}

				$jcePlugins[] = [
					'name'         => (string) WFText::_($plugin->title),
					'realname'     => 'jce_' . $plugin->name,
					'version'      => $plugin->version,
					'type'         => 'jceplugin',
					'authorurl'    => (string) $plugin->authorUrl,
					'creationdate' => $plugin->creationdate,
					'extId'        => (string) $plugin->id,
				];
			}
		}

		return $jcePlugins;
	}

	/**
	 * Get list of all current updates records
	 *
	 * @return array
	 */
	private function getUpdateRecords()
	{
		$query = $this->db->getQuery(true)
			->select('extension_id, update_id, version')
			->from('#__updates');
		$this->db->setQuery($query);
		try
		{
			$this->update_records = $this->db->loadObjectList('extension_id');
		}
		catch (exception $e)
		{
			$this->update_records = [];
		}

		return $this->update_records;
	}

	/**
	 * Get list of all update sites
	 *
	 * @return array
	 */
	private function getUpdateSites()
	{
		$query = $this->db->getQuery(true)
			->select('us.update_site_id')
			->select('location')
			->select('extension_id')
			->from('#__update_sites_extensions AS ue')
			->from('#__update_sites AS us')
			->where('ue.update_site_id = us.update_site_id');
		$this->db->setQuery($query);
		try
		{
			$this->update_sites = $this->db->loadObjectList('extension_id');
		}
		catch (exception $e)
		{
			$this->update_sites = [];
		}

		return $this->update_sites;
	}

	/**
	 * Get list of all extensions records
	 *
	 * @return array
	 */
	private function getExtensionRecords()
	{
		$query = $this->db->getQuery(true)
			->select('name, type, element, folder, client_id, extension_id, manifest_cache, enabled')
			->from('#__extensions AS e')
			->order('type ASC');
		$this->db->setQuery($query);
		try
		{
			return $this->db->loadObjectList();
		}
		catch (exception $e)
		{
			return [];
		}
	}

	/**
	 * Language are "special" extensions. The update servers for languages are
	 * not stored in "manifest_cache" field but directly in the "updates" table
	 *
	 * @param   string  $language
	 *
	 * @return string
	 */
	private function getLanguageUpdateServer($language)
	{
		if (!$language)
		{
			return '';
		}

		$updateserver = '';
		$db           = Factory::getDbo();
		$query        = $db->getQuery(true)
			->select('detailsurl')
			->from('#__updates')
			->where("element = '$language'");
		$db->setQuery($query);
		if ($result = $db->loadResult())
		{
			$updateserver = $result;
		}

		return $updateserver;
	}

	/**
	 * Get Joomla and system versions
	 *
	 * @return array
	 */
	public function getVersions()
	{
		$morevalues = [];
		$this->db->setQuery('SELECT IFNULL(update_id,0) AS jUpdate, version FROM #__updates WHERE name = "Joomla"');
		$upd = $this->db->loadObject();

		$morevalues['j_version'] = Watchfulli::joomla()->getShortVersion();
		if (isset($upd->jUpdate))
		{
			$morevalues['jUpdate'] = $upd->jUpdate;
		}
		if (isset($upd->jUpdate))
		{
			$morevalues['jUpd_version'] = $upd->version;
		}
		$morevalues['php_version']   = phpversion();
		$morevalues['mysql_version'] = $this->db->getVersion();

		if (isset($_SERVER['SERVER_SOFTWARE']))
		{
			$serverSoft = $_SERVER['SERVER_SOFTWARE'];
		}
		else
		{
			if (($sf = getenv('SERVER_SOFTWARE')))
			{
				$serverSoft = $sf;
			}
			else
			{
				$serverSoft = 'NOT_FOUND';
			}
		}

		$morevalues['server_version'] = $serverSoft;

		return $morevalues;
	}

	/**
	 * Get data for some important system files
	 *
	 * @return array
	 */
	public function getFilesProperties()
	{
		$filesProperties = [];
		$filesToCheck    = [
			JPATH_ROOT . '/index.php',
			JPATH_CONFIGURATION . '/configuration.php',
			JPATH_ROOT . '/.htaccess',
			JPATH_ADMINISTRATOR . '/.htaccess',
			JPATH_ADMINISTRATOR . '/index.php',
		];

		$this->db->setQuery('SELECT DISTINCT `template`, `client_id` FROM `#__template_styles` WHERE `template` != "joomla_admin"');
		$currentsTmpl = $this->db->loadObjectList();
		if (!empty($currentsTmpl))
		{
			foreach ($currentsTmpl as $tmpl)
			{
				if (empty($tmpl->template))
				{
					continue;
				}

				if ($tmpl->client_id == 0 && is_dir(JPATH_ROOT . '/templates/' . $tmpl->template))
				{
					$filesToCheck[] = JPATH_ROOT . '/templates/' . $tmpl->template . '/index.php';
					$filesToCheck[] = JPATH_ROOT . '/templates/' . $tmpl->template . '/error.php';
					$filesToCheck[] = JPATH_ROOT . '/templates/' . $tmpl->template . '/component.php';
				}
				if ($tmpl->client_id == 1 && is_dir(JPATH_ADMINISTRATOR . '/templates/' . $tmpl->template))
				{
					$filesToCheck[] = JPATH_ADMINISTRATOR . '/templates/' . $tmpl->template . '/index.php';
					$filesToCheck[] = JPATH_ADMINISTRATOR . '/templates/' . $tmpl->template . '/error.php';
					$filesToCheck[] = JPATH_ADMINISTRATOR . '/templates/' . $tmpl->template . '/component.php';
				}
			}
		}

		foreach ($filesToCheck as $file)
		{
			// if the file exists
			if (file_exists($file))
			{
				$fp    = fopen($file, 'r');
				$fstat = fstat($fp);
				fclose($fp);
				$checksum = md5_file($file);
			}
			elseif ($file !== JPATH_ROOT . '/.htaccess' && $file !== JPATH_ADMINISTRATOR . '/.htaccess')
			{ //If not, we say that the file can't be found
				$checksum = $fstat['size'] = $fstat['mtime'] = 'NOT_FOUND';
			}
			$file              = ['rootpath' => $file, 'size' => $fstat['size'], 'modificationtime' => $fstat['mtime'], 'checksum' => $checksum];
			$filesProperties[] = $file;
		}

		return $filesProperties;
	}

	/**
	 * Get all data from Watchfulli plugins (apps)
	 *
	 * @return array
	 */
	public function getApps()
	{
        $oldPluginsValue = $this->input->get('jmpluginsexvalues', null , 'string');
		jimport('joomla.plugin.helper');
		PluginHelper::importPlugin('watchfulliApps');

		return $this->application->triggerEvent('appMainProgram', [$oldPluginsValue]);
	}

	/**
	 * Get the LiveUpdate server URL from config file
	 *
	 * @param   string  $component_path
	 *
	 * @return string   the update server
	 * @return boolean  false if not found
	 */
	private function getLiveUpdateServer($component_path)
	{
		if (!file_exists($component_path . "/liveupdate/config.php"))
		{
			return false;
		}

		// Parse the file to get the variable. I tried getting an instance of
		// the object and use getUpdateURL() but I had many troubles
		if ($fh = fopen($component_path . "/liveupdate/config.php", "r"))
		{
			$results = [];
			while ($line = fgets($fh))
			{
				$matches = [];
				if (preg_match('/var \$_updateURL\s*=\s*([\'"])([^\'\"]*)/', $line, $matches))
				{
					$results[] = $matches[2];
				}
			}
			if (count($results) == 1)
			{
				return $results[0];
			}
		}

		return false;
	}

	/**
	 * Get a list of all Admin Users
	 * @return array
	 */
    private function getAdminUsers()
    {
        if (!ComponentHelper::getParams('com_watchfulli')->get('admin_user_change', 1)) {
            return [];
        }

        $adminUserList = $this->helper->getAdminUsers();

        $userList = [];

        if (empty($adminUserList)) {
            return [];
        }

        foreach ($adminUserList as $user) {
            $userList[] = [
                "user_login" => $user->username,
                "user_nicename" => $user->name,
                "user_email" => $user->email,
                "id" => $user->id,
                "display_name" => $user->name,
            ];
        }

        return $userList;
    }

	/**
	 * Get latest backup info from local DB
	 *
	 * @return string latest backup date (or empty string if not found)
	 */
    private function getLatestBackupInfo()
    {
        $siteBackups = $this->input->getRaw('siteBackups');

        if (empty($siteBackups))
        {
            Watchfulli::debug('WatchfulliSend::getLatestBackupInfo siteBackups is empty');
            return '';
        }

        $siteBackups = json_decode($siteBackups);

        if (!is_array($siteBackups) || empty($siteBackups))
        {
            Watchfulli::debug('WatchfulliSend::getLatestBackupInfo siteBackups is not an array or is empty');
            return '';
        }

        $akeebaProfiles = array_filter($siteBackups, function ($profile) {
            return $profile->plugin === 'akeebav2';
        });

        if (empty($akeebaProfiles))
        {
            Watchfulli::debug('WatchfulliSend::getLatestBackupInfo akeebaProfiles is empty');
            return '';
        }

        $akeebaProfilesIds = array_map(function ($profile) {
            return $profile->akeebaProfile;
        }, $akeebaProfiles);

        try
        {
            $query = $this->db->getQuery(true)
            ->select('MAX(`backupend`) AS `backupend`')
            ->from($this->getAkeebaBackupStatusTableName())
            ->where(" `status` = 'complete'");

            $query->andWhere('`profile_id` IN (' . implode(',', $akeebaProfilesIds) . ')' );

            Watchfulli::debug('WatchfulliSend::getLatestBackupInfo query: ' . $query);

            $this->db->setQuery($query);
            $result = $this->db->loadResult();

            Watchfulli::debug('WatchfulliSend::getLatestBackupInfo result: ' . $result);

            return $result ?: '';
        }
        catch (Exception $ex)
        {
            Watchfulli::debug('WatchfulliSend::getLatestBackupInfo error while getting latest backup info: ' . $ex->getMessage());
            return '';
        }
    }

    /**
     * @throws Exception
     */
        private function getAkeebaBackupStatusTableName() {
            $tablePrefix = strtolower($this->db->getPrefix());
            Watchfulli::debug('WatchfulliSend::getAkeebaBackupStatusTableName tablePrefix: ' . $tablePrefix);
            $tables = $this->db->setQuery('SHOW TABLES')->loadColumn();

            if (empty($tables) || !is_array($tables)) {
                Watchfulli::debug('WatchfulliSend::getAkeebaBackupStatusTableName unable to get list of tables');
                throw new Exception('Unable to get list of tables');
            }

            array_map('strtolower', $tables);

            Watchfulli::debug('WatchfulliSend::getAkeebaBackupStatusTableName tables: ' . print_r($tables, true));
            if (in_array($tablePrefix.'ak_stats', $tables)) {
                Watchfulli::debug('WatchfulliSend::getAkeebaBackupStatusTableName found table: ' . $tablePrefix.'ak_stats');
                return '#__ak_stats';
            }

            if (in_array($tablePrefix.'akeebabackup_backups', $tables)) {
                Watchfulli::debug('WatchfulliSend::getAkeebaBackupStatusTableName found table: ' . $tablePrefix.'ak_stats');
                return '#__akeebabackup_backups';
            }

            throw new Exception('Unable to find Akeeba Backup status table name');
        }
}
