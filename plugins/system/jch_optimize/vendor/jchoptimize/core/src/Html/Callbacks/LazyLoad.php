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

namespace JchOptimize\Core\Html\Callbacks;

defined( '_JCH_EXEC' ) or die( 'Restricted access' );

use JchOptimize\Core\Helper;
use JchOptimize\Core\Html\ElementObject;
use JchOptimize\Core\Html\Parser;
use JchOptimize\Core\Html\Processor;

class LazyLoad extends CallbackBase
{
	protected $aExcludes;

	protected $aArgs;


	public function __construct( Processor $oProcessor, $aArgs )
	{
		parent::__construct( $oProcessor );

		$this->aArgs = $aArgs;

		$this->getLazyLoadExcludes();
	}

	function processMatches( $aMatches )
	{
		if ( empty( $aMatches[0] ) )
		{
			return $aMatches[0];
		}

		$sFullMatch         = @$aMatches[0] ?: false;
		$sElementName       = @$aMatches[1] ?: false;
		$sClassAttribute    = @$aMatches[2] ?: false;
		$sClassDelimiter    = @$aMatches[3] ?: false;
		$sClassValue        = @$aMatches[4] ?: false;
		$sSrcAttribute      = $sPosterAttribute = $sInnerContent = $sStyleAttribute = @$aMatches[5] ?: false;
		$sSrcDelimiter      = $sPosterDelimiter = $sStyleDelimiter = @$aMatches[6] ?: false;
		$sSrcValue          = $sPosterValue = $sBgDeclaration = @$aMatches[7] ?: false;
		$sSrcsetAttribute   = $sPreloadAttribute = $sCssUrl = @$aMatches[8] ?: false;
		$sSrcsetDelimiter   = $sPreloadDelimiter = $sCssUrlValue = @$aMatches[9] ?: false;
		$sSrcsetValue       = $sPreloadValue = @$aMatches[10] ?: false;
		$sAutoLoadAttribute = @$aMatches[11] ?: false;

		//Return match if it isn't an HTML element
		if ( $sElementName === false )
		{
			return $sFullMatch;
		}

		switch ( $sElementName )
		{
			case 'img':
			case 'input':
			case 'picture':
			case 'iframe':
			case 'source':

				$sImgType = 'embed';
				break;
			case 'video':
			case 'audio':

				$sImgType = 'audiovideo';
				break;
			default:
				$sImgType = 'background';
				break;
		}

		if ( $this->aArgs['lazyload'] )
		{
			##<procode>##
			if ( $sElementName == 'img' || $sElementName == 'input' )
			{
				Helper::addHttp2Push( $sSrcValue, 'image', true );
			}
			##</procode>##

			//Start modifying the element to return
			$sReturn = $sFullMatch;

			if ( $sElementName != 'picture' )
			{
				//If a src attribute is found
				if ( $sSrcAttribute !== false )
				{
					$sImgName = $sImgType == 'embed' ? $sSrcValue : $sCssUrlValue;
					//Abort if this file is excluded
					if ( Helper::findExcludes( $this->aExcludes['url'], $sImgName )
					     || ( $sElementName && Helper::findExcludes( $this->aExcludes['class'], $sClassValue ) ) )
					{
						return $sFullMatch;
					}

					//If no srcset attribute was found, modify the src attribute and add a data-src attribute
					if ( $sSrcsetAttribute === false && $sImgType == 'embed' )
					{
						$sNewSrcValue     = $sElementName == 'iframe' ? 'about:blank' : 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
						$sNewSrcAttribute = 'src=' . $sSrcDelimiter . $sNewSrcValue . $sSrcDelimiter . ' data-' . $sSrcAttribute;

						$sReturn = str_replace( $sSrcAttribute, $sNewSrcAttribute, $sReturn );
					}

					##<procode>##
					if ( $sImgType == 'audiovideo' )
					{
						$sReturn = str_replace( $sPosterAttribute, 'data-' . $sPosterAttribute, $sReturn );
					}
					##</procode>##
				}

				//If class attribute not on the appropriate element add it
				if ( $sElementName != 'source' && $sClassAttribute === false )
				{
					$sReturn = str_replace( '<' . $sElementName, '<' . $sElementName . ' class="jch-lazyload"', $sReturn );
				}

				//If class already on element add the lazy-load class
				if ( $sElementName != 'source' && $sClassAttribute !== false )
				{
					$sNewClassAttribute = 'class=' . $sClassDelimiter . $sClassValue . ' jch-lazyload' . $sClassDelimiter;
					$sReturn            = str_replace( $sClassAttribute, $sNewClassAttribute, $sReturn );
				}

				//If the srcset attribute was found add placeholder srcset attribute to pass W3C Markup validation
				//We also need to specify the width of the image (1w) in this case
				//Modern browsers will lazy-load without loading the src attribute
				if ( $sSrcsetAttribute !== false && $sImgType == 'embed' )
				{
					$sNewSrcsetAttribute = 'srcset=' . $sSrcsetDelimiter . 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7 1w' . $sSrcsetDelimiter . ' data-' . $sSrcsetAttribute;

					$sReturn = str_replace( $sSrcsetAttribute, $sNewSrcsetAttribute, $sReturn );
				}

				##<procode>##
				if ( $sImgType == 'audiovideo' )
				{
					if ( $sPreloadAttribute !== false )
					{
						$sNewPreloadAttribute = 'preload=' . $sPreloadDelimiter . 'none' . $sPreloadDelimiter;
						$sReturn              = str_replace( $sPreloadAttribute, $sNewPreloadAttribute, $sReturn );
					}
					else
					{
						$sReturn = str_replace( '<' . $sElementName, '<' . $sElementName . ' preload="none"', $sReturn );
					}

					if ( $sAutoLoadAttribute !== false )
					{
						$sReturn = str_replace( $sAutoLoadAttribute, '', $sReturn );
					}
				}
				##</procode>##
			}
			//Process and add content of element if not self closing
			if ( $sElementName == 'picture' && $sInnerContent !== false )
			{
				return str_replace( $sInnerContent, $this->lazyLoadInnerContent( $sInnerContent ), $sFullMatch );
			}

			if ( $this->aArgs['parent'] != 'picture' )
			{
				//Wrap and add img elements in noscript
				if ( $sElementName == 'img' || $sElementName == 'iframe' )
				{
					$sReturn .= '<noscript>' . $sFullMatch . '</noscript>';
				}
			}

			##<procode>##
			if ( $sImgType == 'background' )
			{
				$sNewStyleAttribute = str_replace( $sCssUrl, '', $sStyleAttribute );

				if ( strpos( $sBgDeclaration, 'background-image' ) !== false )
				{
					$sNewStyleAttribute = str_replace( $sBgDeclaration, 'background', $sNewStyleAttribute );
				}

				$sNewStyleAttribute = 'data-bg=' . $sStyleDelimiter . $sCssUrlValue . $sStyleDelimiter . ' ' . $sNewStyleAttribute;
				$sReturn            = str_replace( $sStyleAttribute, $sNewStyleAttribute, $sReturn );
			}

			##</procode>##

			return $sReturn;

		}
		else
		{
			##<procode>##
			if ( $sSrcAttribute !== false && ( $sElementName == 'img' || $sElementName == 'input' ) )
			{
				Helper::addHttp2Push( $sSrcValue, 'image', $this->aArgs['deferred'] );
			}

			if ( $sImgType = 'background' && $sStyleAttribute !== false )
			{
				Helper::addHttp2Push( $sCssUrlValue, 'image', $this->aArgs['deferred'] );
			}

			##</procode>##

			return $sFullMatch;
		}
	}

	protected function lazyLoadInnerContent( $sInnerContent )
	{
		$oParser = new Parser();

		$oImgElement               = new ElementObject();
		$oImgElement->bSelfClosing = true;
		$oImgElement->setNamesArray( array( 'img', 'source' ) );
		//language=RegExp
		$oImgElement->addNegAttrCriteriaRegex( '(?:data-(?:src|original))' );
		$oImgElement->setCaptureAttributesArray( array( 'class', 'src', 'srcset' ) );
		$oParser->addElementObject( $oImgElement );

		$aArgs = array(
			'lazyload' => true,
			'deferred' => true,
			'parent'   => 'picture'
		);

		$oLazyLoadCallback = new LazyLoad( $this->oProcessor, $aArgs );

		return $oParser->processMatchesWithCallback( $sInnerContent, $oLazyLoadCallback );
	}

	protected function getLazyLoadExcludes()
	{
		$aExcludesFiles   = Helper::getArray( $this->oParams->get( 'excludeLazyLoad', array() ) );
		$aExcludesFolders = Helper::getArray( $this->oParams->get( 'pro_excludeLazyLoadFolders', array() ) );
		$aExcludesUrl     = array_merge( array( 'data:image' ), $aExcludesFiles, $aExcludesFolders );

		$aExcludeClass = Helper::getArray( $this->oParams->get( 'pro_excludeLazyLoadClass', array() ) );

		$this->aExcludes = array( 'url' => $aExcludesUrl, 'class' => $aExcludeClass );
	}
}
