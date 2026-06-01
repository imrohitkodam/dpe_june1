<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Controller\AdminController;

JLoader::register('TjControllerHouseKeeping', JPATH_SITE . "/libraries/techjoomla/controller/houseKeeping.php");

/**
 * Coupon controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerDashboard extends AdminController
{
	protected $extension;

	protected $downloadid;

	use TjControllerHouseKeeping;

	/**
	 * Constructor.
	 *
	 * @see     JControllerLegacy
	 *
	 * @since   1.0.0
	 *
	 * @throws  Exception
	 */
	public function __construct()
	{
		// Setup vars
		$this->extension                   = new stdClass;
		$this->extension->extensionElement = 'pkg_shika';
		$this->extension->extensionType    = 'package';
		$this->extension->updateStreamName = 'Shika';
		$this->extension->updateStreamType = 'extension';
		$this->extension->updateStreamUrl  = 'https://techjoomla.com/updates/stream/tjlms.xml?format=xml';
		$this->extension->downloadidParam  = 'downloadid';

		// Get download id
		$params           = ComponentHelper::getParams('com_tjlms');
		$this->downloadid = $params->get('downloadid');

		parent::__construct();
	}

	/**
	 * Function getLatestVersion for getting the latest version
	 *
	 * @return  mixed
	 */
	public function getLatestVersion()
	{
		PluginHelper::importPlugin('system', 'tjupdates');

		// Trigger all "sytem" plugins OnAfterLessonCreation method
		$latestVersion = Factory::getApplication()->triggerEvent('getLatestVersion', array($this->extension));

		if (!empty($latestVersion[0]))
		{
			return $latestVersion[0]->version;
		}

		return false;
	}

	/**
	 * This prints the html to show the version is outdated
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function versionCheckerOutput()
	{
		header('Content-type: text/html; UTF-8');
		$input     = Factory::getApplication()->input;
		$xml       = simplexml_load_file(JPATH_SITE . '/administrator/components/com_tjlms/tjlms.xml');
		$installed = $xml->version;
		$latest    = $this->getLatestVersion();

		if ($latest)
		{
			ob_start();

			if (version_compare($installed, $latest) == '-1')
			{
				include JPATH_BASE . '/components/com_tjlms/layouts/version.outdated.php';
			}
			else
			{
				include JPATH_BASE . '/components/com_tjlms/layouts/version.latest.php';
			}

			$layoutOutput = ob_get_contents();
			ob_end_clean();
			echo $layoutOutput;
		}

		jexit();
	}

	/**
	 * Downloads and extract arabic library
	 *
	 * @return  mixed  True on success, error string on failure
	 *
	 * @since   1.3.32
	 */
	public function downloadArabicLib()
	{
		$url            = 'https://github.com/techjoomla/ar-php/archive/v4.0.1.zip';
		$folderName     = "ar-php-4.0.1";
		$destinationDir = JPATH_SITE . "/libraries/";

		if (!is_dir($destinationDir))
		{
			mkdir($destinationDir, 0755, true);
		}

		// Will return only 'some_zip.zip'
		$localZipFile = basename(parse_url($url, PHP_URL_PATH));

		if (!copy($url, $destinationDir . $localZipFile))
		{
			$data = false;
			$errorMessages = Text::_('COM_TJLMS_DOWNLOAD_ARABIC_FAILED');
			echo new JsonResponse($data, $errorMessages);
			jexit();
		}

		$zip = new ZipArchive;

		if ($zip->open($destinationDir . $localZipFile))
		{
			for ($i = 0; $i < $zip->numFiles; $i++)
			{
				$zip->extractTo($destinationDir, array($zip->getNameIndex($i)));
			}

			$zip->close();

			// Clear zip from local storage:
			unlink($destinationDir . $localZipFile);

			$arPath = JPATH_SITE . "/libraries/" . $folderName;
			$arUpdatedPath = JPATH_SITE . "/libraries/ar-php";
			rename($arPath, $arUpdatedPath);

			$data = true;
			$msg  = Text::_('COM_TJLMS_DOWNLOAD_ARABIC_SUCCESS');
			echo new JsonResponse($data, $msg);
			jexit();
		}
	}
}
