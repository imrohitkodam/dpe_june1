<?php
/**
 * @package    Shika_Document_Viewer
 * @copyright  Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;

require_once JPATH_PLUGINS . '/tjdocument/boxapi2/lib/vendor/autoload.php';

use Firebase\JWT\JWT;

/**
 * Box Documents migration
 *
 * @since  1.0.0
 */
class BoxAPI extends CMSObject
{
	public $setting 	    = null;

	public $access_token	= '';

	public $authorize_url 	= 'https://www.box.com/api/oauth2/authorize';

	public $tokenUrl	 	= 'https://api.box.com/oauth2/token';

	public $apiUrl 			= 'https://api.box.com/2.0';

	public $uploadUrl 		= 'https://upload.box.com/api/2.0';

	/**
	 * @var array
	 */
	public $supportedLanguage = array();

	/**
	 * Plugin that supports uploading and tracking the PPTs PDFs documents of Box API
	 *
	 * @param   OBJECT  $setting  Box Seeting
	 *
	 * @since 1.0.0
	 */
	public function __construct($setting = null)
	{
		if (!$setting)
		{
			$plugin = PluginHelper::importPlugin('tjdocument', 'boxapi2');

			if (!empty($plugin))
			{
				$setting = Factory::getApplication()->triggerEvent('initializeSetting');

				if (!empty($setting) && is_array($setting))
				{
					$this->setting = $setting[0];
				}
			}
		}
		else
		{
			$this->setting = $setting;
		}

		$this->supportedLanguage = array(
			'en-AU', 'en-CA', 'en-GB', 'en-US', 'da-DK', 'de-DE',
			'es-ES', 'fi-FI', 'fr-CA', 'fr-FR', 'it-IT', 'ja-JP',
			'ko-KR', 'nb-NO', 'nl-NL', 'pl-PL', 'pt-BR', 'ru-RU',
			'sv-SE', 'tr-TR', 'zh-CN', 'zh-TW'
		);
	}

	/**
	 * Send request to server to get JWT Access Token
	 *
	 * @return MIX Boolean or Token
	 *
	 * @since 1.0.0
	 */
	public function createJWTToken()
	{
		$assertion = $this->createAssertion();

		if ($assertion === false)
		{
			return false;
		}

		$postFields = array(
			'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			'client_id' => $this->setting->client_id,
			'client_secret' => $this->setting->client_secret,
			'assertion' => $assertion,
		);

		$tokenDetail = $this->sendPostCurlRequest($this->tokenUrl, $postFields);

		if ($tokenDetail)
		{
			$tokenDetail = json_decode($tokenDetail);

			if (!empty($tokenDetail->access_token))
			{
				$this->token = $tokenDetail->access_token;

				return $this->token;
			}
			elseif (!empty($tokenDetail->error_description))
			{
				$this->setError($tokenDetail->error_description);
			}
		}

		return false;
	}

	/**
	 * Create assertion from library as per client detail
	 *
	 * @return STRING Assertion token
	 *
	 * @since 1.0.0
	 */
	private function createAssertion()
	{
        try
        {
            $config = json_decode($this->setting->boxjson);

            $private_key = $config->boxAppSettings->appAuth->privateKey;
            $passphrase = $config->boxAppSettings->appAuth->passphrase;
            $key = openssl_pkey_get_private($private_key, $passphrase);
            
            $claims = [
            'iss' => $config->boxAppSettings->clientID,
            'sub' => $config->enterpriseID,
            'box_sub_type' => 'enterprise',
            'aud' => $this->tokenUrl,
            'jti' => base64_encode(random_bytes(64)),
            'exp' => time() + 45,
            'kid' => $config->boxAppSettings->appAuth->publicKeyID
            ];
            
            $assertion = JWT::encode($claims, $key, 'RS512');
           
            return $assertion;
        }
        catch (Exception $e)
        {
            $this->setError($e->getMessage());
            return false;
        }
	}

	/**
	 * Get Signer for private key
	 *
	 * @return MIX Signer Object
	 *
	 * @since 1.0.0
	 */
	private function createSigner()
	{
		return new Rsa\Sha256;
	}

	/**
	 * Get Private key
	 *
	 * @return MIX Private key object
	 *
	 * @since 1.0.0
	 */
	private function getRsaPrivateKey()
	{
		$keychain = new Signer\Keychain;
		$key = $keychain->getPrivateKey($this->setting->privatekey, $this->setting->passphrase);

		return $key;
	}

	/**
	 * Send Curl Request
	 *
	 * @param   STRING  $url         Url to send curl request
	 * @param   ARRAY   $postFields  Data to be posted
	 *
	 * @return MIX Result
	 *
	 * @since 1.0.0
	 */
	public function sendPostCurlRequest($url, $postFields)
	{
		// Ensure we have access to cURL.
		if (!$this->curlInstalled())
		{
			$this->setError(Text::_('PLG_TJDOCUMENT_BOXAPI2_CURL_EXTENSION_NOT_FOUND'));

			return false;
		}

		$ch = curl_init();

		$curlConfig = array(
			CURLOPT_URL            => $url,
			CURLOPT_POST           => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POSTFIELDS     => $postFields
		);

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		curl_setopt_array($ch, $curlConfig);
		$result = curl_exec($ch);

		if (curl_error($ch))
		{
			$this->setError(Text::sprintf('PLG_TJDOCUMENT_BOXAPI2_CURL_ERROR', curl_error($ch)));

			return false;
		}

		curl_close($ch);

		return $result;
	}

	/**
	 * Prepare file to send it to box
	 *
	 * @param   STRING  $filename  File name
	 * @param   STRING  $filepath  Full physical path of the file
	 *
	 * @return MIX JSON Result
	 *
	 * @since 1.0.0
	 */
	public function sendFileToBox($filename, $filepath)
	{
		$url = $this->buildUrl('/files/content', $this->uploadUrl, array());

		if (empty($filename))
		{
			$filename = basename($filepath);
		}

		$file = new \CURLFile($filepath);
		$params = array('file' => $file, 'name' => $filename , 'parent_id' => 0, 'access_token' => $this->access_token);

		$result = $this->sendPostCurlRequest($url, $params);

		if ($result)
		{
			$result = json_decode($result, true);

			if ($result && !empty($result['entries'][0]['id']))
			{
				return $result['entries'][0]['id'];
			}
			elseif ($result['message'])
			{
				$this->setError($result['message']);

				return false;
			}
			else
			{
				$this->setError('Something went wrong during upload.');
			}
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	/**
	 * Build URL
	 *
	 * @param   STRING  $api_func  API Path
	 * @param   STRING  $url       Prefix URL
	 * @param   STRING  $opts      Options
	 *
	 * @return STRING
	 *
	 * @since 1.0.0
	 */
	private function buildUrl($api_func, $url, array $opts = array())
	{
		$opts = $this->set_opts($opts);

		if (isset($url))
		{
			$base = $url . $api_func . '?';
		}
		else
		{
			$base = $this->apiUrl . $api_func . '?';
		}

		$query_string = http_build_query($opts);
		$base = $base . $query_string;

		return $base;
	}

	/**
	 * Sets the required before biulding the query
	 *
	 * @param   ARRAY  $opts  Options
	 *
	 * @return ARRAY
	 *
	 * @since 1.0.0
	 */
	private function set_opts(array $opts)
	{
		if (!array_key_exists('access_token', $opts))
		{
			$opts['access_token'] = $this->access_token;
		}

		return $opts;
	}

	/**
	 * Checks whether or not PHP has the cURL extension enabled.
	 *
	 * @return bool
	 *   Returns TRUE if cURL if is enabled.
	 */
	private function curlInstalled()
	{
		return in_array('curl', get_loaded_extensions()) && function_exists('curl_version');
	}
}
