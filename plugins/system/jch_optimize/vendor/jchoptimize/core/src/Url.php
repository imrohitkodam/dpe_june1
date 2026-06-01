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

use JchOptimize\Platform\Uri;

class Url
{

	/**
	 * Determines if file is internal
	 *
	 * @param   string  $sUrl     Url of file
	 * @param   null    $oParams  If given then should be used to check against saved cdn domains
	 *
	 * @return boolean
	 */
	public static function isInternal( $sUrl, $oParams = null )
	{
		if ( self::isProtocolRelative( $sUrl ) )
		{
			$sUrl = self::toAbsolute( $sUrl );
		}

		$oUrl = clone Uri::getInstance( $sUrl );

		$sUrlBase = $oUrl->toString( array( 'scheme', 'user', 'pass', 'host', 'port', 'path' ) );
		$sUrlHost = $oUrl->toString( array( 'scheme', 'user', 'pass', 'host', 'port' ) );

		$aDomains = array( $sBase = Uri::base() );

		if ( ! is_null( $oParams ) )
		{
			$aDomains = array_merge( $aDomains, array_map( function ( $sCdnDomain ) {
				return self::toAbsolute( $sCdnDomain );
			}, array_keys( Helper::cookieLessDomain( $oParams, '', '', true ) ) ) );
		}

		foreach ( $aDomains as $sDomain )
		{
			if ( ! ( stripos( $sUrlBase, $sDomain ) !== 0 && ! empty( $sUrlHost ) ) )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 *
	 * @param   string  $sUrl
	 *
	 * @return boolean
	 */
	public static function isAbsolute( $sUrl )
	{
		return preg_match( '#^http#i', $sUrl );
	}

	/**
	 *
	 * @param   string  $sUrl
	 *
	 * @return boolean
	 */
	public static function isRootRelative( $sUrl )
	{
		return preg_match( '#^/[^/]#', $sUrl );
	}

	/**
	 *
	 * @param   string  $sUrl
	 *
	 * @return boolean
	 */
	public static function isProtocolRelative( $sUrl )
	{
		return preg_match( '#^//#', $sUrl );
	}

	/**
	 *
	 * @param   string  $sUrl
	 *
	 * @return bool
	 */
	public static function isPathRelative( $sUrl )
	{
		return self::isHttpScheme( $sUrl )
		       && ! self::isAbsolute( $sUrl )
		       && ! self::isProtocolRelative( $sUrl )
		       && ! self::isRootRelative( $sUrl );
	}

	/**
	 *
	 * @param   string  $sUrl
	 *
	 * @return bool
	 */
	public static function isSSL( $sUrl )
	{
		return preg_match( '#^https#i', $sUrl );
	}

	/**
	 *
	 * @param   string  $sUrl
	 *
	 * @return bool
	 */
	public static function isDataUri( $sUrl )
	{
		return preg_match( '#^data:#i', $sUrl );
	}

	/**
	 *
	 * @param   string  $sUrl
	 *
	 * @return bool
	 */
	public static function isInvalid( $sUrl )
	{
		return ( empty( $sUrl ) || trim( $sUrl ) == '/' || trim( $sUrl, ' /\\' ) == trim( Uri::base( true ), ' /\\' ) );
	}

	/**
	 *
	 * @param   string  $sUrl
	 *
	 * @return bool
	 */
	public static function isHttpScheme( $sUrl )
	{
		return ! preg_match( '#^(?!https?)[^:/]+:#i', $sUrl );
	}

	/**
	 *
	 * @param   string  $sUrl
	 *
	 * @return bool
	 */
	public static function AbsToProtocolRelative( $sUrl )
	{
		return preg_replace( '#https?:#i', '', $sUrl );
	}

	/**
	 *
	 * @param   string  $sUrl
	 * @param   string  $sCurFile
	 *
	 * @return string
	 */
	public static function toRootRelative( $sUrl, $sCurFile = '' )
	{
		if ( self::isPathRelative( $sUrl ) )
		{
			$sUrl = ( empty( $sCurFile ) ? '' : dirname( $sCurFile ) . '/' ) . $sUrl;
		}

		$sUrl = Uri::getInstance( $sUrl )->toString( array( 'path', 'query', 'fragment' ) );

		if ( self::isPathRelative( $sUrl ) )
		{
			$sUrl = rtrim( Uri::base( true ), '\\/' ) . '/' . $sUrl;
		}

		return $sUrl;
	}

	/**
	 * Returns the absolute url of a relative url in a file
	 *
	 * @param   string  $sUrl       Url to modify
	 * @param   string  $sCurFile   Current file that contains the url or use uri of server if url is in an inline declaration.
	 *
	 * @return string
	 */
	public static function toAbsolute( $sUrl, $sCurFile = 'SERVER' )
	{
		//If file path already absolute just return
		if ( self::isAbsolute( $sUrl ) )
		{
			return $sUrl;
		}

		//Get URI instance of current file or server
		$oCurUri = clone Uri::getInstance( $sCurFile );

		//If url is relative add to current uri path
		if ( self::isPathRelative( $sUrl ) )
		{
			$oCurUri->setPath( dirname( $oCurUri->getPath() ) . '/' . $sUrl );
		}

		//If root relative set url as path to current uri
		if ( self::isRootRelative( $sUrl ) )
		{
			$oCurUri->setPath( $sUrl );
		}

		if ( self::isProtocolRelative( $sUrl ) )
		{
			$scheme = $oCurUri->getScheme();

			//Just add scheme to url if found
			if ( ! empty( $scheme ) )
			{
				$sUrl = $scheme . ':' . $sUrl;
			}

			$oCurUri = Uri::getInstance( $sUrl );
		}

		$sAbsUrl = $oCurUri->toString();
		$host = $oCurUri->getHost();

		//If url still not absolute but contains a host then return a protocol relative url
		if ( ! self::isAbsolute( $sAbsUrl ) && ! empty( $host ) )
		{
			return '//' . $sAbsUrl;
		}

		return $sAbsUrl;
	}

	/**
	 *
	 * @param   string  $sUrl
	 *
	 * @return bool
	 */
	public static function requiresHttpProtocol( $sUrl )
	{
		return preg_match( '#\.php|^(?![^?\#]*\.(?:css|js|png|jpe?g|gif|bmp)(?:[?\#]|$)).++#i', $sUrl );
	}
}
