<?php
/**
 * @version     admin/classes/coreupdater.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 * @description adapted from com_joomlaupdate controller
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Version;

defined('_JEXEC') or die;

class WatchfulliCoreUpdater
{
	private $options;

	/** @var JoomlaupdateModelDefault|\Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel */
	private $joomlaUpdateModel;
	private $helper;

	/** @var CMSApplication */
	private $application;

	/** @var string */
	private $tmpPath;

	/**
	 * Performs the download of the update package
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->options['format']    = '{DATE}\t{TIME}\t{LEVEL}\t{CODE}\t{MESSAGE}';
		$this->options['text_file'] = 'watchfulli';
		$this->helper               = new WatchfulliHelper();
		$this->application          = Factory::getApplication();
		$this->tmpPath              = $this->application->get('tmp_path');

		Log::addLogger($this->options, Log::INFO, ['Update', 'databasequery', 'jerror']);
	}

	/**
	 * Downloads the update package to the site.
	 *
	 * @param string $packageURL
	 */
	public function download($packageURL)
	{
		$basename = basename($packageURL);
		$target   = $this->tmpPath . '/' . $basename;
		if (!File::exists($target))
		{
			$file = $this->downloadPackage($packageURL, $target);
		}

		// Is it a 0-byte file? If so, re-download please.
		$filesize = @filesize($target);
		if (empty($filesize))
		{
			$file = $this->downloadPackage($packageURL, $target);
		}
		else
		{
			$file = $basename;
		}

		if (!$file)
		{
			$this->helper->response(
				[
					'task'    => 'download',
					'status'  => 'error',
					'message' => Text::_('COM_JOOMLAUPDATE_VIEW_UPDATE_DOWNLOADFAILED'),
				]
			);
		}

		$this->helper->response(
			[
				'task'    => 'download',
				'status'  => 'success',
				'message' => $file,
			]
		);

	}

	/**
	 * Start the installation of the new Joomla! version
	 *
	 * @param   string  $file
	 *
	 * @throws Exception
	 * @throws Exception
	 */
	public function install($file)
	{
		Log::add(Text::_('COM_JOOMLAUPDATE_UPDATE_LOG_INSTALL'), Log::INFO, 'Update');

		$this->setupCoreUpdateModel();

		/** GH #1133 */
		$useNewExtractor = version_compare(Watchfulli::joomla()->getShortVersion(), '4.0.4', '>=');
		$settingsMethod = $useNewExtractor ? 'createUpdateFile' : 'createRestorationFile';
		$settingsFile = $useNewExtractor ? 'update.php' : 'restoration.php';
		$extractor = $useNewExtractor ? 'extract.php' : 'restore.php';

		$this->joomlaUpdateModel->$settingsMethod($file);
		// cannot continue unless we copy update settings file to com_joomlaupdate
		// but we don't want to bother encrypting the JSON
		$src = JPATH_COMPONENT_ADMINISTRATOR . '/' . $settingsFile ;
		$dst = JPATH_ADMINISTRATOR . '/components/com_joomlaupdate/' . $settingsFile;
		$res = preg_replace("/'kickstart\.security\.password' => '.*?'/", "'kickstart.security.password' => null", file_get_contents($src));
		File::write($dst, $res);
		// extractor expects to be running in its own directory
		$cwd = getcwd();
		chdir(dirname($dst));
		// build JSON data
		$json = json_encode(['task' => 'ping']);
		$this->application->input->set('json', $json);
		// capture the output from extractor
		ob_start();
		require_once JPATH_ADMINISTRATOR . '/components/com_joomlaupdate/' . $extractor;
		$output = ob_get_clean();
		// go back to our own directory
		chdir($cwd);
		$this->helper->response(
			[
				'task'    => 'install',
				'status'  => 'success',
				'message' => 'install ok',
				'output'  => $this->parseRestoreResponse($output),
			]
		);
	}

	/**
	 * Extract the update ZIP
	 *
	 * For issue #1002, testing using JInstallerHelper::unpack() in one go, instead of the Akeeba
	 * stepped extraction, due to issue with executing mismatched code during stepping.
	 *
	 * @param   string  $file
	 *
	 * @return  void
	 */
	public function step($file)
	{
		$target = $this->tmpPath . '/' . $file;

		try
		{
			$extractionPath = $this->unpackFile($target);
		}
		catch (Exception $ex)
		{
			$this->helper->response(
				[
					'task'    => 'step',
					'status'  => 'error',
					'message' => $ex->getMessage(),
				]
			);
		}

		//delete the zip package
		if (!@unlink($target))
		{
			File::delete($target);
		}

		$this->helper->response(
			[
				'task'    => 'step',
				'status'  => !empty($extractionPath) ? 'success' : 'error',
				'message' => !empty($extractionPath) ? basename($extractionPath) : 'error',
				'output'  => [
					'status'   => true,
					'message'  => null,
					'files'    => 0,
					'bytesIn'  => 0,
					'bytesOut' => 0,
					'done'     => true,
				],
			]
		);
	}

	/**
	 * Unpack a given file
	 *
	 * @param   string  $file  the name of the file to unpack
	 *
	 * @throws  Exception
	 * @return string
	 */
	private function unpackFile($file)
	{
		if (!$file)
		{
			throw new Exception('COM_JMONITORING_CANT_UNPACK_UPDATE_EMPTY_FILE');
		}

		if (!file_exists($file))
		{
			throw new Exception('COM_JMONITORING_CANT_UNPACK_UPDATE_MISSING_FILE');
		}

		if (extension_loaded('zip'))
		{
			return $this->quickUnpack($file);
		}

		$package = InstallerHelper::unpack($file, true);

		if (empty($package)) {
			throw new Exception('COM_JMONITORING_CANT_UNPACK_UPDATE');
		}

		return $package['dir'];
	}

	/**
	 * @throws Exception
	 */
	private function quickUnpack($file)
	{
		$zip = new ZipArchive();

		$extractionPath = $this->tmpPath . '/' . uniqid('install_');

		if (
			!$zip->open($file)
			|| !$zip->extractTo($extractionPath)
		)
		{
			throw new Exception('COM_JMONITORING_CANT_UNPACK_UPDATE');
		}

		$zip->close();

		return $extractionPath;
	}

    /**
     * Finalise the upgrade by running the necessary scripts
     *
     * @param string $directory
     *
     * @return  void
     * @throws Exception
     */
	public function finalise($directory)
	{
        // https://github.com/joomla/joomla-cms/issues/38474
        if (file_exists(JPATH_ADMINISTRATOR . '/cache/autoload_psr4.php')) {
            @unlink(JPATH_ADMINISTRATOR . '/cache/autoload_psr4.php');
        }

        $tmpDir = $this->tmpPath . '/' . $directory;

        $this->setupCoreUpdateModel();
        $this->copyFiles($tmpDir);

		if ($this->joomlaUpdateModel->finaliseUpgrade())
		{
			$this->helper->response(
				[
					'task'    => 'finalise',
					'status'  => 'success',
					'message' => 'install ok',
				]
			);
		}

		$this->helper->response(
			[
				'task'    => 'finalise',
				'status'  => 'error',
				'message' => Text::_('COM_JOOMLAUPDATE_VIEW_UPDATE_DOWNLOADFAILED'),
			]
		);
	}

    /**
     * @throws Exception
     */
    private function copyFiles($tmpDir)
    {
        $this->helper->copyFolder($tmpDir, JPATH_ROOT);
    }

	/**
	 * Removes the extracted package file.
	 *
	 * @param   string  $directory
	 *
	 * @return  void
	 */
	public function cleanup($directory)
	{
		$directory = $this->tmpPath . '/' . $directory;

		// Remove joomla.xml from the site's root.
		$target = JPATH_ROOT . '/joomla.xml';

		if (!@unlink($target))
		{
			File::delete($target);
		}

		InstallerHelper::cleanupInstall('', $directory);

		// Remove the restoration.php file.
		$target = JPATH_ADMINISTRATOR . '/components/com_joomlaupdate/restoration.php';

		if (file_exists($target))
		{
			if (!@unlink($target))
			{
				File::delete($target);
			}
		}

		$this->helper->response(
			[
				'task'    => 'cleanup',
				'status'  => 'success',
				'message' => '',
			]
		);
	}

	/**
	 * Purges updates.
	 *
	 * @return  void
	 * @throws Exception
	 * @throws Exception
	 */
	public function purge()
	{
		JLoader::import('models.default', JPATH_ADMINISTRATOR . '/components/com_joomlaupdate');
		$this->setupCoreUpdateModel();

		// Purge updates
		// Check for request forgeries
		$this->joomlaUpdateModel->purge();
	}

	protected function parseRestoreResponse($response)
	{
		$delim = '###';
		if (false === strpos($response, $delim))
		{
			return $response;
		}
		list($junk, $str) = explode('###', $response, 2);
		list($junk, $str) = explode('###', strrev($str), 2);
		unset($junk);

		return json_decode(strrev($str));
	}

	/**
	 * Downloads a package file to a specific directory
	 *
	 * @param   string  $url     The URL to download from
	 * @param   string  $target  The directory to store the file
	 *
	 * @return  boolean True on success
	 */
	protected function downloadPackage($url, $target)
	{
		Log::add(Text::sprintf('COM_JOOMLAUPDATE_UPDATE_LOG_URL', $url), Log::INFO, 'Update');

		// Get the handler to download the package
        try {
            $http = $this->getHttpClient();
        } catch (Exception $e) {
            return false;
        }

		// Make sure the target does not exist.
		File::delete($target);

		// Download the package
		$result = $http->get($url);

		if (!$result || ($result->code != 200 && $result->code != 310))
		{
			return false;
		}

		// Write the file to disk
		File::write($target, $result->body);

		return basename($target);
	}

	/**
	 * @throws Exception
	 */
	private function setupCoreUpdateModel()
	{
		switch (Version::MAJOR_VERSION)
		{
			case 3:
				require_once JPATH_ADMINISTRATOR . '/components/com_joomlaupdate/models/default.php';
                $this->joomlaUpdateModel = new JoomlaupdateModelDefault();
                return;
			case 4:
			case 5:
                $this->joomlaUpdateModel = new \Joomla\Component\Joomlaupdate\Administrator\Model\UpdateModel();
                return;
			default:
				throw new Exception('Unsupported Joomla version: ' . Version::MAJOR_VERSION);

		}
	}

	/**
	 * @throws Exception
	 */
	private function getHttpClient()
    {
        switch (Version::MAJOR_VERSION)
        {
            case 3:
                return HttpFactory::getHttp(null, ['curl', 'stream']);
            case 4:
            case 5:
                return HttpFactory::getHttp([], ['curl', 'stream']);
            default:
                throw new Exception('Unsupported Joomla version: ' . Version::MAJOR_VERSION);

        }
    }
}
