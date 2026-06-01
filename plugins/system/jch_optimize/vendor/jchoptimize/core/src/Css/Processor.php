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

namespace JchOptimize\Core\Css;

defined( '_JCH_EXEC' ) or die( 'Restricted access' );

use JchOptimize\Core\Css\Callbacks\CombineMediaQueries;
use JchOptimize\Core\Css\Callbacks\CorrectUrls;
use JchOptimize\Core\Css\Callbacks\ExtractCriticalCss;
use JchOptimize\Core\Css\Callbacks\FormatCss;
use JchOptimize\Core\Css\Callbacks\HandleAtRules;
use JchOptimize\Core\Exception;
use JchOptimize\Core\Helper;
use JchOptimize\Core\Logger;
use JchOptimize\Core\Traits\Debug;
use JchOptimize\Platform\Profiler;

class Processor
{
	use Debug;

	public $oParams;

	protected $sCss;

	protected $aUrl;

	protected $aAtImports = array();

	private $sDebugUrl;

	public function __construct( $oParams )
	{
		$this->oParams = $oParams;
	}

	public function setUrlArray( $aUrl )
	{
		$this->aUrl      = $aUrl;
		$this->sDebugUrl = Helper::prepareFileUrl( $aUrl, 'css' );
		//initialize debug
		$this->_debug( $this->sDebugUrl, '', 'CssProcessorConstructor' );
	}

	public function setCss( $sCss )
	{
		if ( function_exists( 'mb_convert_encoding' ) )
		{
			$sEncoding = mb_detect_encoding( $sCss );

			if ( $sEncoding === false )
			{
				$sEncoding = mb_internal_encoding();
			}

			$sCss = mb_convert_encoding( $sCss, 'utf-8', $sEncoding );
		}

		$this->sCss = $sCss;
	}

	public function getCss()
	{
		return $this->sCss;
	}

	public function formatCss()
	{
		$oParser = new Parser();
		$oParser->setExcludes( array(
			Parser::BLOCK_COMMENT(),
			Parser::LINE_COMMENT(),
			Parser::CSS_NESTED_AT_RULES_CP(),
		) );

		$sPrepareExcludeRegex = '\|"(?>[^"{}]*+"?)*?[^"{}]*+"\|';

		$oSearchObject = new CssSearchObject();
		$oSearchObject->setCssNestedRuleName( 'media', true );
		$oSearchObject->setCssNestedRuleName( 'supports', true );
		$oSearchObject->setCssNestedRuleName( 'document', true );
		$oSearchObject->setCssAtRuleCriteria( Parser::CSS_AT_RULES() );
		$oSearchObject->setCssRuleCriteria( '*' );
		$oSearchObject->setCssCustomRule( $sPrepareExcludeRegex );
		$oSearchObject->setCssCustomRule( Parser::CSS_INVALID_CSS() );
		$oParser->setCssSearchObject( $oSearchObject );
		$oParser->disableBranchReset();

		$oCallback = new FormatCss( $this->oParams, $this->aUrl );

		$oCallback->sValidCssRules = $sPrepareExcludeRegex;

		try
		{
			$this->sCss = $oParser->processMatchesWithCallback( $this->sCss . '}', $oCallback );
		}
		catch ( Exception $oException )
		{
			Logger::log( 'FormatCss failed - ' . $this->sDebugUrl . ': ' . $oException->getMessage(), $this->oParams );
		}

		$this->_debug( $this->sDebugUrl, '', 'formatCss' );
	}

	public function preloadHttp2( $sCss, $bFontOnly=false )
	{
		$this->sCss = $sCss;
		$this->processUrls( true, $bFontOnly );
	}

	public function processUrls( $bHttp2 = false, $bFontOnly = false )
	{
		$oParser       = new Parser();
		$oSearchObject = new CssSearchObject();
		$oSearchObject->setCssNestedRuleName( 'font-face', true );
		$oSearchObject->setCssNestedRuleName( 'media', true );
		$oSearchObject->setCssNestedRuleName( 'supports', true );
		$oSearchObject->setCssNestedRuleName( 'document', true );
		$oSearchObject->setCssRuleCriteria( Parser::CSS_URL_CP( true ) );
		$oSearchObject->setCssAtRuleCriteria( Parser::CSS_AT_IMPORT_CP( true ) );
		$oParser->setCssSearchObject( $oSearchObject );

		$oCallback = new CorrectUrls( $this->oParams, $this->aUrl );

		if ( $bHttp2 )
		{
			$oCallback->bHttp2 = true;
		}

		if ( $bFontOnly )
		{
			$oCallback->bFontOnly = true;
		}

		try
		{
			$this->sCss = $oParser->processMatchesWithCallback( $this->sCss, $oCallback );
		}
		catch ( Exception $oException )
		{
			$sPreMessage = $bHttp2 ? 'Http/2 preload failed' : 'ProcessUrls failed';

			Logger::log( $sPreMessage . ' - ' . $this->sDebugUrl . ': ' . $oException->getMessage(), $this->oParams );
		}

		$this->_debug( $this->sDebugUrl, '', 'processUrls' );
	}

	public function processAtRules( $oHtmlProcessor )
	{
		$oParser       = new Parser();
		$oSearchObject = new CssSearchObject();
		$oSearchObject->setCssAtRuleCriteria( Parser::CSS_AT_IMPORT_CP( true ) );
		$oSearchObject->setCssAtRuleCriteria( Parser::CSS_AT_CHARSET_CP() );
		$oSearchObject->setCssNestedRuleName( 'font-face' );
		$oSearchObject->setCssNestedRuleName( 'media', true );
		$oParser->setCssSearchObject( $oSearchObject );

		$oCallback = new HandleAtRules( $this->oParams, $this->aUrl );
		$oCallback->setHtmlProcessor( $oHtmlProcessor );

		try
		{
			$this->sCss = $this->cleanEmptyMedias( $oParser->processMatchesWithCallback( $this->sCss, $oCallback ) );

			if ( ! empty ( $oCallback->aAtImports ) )
			{
				$this->aAtImports = array_merge( $this->aAtImports, $oCallback->aAtImports );
			}
		}
		catch ( Exception $oException )
		{
			Logger::log( 'ProcessAtRules failed - ' . $this->sDebugUrl . ': ' . $oException->getMessage(), $this->oParams );
		}

		$this->_debug( $this->sDebugUrl, '', 'ProcessAtRules' );
	}

	public function cleanEmptyMedias( $sCss )
	{
		$oParser = new Parser();
		$oParser->setExcludes(
			array(
				Parser::BLOCK_COMMENT(),
				'[@/]'
			)
		);
		$oParser->setParseTerm( '[^@/]*+' );
		$oCssEmptyMediaObject = new CssSearchObject();
		$oCssEmptyMediaObject->setCssNestedRuleName( 'media', false, true );

		$oParser->setCssSearchObject( $oCssEmptyMediaObject );

		return $oParser->replaceMatches( $sCss, '' );
	}

	public function processMediaQueries()
	{
		if ( ! empty( $this->aUrl['media'] ) )
		{
			$oParser       = new Parser();
			$oSearchObject = new CssSearchObject();
			$oSearchObject->setCssNestedRuleName( 'media' );
			$oSearchObject->setCssAtRuleCriteria( Parser::CSS_AT_IMPORT_CP( true ) );
			$oSearchObject->setCssRuleCriteria( '*' );
			$oParser->setCssSearchObject( $oSearchObject );
			$oParser->disableBranchReset();

			$oCallback = new CombineMediaQueries( $this->oParams, $this->aUrl );

			try
			{
				$this->sCss = $oParser->processMatchesWithCallback( $this->sCss, $oCallback );
			}
			catch ( Exception $oException )
			{
				Logger::log( 'HandleMediaQueries failed - ' . $this->sDebugUrl . ': ' . $oException->getMessage(), $this->oParams );
			}

			$this->_debug( $this->sDebugUrl, '', 'handleMediaQueries' );
		}
	}

	public function optimizeCssDelivery( $sCss, $sHtml )
	{
		JCH_DEBUG ? Profiler::start( 'OptimizeCssDelivery' ) : null;

		//$this->_debug('', '');

		//Place space around HTML attributes for easy processing with XPath
		$sHtml = preg_replace( '#\s*=\s*["\']([^"\']++)["\']#i', '=" $1 "', $sHtml );
		//Remove text nodes from HTML elements
		$sHtml = preg_replace_callback( '#(<(?>[^<>]++|(?1))*+>)|((?<=>)(?=[^<>\S]*+[^<>\s])[^<>]++)#',
			function ( $m ) {
				if ( ! empty( $m[1] ) )
				{
					return $m[0];
				}

				if ( ! empty( $m[2] ) )
				{
					return ' ';
				}

			}, $sHtml );

		//$this->_debug('', '', 'afterHtmlAdjust');

		//Truncate HTML to number of elements set in params
		$sHtmlAboveFold = '';
		preg_replace_callback( '#<(?:[a-z0-9]++)(?:[^>]*+)>(?><?[^<]*+)*?(?=<[a-z0-9])#i',
			function ( $aM ) use ( &$sHtmlAboveFold ) {
				$sHtmlAboveFold .= $aM[0];

				return;
			}, $sHtml, (int) $this->oParams->get( 'optimizeCssDelivery', '200' ) );

		//$this->_debug('', '', 'afterHtmlTruncated');

		$oDom = new \DOMDocument();

		//Load HTML in DOM
		libxml_use_internal_errors( true );
		$oDom->loadHtml( $sHtmlAboveFold );
		libxml_clear_errors();

		$oXPath = new \DOMXPath( $oDom );

		//$this->_debug('', '', 'afterLoadHtmlDom');

		$sFullHtml = $sHtml;

		$oParser          = new Parser();
		$oCssSearchObject = new CssSearchObject();
		$oCssSearchObject->setCssNestedRuleName( 'media', true );
		$oCssSearchObject->setCssNestedRuleName( 'supports', true );
		$oCssSearchObject->setCssNestedRuleName( 'document', true );
		$oCssSearchObject->setCssNestedRuleName( 'font-face' );
		$oCssSearchObject->setCssNestedRuleName( 'keyframes' );
		$oCssSearchObject->setCssNestedRuleName( 'page' );
		$oCssSearchObject->setCssNestedRuleName( 'font-feature-values' );
		$oCssSearchObject->setCssNestedRuleName( 'counter-style' );
		$oCssSearchObject->setCssAtRuleCriteria( Parser::CSS_AT_IMPORT_CP() );
		$oCssSearchObject->setCssAtRuleCriteria( Parser::CSS_AT_CHARSET_CP() );
		$oCssSearchObject->setCssAtRuleCriteria( Parser::CSS_AT_NAMESPACE() );
		$oCssSearchObject->setCssRuleCriteria( '.' );

		$oCallback                 = new ExtractCriticalCss( $this->oParams, $this->aUrl );
		$oCallback->sHtmlAboveFold = $sHtmlAboveFold;
		$oCallback->sFullHtml      = $sFullHtml;
		$oCallback->oXPath         = $oXPath;

		$oParser->setCssSearchObject( $oCssSearchObject );

		$sCriticalCss = $oParser->processMatchesWithCallback( $sCss, $oCallback );
		$sCriticalCss = $this->cleanEmptyMedias( $sCriticalCss );

		//Process Font-Face
		$oCallback->bProcessFontFace = true;

		$sFontFace = $oParser->processMatchesWithCallback( $oCallback->sFontFace, $oCallback );

		JCH_DEBUG ? Profiler::stop( 'OptimizeCssDelivery', true ) : null;

		return $sFontFace . $sCriticalCss;

		//$this->_debug(self::cssRulesRegex(), '', 'afterCleanCriticalCss');
	}

	public function getImports()
	{
		return implode( $this->aAtImports );
	}
}