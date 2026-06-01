<?php

/**
 * @version     admin/classes/scanner.php 2024-03-27 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Input\Input;

defined('_JEXEC') or die;
defined('WATCHFULLI_PATH') or die;

class WatchfulliScanner
{
	/** @var CMSApplication */
	private $application;

	/** @var Input */
	private $input;

	/** @var int */
	private $start;

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

		Watchfulli::registerErrorsCatchers();

		$this->application = Factory::getApplication();
		$this->input       = $this->application->input;
		$this->start       = $this->input->getInt('start', 0);
	}

	public function auditJoomlaConfiguration()
	{
		$cache = Factory::getCache()->cache;
		$cache->clean("com_watchfulli");

		$check                                    = new WatchfulliJoomlaAudit();
		$joomlaAudit                              = new stdClass();
		$joomlaAudit->hasAdminUser                = $check->adminUsersExist();
		$joomlaAudit->hasFTPPassword              = $check->checkConfigValue('ftp_pass', '');
		$joomlaAudit->isSEFEnabled                = $check->checkConfigValue('sef', 1);
		$joomlaAudit->Gzip                        = $check->checkConfigValue('gzip', 1);
		$joomlaAudit->Debug                       = $check->checkConfigValue('debug', 0);
		$joomlaAudit->Error_reporting             = $check->checkConfigValue('error_reporting', 'none');
		$joomlaAudit->Caching                     = $check->checkConfigValue('caching', 0, '>');
		$joomlaAudit->sessionLifetime             = $check->checkConfigValue('lifetime', 15, '<=');
		$joomlaAudit->checkSQLPassword            = $check->checkDbPasswordIsWeak();
		$joomlaAudit->hasHtaccess                 = $check->hasHtaccessOrWebConfig();
		$joomlaAudit->isConfigurationModified     = $check->isConfigurationModified();
		$joomlaAudit->zlib                        = $check->compareValues(function_exists('gzcompress'), 1);
		$joomlaAudit->mod_xml                     = $check->compareValues(function_exists('simplexml_load_file'), 1);
		$joomlaAudit->register_globals            = $check->compareValues(ini_get('register_globals'), 0);
		$joomlaAudit->canUserRegistred            = $check->compareValues(ComponentHelper::getParams('com_users')->get('allowUserRegistration'), 0);
		$joomlaAudit->hasKickstart                = $check->compareValues(file_exists(JPATH_BASE . '/kickstart.php'), 0);
		$joomlaAudit->max_execution_time          = $check->getMaxExecutionTime();
		$joomlaAudit->dbPrefix                    = $check->checkConfigValue('dbprefix', 'jos_', '!=');
		$joomlaAudit->joomlaInSubdirectory        = $check->checkHasAnotherJoomlaSiteInSubdirectory();
		$joomlaAudit->robotsTxt                   = $check->checkRobotsFileHasCorrectDenials();
		$joomlaAudit->robotsTxtBadLines           = $check->robotsFileHasUnrecognizedLines();
		$joomlaAudit->joomlaInstallationDirectory = $check->checkJoomlaInstallationDirectoryExists();
		$joomlaAudit->checkAdminPasswords         = $check->checkAdminPasswordsStrength();

		//K2 comment open for public, only set if result is not null
		$k2OpenComments = $check->checkHasK2OpenComments();
		if (!is_null($k2OpenComments))
		{
			$joomlaAudit->k2OpenComments = $k2OpenComments;
		}

		$this->output($joomlaAudit);
	}

	public function auditMalwareScanner()
	{
		$scanner = new WatchfulliFilesScanner();
		$result  = $scanner->auditMalwareScanner($this->start);
		$this->output($result);
	}

	public function auditFolderPermissions()
	{
		$scanner = new WatchfulliFilesScanner();
		$result  = $scanner->auditFolderPermissions($this->start);
		$this->output($result);
	}

	public function auditFilesPermissions()
	{
		$scanner = new WatchfulliFilesScanner();
		$result  = $scanner->auditFilesPermissions($this->start);
		$this->output($result);
	}

	public function auditJoomlaCoreIntegrity()
	{
		$model  = new WatchfulliIntegrity();
		$result = $model->auditJoomlaCoreIntegrity($this->start);
		$this->output($result);
	}

	public function auditJoomlaProtectedCoreDirectories()
	{
		$model  = new WatchfulliIntegrity();
		$result = $model->auditJoomlaProtectedCoreDirectories($this->start);
		$this->output($result);
	}

	private function output($data)
	{
		$output = json_encode($data);
		$this->application->close($output);
	}

}
