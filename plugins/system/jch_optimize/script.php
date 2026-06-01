<?php

/**
 * JCH Optimize - Performs several front-end optimizations for fast downloads
 *
 * @package   jchoptimize/joomla-platform
 * @author    Samuel Marshall <samuel@jch-optimize.net>
 * @copyright Copyright (c) 2020 Samuel Marshall / JCH Optimize
 * @license   GNU/GPLv3, or later. See LICENSE file
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */

defined('_JEXEC') or die('Restricted access');

if (!defined('_JCH_EXEC'))
{
	define('_JCH_EXEC', 1);
}

use JchOptimize\Core\Admin;
use JchOptimize\Core\Helper;
use Joomla\CMS\Installer\InstallerAdapter;

class PlgSystemjch_optimizeInstallerScript
{
	/**
	 *
	 * @param   string            $type
	 * @param   InstallerAdapter  $parent
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function preflight($type, $parent)
	{
		$app = JFactory::getApplication();

		if ($type == 'install')
		{
			if (version_compare(PHP_VERSION, '5.6.0', '<'))
			{
				$app->enqueueMessage('This plugin requires PHP 5.6.0 or greater to work. Your installed version is ' . PHP_VERSION, 'error');

				return false;
			}
		}

		$compatible      = true;
		$minimum_version = '3.7.0';

		if (version_compare(JVERSION, $minimum_version, '<'))
		{
			$compatible = false;
		}

		if (!$compatible)
		{
			$app->enqueueMessage('JCH Optimize is not compatible with your version of Joomla!. This plugin requires v' . $minimum_version . ' or greater to work. Your installed version is ' . JVERSION, 'error');

			return false;
		}

		$manifest    = $parent->getManifest();
		$new_variant = (string) $manifest->variant;

		$file = JPATH_PLUGINS . '/system/jch_optimize/jch_optimize.xml';

		if (file_exists($file))
		{
			$xml         = simplexml_load_file($file);
			$old_variant = (string) $xml->variant;

			if ($old_variant == 'PRO' && $new_variant == 'FREE')
			{
				$app->enqueueMessage('You are trying to install the FREE version of JCH Optimize but you currently have the PRO version installed. You must uninstall the PRO version before you can install the FREE version.', 'error');

				return false;
			}
		}
	}

	/**
	 *
	 * @param   string            $type
	 * @param   InstallerAdapter  $parent
	 */
	public function postflight($type, $parent)
	{
		if (!class_exists('JFormFieldAutoorder'))
		{
			require_once($parent->getParent()->getPath('source') . '/fields/autoorder.php');
		}

		if ($type == 'install')
		{
			JFormFieldAutoorder::fixFilePermissions(true);
			JFormFieldAutoorder::leverageBrowserCaching(true);
		}

		if ($type == 'update')
		{
			JFormFieldAutoorder::cleanCache(true);
		}

		JFormFieldAutoorder::orderPlugins(true);
	}

	/**
	 *
	 * @param   InstallerAdapter  $parent
	 */
	public function uninstall($parent)
	{
		jimport('joomla.filesystem.folder');

		if (!class_exists('JFormFieldAutoorder'))
		{
			require_once($parent->getParent()->getPath('extension_root') . '/fields/autoorder.php');
		}

		$sprites = JPATH_ROOT . '/images/jch-optimize';

		if (file_exists($sprites))
		{
			JFolder::delete($sprites);
		}

		JFormFieldAutoorder::cleanCache(true);
		Admin::cleanHtaccess();
	}

}
