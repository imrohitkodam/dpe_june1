<?php
/**
 * @version     admin/classes/actions.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Cache\CacheController;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Updater\Updater;

defined('_JEXEC') or die();
defined('WATCHFULLI_PATH') or die();

class WatchfulliActions
{
    const EXTENSION_TYPES = [
        'component' => '0',
        'module' => '1',
        'plugin' => '2',
        'library' => '3',
        'update' => '4',
        'jceplugin' => '5',
        'template' => '6',
        'theme' => '6',
        'package' => '7',
        'file' => '8',
    ];

    /** @var CMSApplication */
    private $application;

    /** @var WatchfulliAppSwitcher */
    private $appSwitcher;

	/**
	 * @throws Exception
	 */
	public function __construct()
	{
		if (!Watchfulli::checkToken())
		{
			Watchfulli::debug("[ERROR] Invalid token");
			die;
		}
		$this->application = Factory::getApplication();
        $this->appSwitcher = new WatchfulliAppSwitcher();
	}

	/**
	 *
	 */
	public function test()
	{
		die("<~ok~>");
	}

	/**
	 * Read and return local log (removing all sensitive information like the secret key)
	 */
	public function getLog()
	{
		$log_file = Factory::getConfig()->get('log_path') . '/watchfulli.log.php';
		if (!file_exists($log_file))
		{
			$this->application->close('COM_JMONITORING_CLIENT_GETLOG_NO_LOG');
		}

		$rows = [];
		foreach (explode("\n", file_get_contents($log_file)) as $row)
		{
			if (preg_match('/secret/', $row))
			{
				continue;
			}
			$rows[] = $row;
		}

		$this->response(
			[
				'task'    => 'getLog',
				'message' => implode("\n", $rows),
			]
		);
	}

    /**
     * Run an extension update
     *
     * @return void (closes the app with a success or error message)
     * @throws Exception
     */
	public function doUpdate()
	{
		if ($this->application->get('offline'))
		{
			Watchfulli::debug("WatchfulliActions::doUpdate - Site is offline, exiting");
			$this->response(
				[
					'task'    => 'doUpdate',
					'status'  => 'offline',
					'message' => 'COM_JMONITORING_CLIENT_SITE_IS_OFFLINE',
				]
			);
		}

		$extParams          = $this->getExtensionParameters();

		// core update
		if ($extParams->type == 99)
		{
			Watchfulli::debug("WatchfulliActions::doUpdate - Core update, calling doInstallCore");
            $this->doInstallCore($extParams);
			$this->application->close();
            return;
		}

        Watchfulli::debug("WatchfulliActions::doUpdate calling doInstall");
        $updater = new WatchfulliExtensionUpdater();
        $this->response($updater->update($extParams));
        $this->application->close();
	}

	/**
	 * Custom installer for core updates
	 * Added to fix issues with updating Joomla! 3.4 => 3.5
	 * @throws Exception
	 */
	public function doInstallCore($extParams)
	{
		$step    = $this->application->input->get('step');
		$updater = new WatchfulliCoreUpdater();
		switch ($step)
		{
			case 'install':
			case 'download':
			case 'step':
			case 'finalise':
			case 'cleanup':
				$updater->$step($extParams->update_url);
				break;
			default:
				throw new Exception('COM_JMONITORING_UNKNOWN_STEP');
		}
	}

	/**
	 * Clean Joomla cache
	 *
	 * @return boolean
	 */
	public function cleanJoomlaCache()
	{
        /** @var CacheController $cache */
		/** @todo not supported in Joomla 3 */
		$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController();

		return $cache->cache->clean() && $cache->cache->gc();
	}

	public function fileManager()
	{
		$action = $this->application->input->getCmd('action');
		$path   = $this->application->input->getString('path');
		$args   = $this->application->input->get('args', [], 'array');

		$path = JPATH_ROOT . $path;

		// whitelist methods
		$allowed = ['chmod', 'delete', 'read', 'write'];
		if (!in_array($action, $allowed))
		{
			$this->response(
				[
					'task'    => 'fileManager',
					'success' => false,
					'message' => 'COM_JMONITORING_FILE_MANAGER_UNKNOWN_ACTION',
				]
			);

			return false;
		}

		// attempt to take action
		try
		{
			$callback = ['WatchfulliFile', $action];
			array_unshift($args, $path);
			$result = call_user_func_array($callback, $args);
			$this->response(
				[
					'task'    => 'fileManager',
					'success' => true,
					'result'  => $result,
				]
			);

			return true;
		}
		catch (RuntimeException $e)
		{
			$this->response(
				[
					'task'    => 'fileManager',
					'success' => false,
					'message' => $e->getMessage(),
				]
			);

			return false;
		}
	}

	/**
	 * Check extension update using native updater
	 */
	public function checkExtensionsUpdates()
	{
		$updater = Updater::getInstance();
		$results = $updater->findUpdates(0, 0);

		if (!$results)
		{
			$this->response(
				[
					'task'    => 'checkExtensionsUpdates',
					'success' => false,
					'result'  => '',
				]
			);
		}

		$this->response(
			[
				'task'    => 'checkExtensionsUpdates',
				'success' => true,
				'result'  => '',
			]
		);
	}

	/**
	 * Get parameters from request
	 *
	 * @return stdClass or null
	 */
	private function getExtensionParameters()
	{
        $default = new stdClass();
        $default->type = null;
        $default->package_name = null;
        $default->update_url = null;
        $default->ext_prefix = null;
        $default->is_paid = false;
        $default->has_valid_license_key = false;

		$extParams = $this->application->input->get('extParams', null, 'string');
		if (!$extParams)
		{
			Watchfulli::debug("WatchfulliActions::doUpdate - No extParams!");

			return $default;
		}
		Watchfulli::debug("WatchfulliActions::doUpdate - Raw extParams: " . print_r($extParams, true));

		$extParams = json_decode(stripslashes($extParams));
		if (!is_object($extParams))
		{
			Watchfulli::debug("WatchfulliActions::doUpdate - Unable to get a valid object from extParams. Possibly the JSON is broken.");

            return $default;
		}
		Watchfulli::debug("WatchfulliActions::doUpdate - Decoded extParams: " . print_r($extParams, true));

        $extParams->type = empty($extParams->type) ? null : $extParams->type;
        $extParams->package_name = empty($extParams->package_name) ? null : $extParams->package_name;
        $extParams->update_url = empty($extParams->update_url) ? null : $extParams->update_url;
        $extParams->ext_prefix = empty($extParams->ext_prefix) ? null : $extParams->ext_prefix;
        $extParams->is_paid = empty($extParams->is_paid) ? false : $extParams->is_paid;
        $extParams->has_valid_license_key = empty($extParams->has_valid_license_key) ? false : $extParams->has_valid_license_key;

		return $extParams;
	}

    public function changeExtensionStatus()
    {
        $realname = $this->application->input->getString('realname');
        $type = $this->application->input->getInt('type');
        $status = $this->application->input->getInt('status');

        $parsedType = array_search($type, self::EXTENSION_TYPES);

        if (!$realname || !$parsedType || (!$status && $status !== 0)) {
            $this->response(
                [
                    'task' => 'changeExtensionStatus',
                    'success' => false,
                    'result' => 'Unable to get the required parameters: '.$realname.' - '.$parsedType.' - '.$status,
                ]
            );
        }

        if ($parsedType === 'file') {
            $realname = str_replace('file_', '', $realname);
        }

        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled').' = '.$db->quote($status))
            ->where($db->quoteName($parsedType === 'plugin' ? 'name' : 'element').' = '.$db->quote($realname))
            ->where($db->quoteName('type').' = '.$db->quote($parsedType));

        $db->setQuery($query);

        try {
            $db->execute();
        } catch (Exception $e) {
            $this->response(
                [
                    'status' => 'exception',
                    'message' => $e->getMessage(),
                ]
            );
            return;
        }

        $this->response(
            [
                'status' => 'success',
                'message' => $db->getAffectedRows(),
            ]
        );
    }

	/**
	 * Close the application with a JSON encoded message
	 *
	 * @param   array  $data
	 */
	protected function response($data)
	{
		if (!is_array($data))
		{
			$data = [
				'status'  => 'exception',
				'message' => '$data is not an array',
			];
		}

		$default = [
			'status'         => 'success',
			'message'        => '',
			'joomlaMessages' => $this->application->getMessageQueue(),
		];

		$this->application->close(json_encode(array_merge($default, $data)));
	}

}
