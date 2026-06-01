<?php
/**
 * @version     site/watchfulli.php 2023-09-06 zanardigit
 * @package     Watchful Client
 * @author      Watchful
 * @authorUrl   https://watchful.net
 * @copyright   Copyright (c) 2012-2023 Watchful
 * @license     GNU/GPL v3 or later
 */

use Joomla\CMS\Component\ComponentHelper;

(defined('_JEXEC') or defined('JPATH_PLATFORM')) or die;

// define our base paths
defined('WATCHFULLI_PATH') or define('WATCHFULLI_PATH', JPATH_ADMINISTRATOR . '/components/com_watchfulli');
defined('WATCHFULLI_ROOT') or define('WATCHFULLI_ROOT', dirname(__FILE__));

global $debug;
$localDebugEnabled = ComponentHelper::getParams('com_watchfulli')->get('debug', 0);
if ($localDebugEnabled)
{
	define('WATCHFUL_LOCAL_DEBUG', 1);
}
if (isset($_GET['debug']))
{
	define('WATCHFUL_REMOTE_DEBUG', 1);
}
if ($localDebugEnabled || isset($_GET['debug']))
{
	define('WATCHFULLI_DEBUG', 1);
	$debug                   = new stdClass();
	$debug->time['1. Start'] = time();
}

// ensure there's no notices or anything
@error_reporting(0);
@ini_set('display_errors', false);

// just use admin index, as it does the same thing & is based on the two paths defined above
require_once WATCHFULLI_PATH . '/watchfulli.php';
