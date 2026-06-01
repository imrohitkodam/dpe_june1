<?php

/**
 * @version     admin/classes/helper.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Version;

defined('_JEXEC') or die('Restricted access');

class WatchfulliHelper
{
	const MAX_TIME_DEVIATION = 360;
	public $db;

	public function __construct()
	{

		$this->db                 = Factory::getDbo();

	}

	public function getMemoryLimitInBytes()
	{
		$memory_limit = ini_get('memory_limit');
		switch (substr($memory_limit, -1))
		{
			case 'K':
				$memory_limit = (int) $memory_limit * 1024;
				break;

			case 'M':
				$memory_limit = (int) $memory_limit * 1024 * 1024;
				break;

			case 'G':
				$memory_limit = (int) $memory_limit * 1024 * 1024 * 1024;
				break;
		}

		return $memory_limit;
	}

	/**
	 * Close the application with a JSON encoded message
	 *
	 * @param   array  $data
	 *
	 * @throws Exception
	 */
	public function response($data)
	{
		/** @var CMSApplication $application */
		$application = Factory::getApplication();
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
			'joomlaMessages' => $application->getMessageQueue(),
		];

		$application->close(json_encode(array_merge($default, $data)));
	}

	/**
	 * Get the manifest from the relevant XML file
	 *
	 * @param   object  $row
	 *
	 * @return object|false
	 */
	public static function readManifest($row)
	{
		$baseDir = ($row->client_id == '1') ? JPATH_ADMINISTRATOR : JPATH_SITE;
		$files   = [];
		switch ($row->type)
		{
			case 'component':
				$files = glob($baseDir . '/components/' . $row->element . "/*.xml", GLOB_NOSORT);
				break;

			case 'module':
				$files = glob($baseDir . '/modules/' . $row->element . "/*.xml", GLOB_NOSORT);
				break;

			case 'plugin':
				$base = JPATH_ROOT . '/plugins/' . $row->folder . '/' . $row->element;
				if (
                    (class_exists('Joomla\CMS\Filesystem\Folder') && !Folder::exists($base)) ||
                    (class_exists('JFolder') && !JFolder::exists($base))
                )
				{
					$base = JPATH_ROOT . '/plugins/' . $row->folder;
				}
				$files = glob($base . '/' . $row->element . '*.xml', GLOB_NOSORT);
				break;

			case 'package':
				$files = glob(JPATH_ROOT . '/administrator/manifests/packages/' . $row->element . ".xml", GLOB_NOSORT);
				break;
		}

		if (!is_array($files))
		{
			return false;
		}

		foreach ($files as $file)
		{
			$xml = simplexml_load_file($file);
			if (!$xml)
			{
				continue;
			}

			if ($xml->getName() == 'extension' || $xml->getName() == 'install')
			{
				return $xml;
			}
		}

		return false;
	}

	/**
	 * Get Akeeba Secret Key for remote backup
	 *
	 * @return string
	 */
	public static function getAkeebaSecretKey()
	{
		define('AKEEBAENGINE', 1);
		define('AKEEBASOLO', 1);
		if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_akeeba/version.php'))
		{
			return null;
		}

		$params = ComponentHelper::getParams('com_akeeba');
		if (!$params->get('frontend_enable'))
		{
			return null;
		}

		include_once JPATH_ADMINISTRATOR . '/components/com_akeeba/BackupEngine/Factory.php';
		$secret_word = $params->get('frontend_secret_word');
		if (class_exists(Akeeba\Engine\Factory::class))
		{
			return Akeeba\Engine\Factory::getSecureSettings()->decryptSettings($secret_word, null);
		}

		return $secret_word;
	}

	/**
	 * @return array of objects
	 */
	public function getAdminUsers()
	{
		$admin_groups = $this->getAdminGroups();

		$query = 'SELECT u.id, u.username, u.password, u.name, u.email '
			. 'FROM #__user_usergroup_map m '
			. 'RIGHT JOIN #__users u ON (u.id=m.user_id) '
			. 'WHERE m.group_id IN (' . implode(',', $admin_groups) . ') '
			. 'GROUP BY u.id '
			. 'ORDER BY u.username ASC';
		$this->db->setQuery($query);

		return $this->db->loadObjectList();
	}


	/**
	 * Check which Firewall is installed
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getInstalledFirewalls()
	{
		return $this->db->setQuery(
			$this->db->getQuery(true)
				->select('element')
				->from('#__extensions')
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_admintools'), 'OR')
				->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_rsfirewall'))
		)->loadColumn();
	}

	/**
	 * @return array
	 */
    private function getAdminGroups()
    {
        $query = $this->db->getQuery(true);
        $query->select($this->db->quoteName('id'))
            ->from($this->db->quoteName('#__usergroups'));
        $this->db->setQuery($query);
        $groups = $this->db->loadColumn();

        $admin_groups = [];
        foreach ($groups as $group_id) {
            if (Access::checkGroup($group_id, 'core.login.admin')) {
                $admin_groups[] = $group_id;
            } elseif (Access::checkGroup($group_id, 'core.admin')) {
                $admin_groups[] = $group_id;
            }
        }

        return array_unique($admin_groups);
    }

    /**
     * @throws Exception
     */
    public function copyFolder($src, $dest)
    {
        switch (Version::MAJOR_VERSION) {
            case 3:
                $directoryContent = new RecursiveDirectoryIterator($src);
                $installContent = [];

                foreach ($directoryContent as $element) {
                    $type = 'file';

                    if ($element->getFilename() == '..') {
                        continue;
                    }

                    if ($element->getFilename() == '.') {
                        continue;
                    }

                    if ($element->isDir()) {
                        $type = 'folder';
                    }

                    $installContent[] = [
                        'src' => $src.'/'.$element->getFilename(),
                        'dest' => $dest.'/'.$element->getFilename(),
                        'type' => $type,
                    ];
                }

                $jinstaller = JInstaller::getInstance();
                $jinstaller->copyFiles($installContent, true);

                return;
            case 4:
            case 5:
                // https://github.com/joomla/joomla-cms/issues/39285
                if ($this->isInOpenBaseDir($dest)) {
                    $this->recursiveCopy($src, $dest);
                    return;
                }
                Folder::copy($src, $dest, '', true);
                return;
            default:
                throw new Exception('Unsupported Joomla version: '.Version::MAJOR_VERSION);
        }
    }

    private function isInOpenBaseDir($path)
    {
        $openBasedir = ini_get('open_basedir');
        if (empty($openBasedir))
        {
            return false;
        }

        $openBasedir = explode(PATH_SEPARATOR, $openBasedir);
        foreach ($openBasedir as $dir)
        {
            if (strpos($path, $dir) === 0)
            {
                return true;
            }
        }

        return false;
    }

    private function recursiveCopy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recursiveCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
