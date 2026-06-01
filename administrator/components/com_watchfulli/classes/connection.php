<?php
/**
 * @version     admin/classes/connection.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

defined('_JEXEC') or die();
defined('WATCHFULLI_PATH') or die();

class WatchfulliConnection
{
	public static function getSignatures()
	{
		$config = [
			'url'             => 'https://app.watchful.net/api/v1/signatures?limit=0',
			'timeout'         => 300,
			"follow_location" => false,
		];

		$response = WatchfulliConnection::getCurl($config);
		$response = json_decode($response->data);

		return $response->msg->data;
	}

	public static function getPasswords()
	{
		$config = [
			'url'             => 'http://installer.watchful.net/audit-assets/passwords.txt',
			'timeout'         => 300,
			"follow_location" => false,
		];

		return WatchfulliConnection::getCurl($config);
	}

	/**
	 * Fetch the list of protected core directories from Watchful server
	 *
	 * @return array list of directories that should not have extra files
	 * @throws Exception if data cannot be loaded from server
	 */
	public static function getProtectedCoreDirectories()
	{
		$version = Watchfulli::getJoomlaVersion();
		$config  = [
			'url'             => 'http://installer.watchful.net/audit-assets/core-directories/' . $version . '.txt',
			'timeout'         => 300,
			'follow_location' => false,
		];

		$response = WatchfulliConnection::getCurl($config);
		if ($response->info['http_code'] >= 400)
		{
			throw new Exception('JMON_SCANNER_COREINTEGRITY_CORE_DIRECTORIES_NOT_FOUND');
		}

		return str_getcsv($response->data, "\n");
	}

	/**
	 * @throws Exception
	 */
	public static function getHash()
	{
		$version = Watchfulli::getJoomlaVersion();
		$config  = [
			'url'             => 'https://downloads.watchful.net/hashes/' . $version . '.csv',
			'timeout'         => 300,
			'follow_location' => false,
		];

		$response = WatchfulliConnection::getCurl($config);

		if ($response->info['http_code'] == 404 || $response->info['http_code'] == 403)
		{
			throw new Exception('JMON_SCANNER_COREINTEGRITY_HASHFILE_NOT_FOUND');
		}

		$data = str_getcsv($response->data, "\n"); //parse the rows
		foreach ($data as &$row)
		{
			$row = str_getcsv($row, ","); //parse the items in rows
		}

		return $data;
	}

	/**
	 * Wrapper for curl so we can have a common set of parameters and possibly
	 * cache it in some parts of the system
	 *
	 * @param   array  $config  a configuration array with the following properties:
	 *                          - string url : address to check
	 *                          - int timeout (default 60) the connection timeout in seconds
	 *                          - bool follow_location (default true) true to follow 30x redirects
	 *                          - array post_data (default empty array) an array of key/values to
	 *                          pass as post data
	 *
	 * @return object|false
	 *      - data : raw response
	 *      - info : curl info
	 *      - error : curl error
	 */
	public static function getCurl($config)
	{
		if (!isset($config['url']))
		{
			return false;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $config['url']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Watchfulli/1.0 (+http://www.watchful.net)');
		curl_setopt($ch, CURLOPT_REFERER, $config['url']);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, empty($config['timeout']) ? 60 : $config['timeout']);
		curl_setopt($ch, CURLOPT_TIMEOUT, empty($config['timeout']) ? 60 : $config['timeout']);
		curl_setopt($ch, CURLOPT_HEADER, isset($config['header']) ? $config['header'] : false);
		curl_setopt($ch, CURLOPT_NOBODY, isset($config['nobody']) ? $config['nobody'] : false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, isset($config['follow_location']) ? $config['follow_location'] : true);
		curl_setopt($ch, CURLOPT_ENCODING, isset($config['encoding']) ? $config['encoding'] : "");
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 20);
		// We must only set the "customrequest" option if required. Any other
		// value (empty string, null, false) will break the connection so we
		// cannot just use a default as we did above
		if (isset($config['customrequest']))
		{
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $config['customrequest']);
		}

		$result        = new stdClass();
		$result->data  = curl_exec($ch);
		$result->info  = curl_getinfo($ch);
		$result->error = curl_error($ch);
		curl_close($ch);

		return $result;
	}
}
