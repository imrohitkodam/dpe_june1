<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die;

class plgInstallerRsticketspro extends JPlugin
{
	public function onInstallerBeforePackageDownload(&$url, &$headers)
	{
		$uri 	= JUri::getInstance($url);
		$parts 	= explode('/', $uri->getPath());

		if ($uri->getHost() == 'www.rsjoomla.com' && (in_array('com_rsticketspro', $parts) || in_array('plg_rsticketspro_cron', $parts) || in_array('plg_rsticketspro_reports', $parts))) {
			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/config.php')) {
				return;
			}

			if (!file_exists(JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/version.php')) {
				return;
			}

			// Load our config
			require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/config.php';

			// Load our version
			require_once JPATH_ADMINISTRATOR.'/components/com_rsticketspro/helpers/version.php';

			// Load language
			JFactory::getLanguage()->load('plg_installer_rsticketspro');

			// Get the version
			$version = new RSTicketsProVersion;

			// Get the update code
			$code = RSTicketsProConfig::getInstance()->get('global_register_code');

			// No code added
			if (!strlen($code)) {
				JFactory::getApplication()->enqueueMessage(JText::_('PLG_INSTALLER_RSTICKETSPRO_MISSING_UPDATE_CODE'), 'warning');
				return;
			}

			// Code length is incorrect
			if (strlen($code) != 20) {
				JFactory::getApplication()->enqueueMessage(JText::_('PLG_INSTALLER_RSTICKETSPRO_INCORRECT_CODE'), 'warning');
				return;
			}

			// Compute the update hash
			$uri->setVar('hash', md5($code.$version->key));
			$uri->setVar('domain', JUri::getInstance()->getHost());
			$uri->setVar('code', $code);
			$url = $uri->toString();
		}
	}
}
