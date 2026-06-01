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

namespace JchOptimize\Platform;

use JchOptimize\Core\Logger;
use JchOptimize\Core\Interfaces\Plugin as PluginInterface;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactory;

defined('_JEXEC') or die('Restricted access');

class Plugin implements PluginInterface
{

        protected static $plugin = null;

	/**
	 *
	 * @return integer
	 */
        public static function getPluginId()
        {
                $plugin = static::loadjch();

                return $plugin->extension_id;
        }

	/**
	 *
	 * @return mixed|null
	 */
        public static function getPlugin()
        {
	        return static::loadjch();
        }

	/**
	 *
	 * @return mixed|null
	 */
        private static function loadjch()
        {
                if (self::$plugin !== null)
                {
                        return self::$plugin;
                }

               // $cache = Cache::getCacheObject('output');
               // $id    = 'jchoptimizeplugincache';

               // if (!self::$plugin = $cache->get($id))
               // {
                        $db    = Factory::getDbo();
                        $query = $db->getQuery(true)
                                ->select('folder AS type, element AS name, params, extension_id')
                                ->from('#__extensions')
                                ->where('element = ' . $db->quote('jch_optimize'))
                                ->where('type = ' . $db->quote('plugin'))
	                        ->where('folder = ' . $db->quote('system'));

                        self::$plugin = $db->setQuery($query)->loadObject();

               //         $cache->store(self::$plugin, $id);
               // }

                return self::$plugin;
        }

        /**
         * 
         */
        public static function getPluginParams()
        {
                static $params = null;

                if (is_null($params))
                {
                        $plugin       = self::getPlugin();
                        $pluginParams = new Registry();
                        $pluginParams->loadString($plugin->params);

                        $params = Settings::getInstance($pluginParams);
                }

                return $params;
        }

        /**
         * 
         * @param Settings $params
         */
        public static function saveSettings($params)
        {
                $oPlugin          = Plugin::getPlugin();
                $oPlugin->params  = $params->toArray();
                $oPlugin->name    = 'PLG_SYSTEM_JCH_OPTIMIZE';
                $oPlugin->element = 'jch_optimize';

                $oData = new Registry($oPlugin);
                $aData = $oData->toArray();

		if (version_compare(JVERSION, '4.0', 'ge'))
		{
			$oController = new BaseController(array(), new MVCFactory('Joomla\\Component\\Plugins\\'));
			$oPluginModel = $oController->getModel('Plugin');
		}
		else
		{
			$oController = new BaseController;
			$oController->addModelPath(JPATH_ADMINISTRATOR . '/components/com_plugins/models', 'PluginsModel');
			/** @var \PluginsModelPlugin $oPluginModel */
			$oPluginModel = $oController->getModel('Plugin', 'PluginsModel');
		}

                if ($oPluginModel->save($aData) === FALSE)
                {
                        Logger::log(JText::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $oPluginModel->getError()), $params);
                }
        }

}
