<?php

/**
 * @version     admin/classes/joomlaaudit.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserHelper;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die('Restricted access');

class WatchfulliJoomlaAudit extends WatchfulliAuditProcess
{
	/** @var stdClass */
	private $structure;

	/** @var WatchfulliRobots */
	private $robots;

	/** @var array */
	private $weakPasswords;

	/** @var CMSApplication */
	protected $app;

	/** @var WatchfulliScannerResponse */
	protected $response;

	/** @var Registry */
	protected $config;

	/** @var WatchfulliHelper */
	protected $helper;

	public function __construct()
	{
		parent::__construct();
		$this->loadWeakPasswords();
		$this->db        = Factory::getDBO();
		$this->app       = Factory::getApplication();
		$this->response  = new WatchfulliScannerResponse();
		$this->config    = Factory::getConfig();
		$this->structure = $this->cache->get(['WatchfulliRecursiveListing', 'getStructure'], [JPATH_SITE]);
		$this->helper    = new WatchfulliHelper();
	}

	/**
	 * @param   string  $key
	 * @param   string  $expectedValue
	 * @param   string  $comparaison
	 *
	 * @return object
	 */
	public function checkConfigValue($key, $expectedValue, $comparaison = '==')
	{
		return $this->compareValues($this->config->get($key), $expectedValue, $comparaison);
	}

	/**
	 * @param   string  $value
	 * @param   string  $expectedValue
	 * @param   string  $comparison  ==,<,>,<=,>=
	 *
	 * @return object
	 */
	public function compareValues($value, $expectedValue, $comparison = '==')
	{
		$map = [
			">=" => $value >= $expectedValue,
			">"  => $value > $expectedValue,
			"<=" => $value <= $expectedValue,
			"<"  => $value < $expectedValue,
			"==" => $value == $expectedValue,
			"!=" => $value != $expectedValue,
		];

		if ($map[$comparison])
		{
			return $this->response->sendOk();
		}

		return $this->response->sendKo();
	}

	/**
	 * @return object
	 */
	public function checkDbPasswordIsWeak()
	{
		$password = $this->isAWeakPassword($this->config->get('password'));
		if ($password)
		{
			return $this->response->sendKo($password);
		}

		return $this->response->sendOk();
	}

	/**
	 * @return object
	 */
	public function adminUsersExist()
	{
		$query = 'SELECT id'
			. ' FROM #__users '
			. ' WHERE username =' . $this->db->quote('admin')
			. ' AND block =' . $this->db->quote('0');

		$this->db->setQuery($query);
		$result = $this->db->loadResult();
		if ($result)
		{
			return $this->response->sendKo($result);
		}

		return $this->response->sendOk();
	}

	/**
	 * @return object
	 */
	public function isConfigurationModified()
	{
		$contents      = file_get_contents(JPATH_CONFIGURATION . '/configuration.php');
		$configuration = $this->buildConfigurationString();
		if ($contents != $configuration)
		{
			$contents      = explode("\n", $contents);
			$configuration = explode("\n", $configuration);
			$diff          = array_diff($contents, $configuration);

			return $this->response->sendKo($diff);
		}

		return $this->response->sendOk();
	}

	/**
	 * @return object
	 */
	public function checkAdminPasswordsStrength()
	{
		$return = [];
		foreach ($this->helper->getAdminUsers() as $user)
		{
			foreach ($this->weakPasswords as $password)
			{
				if ($this->isPasswordDecoded($user->password, $password))
				{
					$return[] = [$user->username, $password];
				}
			}
		}

		if (count($return))
		{
			return $this->response->sendKo($return);
		}

		return $this->response->sendOk();
	}

	/**
	 * @return object
	 */
	public function hasHtaccessOrWebConfig()
	{
		$file = '.htaccess';
		if (preg_match('#IIS/([\d.]*)#', $_SERVER['SERVER_SOFTWARE']))
		{
			$file = 'web.config'; // IIS
		}

		if (!file_exists(JPATH_SITE . '/' . $file))
		{
			return $this->response->sendKo();
		}

		return $this->response->sendOk();
	}

	/**
	 * @return object
	 */
	public function checkHasAnotherJoomlaSiteInSubdirectory()
	{
		$files = $this->structure->files;
		$paths = [];

		$escapedBasePath = preg_quote(JPATH_SITE, '#');
		$pattern         = '#^' . $escapedBasePath . '/([a-z0-9_\-.\s]*/){1,2}configuration\.php$#i';

		foreach ($files as $file)
		{
			if (preg_match($pattern, $file) && $this->isAJoomlaConfigFile($file))
			{
				$relativePath = str_replace(JPATH_BASE, '', $file);
				$paths[]      = preg_replace('#configuration.php$#', '', $relativePath);
			}
		}

		if (count($paths))
		{
			return $this->response->sendKo($paths);
		}

		return $this->response->sendOk();
	}

	/**
	 * @return object
	 */
	public function checkRobotsFileHasCorrectDenials()
	{
		$robots = $this->getRobotsTxt();
		// if there are no sections, everything should be ok
		if (empty($robots->sections))
		{
			return $this->response->sendOk();
		}
		$failures = [];
		$known    = $robots->getAgents();
		// TODO load agents and paths from server
		$agents = explode('|', '*|Googlebot|bingbot|Slurp|Yahoo! Slurp|Baiduspider|Yandex|DuckDuckBot');
		$regex  = '#^(/|/templates/?|/media/?)$#';
		// check known agents against the list
		foreach ($known as $agent)
		{
			// this agent is not found, skipping
			if (!in_array($agent, $agents))
			{
				continue;
			}
			// paths for this agent
			$paths = $robots->getPathsByAgent($agent);
			// only check disallowed (for now)?
			if (empty($paths->disallow))
			{
				continue;
			}
			foreach ($paths->disallow as $path)
			{
				if (preg_match($regex, $path))
				{
					// format data for display
					$failures[] = sprintf("User-agent: %s\nDisallowed: %s", $paths->agent, $path);
				}
			}
		}
		if (!empty($failures))
		{
			return $this->response->sendKo($failures);
		}

		return $this->response->sendOk();
	}

	/**
	 * @return object
	 */
	public function robotsFileHasUnrecognizedLines()
	{
		$robots = $this->getRobotsTxt();
		if (!empty($robots->unknown))
		{
			return $this->response->sendKo($robots->unknown);
		}

		return $this->response->sendOk();
	}

	/**
	 * @return object
	 */
	public function checkJoomlaInstallationDirectoryExists()
	{
		$files         = $this->structure->files;
		$paths         = [];
		$hasConfigFile = false;
		// all joomla versions share these installation files
		// they will all be in the same subdirectory
		$filenames = [
			'localise.xml',
			'sql/mysql/joomla.sql',
			'sql/mysql/sample_data.sql',
			'template/index.php',
			'template/css/template.css',
		];

		$escapedBasePath       = preg_quote(JPATH_SITE, '#');
		$escapedConfigFileName = preg_quote('configuration.php-dist', '#');
		$escapedFileNamesArray = [];
		foreach ($filenames as $filename)
		{
			$escapedFileNamesArray[] = preg_quote($filename, '#');
		}
		$escapedFileNames = implode('|', $escapedFileNamesArray);

		$configpattern = "#^$escapedBasePath/([a-z0-9_\-.\s]*/)*?($escapedConfigFileName)$#i";
		$filepattern   = "#^$escapedBasePath/[a-z0-9_\-.\s]*/($escapedFileNames)$#i";

		foreach ($files as $file)
		{
			// this file is one of the potential paths - flag it
			if (preg_match($filepattern, $file))
			{
				$relativePath = str_replace(JPATH_BASE, '', $file);
				$key          = preg_replace("#($escapedFileNames)$#", '', $relativePath);
				// start counting how many times this path comes up
				if (!array_key_exists($key, $paths))
				{
					$paths[$key] = 0;
				}
				$paths[$key] += 1;
			} // this file is a distribution configuration file
			else
			{
				if (preg_match($configpattern, $file) && $this->isAJoomlaConfigFile($file, true))
				{
					$hasConfigFile = true;
				}
			}
		}

		// the paths array should have every file to qualify, plus a configuration file
		if (in_array(count($filenames), $paths) && $hasConfigFile)
		{
			return $this->response->sendKo(array_keys($paths));
		}

		return $this->response->sendOk();
	}

	/**
	 * @return object|null
	 */
	public function checkHasK2OpenComments()
	{
		$params = $this->getK2Configuration();
		if (!is_object($params))
		{
			return null;
		}

		$hasComments = property_exists($params, 'comments') ? intval($params->comments) : 1;
		$hasAntispam = property_exists($params, 'antispam') ? intval($params->antispam) : 0;
		if (1 === $hasComments && 0 === $hasAntispam)
		{
			return $this->response->sendKo();
		}

		return $this->response->sendOk();
	}

	/**
	 * @return string
	 */
	private function buildConfigurationString()
	{
		$data   = ArrayHelper::fromObject(new Registry());
		$config = new Registry('config');
		$config->loadArray($data);

		return $config->toString('PHP', ['class' => 'JConfig', 'closingtag' => false]);
	}

	/**
	 * Compare an encrypted password with a reference and try to decrypt
	 *
	 * @param   string  $encryptedPassword
	 * @param   string  $reference
	 *
	 * @return boolean
	 * @todo this does not do anything in Joomla 4 as the getCryptedPassword method has been removed
	 */
	private function isPasswordDecoded($encryptedPassword, $reference)
	{
		if (!method_exists('UserHelper', 'getCryptedPassword'))
		{
			return false; // Method has been removed in Joomla 4
		}

		if (substr($encryptedPassword, 0, 4) == '$2y$')
		{
			return false; // Cracking these passwor§ is extremely CPU intensive, skip.
		}

		$salt  = '';
		$parts = explode(':', $encryptedPassword);
		$crypt = $parts[0];
		if (array_key_exists(1, $parts))
		{
			$salt = $parts[1];
		}
		if (substr($encryptedPassword, 0, 8) == '{SHA256}')
		{
			$testcrypt = UserHelper::getCryptedPassword($reference, $salt, 'sha256', false);

			return ($encryptedPassword == $testcrypt);
		}

		$testcrypt = UserHelper::getCryptedPassword($reference, $salt, 'md5-hex', false);

		return ($crypt == $testcrypt);
	}

	private function loadWeakPasswords()
	{
		$cache = Factory::getCache('com_watchfulli');
		$cache->cache->setCaching(6 * 3600);
		$contentRaw          = $cache->get(['WatchfulliConnection', 'getPasswords']);
		$contents            = str_replace(["\r\n", "\r"], "\n", $contentRaw->data);
		$this->weakPasswords = explode("\n", $contents);
	}

	/**
	 * @param   string  $original
	 *
	 * @return string|null
	 */
	private function isAWeakPassword($original)
	{
		if (in_array($original, $this->weakPasswords))
		{
			return $original;
		}

		return null;
	}

	/**
	 * @param   string   $filePath        File
	 * @param   boolean  $removeComments  optional, strip PHP comments from config before checking
	 *
	 * @return boolean
	 */
	private function isAJoomlaConfigFile($filePath, $removeComments = false)
	{
		$content = file_get_contents($filePath);
		$pattern = '#^<\?php[.\s]*class[.\s]*JConfig#';
		if ($removeComments)
		{
			$content = php_strip_whitespace($filePath);
		}

		return preg_match($pattern, $content);
	}

	/**
	 * @return object
	 */
	private function getK2Configuration()
	{
		$params = false;
		try
		{
			$data = $this->db->setQuery(
				$this->db->getQuery(true)
					->select('params')
					->from('#__extensions')
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'))
					->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_k2'))
					->where($this->db->quoteName('enabled') . ' = 1')
			)->loadResult();
		}
		catch (Exception $e)
		{
			$data = false;
		}

		if (!empty($data))
		{
			$params = json_decode($data);
		}

		return $params;
	}

	/**
	 * @return WatchfulliRobots
	 */
	private function getRobotsTxt()
	{
		if (!empty($this->robots))
		{
			return $this->robots;
		}

		$content  = '';
		$filePath = JPATH_BASE . '/robots.txt';
		if (file_exists($filePath))
		{
			$content = file_get_contents($filePath);
		}
		$this->robots = new WatchfulliRobots($content);

		return $this->robots;
	}
}
