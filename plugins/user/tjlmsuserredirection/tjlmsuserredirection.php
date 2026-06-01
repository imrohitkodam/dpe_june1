<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die( 'Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

jimport('joomla.html.parameter');
jimport('joomla.plugin.plugin');

// Load language file for plugin.
$lang = Factory::getLanguage();
$lang->load('plg_user_tjlmsuserredirection', JPATH_ADMINISTRATOR);

/**
 * Methods supporting a list of Tjlms action.
 *
 * @since  1.0.0
 */
class PlgUserTjlmsuserredirection extends CMSPlugin
{
	/**
	 * Function used as a trigger after User login
	 *
	 * @param   MIXED  $user     user ID
	 * @param   MIXED  $options  Options available
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function onUserLogin($user, $options)
	{
		$app    = Factory::getApplication();

		if ($app->isClient('administrator'))
		{
			if ($this->params->get('admin_redirect_to_dashboard') == 1)
			{
				$app->redirect('index.php?option=com_tjlms');
			}
		}
		else
		{
			$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

			if (!class_exists('comtjlmsHelper'))
			{
				// Require_once $path;
				JLoader::register('comtjlmsHelper', $path);
				JLoader::load('comtjlmsHelper');
			}

			$tjlmsFrontendHelper = new comtjlmsHelper;

			// Get itemid of all My dashboard
			$itemid = $tjlmsFrontendHelper->getItemId('index.php?option=com_tjlms&view=dashboard&layout=my');

			$dashboardUrl = 'index.php?option=com_tjlms&view=dashboard&layout=my&Itemid=' . $itemid;
			$dashboardUrl = Uri::root() . substr(Route::_($dashboardUrl, false), strlen(Uri::base(true)) + 1);

			if ($this->params->get('user_redirect_to_dashboard') == 1)
			{
				$app->redirect($dashboardUrl);
			}
		}
	}
}
