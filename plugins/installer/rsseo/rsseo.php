<?php
/**
* @package RSSeo!
* @copyright (C) 2016 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class plgInstallerRSSeo extends CMSPlugin
{
	public function onInstallerBeforePackageDownload(&$url, &$headers)
	{
		$uri 	= Uri::getInstance($url);
		$parts 	= explode('/', $uri->getPath());
		
		if ($uri->getHost() == 'www.rsjoomla.com' && in_array('com_rsseo', $parts)) {
			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/rsseo.php')) {
				return;
			}
			
			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/version.php')) {
				return;
			}
			
			// Load our main helper
			require_once JPATH_ADMINISTRATOR.'/components/com_rsseo/helpers/rsseo.php';
			
			// Load language
			Factory::getLanguage()->load('plg_installer_rsseo');
			
			// Get the update code
			$code = rsseoHelper::getConfig('global_register_code');
			
			// No code added
			if (!strlen($code)) {
				Factory::getApplication()->enqueueMessage(Text::_('PLG_INSTALLER_RSSEO_MISSING_UPDATE_CODE'), 'warning');
				return;
			}
			
			// Code length is incorrect
			if (strlen($code) != 20) {
				Factory::getApplication()->enqueueMessage(Text::_('PLG_INSTALLER_RSSEO_INCORRECT_CODE'), 'warning');
				return;
			}
			
			// Compute the hash
			$hash = rsseoHelper::genKeyCode();
			
			// Compute the update hash
			$uri->setVar('hash', $hash);
			$uri->setVar('domain', Uri::getInstance()->getHost());
			$uri->setVar('code', $code);
			$url = $uri->toString();
		}
	}
}
