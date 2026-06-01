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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Console\Loader\LoaderInterface;
use Joomla\DI\Container;
use Joomla\Event\DispatcherInterface;
use Joomla\Session\SessionInterface;
use Psr\Log\LoggerInterface;

define('EXTLY_CRONJOB_RUNNING', true);
define('AUTOTWEET_CRONJOB_RUNNING', true);

// Make sure we're being called from the command line, not a web interface
if (array_key_exists('REQUEST_METHOD', $_SERVER)) {
    exit();
}

/* Joomla Command - Initialization - Begin */

// We are a valid entry point.
const _JEXEC = 1;

// Define the application's minimum supported PHP version as a constant so it can be referenced within the application.
const JOOMLA_MINIMUM_PHP = '7.3.0';

if (version_compare(\PHP_VERSION, JOOMLA_MINIMUM_PHP, '<')) {
    echo 'Sorry, your PHP version is not supported.'.\PHP_EOL;
    echo 'Your host needs to use PHP version '.JOOMLA_MINIMUM_PHP.' or newer to run this version of Perfect Publisher!'.\PHP_EOL;
    echo 'You are currently running PHP version '.\PHP_VERSION.'.'.\PHP_EOL;

    exit;
}

// Load system defines
if (file_exists(dirname(__DIR__).'/defines.php')) {
    require_once dirname(__DIR__).'/defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', dirname(__DIR__));
    require_once JPATH_BASE.'/includes/defines.php';
}

// Check for presence of vendor dependencies not included in the git repository
if (!file_exists(JPATH_LIBRARIES.'/vendor/autoload.php') || !is_dir(JPATH_ROOT.'/media/vendor')) {
    echo 'It looks like you are trying to run Joomla! from our git repository.'.\PHP_EOL;
    echo 'To do so requires you complete a couple of extra steps first.'.\PHP_EOL;
    echo 'Please see https://docs.joomla.org/Special:MyLanguage/J4.x:Setting_Up_Your_Local_Environment for further details.'.\PHP_EOL;

    exit;
}

// Check if installed
if (!file_exists(JPATH_CONFIGURATION.'/configuration.php')
    || (filesize(JPATH_CONFIGURATION.'/configuration.php') < 10)) {
    echo 'Install Joomla to run cli commands'.\PHP_EOL;

    exit;
}

// Get the framework.
require_once JPATH_BASE.'/includes/framework.php';

// Boot the DI container
$container = \Joomla\CMS\Factory::getContainer();

/*
 * Alias the session service keys to the CLI session service as that is the primary session backend for this application
 *
 * In addition to aliasing "common" service keys, we also create aliases for the PHP classes to ensure autowiring objects
 * is supported.  This includes aliases for aliased class names, and the keys for aliased class names should be considered
 * deprecated to be removed when the class name alias is removed as well.
 */
$container->alias('session', 'session.cli')
    ->alias('JSession', 'session.cli')
    ->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\Session::class, 'session.cli')
    ->alias(\Joomla\Session\SessionInterface::class, 'session.cli');

/* Joomla Command - Initialization - End */

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
class PerfectPublisherCronjob extends \XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\ConsoleApplicationForJ4
{
    public function getName(): string
    {
        return 'PerfectPublisherCronjob';
    }

    /**
     * Entry point for PerfectPublisher CLI script.
     */
    public function doExecute(): int
    {
        $this->getConsoleOutput()->writeln('');
        $this->getConsoleOutput()->writeln('\\│/  ╔═╗┌─┐┬─┐┌─┐┌─┐┌─┐┌┬┐  ╔═╗┬ ┬┌┐ ┬  ┬┌─┐┬ ┬┌─┐┬─┐  \\│/');
        $this->getConsoleOutput()->writeln('─ ─  ╠═╝├┤ ├┬┘├┤ ├┤ │   │   ╠═╝│ │├┴┐│  │└─┐├─┤├┤ ├┬┘  ─ ─');
        $this->getConsoleOutput()->writeln('/│\\  ╩  └─┘┴└─└  └─┘└─┘ ┴   ╩  └─┘└─┘┴─┘┴└─┘┴ ┴└─┘┴└─  /│\\');
        $this->getConsoleOutput()->writeln('');
        $this->getConsoleOutput()->writeln(' - '.Factory::getDate()->toSql().' - ');

        if ($this->checkContentAvailability()) {
            $this->publish();
        }

        return 0;
    }

    private function checkContentAvailability()
    {
        // Fool the system into thinking we are running as a WebApplication
        $_SERVER['HTTP_HOST'] = 'domain-error-running-as-cronjob.com';
        $_SERVER['SCRIPT_NAME'] = '/perfect-publisher.html';

        $app = \Joomla\CMS\Factory::getApplication();
        $mvcFactory = $app->bootComponent('com_content')->getMVCFactory();

        $articleModel = $mvcFactory->createModel('Article', 'Administrator', ['ignore_request' => true]);

        if (!$articleModel) {
            throw new \Exception('ContentGenerator: Article model is unavailable');
        }

        return true;
    }

    private function checkingForNewVersions()
    {
        $this->getConsoleOutput()->writeln('Checking for new versions');
        $updateModel = XTF0FModel::getTmpInstance('LiveUpdates', 'AutotweetModel');
        $result = $updateModel->autoupdate();
        $this->getConsoleOutput()->writeln(implode("\n", $result['message']));
    }

    /**
     * Run the job.
     */
    private function publish()
    {
        // Disable caching.
        $config = Factory::getConfig();
        $config->set('caching', 0);
        $config->set('cache_handler', 'file');

        // Starting Indexer.
        $this->getConsoleOutput()->writeln(Text::_('AUTOTWEET_CLI_STARTING_PROCESS'));

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
        $this->getConsoleOutput()->writeln(Text::sprintf('AUTOTWEET_CLI_PROCESS_COMPLETE', round(microtime(true) - $timetrack, 3)));
    }
}

$container->share(
    \PerfectPublisherCronjob::class,
    function (Container $container) {
        $dispatcher = $container->get(DispatcherInterface::class);

        // Console uses the default system language
        $config = $container->get('config');
        $locale = $config->get('language');
        $debug = $config->get('debug_lang');

        $lang = $container->get(LanguageFactoryInterface::class)->createLanguage($locale, $debug);

        $app = new \PerfectPublisherCronjob($config, $dispatcher, $container, $lang);

        // The session service provider needs Factory::$application, set it if still null
        if (null === Factory::$application) {
            Factory::$application = $app;
        }

        $app->setCommandLoader($container->get(LoaderInterface::class));
        $app->setLogger($container->get(LoggerInterface::class));
        $app->setSession($container->get(SessionInterface::class));
        $app->setUserFactory($container->get(UserFactoryInterface::class));

        return $app;
    },
    true
);

// Legacy - XTF0FPlatform initialization, before any JApp creation.
$f0fPlatform = \XTF0FPlatform::getInstance();
$isCli = $f0fPlatform->isCli();

$app = $container->get(\PerfectPublisherCronjob::class);
$app->execute();
