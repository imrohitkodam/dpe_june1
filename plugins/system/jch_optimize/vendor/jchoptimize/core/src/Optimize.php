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

// No direct access
defined('_JCH_EXEC') or die('Restricted access');

use JchOptimize\Core\Html\Processor as HtmlProcessor;
use JchOptimize\Core\Html\Parser as HtmlParser;
use JchOptimize\Core\Html\CacheManager;
use JchOptimize\Core\Html\LinkBuilder;
use JchOptimize\Platform\Settings;
use JchOptimize\Platform\Profiler;
use JchOptimize\Platform\Utility;
use Joomla\Registry\Registry;

/**
 * Main plugin file
 *
 */
class Optimize
{

	/** @var object   Plugin params * */
	public $params = null;
	private $jit = 1;

	/**
	 * Optimize website by aggregating css and js
	 *
	 * @param   string  $sHtml
	 *
	 * @return string
	 * @throws Exception
	 */
	public function process($sHtml)
	{
		JCH_DEBUG ? Profiler::start('Process', true) : null;

		try
		{
			$oHtmlProcessor = new HtmlProcessor($sHtml, $this->params);
			$oHtmlProcessor->processCombineJsCss();
			$oHtmlProcessor->processImageAttributes();

			$oCacheManager = new CacheManager(new LinkBuilder($oHtmlProcessor));
			$oCacheManager->handleCombineJsCss();
			$oCacheManager->handleImgAttributes();

			$oHtmlProcessor->processCdn();
			$oHtmlProcessor->processLazyLoad();

			$sOptimizedHtml = self::reduceDom(Helper::minifyHtml($oHtmlProcessor->getHtml(), $this->params));

			$this->sendHeaders();
		}
		catch (Exception $ex)
		{
			Logger::log($ex->getMessage(), $this->params);

			$sOptimizedHtml = $sHtml;
		}

		JCH_DEBUG ? Profiler::stop('Process', true) : null;

		JCH_DEBUG ? Profiler::attachProfiler($sOptimizedHtml, $oHtmlProcessor->bAmpPage) : null;

		if (version_compare(PHP_VERSION, '7.0.0', '>='))
		{
			ini_set('pcre.jit', $this->jit);
		}

		return $sOptimizedHtml;
	}

	/**
	 * Static method to initialize the plugin
	 *
	 * @param   Settings|Registry  $oParams
	 * @param   string             $sHtml
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function optimize($oParams, $sHtml)
	{
		if (version_compare(PHP_VERSION, '5.3.0', '<'))
		{
			throw new Exception('PHP Version less than 5.3.0. Exiting plugin...');
		}

		$pcre_version = preg_replace('#(^\d++\.\d++).++$#', '$1', PCRE_VERSION);

		if (version_compare($pcre_version, '7.2', '<'))
		{
			throw new Exception('PCRE Version less than 7.2. Exiting plugin...');
		}

		$oOptimize = new Optimize($oParams);

		return $oOptimize->process($sHtml);
	}

	/**
	 * Constructor
	 *
	 * @param   Settings|Registry  $oParams  Plugin parameters
	 */
	private function __construct($oParams)
	{
		ini_set('pcre.backtrack_limit', 1000000);
		ini_set('pcre.recursion_limit', 100000);

		if (version_compare(PHP_VERSION, '7.0.0', '>='))
		{
			$this->jit = ini_get('pcre.jit');
			ini_set('pcre.jit', 0);
		}

		if ($oParams instanceof Settings)
		{
			$this->params = $oParams;
		}
		else
		{
			$this->params = Settings::getInstance($oParams);
		}
	}

	protected function sendHeaders()
	{
		##<procode>##
		$headers = array();

		if ($this->params->get('pro_http2_push_enable', '0'))
		{
			$aPreloads = Helper::$preloads;

			if (!empty($aPreloads))
			{
				$headers['Link'] = implode(',', $aPreloads);
			}
		}

		if (!empty($headers))
		{
			Utility::sendHeaders($headers);
		}
		##</procode>##
	}

	protected function reduceDom($sHtml)
	{
		##<procode>##
                if (!$this->params->get('pro_reduce_dom', '0'))
                {
                        return $sHtml;
                }

		JCH_DEBUG ? Profiler::start('ReduceDom', false) : null;

		$aOptions = array(
			'num-elements'  => 0, //number of elements encountered
			'nesting-level' => 0,
			'in-comments'   => false, //Inside a section being commented out
			'processing'    => false, //Maximum number of elements reached and DOM is now being reduced
			'html-block'    => '' //Html block currently being processed
		);
		$sRegex = '#(?:[^<]*+(?:' . HtmlParser::HTML_HEAD_ELEMENT() . '|' . HtmlParser::COMMENT() . '))?[^<]*+<(/)?(\w++)[^>]*+>#si';

		$sReducedHtml = preg_replace_callback($sRegex, function ($aMatches) use (&$aOptions) {
			//Initialize return string
			$sReturn = '';
			$bEndComments = false;
			$aHtmlSections = array('section', 'header', 'footer', 'article', 'aside', 'nav');

			switch (true)
			{
				//Open tag
				case !empty($aMatches[2]) && empty($aMatches[1]):
					//Increment count of elements
					$aOptions['num-elements']++;

					if ($aOptions['processing'] && in_array($aMatches[2], $aHtmlSections)
					&& $aOptions['nesting-level']++ == 0)
					{
						$sReturn .= '<div class="jch-reduced-dom-container"><!--[start reduce dom]';

						$aOptions['in-comments'] = true;
						$aOptions['html-block']  = $aMatches[2];
					}

					//Start commenting out sections of HTML above 600 DOM elements
					if ($aOptions['num-elements'] == 600)
					{
						$aOptions['processing'] = true;
					}

					break;
				//Closing tag
				case !empty($aMatches[1]):

					if ($aOptions['in-comments'] && in_array($aMatches[2], $aHtmlSections) &&
						--$aOptions['nesting-level'] == 0 && $aMatches[2] == $aOptions['html-block'])
					{
						$bEndComments = true;
					}

					break;
				default:
					break;
			}

			if ($aOptions['in-comments'])
			{
				$aMatches[0] = str_replace('-->', '--//>', $aMatches[0]);
			}

			$sReturn .= $aMatches[0];

			if ($bEndComments)
			{
				$sReturn .= '[end reduce dom]--></div>';

				$aOptions['in-comments'] = false;
			}

			return $sReturn;
		}, $sHtml);

		JCH_DEBUG ? Profiler::stop('ReduceDom', true) : null;

		$sHtml = $sReducedHtml;

		##</procode>##
		return $sHtml;
	}
}
