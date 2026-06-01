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

defined('_JEXEC') or die('Restricted access');

use JchOptimize\Core\Interfaces\Cache as CacheInterface;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Event\Dispatcher;

class Cache implements CacheInterface
{
	/* Array of instances of cache objects */
	protected static $aCacheObject = array();

	/**
	 *
	 * @param   string  $id
	 * @param   bool    $checkexpire
	 *
	 * @return bool
	 */
	public static function getCache($id, $checkexpire = false)
	{
		$oCache = self::getCacheObject();
		$aCache = $oCache->get($id);

		if ($aCache === false)
		{
			return false;
		}

		return $aCache['result'];
	}

	/**
	 *
	 * @param   string    $id
	 * @param   callable  $function
	 * @param   array     $args
	 *
	 * @return bool|array
	 */
	public static function getCallbackCache($id, $function, $args)
	{
		$oCache = self::getCacheObject('callback');
		$oCache->get($function, $args, $id);

		//Joomla! doesn't check if the cache is stored so we gotta check ourselves
		$aCache = self::getCache($id);

		if ($aCache === false)
		{
			$oCache->clean('plg_jch_optimize');
		}

		return $aCache;
	}

	/**
	 *
	 * @param   string  $argtype
	 *
	 * @return mixed
	 */
	public static function getCacheObject($argtype = 'output')
	{
		if (empty(self::$aCacheObject[$argtype]))
		{
			$cachebase = JPATH_SITE . '/cache';
			$group     = 'plg_jch_optimize';
			$type      = $argtype;

			if ($argtype == 'static')
			{
				$cachebase = Paths::cachePath(false);
				$type      = 'output';
				$group     = '';
			}

			if ($argtype == 'jchgc')
			{
				$cachebase = JPATH_SITE . '/cache/plg_jch_optimize';
				$type      = 'output';
				$group     = '';
			}

			if (!file_exists($cachebase))
			{
				Utility::createFolder($cachebase);
			}


			$aOptions = array(
				'defaultgroup' => $group,
				'checkTime'    => true,
				'application'  => 'site',
				'language'     => 'en-GB',
				'cachebase'    => $cachebase,
				'storage'      => 'file',
				'lifetime'     => self::getLifetime(),
				'caching'      => true
			);

			$oCache = \JCache::getInstance($type, $aOptions);

			self::$aCacheObject[$argtype] = $oCache;
		}

		return self::$aCacheObject[$argtype];
	}

	protected static function getLifetime()
	{
		static $lifetime;

		if (!$lifetime)
		{
			$params = Plugin::getPluginParams();

			$lifetime = $params->get('cache_lifetime', '15');
		}

		return (int) $lifetime;
	}


	/**
	 *
	 */
	public static function gc()
	{
		$oCache = self::getCacheObject('jchgc');
		$oCache->gc();

		$oStaticCache = self::getCacheObject('static');
		$oStaticCache->gc();

		//Only delete page cache
		self::deleteCache('page');
	}

	/**
	 *
	 * @param   string  $content
	 * @param   string  $id
	 */
	public static function saveCache($content, $id)
	{
		$oCache = self::getCacheObject();
		$oCache->store(array('result' => $content), $id);
	}

	/**
	 *
	 * @param   string  $context
	 *
	 * @return bool
	 */
	public static function deleteCache($context = 'both')
	{
		$return = false;
		$oCache = Cache::getCacheObject();

		if ($context != 'page')
		{
			$oStaticCache = Cache::getCacheObject('static');

			$return |= $oCache->clean('plg_jch_optimize');
			$return |= $oStaticCache->clean();
		}

		if ($context != 'plugin')
		{
			$return |= $oCache->clean('page');

			//Clean LiteSpeed cache
			$dispatcher = new Dispatcher();
			$dispatcher->triggerEvent('onLSCacheExpired');

			header('X-LiteSpeed-Purge: *');

			//Clean jotcache
			//@TODO add Joomla 4 compatibility
			if (version_compare(JVERSION, '4.0', 'lt'))
			{
				if (file_exists(JPATH_ADMINISTRATOR . '/components/com_jotcache/models/main.php'))
				{
					$oController = new BaseController;
					$oController->addModelPath(JPATH_ADMINISTRATOR . '/components/com_jotcache/models', 'MainModel');
					$oMainModel = $oController->getModel('Main', 'MainModel');
					$oMainModel->refresh();
				}
			}
		}

		return (bool) $return;
	}
}
