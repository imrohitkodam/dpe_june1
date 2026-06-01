<?php
/**
 * SEO Glossary Helper
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */
// No direct access
defined('_JEXEC') or die;
jimport('joomla.filesystem.file');
/**
 * Seoglossary helper.
 */
jimport('joomla.application.component.view');
require_once (JPATH_SITE . '/components/com_seoglossary/helpers/compat.php');
$app=JFactory::getApplication();
$template = method_exists($app,'getTemplate')?JFactory::getApplication()->getTemplate():'';
$tpath = JPATH_SITE . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . $template. DIRECTORY_SEPARATOR . "html" . DIRECTORY_SEPARATOR . "com_seoglossary" . DIRECTORY_SEPARATOR . "seoglossary.php";
$current_path = __DIR__ . DIRECTORY_SEPARATOR . 'seoglossary.php';
if ((JFile::exists($tpath)) && ($tpath != $current_path))
	{
	JLoader::register('SeoglossaryHelper', $tpath);
	}
  else
	{
	class SeoglossaryHelper

		{
		/**
		 * Configure the Linkbar.
		 */
		var $Text = '';
		var $Tags = array();
		 static $itemid = array();
		// $Terms is an array with the following structure:
		// Key: Phrase to be replaced
		// Value: Replacement value
		var $Terms = array();
		// Because the xml character handler can be called several times
		// for the same tag element, we store the content in this temp
		// variable, and then treat it each time we have a start_tag or a end_tag.
		var $PartialText = '';
		static $q = 0;
		static $CountTerms = array();
		public static function injectTemplateForViewLayout($viewLayout = '')

			{
			}
		static $titles;
		public static function addTitleElement($element)

			{
			$config = JFactory::getConfig();
			$sp = $config->get('sitename_pagetitles', 0);
			if (empty(self::$titles))
				{
				self::$titles = array();
				$sitename = $config->get('sitename', '');
				if ($sp && !empty($sitename))
					{
					array_push(self::$titles, $sitename);
					}
				}
			array_push(self::$titles, $element);
			$array = self::$titles;
			if ($sp == 2)
				{
				$array = array_reverse($array);
				}
			$title = implode(' - ', $array);
			$d = JFactory::getDocument();
			$d->setTitle($title);
			}
		public static function slugify($text)

			{
			$params = JComponentHelper::getParams('com_seoglossary');
			if ($params->get('legacy_slug', 0) == 0 && JFactory::getConfig()->get('unicodeslugs') == 1)
				{
				$text = JFilterOutput::stringURLUnicodeSlug($text);
				}
			  else
				{
				require_once (JPATH_SITE . '/components/com_seoglossary/helpers/URLify.php');

				$text = URLify::filter($text);
				if (empty($text) && $text !== '0')
					{
					$text = 'na';
					}
				}
			return $text;
			}
		public static function setMenuMetadata()

			{
			$app = JFactory::getApplication();
			$menu = $app->getMenu();
			$itemId = empty($query['Itemid']) ? null : $query['Itemid'];
			if ($itemId) $menuItem = $menu->getItem($itemId);
			  else $menuItem = $menu->getActive();
			$d = JFactory::getDocument();
			if (isset($menuItem) && !empty($menuItem))
				{
				if ($menuItem->getparams()->get('menu-meta_description') !== NULL) $d->setDescription($menuItem->getparams()->get('menu-meta_description'));
				if ($menuItem->getparams()->get('menu-meta_keywords') !== NULL) $d->setMetadata('keywords', $menuItem->getparams()->get('menu-meta_keywords'));
				}
			}
		public static function addSubmenu($vName = '')

			{
			return;
			}
		public static function getActions()

			{
			$user = JFactory::getUser();
			$result = new JObject;
			$assetName = 'com_seoglossary';
			$actions = array(
				'core.admin',
				'core.manage',
				'core.create',
				'core.edit',
				'core.edit.own',
				'core.edit.state',
				'core.delete'
			);
			foreach($actions as $action)
				{
				$result->set($action, $user->authorise($action, $assetName));
				}
			return $result;
			}
		public static function getSearchform()
			{
			SeoglossaryHelper::loadLanguage();
			$params = JComponentHelper::getParams('com_seoglossary');
			$option = "com_seoglossary";
			$value = JFactory::getApplication()->getUserStateFromRequest($option . '.filter.search', 'filter_search', '', 'string');
			$letter = JFactory::getApplication()->input->getString('letter');
			if (($value == "") || ($value == null) || ($letter))
				{
				$value = strtolower(JText::_('COM_SEOGLOSSARY_SEARCH')) . '...';
				}
			if (defined('SEOGJ3'))
				{
				$seogview = new JViewLegacy();
				}
			  else
				{
				$seogview = new JView();
				}
			$value = $seogview->escape($value);
			$filter_options = JFactory::getApplication()->getUserStateFromRequest($option . '.filter.glossarysearchmethod', 'glossarysearchmethod', '', 'string');
			$str1 = "";
			$str2 = "";
			$str3 = "";
			$str4 = "";
			if ($filter_options == 1)
				{
				$str1 = ' selected ';
				}
			  else if ($filter_options == 2)
				{
				$str2 = ' selected ';
				}
			  else if ($filter_options == 3)
				{
				$str3 = ' selected ';
				}
			  else if ($filter_options == 4)
				{
				$str4 = 'selected ';
				}
			$Itemid = JFactory::getApplication()->input->getInt("Itemid", '');
			$catid = JFactory::getApplication()->input->getInt("catid", '');
			$url = JRoute::_('index.php?option=com_seoglossary&amp;view=glossaries' . ((!empty($Itemid)) ? '&amp;Itemid=' . $Itemid : '') . ((!empty($catid)) ? '&amp;catid=' . $catid : ''));
			$str = ' <div id="glossarysearch" ><form id="searchForm" name="searchForm" method="post" action="' . $url . '"><div id="glossarysearchheading">' . JText::_("COM_SEOGLOSSARY_SEARCH_TEXT") . '</div><div class="input-append"><div class="srch-btn-inpt">
          <div class="srch-inpt"><input type="text" title="' . strtolower(JText::_('COM_SEOGLOSSARY_SEARCH')) . '" id="filter_search" name="filter_search"  value="' . $value . '" size="30" onblur="if(this.value==&quot;&quot;) this.value=&quot;' . strtolower(JText::_('COM_SEOGLOSSARY_SEARCH')) . '...&quot;" onfocus="if(this.value==&quot;' . strtolower(JText::_('COM_SEOGLOSSARY_SEARCH')) . '...&quot;) this.value=&quot;&quot;" class=""/><input type="hidden" name="catid" value="' . $catid . '"/>
     </div><div class="srch-btn"><button type="submit" class="button btn btn-primary" value="' . JText::_("COM_SEOGLOSSARY_SEARCH") . '"></button></div></div><input onclick="document.getElementById(&quot;filter_search&quot;).value=&quot;&quot;;this.form.submit();" type="submit" class="button  btn" value="' . JText::_('JSEARCH_FILTER_CLEAR') . '" /></div>';
			if ($params->get('search_options', 1) != 0)
				{
				$default = $params->get('default_search_mode', 1);
				$str.= '<div class="custom-select bigselect pull-right"><select name="glossarysearchmethod" class="seoselect"><option ' . $str1 . ' value="1" ' . (($default == 1) ? '  selected' : '') . ' />' . JText::_("COM_SEOGLOSSARY_SEARCH_BEGIN") . '<option ' . $str2 . ' value="2" ' . (($default == 2) ? '  selected' : '') . ' />' . JText::_("COM_SEOGLOSSARY_SEARCH_CONTAINS") . '<option ' . $str3 . ' value="3" ' . (($default == 3) ? '  selected' : '') . ' />' . JText::_("COM_SEOGLOSSARY_SEARCH_EXACT");
				if ($params->get('sound_like', 1) != 0)
					{
					$str.= '<option ' . $str4 . ' value="4" ' . (($default == 4) ? '  selected' : '') . ' />' . JText::_("COM_SEOGLOSSARY_SEARCH_LIKE");
					}
				$str.= '</select></div>';
				} //endif ( $params->get( 'search_options', 1 ) != 0 )
			  else
				{
				$str.= '<input type="hidden" name="glossarysearchmethod" value="' . $params->get('default_search_mode', 1) . '" />';
				} // endelse ( $params->get( 'search_options', 1 ) != 0 )
			$str.= '<div> </div> </form></div>';
			return $str;
			}
		/**
		 * Generate the html for alphabet form
		 * @param boolean $singlelayout is single or list layout
		 * @return string
		 */
		public static function getAlphabetForm($singlelayout = null)

			{
			SeoglossaryHelper::loadLanguage();
			$str = '<div class="glossaryalphabet seopagination"><ul class="seopagination-list">';
			$letter = JFactory::getApplication()->input->getString('letter');
			$catid = JFactory::getApplication()->input->getInt('catid', null);
			if (!$singlelayout && (($letter == "") || ($letter == null)))
				{
				$str.= '<li class="active"><span class="glossletselect ">' . JText::_('COM_SEOGLOSSARY_ALL') . '</span></li>';
				}
			  else
				{
				$url = JRoute::_('index.php?option=com_seoglossary&view=glossaries' . (($catid != null) ? '&catid=' . $catid : '') . '&Itemid=' . JFactory::getApplication()->input->getInt("Itemid"));
				$str.= '<li><a href="' . $url . '">' . JText::_('COM_SEOGLOSSARY_ALL') . '</a></li>';
				}
			$alpha = array();
			if ($catid !== null)
				{
				$sql = "SELECT alphabet FROM #__seoglossaries WHERE id = '$catid';";
				$db = JFactory::getDbo();
				$db->setQuery($sql);
				$alphaStr = $db->loadResult();
				$alpha_t = explode(',', $alphaStr);
				foreach($alpha_t as & $l)
					{
					$l = trim($l);
					$alpha[] = $l;
					}
				}
			  else
				{
				$alpha = array(
					'A',
					'B',
					'C',
					'D',
					'E',
					'F',
					'G',
					'H',
					'I',
					'J',
					'K',
					'L',
					'M',
					'N',
					'O',
					'P',
					'Q',
					'R',
					'S',
					'T',
					'U',
					'V',
					'W',
					'X',
					'Y',
					'Z'
				);
				}
			foreach($alpha as $char)
				{
				if (strtolower($letter) != strtolower($char))
					{
					$link_char = str_replace('#', "(s)", $char);
					if ($singlelayout == "alphabet")
						{
						$url = JUri::getInstance()->toString() . '#' . strtolower($char);
						}
					  else
						{
						$route = 'index.php?option=com_seoglossary&view=glossaries' . (($catid != null) ? '&catid=' . $catid : '') . '&letter=' . $link_char . '&Itemid=' . JFactory::getApplication()->input->getInt("Itemid");
						$url = JRoute::_($route);
						if (JFactory::getApplication()->input->getInt('diagnose', 0) == 1)
							{
							echo "<b>$route => $url </b><br />";
							}
						}
					$str.= '<li> <a href="' . $url . '">' . $char . "</a></li>";
					}
				  else
					{
					$str.= ' <li><span class="glossletselect">' . $char . '</span></li>';
					}
				// $str .= '+-'.$letter.'-'.$char.'-+';
				}
			$str.= '</ul></div>';
			return $str;
			}
		function replaceTerms($text)
			{
			$params = JComponentHelper::getParams('com_seoglossary');
			$pattern = $params->get('search_pattern', '/([^\w])({word}[s]?)([^\w\-])/usi');
			// Because the data inside the replacements should never be replaced
			// by another term, we buffer all the replacements during the search,
			// and apply all of them at the end.
			// To avoid losing a replacement at the beginning
			// or at the end, we add spaces before and after the text.
			// To avoid that if a word is repeated twice the following
			// occurrences are ignored, we double all the spaces
			// $text = ' '.str_replace(' ', '  ', $text).' ';
			$text = ' ' . $text . ' ';
			$occurences = (int)$params->get('occurences', 1);
			$buffer = array();
			$i = 0;
			foreach($this->Terms as $phrase => $replacement)
				{
				$key = md5($phrase . $replacement);
				if (empty(self::$CountTerms[$key]))
					{
					self::$CountTerms[$key] = 0;
					}
				// Case insensitive word search, using a regular expression.
				$escaped = str_replace('/', '\/', preg_quote($phrase));
				$searchPattern = str_replace('{word}', $escaped, $pattern);
				if (preg_match_all($searchPattern, $text, $all_matches, PREG_SET_ORDER) != 0)
					{
					foreach($all_matches as $matches)
						{
						if (self::$CountTerms[$key] >= $occurences) break;

						$buffer[$i] = str_replace('[[phrase]]', $matches[2], $replacement);
						$text = preg_replace($searchPattern, $matches[1] . '[[' . $i . ']]' . $matches[3], $text, 1);
						if (JFactory::getApplication()->input->getInt('diagnose', 0) == 1)
							{
							echo "<b>Text is : {$text}</b><br />";
							}
						$i++;
						self::$CountTerms[$key]++;
						}
					}
				}
			foreach($buffer as $i => $replacement)
				{
				if (JFactory::getApplication()->input->getInt('diagnose', 0) == 1)
					{
					echo "<b>Replacing [[{$i}]] with {$replacement}</b><br />";
					}
				$text = str_replace("[[$i]]", $replacement, $text);
				}
			// return str_replace('  ', ' ', substr($text, 1, strlen($text)-2));
			$text = substr($text, 1, strlen($text) - 2);
			if (JFactory::getApplication()->input->getInt('diagnose', 0) == 1)
				{
				echo "<b>Final text : {$text}</b><br />";
				}
			return $text;
			}
		public static function applyShortcuts($text, $term = '')

			{
			$params = JComponentHelper::getParams('com_seoglossary');
			$macro = $params->get('macro', '');
			$text = str_replace('{seog:macro}', $macro, $text);
			$text = str_replace('{seog:term}', $term, $text);
			return $text;
			}
		function replaceContext($text, $glossaryItems)
			{
			$this->replaceItems = array();
			// $this->CountTerms = array();
			// Don't do it if not needed
			$params = JComponentHelper::getParams('com_seoglossary');
			$event=$params->get('event',1);
			$disable_cmd = $params->get('disable_cmd', '{seog:disable}');
			// $text=str_replace('0','ZEROTOREPLACE',$text);
			if (stripos($text, $disable_cmd) !== FALSE)
				{
				return str_replace($disable_cmd, '', $text);
				}
			foreach($glossaryItems as $v)
				{
				if (JFactory::getApplication()->input->getInt('diagnose', 0) == 1)
					{
					echo "<b>Replacing context for {$v['phrase']}</b><br />";
					}
				if (isset($v['link']) && !empty($v['link'])) $link = $v['link'];
				$phrase = $v['phrase'];
				$explanation = $v['explanation'];
				if ($params->get('show_jhtml', 1) == 0)
					{
					$explanation = trim(strip_tags($explanation));
					}
				$explanation = str_replace('"', "'", $explanation);
				if ($params->get('disable_tooltips', 0) == 1)
					{
					$explanation = $phrase;
					}
				  else
					{
					$explanation = self::applyShortcuts($explanation, $phrase);
					}
				$open_tag = "<div id=\"seoglossary-cleanup\">";
				$close_tag = "</div>";
				$full_text = "{$open_tag}{$explanation}{$close_tag}";
				$doc = new DOMDocument();
				$doc->formatOutput = TRUE;
				$doc->substituteEntities = TRUE;
				$doc->recover = TRUE;
				$doc->resolveExternals = TRUE;
				$doc->strictErrorChecking = FALSE;
				$doc->encoding = 'UTF-8';
				@$doc->loadHTML(mb_convert_encoding($full_text, 'HTML-ENTITIES', 'UTF-8'));
				$doc->normalizeDocument();
				$tooltip_layout = $params->get('tooltip_layout', 1);
			
					$str = '';
					$datatipposition = $params->get('datatipposition', '');
					$datatipmaxwidth = $params->get('datatipmaxwidth', '');
					$datatiptheme = $params->get('datatiptheme', 'tipthemeflatdarklight');
					$datatipanimation = $params->get('datatipanimation', '');
					$datatipanimationout = $params->get('datatipanimationout', '');
					$datatipfollowcursor = $params->get('datatipfollowcursor', '');
					$datatipeventout = $params->get('datatipeventout', 'mouseout');
					$datatipdelay = $params->get('datatipdelay', '500');
					$showclose = $params->get('showclose', '0');
				if ($tooltip_layout == 1)
					{
      				if ($datatipposition)
						{
						$str.= 'data-tipposition="' . $datatipposition . '"';
						}
					if ($datatipmaxwidth)
						{
						$str.= 'data-tipmaxwidth="' . $datatipmaxwidth . '"';
						}
					if ($datatiptheme)
						{
						$str.= 'data-tiptheme="' . $datatiptheme . '"';
						}
					if ($datatipanimation)
						{
						$str.= 'data-tipanimation="' . $datatipanimation . '"';
						}
					if ($datatipanimationout)
						{
						$str.= 'data-tipanimationout="' . $datatipanimationout . '"';
						}
					if ($datatipdelay)
						{
						$str.= 'data-tipdelayclose="' . ($datatipdelay) . '"';
						}
					
					$str.= 'data-tipmouseleave="false"';
					$str.= 'data-tipcontent="html"';
					if ($event == 0){
						$str.= ' data-tipfollowcursor="false" data-tipevent="click" data-tipeventout="click" ';
					}
					else
					{
						if ($datatipeventout)
						{
						$str.= 'data-tipeventout="' . $datatipeventout . '"';
						}
					}
					}
				$imgs = $doc->getElementsByTagName('img');
				foreach($imgs as $img)
					{
					if ($img->hasAttributes() && $img->attributes->getNamedItem('src') !== NULL)
						{
						$src = $img->attributes->getNamedItem('src')->value;
						if (stripos($src, 'http') !== 0 && stripos($src, '/') !== 0)
							{
							$src = JUri::root() . $src;
							@$img->attributes->getNamedItem('src')->value = $src;
							}
						}
					}
				$videos = $doc->getElementsByTagName('video');
				foreach($videos as $video)
					{
					if ($video->hasAttributes() && $video->attributes->getNamedItem('src') !== NULL)
						{
						$src = $video->attributes->getNamedItem('src')->value;
						if (stripos($src, 'http') !== 0 && stripos($src, '/') !== 0)
							{
							$src = JUri::root() . $src;
							$video->attributes->getNamedItem('src')->value = $src;
							}
						}
					}
				$doc->normalizeDocument();
				$html = $doc->saveHTML();
				$html = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace(array(
					'<html>',
					'</html>',
					'<body>',
					'</body>'
				) , array(
					'',
					'',
					'',
					''
				) , $html));
				$html = trim($html);
				$explanation = substr($html, strlen($open_tag) , strlen($html) - (strlen($open_tag) + strlen($close_tag)));
				if (!defined('ENT_HTML401'))
					{
					define('ENT_HTML401', 0);
					}
				$explanation = htmlentities($explanation, ENT_QUOTES | ENT_HTML401, 'UTF-8', FALSE);
				$urlattribute = "";
				if (@$v['nofollow'] == 1)
					{
					$urlattribute.= ' rel="nofollow" ';
					}
				if (@$v['target'] == 1)
					{
					$urlattribute.= ' target="_blank" ';
					}
				if ($tooltip_layout == 0)
					{
					$this->Terms[$phrase] = (isset($link) && !empty($link)) ? '<span class="mytool"><a href="' . $link . '" title="' . $explanation . '" ' . $urlattribute . '>[[phrase]]</a></span>' : '<span class="mytool"><abbr title="' . $explanation . '">[[phrase]]</abbr></span>';
					}
				  else if ($tooltip_layout == 2)
					{
					$datatipposition = $params->get('datatipposition', '');
					$position_layout = "";
					if ($datatipposition == "")
						{
						$position_layout = "top";
						}
					  else if ($datatipposition == "tipright")
						{
						$position_layout = "right";
						}
					  else if ($datatipposition == "tipbottom")
						{
						$position_layout = "bottom";
						}
					  else if ($datatipposition == "tipleft")
						{
						$position_layout = "left";
						}
						if (version_compare(JVERSION, '4.0', 'ge'))
				{
					
					$this->Terms[$phrase] = (isset($link) && !empty($link)) ? '<span class="mytool"><a href="' . $link . '" data-toggle="popover" title="' . $phrase . '" data-bs-placement="' . $position_layout . '"  class="hasPopover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="' . $explanation . '" ' . $urlattribute . '>[[phrase]]</a></span>' : '<span class="mytool"><abbr data-bs-toggle="popover" data-bs-placement="' . $position_layout . '" data-s-trigger="hover"  class="hasPopover" data-bs-html="true" title="' . $phrase . '" data-bs-content="' . $explanation . '">[[phrase]]</abbr></span>';
				
				}
				else{
					
						$this->Terms[$phrase] = (isset($link) && !empty($link)) ? '<span class="mytool"><a href="' . $link . '" data-toggle="popover" title="' . $phrase . '" data-placement="' . $position_layout . '"  class="hasPopover" data-trigger="hover" data-html="true" data-content="' . $explanation . '" ' . $urlattribute . '>[[phrase]]</a></span>' : '<span class="mytool"><abbr data-toggle="popover" data-placement="' . $position_layout . '" data-trigger="hover"  class="hasPopover" data-html="true" title="' . $phrase . '" data-content="' . $explanation . '">[[phrase]]</abbr></span>';
				}
					}
     else if($tooltip_layout == 3)
     {
    $this->Terms[$phrase] = (isset($link) && !empty($link)) ? '<span class="mytool"><a href="' . $link . '" title="' . $explanation . '" ' . $urlattribute . '>[[phrase]]</a></span>' : '<span class="mytool"><abbr title="' . $explanation . '">[[phrase]]</abbr></span>';
    
     }
				  else
					{
					if ($showclose == 1)
						{
						$explanation = "<span style='float:right;cursor:pointer;' onClick='javascript:closeJQTip(" . self::$q . ")'><img src='" . JURI::root(true) . "/components/com_seoglossary/assets/images/closebox.png' alt='x'></span>" . $explanation;
						}
					$this->Terms[$phrase] = (isset($link) && !empty($link)) ? '<span ><a href="' . $link . '" data-tipcontent="' . $explanation . '" ' . $str . '  class="jqeasytooltip jqeasytooltip' . self::$q . '" id="jqeasytooltip' . self::$q . '" ' . $urlattribute . ' title="' . $phrase . '">[[phrase]]</a></span>' : '<span><abbr data-tipcontent="' . $explanation . '" ' . $str . ' class="jqeasytooltip" id="jqeasytooltip' . self::$q . '" title="' . $phrase . '">[[phrase]]</abbr></span>';
					self::$q = self::$q + 1;
					}
				$key = md5($phrase . $this->Terms[$phrase]);
				$synonym_of = $v['synonym_of'];
				/*if (!empty($synonym_of)) {
				$parent_key =md5($synonym_of.$this->Terms[$synonym_of]);
				$this->CountTerms[$key] = &$this->CountTerms[$parent_key];
				} else {
				$this->CountTerms[$key] = 0;
				}*/
				}
			if ($this->Terms)
				{
				$this->Text = '';
				$this->Tags = array();
				$this->PartialText = '';
				$parser = xml_parser_create('utf-8');
				xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
				xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
				xml_set_object($parser, $this);
				xml_set_element_handler($parser, "start_tag", "end_tag");
				xml_set_character_data_handler($parser, "data");
				// Clean the text from its entities
				$text = "<div>{$text}</div>";
				$doc = new DOMDocument();
				$doc->formatOutput = TRUE;
				$doc->substituteEntities = TRUE;
				$doc->recover = TRUE;
				$doc->resolveExternals = TRUE;
				$doc->strictErrorChecking = FALSE;
				$doc->encoding = 'UTF-8';
				$text = str_replace('&lt;', '__lt__', $text);
				$text = str_replace('&gt;', '__gt__', $text);
				$text = mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8');
				@$doc->loadHTML($text);
				$doc->normalizeDocument();
				$xml = $doc->saveXML();
				// $xpath = new DOMXPath($doc);
				// $elements = $xpath->query("/html/body/div[1]");
				// if (!is_null($elements) && $elements->length == 1) {
				// $body = $elements->item(0);
				// $outDoc = new DOMDocument();
				// $outDoc->appendChild($outDoc->importNode($body, true));
				// $text = trim($outDoc->saveHTML());
				// }
				// End cleaning
				$xml_old = '<?xml version="1.0" encoding="UTF-8"?>
 
      <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" [
 
       <!ENTITY quot "&#34;">
 
       <!ENTITY amp "&#38;">
 
       <!ENTITY apos "&#39;">
 
       <!ENTITY lt "&#60;">
 
       <!ENTITY gt "&#62;">
 
       <!ENTITY quot "&#34;">
 
       <!ENTITY amp "&#38;">
 
       <!ENTITY apos "&#39;">
 
       <!ENTITY lt "&#60;">
 
       <!ENTITY gt "&#62;">
 
       <!ENTITY nbsp "&#160;">
 
       <!ENTITY iexcl "&#161;">
 
       <!ENTITY cent "&#162;">
 
       <!ENTITY pound "&#163;">
 
       <!ENTITY curren "&#164;">
 
       <!ENTITY yen "&#165;">
 
       <!ENTITY brvbar "&#166;">
 
       <!ENTITY sect "&#167;">
 
       <!ENTITY uml "&#168;">
 
       <!ENTITY copy "&#169;">
 
       <!ENTITY ordf "&#170;">
 
       <!ENTITY laquo "&#171;">
 
       <!ENTITY not "&#172;">
 
       <!ENTITY shy "&#173;">
 
       <!ENTITY reg "&#174;">
 
       <!ENTITY macr "&#175;">
 
       <!ENTITY deg "&#176;">
 
       <!ENTITY plusmn "&#177;">
 
       <!ENTITY sup2 "&#178;">
 
       <!ENTITY sup3 "&#179;">
 
       <!ENTITY acute "&#180;">
 
       <!ENTITY micro "&#181;">
 
       <!ENTITY para "&#182;">
 
       <!ENTITY middot "&#183;">
 
       <!ENTITY cedil "&#184;">
 
       <!ENTITY sup1 "&#185;">
 
       <!ENTITY ordm "&#186;">
 
       <!ENTITY raquo "&#187;">
 
       <!ENTITY frac14 "&#188;">
 
       <!ENTITY frac12 "&#189;">
 
       <!ENTITY frac34 "&#190;">
 
       <!ENTITY iquest "&#191;">
 
       <!ENTITY Agrave "&#192;">
 
       <!ENTITY Aacute "&#193;">
 
       <!ENTITY Acirc "&#194;">
 
       <!ENTITY Atilde "&#195;">
 
       <!ENTITY Auml "&#196;">
 
       <!ENTITY Aring "&#197;">
 
       <!ENTITY AElig "&#198;">
 
       <!ENTITY Ccedil "&#199;">
 
       <!ENTITY Egrave "&#200;">
 
       <!ENTITY Eacute "&#201;">
 
       <!ENTITY Ecirc "&#202;">
 
       <!ENTITY Euml "&#203;">
 
       <!ENTITY Igrave "&#204;">
 
       <!ENTITY Iacute "&#205;">
 
       <!ENTITY Icirc "&#206;">
 
       <!ENTITY Iuml "&#207;">
 
       <!ENTITY ETH "&#208;">
 
       <!ENTITY Ntilde "&#209;">
 
       <!ENTITY Ograve "&#210;">
 
       <!ENTITY Oacute "&#211;">
 
       <!ENTITY Ocirc "&#212;">
 
       <!ENTITY Otilde "&#213;">
 
       <!ENTITY Ouml "&#214;">
 
       <!ENTITY times "&#215;">
 
       <!ENTITY Oslash "&#216;">
 
       <!ENTITY Ugrave "&#217;">
 
       <!ENTITY Uacute "&#218;">
 
       <!ENTITY Ucirc "&#219;">
 
       <!ENTITY Uuml "&#220;">
 
       <!ENTITY Yacute "&#221;">
 
       <!ENTITY THORN "&#222;">
 
       <!ENTITY szlig "&#223;">
 
       <!ENTITY agrave "&#224;">
 
       <!ENTITY aacute "&#225;">
 
       <!ENTITY acirc "&#226;">
 
       <!ENTITY atilde "&#227;">
 
       <!ENTITY auml "&#228;">
 
       <!ENTITY aring "&#229;">
 
       <!ENTITY aelig "&#230;">
 
       <!ENTITY ccedil "&#231;">
 
       <!ENTITY egrave "&#232;">
 
       <!ENTITY eacute "&#233;">
 
       <!ENTITY ecirc "&#234;">
 
       <!ENTITY euml "&#235;">
 
       <!ENTITY igrave "&#236;">
 
       <!ENTITY iacute "&#237;">
 
       <!ENTITY icirc "&#238;">
 
       <!ENTITY iuml "&#239;">
 
       <!ENTITY eth "&#240;">
 
       <!ENTITY ntilde "&#241;">
 
       <!ENTITY ograve "&#242;">
 
       <!ENTITY oacute "&#243;">
 
       <!ENTITY ocirc "&#244;">
 
       <!ENTITY otilde "&#245;">
 
       <!ENTITY ouml "&#246;">
 
       <!ENTITY divide "&#247;">
 
       <!ENTITY oslash "&#248;">
 
       <!ENTITY ugrave "&#249;">
 
       <!ENTITY uacute "&#250;">
 
       <!ENTITY ucirc "&#251;">
 
       <!ENTITY uuml "&#252;">
 
       <!ENTITY yacute "&#253;">
 
       <!ENTITY thorn "&#254;">
 
       <!ENTITY yuml "&#255;">
 
       <!ENTITY OElig "&#338;">
 
       <!ENTITY oelig "&#339;">
 
       <!ENTITY Scaron "&#352;">
 
       <!ENTITY scaron "&#353;">
 
       <!ENTITY Yuml "&#376;">
 
       <!ENTITY fnof "&#402;">
 
       <!ENTITY circ "&#710;">
 
       <!ENTITY tilde "&#732;">
 
       <!ENTITY Alpha "&#913;">
 
       <!ENTITY Beta "&#914;">
 
       <!ENTITY Gamma "&#915;">
 
       <!ENTITY Delta "&#916;">
 
       <!ENTITY Epsilon "&#917;">
 
       <!ENTITY Zeta "&#918;">
 
       <!ENTITY Eta "&#919;">
 
       <!ENTITY Theta "&#920;">
 
       <!ENTITY Iota "&#921;">
 
       <!ENTITY Kappa "&#922;">
 
       <!ENTITY Lambda "&#923;">
 
       <!ENTITY Mu "&#924;">
 
       <!ENTITY Nu "&#925;">
 
       <!ENTITY Xi "&#926;">
 
       <!ENTITY Omicron "&#927;">
 
       <!ENTITY Pi "&#928;">
 
       <!ENTITY Rho "&#929;">
 
       <!ENTITY Sigma "&#931;">
 
       <!ENTITY Tau "&#932;">
 
       <!ENTITY Upsilon "&#933;">
 
       <!ENTITY Phi "&#934;">
 
       <!ENTITY Chi "&#935;">
 
       <!ENTITY Psi "&#936;">
 
       <!ENTITY Omega "&#937;">
 
       <!ENTITY alpha "&#945;">
 
       <!ENTITY beta "&#946;">
 
       <!ENTITY gamma "&#947;">
 
       <!ENTITY delta "&#948;">
 
       <!ENTITY epsilon "&#949;">
 
       <!ENTITY zeta "&#950;">
 
       <!ENTITY eta "&#951;">
 
       <!ENTITY theta "&#952;">
 
       <!ENTITY iota "&#953;">
 
       <!ENTITY kappa "&#954;">
 
       <!ENTITY lambda "&#955;">
 
       <!ENTITY mu "&#956;">
 
       <!ENTITY nu "&#957;">
 
       <!ENTITY xi "&#958;">
 
       <!ENTITY omicron "&#959;">
 
       <!ENTITY pi "&#960;">
 
       <!ENTITY rho "&#961;">
 
       <!ENTITY sigmaf "&#962;">
 
       <!ENTITY sigma "&#963;">
 
       <!ENTITY tau "&#964;">
 
       <!ENTITY upsilon "&#965;">
 
       <!ENTITY phi "&#966;">
 
       <!ENTITY chi "&#967;">
 
       <!ENTITY psi "&#968;">
 
       <!ENTITY omega "&#969;">
 
       <!ENTITY thetasym "&#977;">
 
       <!ENTITY upsih "&#978;">
 
       <!ENTITY piv "&#982;">
 
       <!ENTITY ensp "&#8194;">
 
       <!ENTITY emsp "&#8195;">
 
       <!ENTITY thinsp "&#8201;">
 
       <!ENTITY zwnj "&#8204;">
 
       <!ENTITY zwj "&#8205;">
 
       <!ENTITY lrm "&#8206;">
 
       <!ENTITY rlm "&#8207;">
 
       <!ENTITY ndash "&#8211;">
 
       <!ENTITY mdash "&#8212;">
 
       <!ENTITY lsquo "&#8216;">
 
       <!ENTITY rsquo "&#8217;">
 
       <!ENTITY sbquo "&#8218;">
 
       <!ENTITY ldquo "&#8220;">
 
       <!ENTITY rdquo "&#8221;">
 
       <!ENTITY bdquo "&#8222;">
 
       <!ENTITY dagger "&#8224;">
 
       <!ENTITY Dagger "&#8225;">
 
       <!ENTITY bull "&#8226;">
 
       <!ENTITY hellip "&#8230;">
 
       <!ENTITY permil "&#8240;">
 
       <!ENTITY prime "&#8242;">
 
       <!ENTITY Prime "&#8243;">
 
       <!ENTITY lsaquo "&#8249;">
 
       <!ENTITY rsaquo "&#8250;">
 
       <!ENTITY oline "&#8254;">
 
       <!ENTITY frasl "&#8260;">
 
       <!ENTITY euro "&#8364;">
 
       <!ENTITY image "&#8465;">
 
       <!ENTITY weierp "&#8472;">
 
       <!ENTITY real "&#8476;">
 
       <!ENTITY trade "&#8482;">
 
       <!ENTITY alefsym "&#8501;">
 
       <!ENTITY larr "&#8592;">
 
       <!ENTITY uarr "&#8593;">
 
       <!ENTITY rarr "&#8594;">
 
       <!ENTITY darr "&#8595;">
 
       <!ENTITY harr "&#8596;">
 
       <!ENTITY crarr "&#8629;">
 
       <!ENTITY lArr "&#8656;">
 
       <!ENTITY uArr "&#8657;">
 
       <!ENTITY rArr "&#8658;">
 
       <!ENTITY dArr "&#8659;">
 
       <!ENTITY hArr "&#8660;">
 
       <!ENTITY forall "&#8704;">
 
       <!ENTITY part "&#8706;">
 
       <!ENTITY exist "&#8707;">
 
       <!ENTITY empty "&#8709;">
 
       <!ENTITY nabla "&#8711;">
 
       <!ENTITY isin "&#8712;">
 
       <!ENTITY notin "&#8713;">
 
       <!ENTITY ni "&#8715;">
 
       <!ENTITY prod "&#8719;">
 
       <!ENTITY sum "&#8721;">
 
       <!ENTITY minus "&#8722;">
 
       <!ENTITY lowast "&#8727;">
 
       <!ENTITY radic "&#8730;">
 
       <!ENTITY prop "&#8733;">
 
       <!ENTITY infin "&#8734;">
 
       <!ENTITY ang "&#8736;">
 
       <!ENTITY and "&#8743;">
 
       <!ENTITY or "&#8744;">
 
       <!ENTITY cap "&#8745;">
 
       <!ENTITY cup "&#8746;">
 
       <!ENTITY int "&#8747;">
 
       <!ENTITY there4 "&#8756;">
 
       <!ENTITY sim "&#8764;">
 
       <!ENTITY cong "&#8773;">
 
       <!ENTITY asymp "&#8776;">
 
       <!ENTITY ne "&#8800;">
 
       <!ENTITY equiv "&#8801;">
 
       <!ENTITY le "&#8804;">
 
       <!ENTITY ge "&#8805;">
 
       <!ENTITY sub "&#8834;">
 
       <!ENTITY sup "&#8835;">
 
       <!ENTITY nsub "&#8836;">
 
       <!ENTITY sube "&#8838;">
 
       <!ENTITY supe "&#8839;">
 
       <!ENTITY oplus "&#8853;">
 
       <!ENTITY otimes "&#8855;">
 
       <!ENTITY perp "&#8869;">
 
       <!ENTITY sdot "&#8901;">
 
       <!ENTITY lceil "&#8968;">
 
       <!ENTITY rceil "&#8969;">
 
       <!ENTITY lfloor "&#8970;">
 
       <!ENTITY rfloor "&#8971;">
 
       <!ENTITY lang "&#9001;">
 
       <!ENTITY rang "&#9002;">
 
       <!ENTITY loz "&#9674;">
 
       <!ENTITY spades "&#9824;">
 
       <!ENTITY clubs "&#9827;">
 
       <!ENTITY hearts "&#9829;">
 
       <!ENTITY diams "&#9830;">
 
      ]>
 
      <html>' . $text . '</html>';
				$parsedText = '';
				if (!xml_parse($parser, $xml, true))
					{
					$error = xml_get_error_code($parser);
					if (JFactory::getApplication()->input->getInt('diagnose', 0) == 1)
						{
						echo ("<b>SEOGlossary: xmlclient error: ($error) " . xml_error_string($error) . "<br/>Line: " . xml_get_current_line_number($parser) . "<br/>Col:" . xml_get_current_column_number($parser) . "</b>\r\n");
						$ar = explode("\n", $xml);
						echo ('<!-- ' . str_replace("!", "\\!", $ar[xml_get_current_line_number($parser) - 1]) . '-->');
						}
					}
				  else
					{
					if (!empty($this->Text))
						{
						$this->flushPartialText();
						$parsedText = $this->Text;
						}
					}
				xml_parser_free($parser);
				}
			if (empty($parsedText))
				{
				// It failed
				if (JFactory::getApplication()->input->getInt('diagnose', 0) == 1)
					{
					echo "<b>Received empty parsed text.</b><br />";
					}
				return $text;
				}
			// Remove wrapping <div></div>
			$parsedText = trim($parsedText);
			$parsedText = str_replace('__lt__', '&lt;', $parsedText);
			$parsedText = str_replace('__gt__', '&gt;', $parsedText);
			if (JFactory::getApplication()->input->getInt('diagnose', 0) == 1)
				{
				echo "<b>Parsed text : {$parsedText}</b><br />\n";
				}
			$trimmed = substr($parsedText, 5, strlen($parsedText) - 11);
			if (JFactory::getApplication()->input->getInt('diagnose', 0) == 1)
				{
				echo "<b>Trimmed text : {$trimmed}</b><br />\n";
				}
			// $trimmed=str_replace('ZEROTOREPLACE','0',$trimmed);
			return $trimmed;
			}
		function start_tag($parser, $name, $attr)
			{
			if (strtolower($name) == 'html' || strtolower($name) == 'body') return;
			$this->flushPartialText();
			$this->Tags[] = strtolower($name);
			$this->Text.= "<$name";
			if ($attr)
				{
				foreach($attr as $i => $v) $this->Text.= ' ' . $i . '="' . htmlspecialchars($v) . '"';
				}
			if (strtolower($name) == 'br' || strtolower($name) == 'hr' || strtolower($name) == 'img')
				{
				$this->Text.= ' />';
				return;
				}
			$this->Text.= '>';
			}
		function data($parser, $text)
			{
			if (!$text) return;
			// $text = htmlspecialchars($text, ENT_QUOTES, "UTF-8");
			if (strpos($text, json_decode('"\u00A0"')) !== FALSE)
				{
				$text = str_replace(json_decode('"\u00A0"') , "&nbsp;", $text);
				}
			$this->PartialText.= $text;
			}
		private $ignoredTags = array();
		function flushPartialText()
			{
			if (!empty($this->PartialText))
				{
				if (JFactory::getApplication()->input->getInt('diagnose', 0) == 1)
					{
					echo "<b>Flushing partial text {$this->PartialText}.</b><br />";
					}
				if (count($this->ignoredTags) == 0)
					{
					$params = JComponentHelper::getParams('com_seoglossary');
					$ignoredTags = $params->get('ignored_tags', 'a, abbr');
					$ignoredTags = explode(',', $ignoredTags);
					foreach($ignoredTags as $tag)
						{
						$this->ignoredTags[] = trim($tag);
						}
					}
				$dontReplace = FALSE;
				foreach($this->ignoredTags as $tag)
					{
					if (array_search($tag, $this->Tags) !== FALSE)
						{
						$dontReplace = TRUE;
						}
					}
				// echo "<b>Flushing</b> :<br /> ".$this->PartialText."<br />\n";
				if (!$dontReplace && array_search('a', $this->Tags) === false && array_search('abbr', $this->Tags) === false)
					{
					$this->Text.= $this->replaceTerms($this->PartialText);
					}
				  else
					{
					$this->Text.= $this->PartialText;
					}
				$this->PartialText = '';
				}
			}
		function end_tag($parser, $name)
			{
			$this->flushPartialText();
			array_pop($this->Tags);
			if (strtolower($name) == 'html' || strtolower($name) == 'body' || strtolower($name) == 'br' || strtolower($name) == 'hr' || strtolower($name) == 'img') return;
			$this->Text.= "</$name>";
			}
		function processimage($text)
			{
			$params = JComponentHelper::getParams('com_seoglossary');
			if ($params->get('show_jhtml', 1) == 0)
				{
				$text = trim(strip_tags(str_replace("\n", ' ', str_replace("\r", '', ($text)))));
				}
			return ($text);
			}
		static function getGlossariesList()
			{
			$db = JFactory::getDBO();
			$lang = JFactory::getLanguage();
			$tag = $lang->getTag();
			$current_id = JFactory::getApplication()->input->getInt('catid', null);
			$letter = JFactory::getApplication()->input->getString('letter', null);
			$query = "SELECT id, name, description FROM #__seoglossaries WHERE state = 1 AND (language = '*' OR language LIKE '$tag') ORDER BY ordering;";
			$db->setQuery($query);
			$nb = 0;
			$output = '<div class="custom-select bigselect"><select name="glossarysearchcategory" class="seoselect" onChange="window.location.href=this.value">';
			foreach($db->loadObjectList() as $glossary)
				{
				$nb++;
				$class = "seoglGlossaryCurrentLink";
				$selected = "";
				if ($glossary->id == $current_id)
					{
					$selected = "selected";
					}
				$Itemid = SeoglossaryHelper::getGlossaryMenu($glossary->id);
				
				$item_id="";
				if($Itemid)
				{
					$item_id='&Itemid='.$Itemid;
					
				}
				$url = JRoute::_('index.php?option=com_seoglossary&view=glossaries&catid=' . $glossary->id.$item_id);
				$output.= '<option value="' . $url . '" ' . $selected . '>';
				$output.= $glossary->name;
				}
			$output.= '</select></div>';
			if ($nb > 0) return '<h3>' . JText::_('COM_SEOGLOSSARY_TITLE_CATGLOSSARIES') . '</h3>' . $output;
			return '';
			}
		public static function getUrlLink($item, $itemid = 0)

			{
			if ((@$item->extlink) && ($item->exturl))
				{
				if (strpos($item->exturl, 'menu:') !== false)
					{
					$link = explode(":", $item->exturl);
					$menuitem = intval($link[1]);
					$url = JRoute::_('index.php?Itemid=' . $menuitem);
					}
				  else if (strpos($item->exturl, 'k2:') !== false)
					{
					$link = explode(":", $item->exturl);
					require_once (JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_k2' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'route.php');

					require_once (JPATH_SITE . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'com_k2' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'utilities.php');

					$db = JFactory::getDbo();
					$query = 'SELECT i.*, c.name AS categoryname,c.id AS categoryid, c.alias AS categoryalias, c.params AS categoryparams FROM #__k2_items as i 		LEFT JOIN #__k2_categories c ON c.id = i.catid where i.id=' . (int)$link[1];
					$db->setQuery($query);
					$item = $db->loadObject();
					$url = urldecode(JRoute::_(K2HelperRoute::getItemRoute($item->id . ':' . urlencode($item->alias) , $item->catid . ':' . urlencode($item->categoryalias))));
					}
				  else
					{
					$url = $item->exturl;
					}
				}
			  else if ($itemid == 0)
				{
					$Itemid=SeoglossaryHelper::getGlossaryMenu( $item->catid);
					$item_id="";
				if($Itemid)
				{
					$item_id='&Itemid='.$Itemid;
				}
				$url = JRoute::_('index.php?option=com_seoglossary&view=glossary&catid=' . $item->catid . '&id=' . $item->id.$item_id);
				}
			  else
				{
				$url = JRoute::_('index.php?option=com_seoglossary&view=glossary&catid=' . $item->catid . '&id=' . $item->id . '&Itemid=' . $itemid);
				}
			return $url;
			}
			public static function getGlossaryMenu($catid)
			{
				 if (@self::$itemid[$catid]) {
				 return self::$itemid[$catid];
				 }
				 else
				 {
					
					$db=Jfactory::getDBo();
					$lang="";
				if(JLanguageMultilang::isEnabled())
				{
					$lang='AND  language in (' . $db->quote(JFactory::getLanguage()->getTag()) . ',' . $db->quote('*') . ')';
				}
					$sql = "SELECT id FROM #__menu WHERE published = 1 AND (link = 'index.php?option=com_seoglossary&view=glossaries&catid={$catid}' OR link LIKE  'index.php?option=com_seoglossary&view=glossaries&catid={$catid}&%' OR link LIKE  'index.php?option=com_seoglossary&view=glossaries&%catid={$catid}' ) ".$lang." LIMIT 0,1;";
					$db->setQuery($sql);
				$id = $db->loadResult();
				
				if($id)
				{
				self::$itemid[$catid]=$id;
				}else
				{
				self::$itemid[$catid]="";
					
				}
				return self::$itemid[$catid];
				 }
			}
		public static function loadLanguage()

			{
			$lang = JFactory::getLanguage();
			$lang->load('com_seoglossary', JPATH_ADMINISTRATOR . '/components/com_seoglossary', null, true);
			$lang->load('com_seoglossary.override', JPATH_ADMINISTRATOR . '/components/com_seoglossary', null, true);
			$lang->load('com_seoglossary.sys', JPATH_ADMINISTRATOR . '/components/com_seoglossary', null, true);
			}
			public static function checkCms()
			{
        if (version_compare(JVERSION, '4.0', 'ge'))
				{
					return true;
        }
        else
        {
            return false;
        }
        
			}
		}
	}
