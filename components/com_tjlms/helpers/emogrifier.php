<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * 2008-08-10  Fixed CSS comment stripping regex to add PCRE_DOTALL (changed from '/\/\*.*\*\//U' to '/\/\*.*\*\//sU')
 * 2008-08-18  Added lines instructing DOMDocument to attempt to normalize HTML before processing
 * 2008-10-20  Fixed bug with bad variable name... Thanks Thomas!
 * 2008-03-02  Added licensing terms under the MIT License
 * Only remove unprocessable HTML tags if they exist in the array
 * 2009-06-03  Normalize existing CSS (style) attributes in the HTML before we process the CSS.
 * Made it so that the display:none stripper doesn't require a trailing semi-colon.
 * 2009-08-13  Added support for subset class values (e.g. "p.class1.class2").
 * Added better protection for bad css attributes.
 * Fixed support for HTML entities.
 * 2009-08-17  Fixed CSS selector processing so that selectors are processed by precedence/specificity, and not just in order.
 * 2009-10-29  Fixed so that selectors appearing later in the CSS will have precedence over identical selectors appearing earlier.
 * 2009-11-04  Explicitly declared static functions static to get rid of E_STRICT notices.
 * 2010-05-18  Fixed bug where full url filenames with protocols wouldn't get split improperly when we explode on ':'... Thanks Mark!
 * Added two new attribute selectors
 * 2010-06-16  Added static caching for less processing overhead in situations where multiple emogrification takes place
 * 2010-07-26  Fixed bug where '0' values were getting discarded because of php's empty() function... Thanks Scott!
 * 2010-09-03  Added checks to invisible node removal to ensure that we don't try to remove non-existent child nodes of parent
 * that have already been deleted
 *
 * @since      1.0
 */

// No direct access.
defined('_JEXEC') or die;
/**
 * Emogrifier class
 *
 * @since  1.6
 */
class Emogrifier
{
	private $html = '';

	private $css = '';

	private $unprocessableHTMLTags = array('wbr');

	/**
	 * @var string
	 */
	const ID_ATTRIBUTE_MATCHER = '/(\\w+)?\\#([\\w\\-]+)/';

	/**
	 * @var string
	 */
	const CLASS_ATTRIBUTE_MATCHER = '/(\\w+|[\\*\\]])?((\\.[\\w\\-]+)+)/';

	/**
	 * setHTML
	 *
	 * @param   STRING  $html  html
	 * @param   STRING  $css   css
	 */
	public function __construct($html = '', $css = '')
	{
		$this->html = $html;
		$this->css  = $css;
	}

	/**
	 * setHTML
	 *
	 * @param   STRING  $html  html
	 *
	 * @return  STRING
	 */
	public function setHTML($html = '')
	{
		$this->html = $html;
	}

	/**
	 * setCSS
	 *
	 * @param   STRING  $css  css
	 *
	 * @return  STRING
	 */
	public function setCSS($css = '')
	{
		$this->css = $css;
	}

	/**
	 * there are some HTML tags that DOMDocument cannot process, and will throw an error if it encounters them.
	 * these functions allow you to add/remove them if necessary.
	 * it only strips them from the code (does not remove actual nodes).
	 *
	 * @param   ARRAY  $tag  tag
	 *
	 * @return  ARRAY
	 */
	public function addUnprocessableHTMLTag($tag)
	{
		$this->unprocessableHTMLTags[] = $tag;
	}

	/**
	 * sortBySelectorPrecedence
	 *
	 * @param   ARRAY  $tag  tag
	 *
	 * @return  ARRAY
	 */
	public function removeUnprocessableHTMLTag($tag)
	{
		if (($key = array_search($tag, $this->unprocessableHTMLTags)) !== false)
		{
			unset($this->unprocessableHTMLTags[$key]);
		}
	}

	/**
	 * applies the CSS you submit to the html you submit. places the css inline
	 *
	 * @param   BOOLEAN  $bodyContent  get Only body content
	 *
	 * @return  ARRAY
	 */
	public function emogrify($bodyContent = false)
	{
		$body = $this->html;

		// Process the CSS here, turning the CSS style blocks into inline css
		if (count($this->unprocessableHTMLTags))
		{
			$unprocessableHTMLTags = implode('|', $this->unprocessableHTMLTags);
			$body                  = preg_replace("/<($unprocessableHTMLTags)[^>]*>/i", '', $body);
		}

		$encoding = mb_detect_encoding($body);

		$body = mb_convert_encoding($body, 'HTML-ENTITIES', $encoding);

		$xmldoc                      = new DOMDocument;
		$xmldoc->encoding            = $encoding;
		$xmldoc->strictErrorChecking = false;
		$xmldoc->formatOutput        = true;

		if (@$xmldoc->loadHTML($body) === false)
		{
			return false;
		}

		$xmldoc->normalizeDocument();

		$xpath = new DOMXPath($xmldoc);

		/* before be begin processing the CSS file, parse the document and normalize all
		 * existing CSS attributes (changes 'DISPLAY: none' to 'display: none');
		we wouldn't have to do this if DOMXPath supported XPath 2.0.*/
		$nodes = @$xpath->query('//*[@style]');

		if ($nodes->length > 0)
		{
			foreach ($nodes as $node)
			{
				$result = preg_replace_callback("/[A-z\-]+(?=\:)/S", function($m){
					return strtolower($m[0]);
				},
				$node->getAttribute('style')
				);
				$node->setAttribute('style', $result);
			}
		}

		// Get rid of css comment code
		$re_commentCSS = '/\/\*.*\*\//sU';
		$css           = preg_replace($re_commentCSS, '', $this->css);

		static $csscache = array();
		$csskey = md5($css);

		if (!isset($csscache[$csskey]))
		{
			// Process the CSS file for selectors and definitions
			$re_CSS = '/^\s*([^{]+){([^}]+)}/mis';
			preg_match_all($re_CSS, $css, $matches);

			$all_selectors = array();

			foreach ($matches[1] as $key => $selectorString)
			{
				// If there is a blank definition, skip
				if (!strlen(trim($matches[2][$key])))
				{
					continue;
				}

				// Else split by commas and duplicate attributes so we can sort by selector precedence
				$selectors = explode(',', $selectorString);

				foreach ($selectors as $selector)
				{
					// Don't process pseudo-classes
					if (strpos($selector, ':') !== false)
					{
						continue;
					}

					$all_selectors[] = array(
						'selector' => $selector,
						'attributes' => $matches[2][$key],
						'index' => $key // Keep track of where it appears in the file, since order is important
					);
				}
			}

			// Now sort the selectors by precedence
			usort($all_selectors, array('self','sortBySelectorPrecedence'));

			$csscache[$csskey] = $all_selectors;
		}

		foreach ($csscache[$csskey] as $value)
		{
			// Query the body for the xpath selector
			$nodes = $xpath->query($this->translateCSStoXpath(trim($value['selector'])));

			if ($nodes)
			{
				foreach ($nodes as $node)
				{
					// If it has a style attribute, get it, process it, and append (overwrite) new stuff
					if ($node->hasAttribute('style'))
					{
						// Break it up into an associative array
						$oldStyleArr = $this->cssStyleDefinitionToArray($node->getAttribute('style'));
						$newStyleArr = $this->cssStyleDefinitionToArray($value['attributes']);

						// New styles overwrite the old styles (not technically accurate, but close enough)
						$combinedArr = array_merge($oldStyleArr, $newStyleArr);
						$style       = '';

						foreach ($combinedArr as $k => $v)
						{
							$style .= (strtolower($k) . ':' . $v . ';');
						}
					}
					else
					{
						// Otherwise create a new style
						$style = trim($value['attributes']);
					}

					$node->setAttribute('style', $style);
				}
			}
		}

		// This removes styles from your email that contain display:none. You could comment these out if you want.
		$nodes = $xpath->query('//*[contains(translate(@style," ",""),"display:none")]');

		/* the checks on parentNode and is_callable below are there to ensure that if we've deleted the parent node,
		we don't try to call removeChild on a nonexistent child node*/

		if ($nodes->length > 0)
		{
			foreach ($nodes as $node)
			{
				if ($node->parentNode && is_callable(
					array(
					$node->parentNode,
					'removeChild'
					)
					))
				{
					$node->parentNode->removeChild($node);
				}
			}
		}

		if ($bodyContent)
		{
			$innerDocument = new \DOMDocument;

			foreach ($xmldoc->documentElement->getElementsByTagName('body')->item(0)->childNodes as $childNode)
			{
				$innerDocument->appendChild($innerDocument->importNode($childNode, true));
			}

			return html_entity_decode($innerDocument->saveHTML());
		}

		return $xmldoc->saveHTML();
	}

	/**
	 * sortBySelectorPrecedence
	 *
	 * @param   ARRAY  $a  a
	 * @param   ARRAY  $b  b
	 *
	 * @return  ARRAY
	 */
	private static function sortBySelectorPrecedence($a, $b)
	{
		$precedenceA = self::getCSSSelectorPrecedence($a['selector']);
		$precedenceB = self::getCSSSelectorPrecedence($b['selector']);

		/* We want these sorted ascendingly so selectors with lesser precedence get processed first and
		selectors with greater precedence get sorted last*/
		return ($precedenceA == $precedenceB) ? ($a['index'] < $b['index'] ? -1 : 1) : ($precedenceA < $precedenceB ? -1 : 1);
	}

	/**
	 * getCSSSelectorPrecedence
	 *
	 * @param   ARRAY  $selector  selector
	 *
	 * @return  ARRAY
	 */
	private static function getCSSSelectorPrecedence($selector)
	{
		static $selectorcache = array();
		$selectorkey = md5($selector);

		if (!isset($selectorcache[$selectorkey]))
		{
			$precedence = 0;
			$value      = 100;

			// Ids: worth 100, classes: worth 10, elements: worth 1
			$search     = array(
				'\#',
				'\.',
				''
			);

			foreach ($search as $s)
			{
				if (trim($selector == ''))
				{
					break;
				}

				$num      = 0;
				$selector = preg_replace('/' . $s . '\w+/', '', $selector, -1, $num);
				$precedence += ($value * $num);
				$value /= 10;
			}

			$selectorcache[$selectorkey] = $precedence;
		}

		return $selectorcache[$selectorkey];
	}

	/**
	 * right now we support all CSS 1 selectors and /some/ CSS2/3 selectors.
	 * http://plasmasturm.org/log/444/
	 *
	 * @param   ARRAY  $css_selector  css_selector
	 *
	 * @return  ARRAY
	 */
	private function translateCSStoXpath($css_selector)
	{
		$css_selector = trim($css_selector);
		static $xpathcache = array();
		$xpathkey = md5($css_selector);

		if (!isset($xpathcache[$xpathkey]))
		{
			// Returns an Xpath selector
			$search = array(
				// Matches any element that is a child of parent.
				'/\\s+>\\s+/',
				// Matches any element that is an adjacent sibling.
				'/\\s+\\+\\s+/',
				// Matches any element that is a descendant of an parent element element.
				'/\\s+/',
				// First-child pseudo-selector
				'/([^\\/]+):first-child/i',
				// Last-child pseudo-selector
				'/([^\\/]+):last-child/i',
				// Matches element with attribute
				'/(\\w)\\[(\\w+)\\]/',
				// Matches element with EXACT attribute
				'/(\\w)\\[(\\w+)\\=[\'"]?(\\w+)[\'"]?\\]/',
			);
			$replace = array(
				'/',
				'/following-sibling::*[1]/self::',
				'//',
				'*[1]/self::\\1',
				'*[last()]/self::\\1',
				'\\1[@\\2]',
				'\\1[@\\2="\\3"]',
			);
			$css_selector = '//' . preg_replace($search, $replace, $css_selector);
			$css_selector = preg_replace_callback(self::ID_ATTRIBUTE_MATCHER, array($this, 'matchIdAttributes'), $css_selector);
			$css_selector = preg_replace_callback(self::CLASS_ATTRIBUTE_MATCHER, array($this, 'matchClassAttributes'), $css_selector);

			// Advanced selectors are going to require a bit more advanced emogrification.
			// When we required PHP 5.3, we could do this with closures.
			$css_selector = preg_replace_callback(
					'/([^\\/]+):nth-child\\(\s*(odd|even|[+\-]?\\d|[+\\-]?\\d?n(\\s*[+\\-]\\s*\\d)?)\\s*\\)/i', array($this, 'translateNthChild'), $css_selector
			);
			$css_selector = preg_replace_callback(
					'/([^\\/]+):nth-of-type\\(\s*(odd|even|[+\-]?\\d|[+\\-]?\\d?n(\\s*[+\\-]\\s*\\d)?)\\s*\\)/i', array($this, 'translateNthOfType'), $css_selector
			);
			$xpathcache[$xpathkey] = $css_selector;
		}

		return $xpathcache[$xpathkey];
	}

	/**
	 * Match ID Attributes
	 *
	 * @param   array  $match  Match
	 *
	 * @return string
	 */
	private function matchIdAttributes(array $match)
	{
		return (strlen($match[1]) ? $match[1] : '*') . '[@id="' . $match[2] . '"]';
	}

	/**
	 * Match Class Attributes
	 *
	 * @param   array  $match  Match
	 *
	 * @return string
	 */
	private function matchClassAttributes(array $match)
	{
		return (strlen($match[1]) ? $match[1] : '*') . '[contains(concat(" ",@class," "),concat(" ","' .
				implode(
						'"," "))][contains(concat(" ",@class," "),concat(" ","', explode('.', substr($match[2], 1))
				) . '"," "))]';
	}

	/**
	 * Translate Nth Child
	 *
	 * @param   array  $match  Match
	 *
	 * @return string
	 */
	private function translateNthChild(array $match)
	{
		$result = $this->parseNth($match);

		if (isset($result[self::MULTIPLIER]))
		{
			if ($result[self::MULTIPLIER] < 0)
			{
				$result[self::MULTIPLIER] = abs($result[self::MULTIPLIER]);

				return sprintf('*[(last() - position()) mod %u = %u]/self::%s', $result[self::MULTIPLIER], $result[self::INDEX], $match[1]);
			}
			else
			{
				return sprintf('*[position() mod %u = %u]/self::%s', $result[self::MULTIPLIER], $result[self::INDEX], $match[1]);
			}
		}
		else
		{
			return sprintf('*[%u]/self::%s', $result[self::INDEX], $match[1]);
		}
	}

	/**
	 * Translate Nth of Type
	 *
	 * @param   array  $match  Match
	 *
	 * @return string
	 */
	private function translateNthOfType(array $match)
	{
		$result = $this->parseNth($match);

		if (isset($result[self::MULTIPLIER]))
		{
			if ($result[self::MULTIPLIER] < 0)
			{
				$result[self::MULTIPLIER] = abs($result[self::MULTIPLIER]);

				return sprintf('%s[(last() - position()) mod %u = %u]', $match[1], $result[self::MULTIPLIER], $result[self::INDEX]);
			}
			else
			{
				return sprintf('%s[position() mod %u = %u]', $match[1], $result[self::MULTIPLIER], $result[self::INDEX]);
			}
		}
		else
		{
			return sprintf('%s[%u]', $match[1], $result[self::INDEX]);
		}
	}

	/**
	 * Parse Nth
	 *
	 * @param   array  $match  Match
	 *
	 * @return array
	 */
	private function parseNth(array $match)
	{
		if (in_array(strtolower($match[2]), array('even', 'odd')))
		{
			$index = strtolower($match[2]) == 'even' ? 0 : 1;

			return array(self::MULTIPLIER => 2, self::INDEX => $index);
		}
		elseif (stripos($match[2], 'n') === false)
		{
			// If there is a multiplier
			$index = intval(str_replace(' ', '', $match[2]));

			return array(self::INDEX => $index);
		}
		else
		{
			if (isset($match[3]))
			{
				$multipleTerm = str_replace($match[3], '', $match[2]);
				$index = intval(str_replace(' ', '', $match[3]));
			}
			else
			{
				$multipleTerm = $match[2];
				$index = 0;
			}

			$multiplier = str_ireplace('n', '', $multipleTerm);

			if (!strlen($multiplier))
			{
				$multiplier = 1;
			}
			elseif ($multiplier == 0)
			{
				return array(self::INDEX => $index);
			}
			else
			{
				$multiplier = intval($multiplier);
			}

			while ($index < 0)
			{
				$index += abs($multiplier);
			}

			return array(self::MULTIPLIER => $multiplier, self::INDEX => $index);
		}
	}

	/**
	 * This formats the data to be stored in the config file
	 *
	 * @param   ARRAY  $style  style
	 *
	 * @return  ARRAY
	 */
	private function cssStyleDefinitionToArray($style)
	{
		$definitions = explode(';', $style);
		$retArr = array();

		foreach ($definitions as $def)
		{
			if (empty($def) || strpos($def, ':') === false)
			{
				continue;
			}

			list($key, $value) = explode(':', $def, 2);

			if (empty($key) || strlen(trim($value)) === 0)
			{
				continue;
			}

			$retArr[trim($key)] = trim($value);
		}

		return $retArr;
	}
}
