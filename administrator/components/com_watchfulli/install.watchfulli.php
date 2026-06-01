<?php
/**
 * @version     install.watchfulli.php 2020-05-28 zanardi
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseDriver;

defined('_JEXEC') or defined('JPATH_PLATFORM') or die;

if (!defined('WATCHFULLI_PATH'))
{
	define('WATCHFULLI_PATH', JPATH_ADMINISTRATOR . '/components/com_watchfulli');
}

class com_watchfulliInstallerScript
{
	/** @var Language */
	private $language;

	/** @var DatabaseDriver */
	private $db;

	public function __construct()
	{
		$this->language = Factory::getLanguage();
		$this->db       = Factory::getDbo();
		$this->app      = Factory::getApplication();
	}

	/**
	 * @param   string  $type
	 * @param   object  $parent
	 *
	 * @return  boolean
	 */
	public function preflight($type, $parent)
	{
		$this->language->load('com_watchfulli');
		if (!version_compare($this->getPhpVersion(), '5.3.10', '>='))
		{
			$message = Text::sprintf('COM_WATCHFULLI_INSTALL_PHP_TOO_OLD', '5.3.10');
			$this->addtoLog($message);

			return false;
		}

		return true;
	}

	/**
	 * @param   string  $type
	 * @param   object  $parent
	 *
	 * @throws Exception
	 */
	public function postflight($type, $parent)
	{
		if ($type === 'uninstall')
		{
			return;
		}

		if ($type === 'update')
		{
			$this->cleanUpdateRecord();
			$this->cleanUpdateSites();
		}

		$this->language->load('com_watchfulli');
        $watchfulSecretKey = $this->getWatchfulSecretKey($type);

		$message = Text::_('COM_WATCHFULLI_INSTALL_MESSAGE')
			. Text::_('COM_WATCHFULLI_INSTALL_BEFORE_FORM')
            .'<p><a href="'.$this->buildAddToWatchfulUrl($watchfulSecretKey).'" class="btn btn-primary" target="_blank">'.Text::_('COM_WATCHFULLI_ADDSITE').'</a></p>'
			. Text::_('COM_WATCHFULLI_INSTALL_BEFORE_KEY')
            .'<p><input readonly="readonly" type="text" style="width:250px;" size="55" value="'.$watchfulSecretKey.'" /></p>'
			. Text::_('COM_WATCHFULLI_INSTALL_AFTER_KEY');

		if (!$this->isFopenEnabled())
		{
			$message .= Text::_('COM_WATCHFULLI_INSTALL_NO_FOPEN');
		}
		if (!$this->isMaxExecutionTimeSetAsSuggested())
		{
			$message .= Text::_('COM_WATCHFULLI_INSTALL_NO_MAX_EXECUTION_TIME');
		}
		if (!$this->isCurlEnabled())
		{
			$message .= Text::_('COM_WATCHFULLI_INSTALL_NO_CURL');
		}
		if (!$this->isZipEnabled())
        {
            $message .= Text::_('COM_WATCHFULLI_INSTALL_NO_ZIP');
        }
		if (!$this->isTimeSynchronizedWithWatchful())
		{
			$message .= Text::_('COM_WATCHFULLI_INSTALL_NO_TIME_SYNC');
		}

		$this->app->enqueueMessage($message);

		// Add Watchful IPs in Admintools & RSFirewall config
		$this->whiteListWatchful();
	}

    private function buildAddToWatchfulUrl($watchfulSecretKey)
    {
        require_once WATCHFULLI_PATH . '/classes/helper.php';

        $query = http_build_query(
            array(
                'name' => $this->app->get('sitename'),
                'access_url' => Uri::root(),
                'secret_word' => $watchfulSecretKey,
                'word_akeeba' => WatchfulliHelper::getAkeebaSecretKey(),
                'cms' => 'joomla',
            )
        );
        return 'https://app.watchful.net/app/?'.$query.'#/dashboard/';
    }

	/**
	 * Delete previously existing update records
	 *
	 * @return bool
	 */
	private function cleanUpdateRecord()
	{
		$this->db->setQuery("DELETE FROM #__updates WHERE element = 'com_watchfulli'");

		return $this->db->execute();
	}

	/**
	 * Delete previously existing update sites if there are more than ones
	 *
	 * @return bool
	 */
	private function cleanUpdateSites()
	{
		$this->db->setQuery("SELECT COUNT(*) FROM #__update_sites WHERE name = 'Watchfully Slave Update'");
		if ($this->db->loadResult() <= 1)
		{
			return true;
		}

		$this->db->setQuery("DELETE FROM #__update_sites WHERE name = 'Watchfully Slave Update'");

		return $this->db->execute();
	}

	/**
	 * Fetches the secret key or creates one if empty
	 *
	 * @param   string  $type  "install" or "update"
	 *
	 * @return string
	 */
	private function getWatchfulSecretKey($type = 'install')
	{
		jimport('joomla.user.helper');

		$app                = Factory::getApplication();
		$params             = ComponentHelper::getParams('com_watchfulli');
		$current_secret_key = $params->get('secret_key');
		$new_secret_key     = md5(UserHelper::genRandomPassword(32));
		$old_generated_key  = md5('watch' . $app->get('secret') . 'fulli');

		// for a new install
		if ($type === 'install')
		{
			$this->saveJsonSecret($new_secret_key);

			return $new_secret_key;
		}

		if (empty($current_secret_key) || $current_secret_key == $old_generated_key)
		{
			// this is an update - we must update the key on the master too
			if (!$this->updateMaster($old_generated_key, $new_secret_key))
			{
				// If the connection to the watchful API fails, we alert the customer that the client is broken
				$app->enqueueMessage(Text::_('COM_WATCHFULLI_INSTALL_SECRETKEY_UPDATE_MASTER_FAILED'), 'alert');
			}
			$this->saveJsonSecret($new_secret_key);

			return $new_secret_key;
		}

		return $current_secret_key;
	}

	/**
	 * @param           $endpoint
	 * @param   array   $data
	 * @param   string  $method
	 *
	 * @return stdClass
	 */
	private function callWatchfulApi($endpoint, $data = [], $method = 'GET')
	{
		$uri = 'https://app.watchful.net/api/v1/' . $endpoint;
		$ch  = curl_init();
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 45);
		curl_setopt($ch, CURLOPT_REFERER, Uri::root());
		curl_setopt($ch, CURLOPT_USERAGENT, 'Watchfulli/1.0 (+http://www.watchful.net)');
		curl_setopt($ch, CURLOPT_URL, $uri);
		switch ($method)
		{
			case 'POST':
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_POST, true);
				break;
			case 'DELETE':
			case 'PUT':
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
				break;
			default:
				if (!empty($data))
				{
					curl_setopt($ch, CURLOPT_URL, $uri . '?' . http_build_query($data));
				}
		}
		$response        = new stdClass();
		$response->data  = curl_exec($ch);
		$response->info  = curl_getinfo($ch);
		$response->error = curl_error($ch);
		curl_close($ch);

		return $response;
	}

	/**
	 * @param $response
	 *
	 * @return bool|mixed
	 */
	private function decodeWatchfulResponse($response)
	{

		if (!is_object($response))
		{
			$this->addtoLog('[decodeWatchfulResponse] Response is not an object');

			return false;
		}

		$data = json_decode($response->data);
		if (json_last_error() !== JSON_ERROR_NONE)
		{
			$this->addtoLog('[updateMaster] Response is not a JSON string');

			return false;
		}

		if (empty($data->msg))
		{
			$this->addtoLog('[updateMaster] Response msg is empty');

			return false;
		}

		return $data->msg;
	}

	/**
	 * @return bool
	 */
	private function isTimeSynchronizedWithWatchful()
	{
		if (!$this->isCurlEnabled())
		{
			return false;
		}
		$systemTime = time();
		$response   = $this->callWatchfulApi('time');
		$data       = $this->decodeWatchfulResponse($response);
		if (empty($data->time))
		{
			return false;
		}

		return ($data->time > $systemTime - WatchfulliHelper::MAX_TIME_DEVIATION) && ($data->time < $systemTime + WatchfulliHelper::MAX_TIME_DEVIATION);
	}

	/**
	 * @return bool
	 */
	private function isMaxExecutionTimeSetAsSuggested()
	{
		$maxExecutionTimeValue = ini_get('max_execution_time');

		return $maxExecutionTimeValue >= 120 || $maxExecutionTimeValue === '0';
	}

	/**
	 * @return bool
	 */
	private function isFopenEnabled()
	{
		return in_array(ini_get('allow_url_fopen'), ['On', '1'], true);
	}

	/**
	 * @return bool
	 */
	private function isCurlEnabled()
	{
		return extension_loaded('curl');
	}

    /**
     * @return bool
     */
	private function isZipEnabled()
    {
        return extension_loaded('zip');
    }

	/**
	 * This method calls the Watchful server the save a new key without the user
	 * needing to manually edit the site on Watchful dashboard. This only
	 * happens when user updates an existing Watchful client and the site is
	 * still using the old, relatively less secure key.
	 *
	 * We don't like to make "home calls" but the JED insisted that it was not
	 * enough to generate new keys on new installs and give existing clients the
	 * ability to refresh.
	 *
	 * With this "home call" we generate and save a new secret key for you and
	 * you won't have to do anything.
	 *
	 * @param   string  $old_generated_key  the old, less secure key
	 * @param   string  $new_secret_key     the new, more secure key
	 *
	 * @return boolean
	 */
	private function updateMaster($old_generated_key, $new_secret_key)
	{
		$response = $this->callWatchfulApi(
			'changekey',
			[
				'key'    => $old_generated_key,
				'url'    => urlencode(Uri::root()),
				'newkey' => $new_secret_key,
			]
		);

		return $this->decodeWatchfulResponse($response) !== false;
	}

	/**
	 * @param   string  $message
	 */
	private function addtoLog($message)
	{
		JLog::add($message, JLog::DEBUG, 'watchful');
	}

	/**
	 * Save the secret as component parameter in JSON format
	 *
	 * @param   string  $secret
	 *
	 * @todo probably we should use Joomla framework commands instead of manual
	 *       saves
	 *
	 */
	private function saveJsonSecret($secret)
	{
		try
		{
			$params = $this->db->setQuery(
				$this->db->getQuery(true)
					->select('params')
					->from('#__extensions')
					->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_watchfulli'))
			)->loadResult();
			if (empty($params))
			{
				$params = '{}';
			}
			$json = json_decode($params);
			if (isset($json->secret_key) && $secret === $json->secret_key && !empty($secret))
			{
				return true;
			}
			$json->secret_key = $secret;
			$this->db->setQuery(
				$this->db->getQuery(true)
					->update('#__extensions')
					->set($this->db->quoteName('params') . ' = ' . $this->db->quote(json_encode($json)))
					->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_watchfulli'))
			)->execute();
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');

			return false;
		}

		return true;
	}

	/**
	 * @return string
	 */
	private function getPhpVersion()
	{
		if (defined('PHP_VERSION'))
		{
			return PHP_VERSION;
		}

		if (function_exists('phpversion'))
		{
			return phpversion();
		}

		return '5.0.0';
	}

	/**
	 * Auto-Whitelist Watchful IPs after install/update
	 *
	 * @return bool
	 * @see admin/controller.php whitelist
	 */
	private function whiteListWatchful()
	{
		if (!$this->isCurlEnabled())
		{
			return false;
		}

		require_once WATCHFULLI_PATH . '/classes/watchfulli.php';
		require_once WATCHFULLI_PATH . '/classes/connection.php';
		require_once WATCHFULLI_PATH . '/classes/whitelistip.php';

		$response = WatchfulliConnection::getCurl(
			[
				'url'             => 'https://app.watchful.net/ip-v4.txt',
				'timeout'         => 300,
				"follow_location" => false,
			]
		);

		if (empty($response->data))
		{
			return false;
		}

		$watchfuIps = explode("\n", $response->data);

		// Remove mask (Watchful will ever use /32)
		$watchfuIps = preg_replace("/\/32/", '', $watchfuIps);

		try
		{
			$whiteList = new WatchfulliWhitelistIp(json_encode($watchfuIps), 'add', false);
		}
		catch (Exception $e)
		{
			return false;
		}

		return true;
	}
}


