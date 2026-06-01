<?php
/**
 * @version     SVN: <svn_id>
 * @package     JBolo
 * @subpackage  tjassetsloader
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2015 TechJoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;


if (!defined('DS'))
{
	define('DS', '/');
}

// Load language file for plugin
$lang = Factory::getLanguage();
$lang->load('plg_system_tjassetsloader', JPATH_ADMINISTRATOR);

/**
 * Class for TJ assets loader plugin
 *
 * @package     JBolo
 * @subpackage  tjassetsloader
 * @since       3.1.4
 */
class PlgSystemTjassetsloader extends CMSPlugin
{
}
