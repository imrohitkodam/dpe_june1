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

defined( '_JEXEC' ) or die( 'Restricted access' );

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\HTML\HTMLHelper;
use JchOptimize\Core\Helper;
use JchOptimize\Core\Ajax;
use JchOptimize\Core\Admin;
use JchOptimize\Core\Json;
use JchOptimize\Core\Browser;
use JchOptimize\Core\Optimize;
use JchOptimize\Core\Logger;
use JchOptimize\Platform\Settings;
use JchOptimize\Platform\Cache;
use JchOptimize\Platform\Utility;
use JchOptimize\Platform\Plugin;
use JchOptimize\Platform\Html;
use JchOptimize\Platform\Uri;

if ( ! defined( 'JCH_PLUGIN_DIR' ) )
{
	define( 'JCH_PLUGIN_DIR', dirname( __FILE__ ) );
}

if ( ! defined( 'JCH_VERSION' ) )
{
	define( 'JCH_VERSION', '6.2.1' );
}

require_once __DIR__ . '/autoload.php';

class plgSystemJCH_Optimize extends CMSPlugin
{

	public function __construct( &$subject, $config )
	{
		parent::__construct( $subject, $config );

		if ( ! defined( 'JCH_DEBUG' ) )
		{
			define( 'JCH_DEBUG', ( $this->params->get( 'debug', 0 ) && JDEBUG ) );
		}
	}


	protected function isPluginDisabled()
	{
		try
		{
			$app = JFactory::getApplication();
		}
		catch ( Exception $e )
		{
			return false;
		}

		$user = JFactory::getUser();

		$menuexcluded    = $this->params->get( 'menuexcluded', array() );
		$menuexcludedurl = $this->params->get( 'menuexcludedurl', array() );

		return ( ! $app->isClient( 'site' )
		         || ( $app->input->get( 'jchnooptimize', '', 'int' ) == 1 )
		         || ( $app->get( 'offline', '0' ) && $user->get( 'guest' ) )
		         || $this->isEditorLoaded()
		         || in_array( $app->input->get( 'Itemid', '', 'int' ), $menuexcluded )
		         || Helper::findExcludes( $menuexcludedurl, Uri::getInstance()->toString() ) );
	}

	/**
	 *
	 * @return boolean
	 * @throws Exception
	 */
	public function onAfterRender()
	{
		if ( $this->isPluginDisabled() )
		{
			return false;
		}

		if ( $this->params->get( 'debug', 0 ) )
		{
			error_reporting( E_ALL & ~E_NOTICE );
		}

		$app   = JFactory::getApplication();
		$sHtml = $app->getBody();


		if ( ! Helper::validateHtml( $sHtml ) )
		{
			return false;
		}

		if ( $app->input->get( 'jchbackend' ) == '1' )
		{
			return false;
		}

		if ( $app->input->get( 'jchbackend' ) == '2' )
		{
			echo $sHtml;
			while ( @ob_end_flush() )
			{
				;
			}
			exit;
		}

		try
		{
			$sOptimizedHtml = Optimize::optimize( $this->params, $sHtml );
		}
		catch ( Exception $ex )
		{
			Logger::log( $ex->getMessage(), Settings::getInstance( $this->params ) );

			$sOptimizedHtml = $sHtml;
		}

		$app->setBody( $sOptimizedHtml );
	}

	/**
	 * Gets the name of the current Editor
	 *
	 * @staticvar string $sEditor
	 * @return string
	 */
	protected function isEditorLoaded()
	{
		$aEditors = JPluginHelper::getPlugin( 'editors' );

		foreach ( $aEditors as $sEditor )
		{
			if ( class_exists( 'plgEditor' . $sEditor->name, false ) )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 *
	 */
	public function onAjaxGetmultiselect()
	{
		$aData = Utility::get( 'data', array(), 'array' );

		$params = Plugin::getPluginParams();
		$oAdmin = new Admin( $params );
		$oHtml  = new Html( $params );

		try
		{
			$sHtml = $oHtml->getHomePageHtml();
			$oAdmin->getAdminLinks( $sHtml );
		}
		catch ( Exception $e )
		{
		}

		$response = array();

		foreach ( $aData as $sData )
		{
			$options                = $oAdmin->prepareFieldOptions( $sData['type'], $sData['param'], $sData['group'], false );
			$response[$sData['id']] = new Json( $options );
		}

		return new Json( $response );

	}

	/**
	 * Provide a hash for the default page cache plugin's key based on type of browser detected by Google font
	 *
	 *
	 * @return string $hash    Calculated hash of browser type
	 */
	public function onPageCacheGetKey()
	{
		$browser = Browser::getInstance();
		$hash    = $browser->getFontHash();

		return $hash;
	}

	public function onJchCacheExpired()
	{
		return Cache::deleteCache( 'plugin' );
	}


	/**
	 *
	 */
	public function onAfterDispatch()
	{
		if ( $this->params->get( 'lazyload_enable', '0' ) && ! $this->isPluginDisabled() )
		{
			$options = array(
				'relative' => true
			);

			HTMLHelper::script( 'plg_jchoptimize/ls.loader.js', $options );

			##<procode>##
			if ( $this->params->get( 'pro_lazyload_bgimages', '0' ) || $this->params->get( 'pro_lazyload_audiovideo', '0' ) )
			{
				HTMLHelper::script( 'plg_jchoptimize/pro-ls.unveilhooks.js', $options );
			}

			if ( $this->params->get( 'pro_lazyload_effects', '0' ) )
			{
				HTMLHelper::stylesheet( 'plg_jchoptimize/pro-ls.effects.css', $options );
				HTMLHelper::script( 'plg_jchoptimize/pro-ls.loader.effects.js', $options );
			}
			##</procode>##

			if ( $this->params->get( 'lazyload_autosize', '0' ) )
			{
				HTMLHelper::script( 'plg_jchoptimize/ls.autosize.js', $options );
			}

			HTMLHelper::script( 'plg_jchoptimize/lazysizes.js', $options );
		}
	}

	##<procode>##

	/**
	 *
	 * @return string
	 */
	public function onAjaxFiletree()
	{
		return Ajax::fileTree();
	}

	/**
	 *
	 */
	public function onAjaxOptimizeimages()
	{
		return Ajax::optimizeImages();
	}

	public function onAjaxSmartcombine()
	{
		$oParams = Plugin::getPluginParams();
		$oAdmin  = new Admin( $oParams );
		$oHtml   = new Html( $oParams );

		try
		{
			$aHtml       = $oHtml->getMainMenuItemsHtmls();
			$aLinksArray = array();

			foreach ( $aHtml as $sHtml )
			{
				$aLinks = $oAdmin->generateAdminLinks( $sHtml, '', true );

				$aLinks['css'] = $this->setUpArray( $aLinks['css'][0] );
				$aLinks['js']  = $this->setUpArray( $aLinks['js'][0] );

				$aLinksArray[] = $aLinks;
			}

			$aReturnArray = array(
				'css' => $aLinksArray[0]['css'],
				'js'  => $aLinksArray[0]['js']
			);

			for ( $i = 1; $i < count( $aLinksArray ); $i ++ )
			{
				$aReturnArray['css'] = array_filter( array_intersect( $aReturnArray['css'], $aLinksArray[$i]['css'] ), function ( $sUrl ) {
					return ! preg_match( '#fonts\.googleapis\.com#i', $sUrl );
				} );
				$aReturnArray['js']  = array_intersect( $aReturnArray['js'], $aLinksArray[$i]['js'] );
			}
		}
		catch ( Exception $oException )
		{
			return new Json( array() );
		}

		return new Json( $aReturnArray );
	}

	protected function setUpArray( $aLinks )
	{
		return array_map(
			function ( $sValue ) {
				return preg_replace( '#[?\#].*+#i', '', $sValue );
			},
			array_column( $aLinks, 'url' )
		);
	}
	##</procode>##
}
