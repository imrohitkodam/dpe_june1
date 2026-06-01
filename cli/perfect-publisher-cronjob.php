<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

define('EXTLY_CRONJOB_RUNNING', true);
define('AUTOTWEET_CRONJOB_RUNNING', true);

/*
 * Starts the Perfect Publisher cronjob
 * Call this file from a Cron Job.
 */

// Make sure we're being called from the command line, not a web interface
if (array_key_exists('REQUEST_METHOD', $_SERVER)) {
    exit();
}

// Avoid notices
$_SERVER['REQUEST_METHOD'] = null;

/*
 * PerfectPublisher CLI Bootstrap
 *
 * Run the framework bootstrap with a couple of mods based on the script's needs
*/

// We are a valid entry point.
define('_JEXEC', 1);
define('DS', \DIRECTORY_SEPARATOR);

// Load system defines
if (file_exists(dirname(__DIR__).'/defines.php')) {
    require_once dirname(__DIR__).'/defines.php';
}

if (!defined('_JDEFINES')) {
    // ..../cli
    $path = dirname(__DIR__);
    $dirs = explode(\DIRECTORY_SEPARATOR, $path);

    $path = implode(\DIRECTORY_SEPARATOR, $dirs);

    define('JPATH_BASE', $path);
    require_once JPATH_BASE.'/includes/defines.php';
}

// Get the framework.
require_once JPATH_LIBRARIES.'/import.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES.'/cms.php';

// Get the framework.
if (version_compare(JVERSION, '3.0.0', 'ge')) {
    require_once JPATH_LIBRARIES.'/import.legacy.php';
}

// Force library to be in JError legacy mode
JError::$legacy = true;

// Import necessary classes not handled by the autoloaders
jimport('joomla.application.menu');
jimport('joomla.environment.uri');
jimport('joomla.event.dispatcher');
jimport('joomla.utilities.utility');
jimport('joomla.utilities.arrayhelper');

// Import the configuration.
require_once JPATH_CONFIGURATION.'/configuration.php';

// System configuration.
$config = new JConfig();

// Configure error reporting to maximum for CLI output.
error_reporting(\E_ALL);
ini_set('display_errors', 1);

require_once JPATH_ADMINISTRATOR.'/components/com_autotweet/api/autotweetapi.php';

// Load Library language
$lang = \Joomla\CMS\Factory::getLanguage();

// Try the finder_cli file in the current language (without allowing the loading of the file in the default language)
// Fallback to the finder_cli file in the default language
$lang->load('autotweet_cli', JPATH_SITE, null, false, false) || $lang->load('autotweet_cli', JPATH_SITE, null, true);

/**
 * A command line cron job to run the PerfectPublisher job.
 *
 * @since       2.5
 */
class PerfectPublisherCronjob extends JApplicationCli
{
    /**
     * Start time for the posting process.
     *
     * @var string
     */
    private $_time;

    /**
     * Start time for each batch.
     *
     * @var string
     */
    private $_qtime;

    /**
     * Entry point for PerfectPublisher CLI script.
     */
    public function doExecute()
    {
        // Print a blank line.
        $this->out(JText::_('Perfect Publisher Cronjob'));
        $this->out('============================');
        $this->out();

        $this->publish();

        $this->out('Checking for new versions');

        $updateModel = XTF0FModel::getTmpInstance('LiveUpdates', 'AutotweetModel');
        $result = $updateModel->autoupdate();

        $this->out(implode("\n", $result['message']));

        // Print a blank line at the end.
        $this->out();
    }

    /**
     * Run the job.
     */
    private function publish()
    {
        jimport('joomla.application.component.helper');

        // Fool the system into thinking we are running as JSite with PerfectPublisher as the active component
        @\Joomla\CMS\Factory::getApplication('site');
        $_SERVER['HTTP_HOST'] = 'domain-error-running-as-cronjob.com';

        // Disable caching.
        $config = \Joomla\CMS\Factory::getConfig();
        $config->set('caching', 0);
        $config->set('cache_handler', 'file');

        // Starting Indexer.
        $this->out(JText::_('AUTOTWEET_CLI_STARTING_PROCESS'), true);

        // Remove the script time limit.
        @set_time_limit(0);

        // Initialize the time value
        $timetrack = microtime(true);

        $max_posts = EParameter::getComponentParam(CAUTOTWEETNG, 'max_posts', 1);
        PostShareManager::postQueuedMessages($max_posts);

        $cronjobHelper = CronjobHelper::getInstance();
        $cronjobHelper->publishPosts();

        if (EParameter::getComponentParam(CAUTOTWEETNG, 'feeds_enabled', false)) {
            FeedLoaderHelper::importFeeds();
        }

        $cronjobHelper->contentPolling();

        // Total reporting.
        $this->out(JText::sprintf('AUTOTWEET_CLI_PROCESS_COMPLETE', round(microtime(true) - $timetrack, 3)), true);
    }
}

// XTF0FPlatform initialization, before any JApp creation.
$f0fPlatform = \XTF0FPlatform::getInstance();
$isCli = $f0fPlatform->isCli();

// Instantiate the application object, passing the class name to JCli::getInstance
// and use chaining to execute the application.
JApplicationCli::getInstance('PerfectPublisherCronjob')->execute();
