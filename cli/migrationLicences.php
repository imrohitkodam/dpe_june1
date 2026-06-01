<?php
/**
 * @package    Joomla.Cli
 *
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * This is a CRON script which should be called from the command-line, not the
 * web. For example something like:
 * /usr/bin/php /path/to/site/cli/update_cron.php
 */

// Make sure this is being called from the command line
if (PHP_SAPI !== 'cli')
{
	die('This is a command line only application.');
}

// Set flag that this is a parent file.
const _JEXEC = 1;

error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_LIBRARIES . '/import.legacy.php';
require_once JPATH_LIBRARIES . '/cms.php';
require_once JPATH_CONFIGURATION . '/configuration.php';
require_once JPATH_BASE . '/includes/framework.php';

// Import joomla cli app file
jimport('joomla.application.cli');

// Load Library language
$lang = JFactory::getLanguage();
$lang->load('files_joomla.sys', JPATH_SITE, null, false, false) || $lang->load('files_joomla.sys', JPATH_SITE, null, true);

$app = JFactory::getApplication('administrator');
$app->initialise();

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Cron job
 *
 */
class migrationLicences extends JApplicationCli
{
	public function execute()
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_dpe/migrationSla.php';
		$MigrationSla = new MigrationSla;
		$MigrationSla->migrateLicenceActivityData();
	}
}

JApplicationCli::getInstance('migrationLicences')->execute();
