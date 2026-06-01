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

namespace JchOptimize\Core\Html;

defined( '_JCH_EXEC' ) or die( 'Restricted access' );

use JchOptimize\Core\Exception;
use JchOptimize\Core\Helper;
use JchOptimize\Core\Output;
use JchOptimize\Core\Url;
use JchOptimize\Platform\Uri;
use JchOptimize\Platform\Cache;
use JchOptimize\Platform\Profiler;
use JchOptimize\Platform\Utility;
use JchOptimize\Platform\Paths;

/**
 *
 *
 */
class LinkBuilder
{

	/** @var Parser Object       Parser object */
	public $oProcessor;

	/** @var string         Document line end */
	protected $sLnEnd;

	/** @var string         Document tab */
	protected $sTab;

	/** @var string cache id * */
	public $oParams;

	/**
	 * Constructor
	 *
	 * @param   Parser  $oProcessor
	 */
	public function __construct( Processor $oProcessor = null )
	{
		$this->oProcessor = $oProcessor;
		$this->oParams    = $this->oProcessor->oParams;
		$this->sLnEnd     = $this->oProcessor->sLnEnd;
		$this->sTab       = $this->oProcessor->sTab;
	}


	public function addCriticalCssToHead( $sCriticalCss )
	{
		$sCriticalStyle = '<style type="text/css">' . $this->sLnEnd .
		                  $sCriticalCss . $this->sLnEnd .
		                  '</style>' . $this->sLnEnd .
		                  '</head>';

		$sHeadHtml = preg_replace( '#' . Parser::HTML_END_HEAD_TAG() . '#i',
			Helper::cleanReplacement( $sCriticalStyle ),
			$this->oProcessor->getHeadHtml(), 1 );
		$this->oProcessor->setHeadHtml( $sHeadHtml );

	}

	/**
	 *
	 * @param   string  $sUrl  Url of file
	 *
	 * @return string
	 */
	protected function getNewJsLink( $sUrl )
	{
		return '<script type="application/javascript" src="' . $sUrl . '"></script>';
	}

	/**
	 *
	 * @param   string  $sUrl  Url of file
	 *
	 * @return string
	 */
	protected function getNewCssLink( $sUrl )
	{
		return '<link rel="stylesheet" type="text/css" href="' . $sUrl . '" />';
	}

	public function addExcludedJsToSection( $sSection )
	{
		$aExcludedJs = $this->oProcessor->aExcludedJs;

		//Add excluded javascript files to the bottom of the HTML section
		$sExcludedJs  = implode( $this->sLnEnd, $aExcludedJs['ieo'] ) . implode( $this->sLnEnd, $aExcludedJs['peo'] );
		$sExcludedJs  = Helper::cleanReplacement( $sExcludedJs );
		$sSearchArea1 = preg_replace( '#' . Parser::{'HTML_END_' . strtoupper( $sSection ) . '_Tag'}() . '#i', $this->sTab . $sExcludedJs . $this->sLnEnd . '</' . $sSection . '>', $this->oProcessor->getFullHtml(), 1 );
		$this->oProcessor->setFullHtml( $sSearchArea1 );
	}

	public function addDeferredJs( $aDefers, $sSection )
	{
		$sDefers     = implode( $this->sLnEnd, $aDefers );
		$sSearchArea = preg_replace( '#' . Parser::{'HTML_END_' . strtoupper( $sSection ) . '_Tag'}() . '#i', $this->sTab . $sDefers . $this->sLnEnd . '</' . $sSection . '>', $this->oProcessor->getFullHtml(), 1 );
		$this->oProcessor->setFullHtml( $sSearchArea );
	}

	public function setImgAttributes( $aCachedImgAttributes )
	{
		$sHtml = $this->oProcessor->getBodyHtml();
		$this->oProcessor->setBodyHtml( str_replace( $this->oProcessor->aLinks['img'][0], $aCachedImgAttributes, $sHtml ) );
	}

	/**
	 * Calculates the id of combined files from array of urls
	 *
	 * @param   array   $aUrlArrays
	 * @param   string  $sType
	 *
	 * @return   string   ID of combined file
	 */
	private function getCacheId( $aUrlArrays, $sType )
	{
		return md5( serialize( $aUrlArrays ) );
	}

	/**
	 * Returns url of aggregated file
	 *
	 * @param   string  $sId
	 * @param   string  $sType  css or js
	 *
	 * @return string  Url of aggregated file
	 * @throws Exception
	 */
	public function buildUrl( $sId, $sType )
	{
		$bGz = $this->isGZ();

		$htaccess = $this->oParams->get( 'htaccess', 2 );
		switch ( $htaccess )
		{
			case '1':
			case '3':

				$sPath = Paths::relAssetPath();
				$sPath = $htaccess == 3 ? $sPath . '3' : $sPath;
				$sUrl  = $sPath . Paths::rewriteBaseFolder()
				         . ( $bGz ? 'gz' : 'nz' ) . '/' . $sId . '.' . $sType;

				break;

			case '0':

				$oUri = clone Uri::getInstance( Paths::relAssetPath() );

				$oUri->setPath( $oUri->getPath() . '2/jscss.php' );

				$aVar         = array();
				$aVar['f']    = $sId;
				$aVar['type'] = $sType;
				$aVar['gz']   = $bGz ? 'gz' : 'nz';

				$oUri->setQuery( $aVar );

				$sUrl = htmlentities( $oUri->toString() );

				break;

			case '2':
			default:

				$sPath = Paths::cachePath();
				$sUrl  = $sPath . '/' . $sType . '/' . $sId . '.' . $sType;// . ($bGz ? '.gz' : '');

				$this->createStaticFiles( $sId, $sType, $sUrl );

				break;
		}

		if ( $this->oParams->get( 'cookielessdomain_enable', '0' ) && ! Url::isRootRelative( $sUrl ) )
		{
			$sUrl = Url::toRootRelative( $sUrl );
		}

		return Helper::cookieLessDomain( $this->oParams, $sUrl, $sUrl );
	}

	/**
	 * Create static combined file if not yet exists
	 *
	 *
	 * @param   string  $sId    Cache id of file
	 * @param   string  $sType  Type of file css|js
	 * @param   string  $sUrl   Url of combine file
	 *
	 * @return null
	 * @throws Exception
	 * @throws \Exception
	 */
	protected function createStaticFiles( $sId, $sType, $sUrl )
	{
		JCH_DEBUG ? Profiler::start( 'CreateStaticFiles - ' . $sType ) : null;

		//File path of combined file
		$sCombinedFile = Helper::getFilePath( $sUrl );

		if ( ! file_exists( $sCombinedFile ) )
		{
			$aGet = array(
				'f'    => $sId,
				'type' => $sType
			);

			$sContent = Output::getCombinedFile( $aGet, false );

			if ( $sContent === false )
			{
				throw new Exception( 'Error retrieving combined contents' );
			}

			//Create file and any directory
			if ( ! Utility::write( $sCombinedFile, $sContent ) )
			{
				Cache::deleteCache();

				throw new Exception( 'Error creating static file' );
			}
		}

		JCH_DEBUG ? Profiler::stop( 'CreateStaticFiles - ' . $sType, true ) : null;
	}


	/**
	 * Insert url of aggregated file in html
	 *
	 * @param   string  $sId
	 * @param   string  $sType
	 * @param   string  $sSection           Whether section being processed is head|body
	 * @param   int     $iJsLinksKey        Index key of javascript combined file
	 *
	 * @throws Exception
	 */
	public function replaceLinks( $sId, $sType, $sSection = 'head', $iJsLinksKey = 0 )
	{
		JCH_DEBUG ? Profiler::start( 'ReplaceLinks - ' . $sType ) : null;

		$sSearchArea = $this->oProcessor->getFullHtml();

		$sUrl     = $this->buildUrl( $sId, $sType );
		$sNewLink = $this->{'getNew' . ucfirst( $sType ) . 'Link'}( $sUrl );

		//If the last javascript file on the HTML page was not excluded while preserving
		//execution order, we may need to place it at the bottom and add the async
		//or defer attribute
		if ( $sType == 'js' && $iJsLinksKey >= $this->oProcessor->jsExcludeIndex && ! $this->oProcessor->bExclude_js )
		{
			//If last combined file is being inserted at the bottom of the page then
			//add the async or defer attribute
			if ( $sSection == 'body' )
			{
				//Add async attribute to last combined js file if option is set
				$sNewLink = str_replace( '></script>', $this->getAsyncAttribute() . '></script>', $sNewLink );
			}

			//Insert script tag at the appropriate section in the HTML
			$sSearchArea = preg_replace( '#' . Parser::{'HTML_END_' . ucfirst( $sSection ) . '_TAG'}() . '#i', $this->sTab . $sNewLink . $this->sLnEnd . '</' . $sSection . '>', $sSearchArea, 1 );

			##<procode>##
			if ( !is_null($this->oProcessor->jsSystemFileIndex) && $iJsLinksKey <= $this->oProcessor->jsSystemFileIndex)
			{
				Helper::addHttp2Push( $sUrl , 'js', false);
			}
			else
			{
				$deferred = $this->oProcessor->isFileDeferred( $sNewLink );
				Helper::addHttp2Push( $sUrl, $sType, $deferred );
			}
			##</procode>##
		}

		##<procode>##
		else
		{
			Helper::addHttp2Push( $sUrl, $sType );
		}
		##</procode>##
		//Replace placeholders in HTML with combined files
		$sSearchArea = preg_replace( '#<JCH_' . strtoupper( $sType ) . '([^>]++)>#', $sNewLink, $sSearchArea, 1 );
		$this->oProcessor->setFullHtml( $sSearchArea );

		JCH_DEBUG ? Profiler::stop( 'ReplaceLinks - ' . $sType, true ) : null;
	}

	/**
	 * Check if gzip is set or enabled
	 *
	 * @return boolean   True if gzip parameter set and server is enabled
	 */
	public function isGZ()
	{
		return ( $this->oParams->get( 'gzip', 0 ) && extension_loaded( 'zlib' ) && ! ini_get( 'zlib.output_compression' )
		         && ( ini_get( 'output_handler' ) != 'ob_gzhandler' ) );
	}

	/**
	 * Determine if document is of XHTML doctype
	 *
	 * @return boolean
	 */
	public function isXhtml()
	{
		return (bool) preg_match( '#^\s*+(?:<!DOCTYPE(?=[^>]+XHTML)|<\?xml.*?\?>)#i', trim( $this->oProcessor->getHtml() ) );
	}

	/**
	 *
	 * @param   string  $sScript
	 *
	 * @return string|string[]
	 */
	protected function cleanScript( $sScript )
	{
		if ( ! Helper::isXhtml( $this->oProcessor->getHtml() ) )
		{
			$sScript = str_replace( array(
				'<script type="text/javascript"><![CDATA[',
				'<script><![CDATA[',
				']]></script>'
			),
				array( '<script type="text/javascript">', '<script>', '</script>' ), $sScript );
		}

		return $sScript;
	}


	/**
	 *
	 * @param   array  $sUrl
	 *
	 * @throws Exception
	 */
	public function loadCssAsync( $aCssUrls )
	{
		$sScript = '';

		##<procode>##
		if ( $this->oParams->get( 'pro_reduce_dom', '0' ) || $this->oParams->get( 'pro_remove_unused_css', '0' ) )
		{
			$sScript .= <<<HTML
<script>
function onUserInteract(callback) { 
	window.addEventListener('load', function() {
	        if (window.pageYOffset !== 0){
	        	callback()
	        }
	});
	
	window.addEventListener('scroll', function() {
	        callback()
	});
	
	document.addEventListener('DOMContentLoaded', function() {
		let b = document.getElementsByTagName('body')[0];
		b.addEventListener('mouseenter', function() {
	        	callback()
		});
	});
}

</script>
HTML;
		}
		##</procode>##

		$sNoScriptUrls = implode( Utility::lnEnd(), array_map( function ( $sUrl ) {
			//language=HTML
			return '<link rel="stylesheet" href="' . $sUrl . '" />';
		}, $aCssUrls ) );

		if ( ! $this->oParams->get( 'pro_remove_unused_css', '0' ) )
		{
			$sPreloadCssUrls = implode( Utility::lnEnd(), array_map( function ( $sUrl ) {
				//language=HTML
				return '<link rel="preload" href="' . $sUrl . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" />';
			}, $aCssUrls ) );

			$sScript .= <<<HTML
$sPreloadCssUrls
<noscript>
$sNoScriptUrls
</noscript>
<script>
/*! loadCSS. [c]2017 Filament Group, Inc. MIT License */
/* This file is meant as a standalone workflow for
- testing support for link[rel=preload]
- enabling async CSS loading in browsers that do not support rel=preload
- applying rel preload css once loaded, whether supported or not.
*/
(function( w ){
	"use strict";
	// rel=preload support test
	if( !w.loadCSS ){
		w.loadCSS = function(){};
	}
	// define on the loadCSS obj
	var rp = loadCSS.relpreload = {};
	// rel=preload feature support test
	// runs once and returns a function for compat purposes
	rp.support = (function(){
		var ret;
		try {
			ret = w.document.createElement( "link" ).relList.supports( "preload" );
		} catch (e) {
			ret = false;
		}
		return function(){
			return ret;
		};
	})();

	// if preload isn't supported, get an asynchronous load by using a non-matching media attribute
	// then change that media back to its intended value on load
	rp.bindMediaToggle = function( link ){
		// remember existing media attr for ultimate state, or default to 'all'
		var finalMedia = link.media || "all";

		function enableStylesheet(){
			// unbind listeners
			if( link.addEventListener ){
				link.removeEventListener( "load", enableStylesheet );
			} else if( link.attachEvent ){
				link.detachEvent( "onload", enableStylesheet );
			}
			link.setAttribute( "onload", null ); 
			link.media = finalMedia;
		}

		// bind load handlers to enable media
		if( link.addEventListener ){
			link.addEventListener( "load", enableStylesheet );
		} else if( link.attachEvent ){
			link.attachEvent( "onload", enableStylesheet );
		}

		// Set rel and non-applicable media type to start an async request
		// note: timeout allows this to happen async to let rendering continue in IE
		setTimeout(function(){
			link.rel = "stylesheet";
			link.media = "only x";
		});
		// also enable media after 3 seconds,
		// which will catch very old browsers (android 2.x, old firefox) that don't support onload on link
		setTimeout( enableStylesheet, 3000 );
	};

	// loop through link elements in DOM
	rp.poly = function(){
		// double check this to prevent external calls from running
		if( rp.support() ){
			return;
		}
		var links = w.document.getElementsByTagName( "link" );
		for( var i = 0; i < links.length; i++ ){
			var link = links[ i ];
			// qualify links to those with rel=preload and as=style attrs
			if( link.rel === "preload" && link.getAttribute( "as" ) === "style" && !link.getAttribute( "data-loadcss" ) ){
				// prevent rerunning on link
				link.setAttribute( "data-loadcss", true );
				// bind listeners to toggle media back
				rp.bindMediaToggle( link );
			}
		}
	};

	// if unsupported, run the polyfill
	if( !rp.support() ){
		// run once at least
		rp.poly();

		// rerun poly on an interval until onload
		var run = w.setInterval( rp.poly, 500 );
		if( w.addEventListener ){
			w.addEventListener( "load", function(){
				rp.poly();
				w.clearInterval( run );
			} );
		} else if( w.attachEvent ){
			w.attachEvent( "onload", function(){
				rp.poly();
				w.clearInterval( run );
			} );
		}
	}


	// commonjs
	if( typeof exports !== "undefined" ){
		exports.loadCSS = loadCSS;
	}
	else {
		w.loadCSS = loadCSS;
	}
}( typeof global !== "undefined" ? global : this ) );
</script>
HTML;
		}
		##<procode>##
		else
		{
			$sDecodedUrls = array_map( function ( $sUrl ) {
				return html_entity_decode( $sUrl );
			}, $aCssUrls );
			$aJsUrlArray  = json_encode( $sDecodedUrls );

			$sScript .= <<<HTML
<script>
let loaded = false;

onUserInteract(function(){ 
	var css_urls = $aJsUrlArray;
        
	if (!loaded){
	    	css_urls.forEach(function(url, index){
	       		let l = document.createElement('link');
			l.rel = 'stylesheet';
			l.href = url;
			let h = document.getElementsByTagName('head')[0];
			h.append(l); 
	    	});
	    
		loaded = true;
        }
});
</script>
<noscript>
$sNoScriptUrls
</noscript>
HTML;
		}

		if ( $this->oParams->get( 'pro_reduce_dom', '0' ) )
		{
			$sScript .= <<<HTML
<script>
onUserInteract(function(){
	let sections = document.getElementsByClassName('jch-reduced-dom-container');
	
	if (sections.length > 0){
		for (let section of sections){
		        let replaceObject = {
		                '<!--[start reduce dom]':'',
		                '[end reduce dom]-->':'',
		                '--//>':'-->'
		        };
		        let html = section.innerHTML.replace(/<!--\[start reduce dom\]|\[end reduce dom\]-->|--\/\/>/g, function(matched){
		        	return replaceObject[matched];
		        });
		        
		        section.outerHTML = html;
		}
		
		dispatchEvent(new Event('load'));
	}
});

</script>
HTML;
		}
		##</procode>##

		$sScript   = $this->cleanScript( $sScript );
		$sHeadHtml = $this->oProcessor->getHeadHtml();
		$sHeadHtml = preg_replace( '#' . Parser::HTML_END_HEAD_TAG() . '#i', $sScript . $this->sLnEnd . $this->sTab . '</head>', $sHeadHtml, 1 );

		$this->oProcessor->setHeadHtml( $sHeadHtml );
	}

	/**
	 * Adds the async attribute to the aggregated js file link
	 *
	 * @return string
	 */
	protected function getAsyncAttribute()
	{
		if ( $this->oParams->get( 'loadAsynchronous', '0' ) )
		{
			$attr = $this->oProcessor->bLoadAsync ? 'async' : 'defer';

			return Helper::isXhtml( $this->oProcessor->getHtml() ) ? ' ' . $attr . '="' . $attr . '" ' : ' ' . $attr . ' ';
		}
		else
		{
			return '';
		}
	}
}


