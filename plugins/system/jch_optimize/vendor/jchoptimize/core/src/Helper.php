<?php

/**
 * JCH Optimize - Performs several front-end optimizations for fast downloads
 *
 * @package   jchoptimize/core
 * @author    Samuel Marshall <samuel@jch-optimize.net>
 * @copyright Copyright (c) 2020 Samuel Marshall / JCH Optimize
 * @license   GNU/GPLv3, or later. See LICENSE file
 *
 * If LICENSE file missing, see <http://www.gnu.org/licenses/>.
 */

namespace JchOptimize\Core;

defined( '_JCH_EXEC' ) or die( 'Restricted access' );

use CodeAlfa\Minify\Js;
use CodeAlfa\Minify\Css;
use CodeAlfa\Minify\Html;
use JchOptimize\Platform\Settings;
use JchOptimize\Platform\Uri;
use JchOptimize\Platform\Plugin;
use JchOptimize\Platform\Profiler;
use JchOptimize\Platform\Paths;

class HelperBase
{

	/**
	 * @param         $params
	 * @param         $path
	 * @param         $orig_path
	 * @param   bool  $domains_only
	 *
	 * @return array
	 */
	public static function cookieLessDomain( $params, $path, $orig_path, $domains_only = false )
	{
		return $domains_only ? array() : $orig_path;
	}

	public static function addHttp2Push( $url, $type )
	{
		return $url;
	}
}

/**
 * Some helper functions
 *
 */
class Helper extends HelperBase
{
	public static $preloads = array();

	/**
	 * Checks if file (can be external) exists
	 *
	 * @param   string  $sPath
	 *
	 * @return boolean
	 */
	public static function fileExists( $sPath )
	{
		if ( ( strpos( $sPath, 'http' ) === 0 ) )
		{
			$sFileHeaders = @get_headers( $sPath );

			return ( $sFileHeaders !== false && strpos( $sFileHeaders[0], '404' ) === false );
		}
		else
		{
			return file_exists( $sPath );
		}
	}

	/**
	 *
	 * @return boolean
	 */
	public static function isMsieLT10()
	{
		$browser = Browser::getInstance();

		return ( $browser->getBrowser() == 'IE' && $browser->getVersion() < 10 );
	}

	/**
	 *
	 * @param   string  $string
	 *
	 * @return string
	 */
	public static function cleanReplacement( $string )
	{
		return strtr( $string, array( '\\' => '\\\\', '$' => '\$' ) );
	}

	/**
	 * Get local path of file from the url in the HTML if internal
	 * If external or php file, the url is returned
	 *
	 * @param   string  $sUrl  Url of file
	 *
	 * @return string       File path
	 */
	public static function getFilePath( $sUrl )
	{
		$sUriPath = Uri::base( true );

		$oUri = clone Uri::getInstance();
		$oUrl = clone Uri::getInstance( html_entity_decode( $sUrl ) );

		//Use absolute file path if file is internal and a static file
		if ( Url::isInternal( $sUrl ) && ! Url::requiresHttpProtocol( $sUrl ) )
		{
			return Paths::absolutePath( preg_replace( '#^' . preg_quote( $sUriPath, '#' ) . '#', '', $oUrl->getPath() ) );
		}
		else
		{
			$scheme = $oUrl->getScheme();

			if ( empty( $scheme ) )
			{
				$oUrl->setScheme( $oUri->getScheme() );
			}

			$host = $oUrl->getHost();

			if ( empty( $host ) )
			{
				$oUrl->setHost( $oUri->getHost() );
			}

			$path = $oUrl->getPath();

			if ( ! empty( $path ) )
			{
				if ( substr( $path, 0, 1 ) != '/' )
				{
					$oUrl->setPath( $sUriPath . '/' . $path );
				}
			}

			$sUrl = $oUrl->toString();

			$query = $oUrl->getQuery();

			if ( ! empty( $query ) )
			{
				parse_str( $query, $args );

				$sUrl = str_replace( $query, http_build_query( $args, '', '&' ), $sUrl );
			}

			return $sUrl;
		}
	}

	/**
	 *
	 * @return string
	 */
	public static function getBaseFolder()
	{
		return Uri::base( true ) . '/';
	}

	/**
	 *
	 * @param   string  $search
	 * @param   string  $replace
	 * @param   string  $subject
	 *
	 * @return string|string[]
	 */
	public static function strReplace( $search, $replace, $subject )
	{
		return str_replace( self::cleanPath( $search ), $replace, self::cleanPath( $subject ) );
	}

	/**
	 *
	 * @param   string  $str
	 *
	 * @return string|string[]
	 */
	public static function cleanPath( $str )
	{
		return str_replace( array( '\\\\', '\\' ), '/', $str );
	}

	/**
	 * If parameter is set will minify HTML before sending to browser;
	 * Inline CSS and JS will also be minified if respective parameters are set
	 *
	 * @param   string    $sHtml
	 * @param   Settings  $oParams
	 *
	 * @return string                       Optimized HTML
	 */
	public static function minifyHtml( $sHtml, $oParams )
	{
		JCH_DEBUG ? Profiler::start( 'MinifyHtml' ) : null;


		if ( $oParams->get( 'html_minify', 0 ) )
		{
			$aOptions = array();

			if ( $oParams->get( 'css_minify', 0 ) )
			{
				$aOptions['cssMinifier'] = array( 'CodeAlfa\Minify\Css', 'optimize' );
			}

			if ( $oParams->get( 'js_minify', 0 ) )
			{
				$aOptions['jsMinifier'] = array( 'CodeAlfa\Minify\Js', 'optimize' );
			}

			$aOptions['jsonMinifier'] = array( 'CodeAlfa\Minify\Json', 'optimize' );
			$aOptions['minifyLevel']  = $oParams->get( 'html_minify_level', 2 );
			$aOptions['isXhtml']      = self::isXhtml( $sHtml );
			$aOptions['isHtml5']      = self::isHtml5( $sHtml );

			$sHtmlMin = Html::optimize( $sHtml, $aOptions );

			if ( $sHtmlMin == '' )
			{
				Logger::log( 'Error while minifying HTML', $oParams );

				$sHtmlMin = $sHtml;
			}

			$sHtml = $sHtmlMin;

			JCH_DEBUG ? Profiler::stop( 'MinifyHtml', true ) : null;
		}

		return $sHtml;
	}

	/**
	 * Determine if document is of XHTML doctype
	 *
	 * @param   string  $sHtml
	 *
	 * @return boolean
	 */
	public static function isXhtml( $sHtml )
	{
		return (bool) preg_match( '#^\s*+(?:<!DOCTYPE(?=[^>]+XHTML)|<\?xml.*?\?>)#i', trim( $sHtml ) );
	}

	/**
	 * Determines if document is of html5 doctype
	 *
	 * @param   string  $sHtml
	 *
	 * @return boolean        True if doctype is html5
	 */
	public static function isHtml5( $sHtml )
	{
		return (bool) preg_match( '#^<!DOCTYPE html>#i', trim( $sHtml ) );
	}

	/**
	 * Splits a string into an array using any regular delimiter or whitespace
	 *
	 * @param   string  $sString  Delimited string of components
	 *
	 * @return array            An array of the components
	 */
	public static function getArray( $sString )
	{
		if ( is_array( $sString ) )
		{
			$aArray = $sString;
		}
		else
		{
			$aArray = explode( ',', trim( $sString ) );
		}

		$aArray = array_map( function ( $sValue ) {
			return trim( $sValue );
		}, $aArray );

		return array_filter( $aArray );
	}

	/**
	 *
	 * @param   string    $url
	 * @param   Settings  $params
	 * @param   array     $posts
	 */
	public static function postAsync( $url, $params, array $posts )
	{
		$post_params = array();

		foreach ( $posts as $key => &$val )
		{
			if ( is_array( $val ) )
			{
				$val = implode( ',', $val );
			}

			$post_params[] = $key . '=' . urlencode( $val );
		}

		$post_string = implode( '&', $post_params );

		$parts = Helper::parseUrl( $url );

		if ( isset( $parts['scheme'] ) && ( $parts['scheme'] == 'https' ) )
		{
			$protocol     = 'ssl://';
			$default_port = 443;
		}
		else
		{
			$protocol     = '';
			$default_port = 80;
		}

		$fp = @fsockopen( $protocol . $parts['host'], isset( $parts['port'] ) ? $parts['port'] : $default_port, $errno, $errstr, 1 );

		if ( ! $fp )
		{
			Logger::log( $errno . ': ' . $errstr, $params );
			Logger::debug( $errno . ': ' . $errstr, 'JCH_post-error' );
		}
		else
		{
			$out = "POST " . $parts['path'] . '?' . $parts['query'] . " HTTP/1.1\r\n";
			$out .= "Host: " . $parts['host'] . "\r\n";
			$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$out .= "Content-Length: " . strlen( $post_string ) . "\r\n";
			$out .= "Connection: Close\r\n\r\n";

			if ( isset( $post_string ) )
			{
				$out .= $post_string;
			}

			fwrite( $fp, $out );
			fclose( $fp );
			Logger::debug( $out, 'JCH_post' );
		}
	}

	/**
	 *
	 * @param   string  $sUrl
	 *
	 * @return array
	 */
	public static function parseUrl( $sUrl )
	{
		preg_match( '#^(?:([a-z][a-z0-9+.-]*+):)?(?://(?:([^:@/]*+)(?::([^@/]*+))?@)?([^:/]++)(?::([^/]*+))?)?([^?\#\n]*+)?(?:\?([^\#\n]*+))?(?:\#(.*+))?$#i', $sUrl, $m );

		$parts = array();

		$parts['scheme']   = ! empty( $m[1] ) ? $m[1] : null;
		$parts['user']     = ! empty( $m[2] ) ? $m[2] : null;
		$parts['pass']     = ! empty( $m[3] ) ? $m[3] : null;
		$parts['host']     = ! empty( $m[4] ) ? $m[4] : null;
		$parts['port']     = ! empty( $m[5] ) ? $m[5] : null;
		$parts['path']     = ! empty( $m[6] ) ? $m[6] : '';
		$parts['query']    = ! empty( $m[7] ) ? $m[7] : null;
		$parts['fragment'] = ! empty( $m[8] ) ? $m[8] : null;

		return $parts;
	}

	/**
	 *
	 * @param   string  $sHtml
	 *
	 * @return false|int
	 */
	public static function validateHtml( $sHtml )
	{
		return preg_match( '#^(?>(?><?[^<]*+)*?<html(?><?[^<]*+)*?<head(?><?[^<]*+)*?</head\s*+>)(?><?[^<]*+)*?'
		                   . '<body.*</body\s*+>(?><?[^<]*+)*?</html\s*+>#is', $sHtml );
	}

	/**
	 *
	 * @param   string  $image
	 *
	 * @return array
	 */
	public static function prepareImageUrl( $image )
	{
		//return array('path' => Utility::encrypt($image));
		return array( 'path' => $image );
	}

	/**
	 *
	 * @param   Settings  $params
	 */
	public static function clearHiddenValues( Settings $params )
	{
		$params->set( 'hidden_containsgf', '' );
		Plugin::saveSettings( $params );
	}

	/**
	 * Truncate url at the '/' less than 40 characters prepending '...' to the string
	 *
	 * @param   array   $aUrl
	 * @param   string  $sType
	 *
	 * @return string
	 */
	public static function prepareFileUrl( $aUrl, $sType )
	{
		$sUrl = isset( $aUrl['url'] ) ?
			Admin::prepareFileValues( $aUrl['url'], '', 40 ) :
			( $sType == 'css' ? 'Style' : 'Script' ) . ' Declaration';

		return $sUrl;
	}

	/**
	 * @param         $url
	 * @param         $type
	 * @param   bool  $deferred
	 *
	 * @return bool|mixed
	 */
	public static function addHttp2Push( $url, $type, $deferred = false )
	{
		//Avoid invalid urls
		if ( $url == '' || Url::isDataUri( trim( $url ) ) )
		{
			return false;
		}

		$params = Plugin::getPluginParams();

		if ( Helper::findExcludes( $params->get( 'pro_http2_exclude', array() ), $url ) )
		{
			return false;
		}

		//Skip external files
		if ( ! Url::isInternal( $url, $params ) )
		{
			return parent::addHttp2Push( $url, $type );
		}

		static $bAlreadyCached = null;

		//If http2 is not enabled or file is deferred when 'Exclude deferred' is enabled, return
		if ( ! $params->get( 'pro_http2_push_enable', '0' )
		     || ( $params->get( 'pro_http2_exclude_deferred', '1' ) && $deferred ) )
		{
			return parent::addHttp2Push( $url, $type );
		}

		if ( $params->get( 'cookielessdomain_enable', '0' ) )
		{
			static $sCdnFileTypesRegex = '';

			if ( empty( $sCdnFileTypesRegex ) )
			{
				$sCdnFileTypesRegex = implode( '|', self::getCdnFileTypes( $params ) );
			}

			//If this file type will be loaded by CDN don't push if option not set
			if ( $sCdnFileTypesRegex != '' && preg_match( '#\.(?>' . $sCdnFileTypesRegex . ')#i', $url ) && ! $params->get( 'pro_http2_push_cdn', '0' ) )
			{
				return parent::addHttp2Push( $url, $type );
			}
		}

		if ( $type == 'image' )
		{
			static $no_image = 0;

			if ( $no_image ++ > 5 )
			{
				return parent::addHttp2Push( $url, $type );
			}
		}

		if ( $type == 'js' )
		{
			static $no_js = 0;

			if ( $no_js ++ > 5 )
			{
				return parent::addHttp2Push( $url, $type );
			}

			$type = 'script';
		}

		if ( $type == 'css' )
		{
			static $no_css = 0;

			if ( $no_css ++ > 5 )
			{
				return parent::addHttp2Push( $url, $type );
			}
			$type = 'style';
		}

		if ( ! in_array( $type, $params->get( 'pro_http2_file_types', array(
			'style',
			'script',
			'font',
			'image'
		) ) ) )
		{
			return parent::addHttp2Push( $url, $type );
		}

		if ( $type == 'font' )
		{
			//Only push fonts of type woff/woff2
			if ( preg_match( "#\.\K(?:woff2?)(?=$|[\#?])#", $url, $m ) == '1' )
			{
				static $no_font = 0;

				if ( $no_font ++ > 10 )
				{
					return parent::addHttp2Push( $url, $type );
				}

				self::addToPreload( $url, $type, $m[0] );
			}
			else
			{
				return parent::addHttp2Push( $url, $type );
			}
		}
		else
		{
			//Populate preload variable
			self::addToPreload( $url, $type );
		}

	}

	/**
	 *
	 * @param   array   $aArray
	 * @param   string  $sString
	 * @param   string  $sType
	 *
	 * @return boolean
	 */
	public static function findExcludes( $aArray, $sString, $sType = '' )
	{
		foreach ( $aArray as $sValue )
		{
			if ( $sType == 'js' )
			{
				$sString = Js::optimize( $sString );
			}
			elseif ( $sType == 'css' )
			{
				$sString = Css::optimize( $sString );
			}

			if ( $sValue && strpos( htmlspecialchars_decode( $sString ), $sValue ) !== false )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns an array of file types that will be loaded by CDN
	 *
	 * @param   Settings  $params
	 *
	 * @return array $aCdnFileTypes Array of file type extensions
	 */
	public static function getCdnFileTypes( $params )
	{
		$aCdnFileTypes = null;

		if ( is_null( $aCdnFileTypes ) )
		{
			$aCdnFileTypes = array();
			$aDomains      = Helper::cookieLessDomain( $params, '', '', true );

			if ( ! empty( $aDomains ) )
			{
				foreach ( $aDomains as $cdn_file_types )
				{
					$aCdnFileTypes = array_merge( $aCdnFileTypes, explode( '|', $cdn_file_types ) );
				}

				$aCdnFileTypes = array_unique( $aCdnFileTypes );
			}
		}

		return $aCdnFileTypes;
	}

	/**
	 * @param   Settings  $params
	 * @param   string    $path
	 * @param   string    $orig_path
	 * @param   bool      $domains_only
	 * @param   bool      $reset
	 *
	 * @return array|bool|mixed
	 */

	public static function cookieLessDomain( $params, $path, $orig_path, $domains_only = false, $reset = false )
	{
		//If feature disabled just return the path if present
		if ( ! $params->get( 'cookielessdomain_enable', '0' ) && ! $domains_only )
		{
			return parent::cookieLessDomain( $params, $path, $orig_path, $domains_only );
		}

		//Cache processed files to ensure the same file isn't placed on a different domain
		//if it occurs on the page twice
		static $aDomain = array();
		static $aFilePaths = array();

		//reset $aFilePaths for unit testing
		if ( $reset )
		{
			foreach ( $aFilePaths as $key => $value )
			{
				unset( $aFilePaths[$key] );
			}

			foreach ( $aDomain as $key => $value )
			{
				unset( $aDomain[$key] );
			}

			return false;
		}

		if ( empty( $aDomain ) )
		{
			switch ( $params->get( 'cdn_scheme', '0' ) )
			{
				case '1':
					$scheme = 'http:';
					break;
				case '2':
					$scheme = 'https:';
					break;
				case '0':
				default:
					$scheme = '';
					break;
			}

			$aDefaultFiles = self::getStaticFiles();

			if ( trim( $params->get( 'cookielessdomain', '' ) ) != '' )
			{
				$domain1      = $params->get( 'cookielessdomain' );
				$staticfiles1 = implode( '|', array_merge( $params->get( 'staticfiles', $aDefaultFiles ), $params->get( 'pro_customcdnextensions', array() ) ) );

				$aDomain[$scheme . self::prepareDomain( $domain1 )] = $staticfiles1;
			}
			##<procode>##

			if ( trim( $params->get( 'pro_cookielessdomain_2', '' ) ) != '' )
			{
				$domain2      = $params->get( 'pro_cookielessdomain_2' );
				$staticfiles2 = implode( '|', $params->get( 'pro_staticfiles_2', $aDefaultFiles ) );

				$aDomain[$scheme . self::prepareDomain( $domain2 )] = $staticfiles2;
			}

			if ( trim( $params->get( 'pro_cookielessdomain_3', '' ) ) != '' )
			{
				$domain3      = $params->get( 'pro_cookielessdomain_3' );
				$staticfiles3 = implode( '|', $params->get( 'pro_staticfiles_3', $aDefaultFiles ) );

				$aDomain[$scheme . self::prepareDomain( $domain3 )] = $staticfiles3;
			}
			##</procode>##
		}

		//Sprite Generator needs this to remove CDN domains from images to create sprite
		if ( $domains_only )
		{
			return $aDomain;
		}

		//if no domain is configured abort
		if ( empty( $aDomain ) )
		{
			return parent::cookieLessDomain( $params, $path, $orig_path );
		}

		//If we haven't matched a cdn domain to this file yet then find one.
		if ( ! isset( $aFilePaths[$path] ) )
		{
			$aFilePaths[$path] = self::selectDomain( $aDomain, $path );
		}

		if ( $aFilePaths[$path] === false )
		{
			return $orig_path;
		}

		return $aFilePaths[$path];
	}

	/**
	 * Returns array of default static files to load from CDN
	 *
	 *
	 * @return array $aStaticFiles Array of file type extensions
	 */
	public static function getStaticFiles()
	{
		return array( 'css', 'js', 'jpe?g', 'gif', 'png', 'ico', 'bmp', 'pdf', 'webp', 'svg' );
	}

	/**
	 *
	 * @param   string  $domain
	 *
	 * @return string
	 */
	private static function prepareDomain( $domain )
	{

		return '//' . preg_replace( '#^(?:https?:)?//|/$#i', '', trim( $domain ) );
	}

	##<procode>##

	/**
	 *
	 * @staticvar int $iIndex
	 *
	 * @param   array   $aDomain
	 * @param   string  $sPath
	 *
	 * @return bool|string
	 */
	private static function selectDomain( &$aDomain, $sPath )
	{
		//If no domain is matched to a configured file type then we'll just return the file
		$sCdnUrl = false;

		for ( $i = 0; count( $aDomain ) > $i; $i ++ )
		{
			$sStaticFiles = current( $aDomain );
			$sDomain      = key( $aDomain );
			next( $aDomain );

			if ( current( $aDomain ) === false )
			{
				reset( $aDomain );
			}

			if ( preg_match( '#\.(?>' . $sStaticFiles . ')#i', $sPath ) )
			{
				//Prepend the cdn domain to the file path if a match is found.
				$sCdnUrl = $sDomain . $sPath;

				break;
			}
		}

		return $sCdnUrl;
	}

	/**
	 * @param   string  $url
	 * @param   string  $type
	 * @param   string  $ext
	 */
	private static function addToPreload( $url, $type, $ext = '' )
	{
		$RR_url  = html_entity_decode( $url );
		$preload = "<{$RR_url}>; rel=preload; as={$type}";

		if ( $type == 'font' )
		{
			$preload .= '; crossorigin';

			switch ( $ext )
			{
				case 'woff':
					$preload .= '; type="font/woff"';

					//If we already have the woff2 version of this file, abort
					if ( in_array( str_replace( 'woff', 'woff2', $preload ), self::$preloads ) )
					{
						return false;
					}

					break;
				case 'woff2':
					$preload .= '; type="font/woff2"';

					//If we already have the woff version of this file,
					// let's remove it and preload the woff2 version instead
					$woff = str_replace( 'woff2', 'woff', $preload );
					$key  = array_search( $woff, self::$preloads );
					unset( self::$preloads[$key] );

					break;
				default:
					break;
			}

		}


		if ( ! in_array( $preload, self::$preloads ) )
		{
			self::$preloads[] = $preload;
		}

		return true;
	}

	public static function updateNewSettings()
	{
		$params = Plugin::getPluginParams();

		//Some settings have changed
		//Update new settings from the old ones
		$aSettingsMap = array(
			'pro_replaceImports'             => 'replaceImports',
			'pro_phpAndExternal'             => 'phpAndExternal',
			'pro_inlineStyle'                => 'inlineStyle',
			'pro_inlineScripts'              => 'inlineScripts',
			'pro_bottom_js'                  => 'bottom_js',
			'pro_loadAsynchronous'           => 'loadAsynchronous',
			'pro_excludeStyles'              => 'excludeStyles',
			'pro_excludeScripts_peo'         => 'excludeScripts_peo',
			'pro_excludeAllStyles'           => 'excludeAllStyles',
			'pro_excludeAllScripts'          => 'excludeAllScripts',
			'pro_excludeScripts'             => 'excludeScripts',
			'pro_optimizeCssDelivery_enable' => 'optimizeCssDelivery_enable',
			'pro_optimizeCssDelivery'        => 'optimizeCssDelivery',
			'pro_lazyload'                   => array(
				'lazyload_enable',
				'pro_lazyload_iframe'
			),
			'pro_excludeLazyLoad'            => 'excludeLazyLoad',
			'pro_lazyload_autosize'          => 'lazyload_autosize',
			'pro_cookielessdomain_enable'    => 'cookielessdomain_enable',
			'pro_cdn_scheme'                 => 'cdn_scheme',
			'pro_cookielessdomain'           => 'cookielessdomain',
			'pro_staticfiles'                => 'staticfiles'
		);

		foreach ( $aSettingsMap as $old => $new )
		{
			if ( ! is_null( $params->get( $old ) ) )
			{
				if ( is_array( $new ) )
				{
					foreach ( $new as $value )
					{
						$params->set( $value, $params->get( $old ) );
					}
				}
				else
				{
					$params->set( $new, $params->get( $old ) );
				}

				$params->remove( $old );
			}
		}

		Plugin::saveSettings( $params );
	}
	##</procode>##		
}
