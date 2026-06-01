<?php
/**
 * @version     admin/classes/extensions/jce.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Table\Table;

defined('_JEXEC') or die;
defined('WATCHFULLI_PATH') or die;

class WatchfulliExtensionsJce
{
	/** @var string|null */
	public static $jce_base = null;

	public function __construct()
	{
		self::$jce_base = JPATH_ADMINISTRATOR . '/components/com_jce/includes/base.php';
	}

	public function jceIsInstalled()
	{
		return file_exists(self::$jce_base);
	}

	/**
	 * Get the current configured JCE KEY
	 *
	 * @return string
	 */
	public function getJceKey()
	{
		$comWatchParam = ComponentHelper::getParams('com_jce');

		return $comWatchParam->get('updates_key');
	}

	/**
	 * Save JCE key in com_jce configuration
	 *
	 * @param         $key
	 * @param   bool  $forceNull
	 *
	 * @return bool|void
	 */
	public function saveJceKey($key, $forceNull = false)
	{
		if (empty($key) && !$forceNull)
		{
			return;
		}

		$params = ComponentHelper::getParams('com_jce');
		$params->set('updates_key', $key);

		$componentId = ComponentHelper::getComponent('com_jce')->id;
		$table       = Table::getInstance('extension');
		$table->load($componentId);
		$table->bind(['params' => $params->toString()]);

		if (!$table->check())
		{
			return false;
		}

		if (!$table->store())
		{
			return false;
		}

		return true;
	}

	/**
	 * @throws Exception
	 */
	public function installJcePlugin($id)
	{
		require_once(self::$jce_base);
		require_once(JPATH_ADMINISTRATOR . '/components/com_jce/models/updates.php');

		$WFModelUpdates = new WFModelUpdates();
		/** @var CMSApplication $application */
		$application = Factory::getApplication();
		$input       = $application->input;

		$input->set('id', $id);
		$result = json_decode($WFModelUpdates->download());

		if (!empty($result->error))
		{
			return $result->error;
		}

		if (!$result->file)
		{
			return "COM_JMONITORING_CANT_DOWNLOAD_UPDATE";
		}

		$input->set('file', $result->file);
		$input->post->set('hash', $result->hash);
		$input->set('installer', $result->installer);
		$input->set('type', $result->type);

		$install = json_decode($WFModelUpdates->install());
		if ($install->error)
		{
			return "COM_JMONITORING_CANT_INSTALL_UPDATE";
		}

		$plugin_name_parts = explode("_", $result->file);
		if (count($plugin_name_parts) == 3)
		{
			$finalDir = $plugin_name_parts[1];
		}
		else
		{
			$finalDir = $plugin_name_parts[1] . '_' . $plugin_name_parts[2];
		}

		$source      = JPATH_ROOT . "/components/com_watchfulli/editor/tiny_mce/plugins/" . $finalDir;
		$destination = JPATH_ROOT . "/components/com_jce/editor/tiny_mce/plugins/" . $finalDir;

		if (!Folder::delete($destination))
		{
			return 'JCE - can delete ' . $destination;
		}

		if (!Folder::move($source, $destination))
		{
			return 'JCE - can move from ' . $source . ' to ' . $destination;
		}

		$path = JPATH_ROOT . "/components/com_watchfulli/editor/";
		if (!Folder::delete($path))
		{
			return 'JCE - can delete ' . $path;
		}

		return "ok_" . $result->file;
	}

}
