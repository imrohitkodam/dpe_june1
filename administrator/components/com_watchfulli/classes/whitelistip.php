<?php
/**
 * @version     admin/classes/whitelistIp.php 2021-12-12 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseDriver;

defined('_JEXEC') or die('Restricted access');

class WatchfulliWhitelistIp
{
	/** @var CMSApplication */
	private $application;

	/** @var DatabaseDriver */
	private $db;

	/** @var WatchfulliHelper */
	private $helper;

	private $ip = [];
	private $result = [];

	/**
	 * Small description for provider that allow a comment
	 *
	 * @var string
	 */
	private $desc = 'watchful.net';

	/**
	 * WatchfulliWhitelistIp constructor.
	 *
	 * @param   string  $ip      JSON Array|string
	 * @param   string  $action  add|del
	 * @param   bool    $api     False, if you don't want an application close at the end
	 *                           You also need to add a Try Catch before calling this function
	 *
	 * @throws Exception
	 */
	public function __construct($ip = null, $action = null, $api = true)
	{
		$this->application = Factory::getApplication();
		$this->db          = Factory::getDbo();
		$this->ip          = $this->getIps($ip);
		$this->helper      = new WatchfulliHelper();

		$action    = $this->getAction($action);
		$providers = $this->getInstalledFirewalls();
		if (empty($providers))
		{
			throw new Exception('No supported fireWall is installed on this site.');
		}

		$providers = preg_replace("/^com_/", '', $providers);

		foreach ($providers as $provider)
		{
			$fct = $action . ucfirst($provider);
			if (!method_exists($this, $fct))
			{
				throw new Exception('Invalid method ' . $fct);
			}

			$this->$fct();

			$this->result['provider'][] = $provider;
		}

		if (!$api)
		{
			return true;
		}

		$helper = new WatchfulliHelper();
		$helper->response(
			[
				'task'   => 'whitelistIp',
				'action' => $action,
				'status' => 'success',
				'data'   => $this->result,
			]
		);
	}

	/**
	 * Check which Firewall is installed
	 *
	 * @return mixed
	 * @throws Exception
	 * @todo remove this one and use the Helper in next client release
	 */
	private function getInstalledFirewalls()
	{
		return $this->db->setQuery(
			$this->db->getQuery(true)
				->select('element')
				->from('#__extensions')
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_admintools'), 'OR')
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_rsfirewall'))
		)->loadColumn();
	}

	public function getResult()
	{
		return $this->result;
	}

	/**
	 * Get action send in GET parameter
	 *
	 * @param   null  $action
	 *
	 * @return string
	 * @throws Exception
	 */
	private function getAction($action = null)
	{
		if (empty($action))
		{
			$action = $this->getParam('action');
		}

		switch ($action)
		{
			case 'add':
				return 'add';
				break;
			case 'del':
				return 'del';
				break;
			default:
				throw new Exception('Invalid value for parameter "action"');
		}
	}

	/**
	 * Get IPs send in GET parameters
	 *
	 * @param   null  $ip
	 *
	 * @return array|mixed
	 * @throws Exception
	 */
	private function getIps($ip = null)
	{
		if (empty($ip))
		{
			$ip = $this->getParam('ip');
		}

		$ip = json_decode($ip);

		if (json_last_error())
		{
			throw new Exception('Invalid IP parameter');
		}

		if (empty($ip))
		{
			throw new Exception('Empty IP not allowed');
		}

		$ip = $this->cleanIps($ip);

		if (is_array($ip))
		{
			return $ip;
		}

		return [$ip];
	}

	/**
	 * Add a new IP(s) to AdminTools
	 *
	 * @return mixed
	 */
	private function addAdmintools()
	{
		$provider   = 'admintools';
		$config     = $this->getAdmintoolsConfig();
		$ipInConfig = $this->getAdmintoolsWhitelistedIp($config);

		foreach ($this->ip as $ip)
		{
			if (in_array($ip, $ipInConfig))
			{
				$this->result['ip'][$ip][$provider] = 'Already in the AdminTools white list';
				continue;
			}

			$ipInConfig[]                       = $ip;
			$this->result['ip'][$ip][$provider] = 'IP added to the AdminTools white list';
		}

        # https://github.com/watchfulli/client/issues/1158
		$config->neverblockips = Version::MAJOR_VERSION == 3 ? implode(',', $ipInConfig) : $ipInConfig;

		return $this->setAdmintoolsConfig($config);
	}

	/**
	 * Add a new IP(s) to RSFirewall
	 *
	 * @return mixed
	 */
	private function addRsfirewall()
	{
		$provider = 'rsfirewall';

		$rsFirewallIps = $this->getRsfirewallIps();

		foreach ($this->ip as $ip)
		{
			if (!array_key_exists($ip, $rsFirewallIps))
			{
				$this->newRsfirewallIp($ip);
				$this->result['ip'][$ip][$provider] = 'IP added to the RSFirewall white list';
				continue;
			}

			$this->editRsfirewallIp($rsFirewallIps, $ip, $provider);
		}

		return true;
	}

	/**
	 * Remove IP(s) from AdminTools
	 *
	 * @return mixed
	 */
	private function delAdmintools()
	{
		$provider   = 'admintools';
		$config     = $this->getAdmintoolsConfig();
		$ipInConfig = $this->getAdmintoolsWhitelistedIp($config);

		foreach ($this->ip as $ip)
		{
			$index = array_search($ip, $ipInConfig);
			if ($index !== false)
			{
				unset($ipInConfig[$index]);
				$this->result['ip'][$ip][$provider] = 'IP removed from the AdminTools white list';
				continue;
			}

			$this->result['ip'][$ip][$provider] = 'IP not found in the AdminTools white list';
		}

		$config->neverblockips = implode(',', $ipInConfig);

		return $this->setAdmintoolsConfig($config);
	}

	/**
	 * Remove IP(s) from RSFirewall
	 *
	 * @return mixed
	 */
	private function delRsfirewall()
	{
		$provider = 'rsfirewall';

		$rsFirewallIps = $this->getRsfirewallIps();

		foreach ($this->ip as $ip)
		{
			if (!array_key_exists($ip, $rsFirewallIps))
			{
				$this->result['ip'][$ip][$provider] = 'IP not found in the RSFirewall white list';
				continue;
			}

			$this->delRsfirewallIp($ip);
			$this->result['ip'][$ip][$provider] = 'IP removed from the RSFirewall white list';
		}

		return true;
	}

	/**
	 * Get request GET params
	 *
	 * @param $param
	 *
	 * @return mixed
	 */
	private function getParam($param)
	{
		return $this->application->input->get($param, null, 'raw');
	}

	/**
	 * Get the AdminTools config stored in DB
	 *
	 * @return mixed
	 * @throws Exception
	 */
	private function getAdmintoolsConfig()
	{
		$configString = $this->db->setQuery(
			$this->db->getQuery(true)
				->select('value')
				->from('#__admintools_storage')
				->where($this->db->quoteName('key') . ' = ' . $this->db->quote('cparams'))
		)->loadResult();

		if (is_null($configString))
		{
			return new stdClass();
		}

		$config = json_decode($configString);

		if (json_last_error())
		{
			throw new Exception('Error when decoding Admintools config');
		}

		return $config;
	}

	/**
	 * Extract a list of IP from the AdminTools config
	 *
	 * @param $config stdClass AdminTools config
	 *
	 * @return array
	 */
	private function getAdmintoolsWhitelistedIp($config)
	{
		if (empty($config->neverblockips))
		{
			return [];
		}

        if (is_array($config->neverblockips))
        {
            return $config->neverblockips;
        }

        if (!is_string($config->neverblockips))
        {
            return [];
        }

		return explode(',', $config->neverblockips);
	}

	/**
	 * Update AdminTools DB Config
	 *
	 * @param $config
	 *
	 * @return mixed
	 * @throws Exception
	 */
	private function setAdmintoolsConfig($config)
	{

		$configString = json_encode($config);

		if (json_last_error())
		{
			throw new Exception('Error when encoding Admintools config');
		}

		$configExist = !is_null(
			$this->db->setQuery(
				$this->db->getQuery(true)
					->select('value')
					->from('#__admintools_storage')
					->where($this->db->quoteName('key') . ' = ' . $this->db->quote('cparams'))
			)->loadResult()
		);

		// Config already in DB perform an update
		if ($configExist)
		{
			return $this->db->setQuery(
				$this->db->getQuery(true)
					->update('#__admintools_storage')
					->set($this->db->quoteName('value') . ' = ' . $this->db->quote($configString))
					->where($this->db->quoteName('key') . ' = ' . $this->db->quote('cparams'))
			)->execute();
		}

		// Config not already in DB perform an insert
		return $this->db->setQuery(
			$this->db->getQuery(true)
				->insert('#__admintools_storage')
				->columns(
					$this->db->quoteName(
						[
							'key',
							'value',
						]
					)
				)
				->values(
					$this->db->quote('cparams') . ', ' . $this->db->quote($configString)
				)
		)->execute();
	}

	/**
	 * Get list of all IPs in RSFireWall
	 *
	 * @return mixed
	 */
	private function getRsfirewallIps()
	{
		return $this->db->setQuery(
			$this->db->getQuery(true)
				->select('*')
				->from('#__rsfirewall_lists')
		)->loadObjectList('ip');
	}

	/**
	 * White list an IP in RSFirewall config
	 *
	 * @param $ip
	 *
	 * @return bool
	 */
	private function newRsfirewallIp($ip)
	{
		$ipObject            = new stdClass();
		$ipObject->ip        = $ip;
		$ipObject->type      = 1;
		$ipObject->reason    = $this->desc;
		$ipObject->published = 1;
		$ipObject->date      = date('Y-m-d H:i:s');

		return $this->db->insertObject(
			'#__rsfirewall_lists',
			$ipObject
		);
	}

	/**
	 * Edit a RSFirewall IP config
	 *
	 * @param $rsFirewallIps
	 * @param $ip
	 * @param $provider
	 */
	private function editRsfirewallIp($rsFirewallIps, $ip, $provider)
	{
		$newRsFirewallIp            = clone $rsFirewallIps[$ip];
		$newRsFirewallIp->type      = 1;
		$newRsFirewallIp->reason    = $this->desc;
		$newRsFirewallIp->published = 1;

		if ($newRsFirewallIp == $rsFirewallIps[$ip])
		{
			$this->result['ip'][$ip][$provider] = 'Already in the RSFirewall white list';

			return;
		}

		$this->db->updateObject('#__rsfirewall_lists', $newRsFirewallIp, 'id');
		$this->result['ip'][$ip][$provider] = 'IP updated in the RSFirewall white list';
	}

	/**
	 * Delete an IP from RSFirewall config
	 *
	 * @param $ip
	 *
	 * @return mixed
	 */
	private function delRsfirewallIp($ip)
	{
		return $this->db->setQuery(
			$this->db->getQuery(true)
				->delete('#__rsfirewall_lists')
				->where($this->db->quoteName('ip') . ' = ' . $this->db->quote($ip))
		)->execute();
	}

	/**
	 * Clean the IPs for only get numbers and dots.
	 *
	 * IPs can come from a CURL get of a TXT file with special Chars like return line tabs
	 *
	 * @param   array|string  $ip
	 *
	 * @return array|string
	 */
	private function cleanIps($ip)
	{
		if (!is_array($ip))
		{
			return preg_replace("/[^0-9\.]/", "", $ip);
		}

		foreach ($ip as &$item)
		{
			$item = preg_replace("/[^0-9\.]/", "", $item);
		}

		return $ip;
	}
}
