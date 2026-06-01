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

use CodeAlfa\Minify\Html;
use JchOptimize\Core\Browser;
use JchOptimize\Core\Helper;
use JchOptimize\Core\Html\Processor;
use JchOptimize\Core\Url;
use JchOptimize\Platform\Cache;
use JchOptimize\Platform\Profiler;
use JchOptimize\Platform\Settings;
use JchOptimize\Platform\Excludes;
use JchOptimize\Platform\Uri;

class CombineJsCss extends CallbackBase
{
	/** @var array          Array of excludes parameters */
	protected $aExcludes;

	/** @var array          Array of files containing Google Font files */
	protected $aContainsGF = array();

	/** @var array  Container for files to check for duplicates */
	protected $aUrls = array();

	protected $sSection = 'head';

	/**
	 * CombineJsCss constructor.
	 *
	 * @param   Processor  $oProcessor
	 */
	public function __construct( Processor $oProcessor )
	{
		parent::__construct( $oProcessor );

		$this->setupExcludes();

		//Get array of filenames from cache that imports Google font files
		$aContainsGF = Cache::getCache( 'jch_hidden_containsgf' );
		//If cache is not empty save to class property
		if ( $aContainsGF !== false )
		{
			$this->aContainsGF = $aContainsGF;
		}
	}

	/**
	 * Callback function used to remove urls of css and js files in head tags
	 *
	 * @param   array  $aMatches  Array of all matches
	 * @param          $this      ->aExcludes
	 * @param          $sSection
	 *
	 * @return string               Returns the url if excluded, empty string otherwise
	 */
	public function processMatches( $aMatches )
	{
		if ( empty( $aMatches[0] ) )
		{
			return $aMatches[0];
		}

		$sUrl         = $aMatches['url'] = trim( isset( $aMatches[4] ) ? $aMatches[4] : '' );
		$sDeclaration = $aMatches['content'] = ! isset( $aMatches[4] ) ? $aMatches[2] : '';

		if ( preg_match( '#^<!--#', $aMatches[0] )
		     || ( Url::isInvalid( $sUrl ) && trim( $sDeclaration ) == '' ) )
		{
			return $aMatches[0];
		}

		$sType = strcasecmp( $aMatches[1], 'script' ) == 0 ? 'js' : 'css';

		if ( $sType == 'js' && ( ! $this->oParams->get( 'javascript', '1' ) || ! $this->oProcessor->isCombineFilesSet() ) )
		{
			$deferred = $this->oProcessor->isFileDeferred( $aMatches[0] );

			Helper::addHttp2Push( $sUrl, 'script', $deferred );

			return $aMatches[0];
		}

		if ( $sType == 'css' && ( ! $this->oParams->get( 'css', '1' ) || ! $this->oProcessor->isCombineFilesSet() ) )
		{
			Helper::addHttp2Push( $sUrl, 'style' );

			return $aMatches[0];
		}

		$aExcludes     = $this->aExcludes[$this->sSection]['excludes'];
		$aExcludes_ieo = $this->aExcludes[$this->sSection]['excludes_ieo'];
		$aDontMoves    = $this->aExcludes[$this->sSection]['dontmove'];
		$aRemoves      = $this->aExcludes[$this->sSection]['remove'];

		$sMedia = '';

		if ( ( $sType == 'css' ) && ( preg_match( '#media=(?(?=["\'])(?:["\']([^"\']+))|(\w+))#i', $aMatches[0], $aMediaTypes ) > 0 ) )
		{
			$sMedia .= $aMediaTypes[1] ? $aMediaTypes[1] : $aMediaTypes[2];
		}


		switch ( true )
		{
			case ( $sUrl != '' && ! empty( $aRemoves['js'] ) && Helper::findExcludes( $aRemoves['js'], $sUrl ) ):

				return '';

			//These cases are being excluded without preserving execution order
			case ( $sUrl != '' && ! Url::isHttpScheme( $sUrl ) && ! Url::isDataUri( $sUrl ) ):
			case ( ! empty( $sUrl ) && $sType == 'js' && ! empty( $aExcludes_ieo['js'] ) && Helper::findExcludes( $aExcludes_ieo['js'], $sUrl ) ):
			case ( $sDeclaration != '' && $sType == 'js' && Helper::findExcludes( $aExcludes_ieo['js_script'], $sDeclaration, 'js' ) ):
				//Exclude javascript files with async attributes


				if ( $sUrl != '' )
				{
					$deferred = $this->oProcessor->isFileDeferred( $aMatches[0] );
					Helper::addHttp2Push( $sUrl, $sType, $deferred );
				}

				//If file or declaration was not selected in 'DontMove' then add to array of excluded files
				//to be moved to the bottom of section
				if ( $sType == 'js' && ( ! ( ! empty( $sUrl ) && ! empty( $aDontMoves['js'] ) && Helper::findExcludes( $aDontMoves['js'], $sUrl ) ) && ! ( $sDeclaration != '' && Helper::findExcludes( $aDontMoves['scripts'], $sDeclaration, 'js' ) ) ) )
				{
					//All these files were excluded while ignoring execution order
					$this->oProcessor->aExcludedJs['ieo'][] = $aMatches[0];

					return '';
				}

				//This file was selected as 'DontMove'
				return $aMatches[0];

			//Remove deferred javascript files (without async attributes) and add them to the $aDefers array
			case ( $sUrl != '' && $sType == 'js' && $this->oProcessor->isFileDeferred( $aMatches[0], true ) ):

				Helper::addHttp2Push( $sUrl, $sType, true );

				$this->oProcessor->aDefers[] = $aMatches[0];
				//We now have to defer the last js file
				$this->oProcessor->bLoadAsync = false;

				return '';

			//These cases are being excluded while preserving execution order
			case ( ( $sUrl != '' ) && Url::isDataUri( $sUrl ) ):
			case ( ( $sUrl != '' ) && ! $this->oProcessor->isHttpAdapterAvailable( $sUrl ) ):
			case ( $sUrl != '' && Url::isSSL( $sUrl ) && ! extension_loaded( 'openssl' ) ):
			case ( ( $sUrl != '' ) && ! empty( $aExcludes[$sType] ) && Helper::findExcludes( $aExcludes[$sType], $sUrl ) ):
			case ( $sDeclaration != '' && $this->excludeDeclaration( $sType ) ):
			case ( $sDeclaration != '' && Helper::findExcludes( $aExcludes[$sType . '_script'], $sDeclaration, $sType ) ):
			case ( ( $sUrl != '' ) && $this->excludeExternalExtensions( $sUrl ) ):

				//We want to put the combined js files as low as possible, if files were removed before,
				//we place them just above the excluded files
				if ( $sType == 'js' && ! $this->oProcessor->bExclude_js && ! empty( $this->oProcessor->aLinks['js'] ) )
				{
					$jsReturn = '';

					for ( $i = $this->oProcessor->jsExcludeIndex; $i <= $this->oProcessor->iIndex_js; $i ++ )
					{
						$jsReturn .= '<JCH_JS' . $i . '>' . $this->oProcessor->sLnEnd . $this->oProcessor->sTab;
					}

					$aMatches[0] = $jsReturn . $aMatches[0];
				}

				//Last js file should be deferred
				if ( $sType == 'js' )
				{
					$this->oProcessor->bLoadAsync = false;
					//record excluded index
					if ( ! empty( $this->oProcessor->aLinks['js'] ) )
					{
						$this->oProcessor->jsExcludeIndex = $this->oProcessor->iIndex_js + 1;
					}
				}

				//Set the exclude flag so hereafter we know the last file was excluded while preserving
				//the execution order
				$this->oProcessor->{'bExclude_' . $sType} = true;

				if ( $sUrl != '' )
				{
					Helper::addHttp2Push( $sUrl, $sType );
				}

				if ( $sType == 'js' && ( ! ( ! empty( $sUrl ) && ! empty( $aDontMoves['js'] ) && Helper::findExcludes( $aDontMoves['js'], $sUrl ) ) && ! ( $sDeclaration != '' && Helper::findExcludes( $aDontMoves['scripts'], $sDeclaration, 'js' ) ) ) )
				{
					//These files were excluded while preserving execution order
					$this->oProcessor->aExcludedJs['peo'][] = $aMatches[0];

					return '';
				}

				//If we get to this point then this file/script was marked as 'DontMove'
				// so now we have to put all the excluded files while peo above it
				$aMatches[0] = implode( $this->oProcessor->sLnEnd, $this->oProcessor->aExcludedJs['peo'] ) . $this->oProcessor->sLnEnd . $aMatches[0];
				//reinitialize array of excludes (peo)
				$this->oProcessor->aExcludedJs['peo'] = array();

				return $aMatches[0];

			//Remove duplicated files from the HTML. We don't need duplicates in the combined files
			//Placed below the exclusions so it's possible to exclude them
			case ( ( $sUrl != '' ) && $this->oProcessor->isDuplicated( $sUrl ) ):

				return '';

			//These files will be combined
			default:
				$return = '';

				//mark location of first css file
				if ( $sType == 'css' && empty( $this->oProcessor->aLinks['css'] )
				     && ! $this->oParams->get( 'optimizeCssDelivery_enable', '0' ) )
				{
					$return = '<JCH_CSS' . $this->oProcessor->iIndex_css . '>';
				}

				//The last file was excluded while preserving execution order
				if ( $this->oProcessor->{'bExclude_' . $sType} )
				{
					//mark location of next removed css file
					if ( $sType == 'css' && ! empty( $this->oProcessor->aLinks['css'] )
					     && ! $this->oParams->get( 'optimizeCssDelivery_enable', '0' ) )
					{
						$return = '<JCH_CSS' . ++ $this->oProcessor->iIndex_css . '>';
					}

					if ( $sType == 'js' && ! empty( $this->oProcessor->aLinks['js'] ) )
					{
						$this->oProcessor->iIndex_js ++;
					}
				}

				##<procode>##
				if ( $this->oParams->get( 'pro_smart_combine', '0' ) )
				{
					//Index of files currently being smart combined
					static $iSmartCombineIndex_js = false;
					static $iSmartCombineIndex_css = false;

					foreach ( Excludes::smartCombine() as $iIndex => $sRegex )
					{
						if ( $sUrl != '' && preg_match( '#' . $sRegex . '#', $sUrl ) && in_array( preg_replace( '#[?\#].*+#i', '', $sUrl ), $this->oParams->get( 'pro_smart_combine_values', array() ) ) )
						{
							//Is this the first file in this batch?
							if ( ! empty( $this->oProcessor->aLinks[$sType] ) && ${'iSmartCombineIndex_' . $sType} !== $iIndex && ! empty( $this->oProcessor->aLinks[$sType][$this->oProcessor->{'iIndex_' . $sType}] ) )
							{
								//Increment index
								if ( ! $this->oProcessor->{'bExclude_' . $sType} )
								{
									$this->oProcessor->{'iIndex_' . $sType} ++;
								}

								if ( $sType == 'css' && $return == '' && ! $this->oParams->get( 'optimizeCssDelivery_enable', '0' ) )
								{
									$return = '<JCH_CSS' . $this->oProcessor->iIndex_css . '>';
								}

								if ( $sType == 'js' )
								{
									$this->oProcessor->bLoadAsync = false;
								}
							}

							//Save index
							${'iSmartCombineIndex_' . $sType} = $iIndex;

							if ( $sType == 'js' && is_null( $this->oProcessor->jsSystemFileIndex ) && $iIndex == '0' )
							{
								$this->oProcessor->jsSystemFileIndex = $this->oProcessor->iIndex_js;
							}

							break;
						}
						else
						{
							//Have we just finished a batch?
							if ( ${'iSmartCombineIndex_' . $sType} === $iIndex )
							{
								${'iSmartCombineIndex_' . $sType} = false;
//Increment index
								if ( ! $this->oProcessor->{'bExclude_' . $sType} )
								{
									$this->oProcessor->{'iIndex_' . $sType} ++;
								}
								
								if ( $sType == 'css' && $return == '' && ! $this->oParams->get( 'optimizeCssDelivery_enable', '0' ) )
								{
									$return = '<JCH_CSS' . $this->oProcessor->iIndex_css . '>';
								}

								if ( $sType == 'js' )
								{
									$this->oProcessor->bLoadAsync = false;
								}
							}
						}
					}
				}
				##</procode>##

				//reset Exclude flag
				$this->oProcessor->{'bExclude_' . $sType} = false;

				$array = array(
					'match' => $aMatches[0]
				);

				if ( $sUrl == '' && trim( $sDeclaration ) != '' )
				{
					$content = Html::cleanScript( $sDeclaration, $sType );

					$array['content'] = $content;
				}
				else
				{
					$array['url'] = $sUrl;
				}

				if ( $this->oProcessor->sFileHash != '' )
				{
					$array['id'] = $this->getFileID( $aMatches );
				}

				if ( $sType == 'css' )
				{
					$array['media'] = $sMedia;
				}

				$this->oProcessor->aLinks[$sType][$this->oProcessor->{'iIndex_' . $sType}][] = $array;

				return $return;
		}
	}

	public function setSection( $sSection )
	{
		$this->sSection = $sSection;
	}

	/**
	 * Retrieves all exclusion parameters for the Combine Files feature
	 *
	 * @return array
	 */
	protected function setupExcludes()
	{
		JCH_DEBUG ? Profiler::start( 'SetUpExcludes' ) : null;

		$this->aExcludes = array();
		$aExcludes       = array();
		$oParams         = $this->oParams;

		//These parameters will be excluded while preserving execution order
		$aExJsComp  = $this->getExComp( $oParams->get( 'excludeJsComponents_peo', '' ) );
		$aExCssComp = $this->getExComp( $oParams->get( 'excludeCssComponents', '' ) );

		$aExcludeJs     = Helper::getArray( $oParams->get( 'excludeJs_peo', '' ) );
		$aExcludeCss    = Helper::getArray( $oParams->get( 'excludeCss', '' ) );
		$aExcludeScript = Helper::getArray( $oParams->get( 'excludeScripts_peo' ) );
		$aExcludeStyle  = Helper::getArray( $oParams->get( 'excludeStyles' ) );

		$aExcludeScript = array_map( function ( $sScript ) {
			return stripslashes( $sScript );
		}, $aExcludeScript );

		$this->aExcludes['excludes']['js']         = array_merge( $aExcludeJs, $aExJsComp, array(
			'.com/maps/api/js',
			'.com/jsapi',
			'.com/uds',
			'typekit.net',
			'cdn.ampproject.org',
			'googleadservices.com/pagead/conversion'
		), Excludes::head( 'js' ) );
		$this->aExcludes['excludes']['css']        = array_merge( $aExcludeCss, $aExCssComp, Excludes::head( 'css' ) );
		$this->aExcludes['excludes']['js_script']  = $aExcludeScript;
		$this->aExcludes['excludes']['css_script'] = $aExcludeStyle;

		$this->aExcludes['remove']['js'] = Helper::getArray( $oParams->get( 'remove_js', '' ) );

		//These parameters will be excluded without preserving execution order
		$aExJsComp_ieo      = $this->getExComp( $oParams->get( 'excludeJsComponents', '' ) );
		$aExcludeJs_ieo     = Helper::getArray( $oParams->get( 'excludeJs', '' ) );
		$aExcludeScript_ieo = Helper::getArray( $oParams->get( 'excludeScripts' ) );

		$this->aExcludes['excludes_ieo']['js']        = array_merge( $aExcludeJs_ieo, $aExJsComp_ieo );
		$this->aExcludes['excludes_ieo']['js_script'] = $aExcludeScript_ieo;

		$this->aExcludes['dontmove']['js']      = Helper::getArray( $oParams->get( 'dontmoveJs', '' ) );
		$this->aExcludes['dontmove']['scripts'] = Helper::getArray( $oParams->get( 'dontmoveScripts', '' ) );

		$aExcludes['head'] = $this->aExcludes;

		if ( $this->oParams->get( 'bottom_js', '0' ) == 1 )
		{
			$this->aExcludes['excludes']['js_script'] = array_merge(
				$this->aExcludes['excludes']['js_script'],
				array( '.write(', 'var google_conversion' ),
				Excludes::body( 'js', 'script' )
			);
			$this->aExcludes['excludes']['js']        = array_merge(
				$this->aExcludes['excludes']['js'],
				array( '.com/recaptcha/api' ),
				Excludes::body( 'js' )
			);
			$this->aExcludes['dontmove']['scripts']   = array_merge(
				$this->aExcludes['dontmove']['scripts'],
				array( '.write(' )
			);

			$aExcludes['body'] = $this->aExcludes;

		}

		JCH_DEBUG ? Profiler::stop( 'SetUpExcludes', true ) : null;


		$this->aExcludes = $aExcludes;
	}

	/**
	 * Generates regex for excluding components set in plugin params
	 *
	 * @param $sExComParam
	 *
	 * @return array
	 */
	protected function getExComp( $sExComParam )
	{
		$aComponents = Helper::getArray( $sExComParam );
		$aExComp     = array();

		if ( ! empty( $aComponents ) )
		{
			$aExComp = array_map( function ( $sValue ) {
				return $sValue . '/';
			}, $aComponents );
		}

		return $aExComp;
	}

	protected function excludeDeclaration( $sType )
	{
		return ( $sType == 'css' && ( ! $this->oParams->get( 'inlineStyle', '0' ) || $this->oParams->get( 'excludeAllStyles', '0' ) ) )
		       || ( $sType == 'js' && ( ! $this->oParams->get( 'inlineScripts', '0' ) || $this->oParams->get( 'excludeAllScripts', '0' ) ) );
	}

	protected function excludeExternalExtensions( $sPath )
	{
		if ( ! $this->oParams->get( 'includeAllExtensions', '0' ) )
		{
			return ! Url::isInternal( $sPath ) || preg_match( '#' . Excludes::extensions() . '#i', $sPath );
		}

		return false;
	}

	/**
	 * Generates a cache id for each matched file/script. If the files is associated with Google fonts,
	 * a browser hash is also computed.
	 *
	 *
	 * @param   array  $aMatches  Array of files/scripts matched to be optimized and combined
	 *
	 * @return string                md5 hash for the cache id
	 */
	protected function getFileID( $aMatches )
	{
		$id = '';

		//If name of file present in match set id to filename
		if ( ! empty( $aMatches['url'] ) )
		{
			$id .= $aMatches['url'];

			//If file is a, or imports Google fonts, add browser hash to id
			if ( strpos( $aMatches['url'], 'fonts.googleapis.com' ) !== false
			     || in_array( $aMatches['url'], $this->aContainsGF ) )
			{
				$browser = Browser::getInstance();
				$id      .= $browser->getFontHash();
			}
		}
		else
		{
			//No file name present so just use contents of declaration as id
			$id .= $aMatches['content'];
		}

		return md5( $this->oProcessor->sFileHash . $id );
	}

}
