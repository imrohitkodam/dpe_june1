<?php
/* ======================================================
# Cookies Policy Notification Bar for Joomla! - v4.0.0 (Pro version)
# -------------------------------------------------------
# For Joomla! CMS
# Author: Web357 (Yiannis Christodoulou)
# Copyright (©) 2009-2020 Web357. All rights reserved.
# License: GNU/GPLv3, http://www.gnu.org/licenses/gpl-3.0.html
# Website: https:/www.web357.com/
# Demo: https://demo.web357.com/joomla/cookiespolicynotificationbar
# Support: support@web357.com
# Last modified: 26 Nov 2020, 01:41:47
========================================================= */

defined('_JEXEC') or die;
require_once __DIR__ . '/script.install.helper.php';

class PlgSystemCookiespolicynotificationbarInstallerScript extends PlgSystemCookiespolicynotificationbarInstallerScriptHelper
{
	public $name           = 'Cookies Policy Notification Bar';
	public $alias          = 'cookiespolicynotificationbar';
	public $extension_type = 'plugin';
	public $plugin_folder  = 'system';

	public function onAfterInstall($route)
	{
		$this->createTable();
		$this->alterTable();
		$this->deleteOldFiles();
	}

	public function uninstall($adapter)
	{
		$this->dropTable();
	}

	private function createTable()
	{
		$query = "CREATE TABLE IF NOT EXISTS `#__plg_system_cookiespolicynotificationbar_logs` (
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`ip_address` VARCHAR(15) NOT NULL,
			`status` VARCHAR(255) NOT NULL,
			`datetime` datetime NOT NULL,
			`user_id` int(11) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
		$this->db->setQuery($query);
		$this->db->execute();
	}

	private function alterTable()
	{
		// add user_id column
		$query = "SHOW COLUMNS FROM `" . $this->db->getPrefix() . "plg_system_cookiespolicynotificationbar_logs` LIKE 'user_id'";
		$this->db->setQuery($query);
		$has_field = $this->db->loadResult();
		if (!$has_field)
		{
			$query = "ALTER TABLE `#__plg_system_cookiespolicynotificationbar_logs` ADD COLUMN `user_id` int(11) NOT NULL AFTER `datetime`";
			$this->db->setQuery($query);
			$this->db->execute();
		}

		// add cookiesinfo column
		$query = "SHOW COLUMNS FROM `" . $this->db->getPrefix() . "plg_system_cookiespolicynotificationbar_logs` LIKE 'cookiesinfo'";
		$this->db->setQuery($query);
		$has_field = $this->db->loadResult();
		if (!$has_field)
		{
			$query = "ALTER TABLE `#__plg_system_cookiespolicynotificationbar_logs` ADD COLUMN `cookiesinfo` text NOT NULL AFTER `user_id`";
			$this->db->setQuery($query);
			$this->db->execute();
		}	
	}

	private function dropTable()
	{
		$query = "DROP TABLE `#__plg_system_cookiespolicynotificationbar_logs`";
		$this->db->setQuery($query);
		$this->db->execute();
	}

	private function deleteOldFiles()
	{
		$this->delete(
			array(
				JPATH_SITE . '/plugins/system/cookiespolicynotificationbar/lib/vendor/composer/ca-bundle/',
				JPATH_SITE . '/plugins/system/cookiespolicynotificationbar/lib/vendor/composer/installed.json',
				JPATH_SITE . '/plugins/system/cookiespolicynotificationbar/lib/vendor/geoip2/geoip2/.gitmodules',
				JPATH_SITE . '/plugins/system/cookiespolicynotificationbar/lib/vendor/geoip2/geoip2/.php_cs',
				JPATH_SITE . '/plugins/system/cookiespolicynotificationbar/lib/vendor/geoip2/geoip2/CHANGELOG.md',
				JPATH_SITE . '/plugins/system/cookiespolicynotificationbar/lib/vendor/geoip2/geoip2/composer.json',
				JPATH_SITE . '/plugins/system/cookiespolicynotificationbar/lib/vendor/maxmind-db/reader/autoload.php',
				JPATH_SITE . '/plugins/system/cookiespolicynotificationbar/lib/vendor/maxmind-db/reader/composer.json',
				JPATH_SITE . '/plugins/system/cookiespolicynotificationbar/lib/vendor/maxmind-db/reader/ext/',
			)
		);
	}
}