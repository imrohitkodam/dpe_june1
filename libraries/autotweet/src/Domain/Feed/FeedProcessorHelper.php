<?php

/*
 * @package     Perfect Publisher
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 */

defined('_JEXEC') || exit;

/**
 * FeedProcessorHelper class.
 *
 * @since       1.0
 */
class FeedProcessorHelper
{
    private static $_params;

    private $_search;

    private $_replace;

    private $_regex;

    private $_regplace;

    private $_clean_config;

    private $_clean_whitelistmode;

    private $_alltext;

    private $_rootUrl;

    private $_clean_feed_config;

    private $_spec;

    private $_shorturl_always = false;

    private static $_tags = null;

    private static $_hook_tag = null;

    private $_logger;

    /**
     * FeedHelper.
     */
    public function __construct()
    {
        self::$_params = null;

        $this->_shorturl_always = EParameter::getComponentParam(CAUTOTWEETNG, 'shorturl_always', 1);

        $this->_logger = AutotweetLogger::getInstance();
    }

    /**
     * getHookTag.
     *
     * @return string
     */
    public static function getHookTag()
    {
        return self::$_hook_tag;
    }

    /**
     * getParams.
     *
     * @return string
     */
    public static function getParams()
    {
        return self::$_params;
    }

    /**
     * setParams.
     *
     * @param object &$params Params
     */
    public static function setParams(&$params)
    {
        self::$_params = $params;
    }

    /**
     * getAllText.
     *
     * @return string
     */
    public function getAllText()
    {
        return $this->_alltext;
    }

    /**
     * setAllText.
     *
     * @param string $alltext Params
     */
    public function setAllText($alltext)
    {
        $this->_alltext = $alltext;
    }

    /**
     * getHash.
     *
     * @param object $item Params
     */
    public static function getHash($item)
    {
        return md5($item->get_id());
    }

    /**
     * process.
     *
     * @param object &$feed         Params
     * @param object &$loadResult   Params
     * @param bool   $onlyFirstItem Params
     *
     * @return array
     */
    public function process(&$feed, &$loadResult, $onlyFirstItem = false)
    {
        if ((empty($loadResult)) || (!isset($loadResult->title)) || (!isset($loadResult->items))) {
            return [];
        }

        $this->setParams($feed->xtform);
        $this->routeHelp = RouteHelp::getInstance();

        $this->_textAdjustmentsInitialization();
        $this->_textCleaningInitialization();
        $this->_htmlCleaningInit();
        FeedTextHelper::hookTagCleaningInit();

        $articles = [];

        $feedTitle = $loadResult->title;
        $items = $loadResult->items;

        $this->_logger->log(
            \Joomla\CMS\Log\Log::INFO,
            'FeedProcessorHelper process: '
                .$feedTitle
        );

        $i = 0;

        if (empty($items)) {
            return [];
        }

        foreach ($items as $item) {
            if (($onlyFirstItem) && (!empty($articles))) {
                return $articles;
            }

            FeedTextHelper::hookTagCleaningItemInit();

            $article = new FeedContent();

            // Basic Initialization
            $article->cat_id = self::$_params->get('cat_id');
            $article->access = self::$_params->get('access');
            $article->featured = self::$_params->get('front_page');
            $article->language = self::$_params->get('language');

            // Hash
            $hash = $this->getHash($item);
            $article->hash = $hash;

            // Permalink
            $permalink = $item->get_permalink();

            if (self::$_params->get('solve_redirection')) {
                $permalink = $this->processSolveRedirection($permalink);
            }

            $article->permalink = $permalink;

            // Category Term
            $category_term = $item->get_category();
            $article->category_term = null;

            if (isset($category_term->term)) {
                $article->category_term = $category_term->term;
            }

            // FeedItemBase
            preg_match('#^[a-zA-Z\d\-+.]+://[^/]+#', $permalink, $matches);
            $feedItemBase = $matches[0].'/';
            $article->feedItemBase = $feedItemBase;

            $this->_clean_feed_config['base_url'] = $feedItemBase;

            // NamePrefix
            $namePrefix = $hash.'_';
            $article->namePrefix = $namePrefix;

            // Feed Text
            $theText = $this->_createFeedText($item);

            // Default Intro
            if (empty($theText)) {
                $theText = self::$_params->get('introtext');
            }

            // Feed Title
            $title = $item->get_title();

            $this->_logger->log(
                \Joomla\CMS\Log\Log::INFO,
                'FeedProcessorHelper process: '
                    .$title
                    .' - '
                    .$permalink
            );

            // Get external fulltext
            if (self::$_params->get('fulltext')) {
                $readability_result = $this->_getFullText($permalink);

                if ($readability_result) {
                    $theText = $readability_result->content;

                    if (self::$_params->get('readability_title')) {
                        $title = $readability_result->title;
                    }
                }
            }

            // Text Cleaning
            $theText = $this->_htmlCleaning($theText);

            // Test for empty content
            if ((!self::$_params->get('ignore_empty_intro')) && (empty($theText))) {
                $this->_logger->log(
                    \Joomla\CMS\Log\Log::INFO,
                    'FeedProcessorHelper process: '
                        .$article->title
                        .' - Empty intro!'
                );

                continue;
            }

            // Title & alias
            $article->title = $this->_createTitle($title, $feedTitle, $theText, $hash);
            $article->alias = $this->_createAlias($article->title);

            if ($this->_isDuplicated($article)) {
                $this->_logger->log(
                    \Joomla\CMS\Log\Log::INFO,
                    'FeedProcessorHelper isDuplicated: '
                        .$article->title
                        .' - Duplicated!'
                );

                continue;
            }

            // Black White Listing Control
            $article->blacklisted = false;
            $article->whitelisted = false;

            // Check item filtering
            if (self::$_params->get('filtering')) {
                $alltext = [];
                $alltext[] = $article->title;
                $alltext[] = $theText;
                $alltext[] = $article->category_term;
                $this->_alltext = strtolower(implode(' ', $alltext));

                if (self::$_params->get('filter_category_term')) {
                    if ($article->category_term !== self::$_params->get('filter_category_term')) {
                        continue;
                    }
                }

                if (self::$_params->get('filter_blacklist')) {
                    $article->blacklisted = $this->_checkBlackListed();

                    if ($article->blacklisted) {
                        if (self::$_params->get('save_filter_result')) {
                            $this->_logger->log(
                                \Joomla\CMS\Log\Log::INFO,
                                'FeedProcessorHelper process: '
                                    .$article->title
                                    .' - Blacklisted!'
                            );
                        }

                        continue;
                    }
                }

                if (self::$_params->get('filter_whitelist')) {
                    $article->whitelisted = $this->_checkWhiteListed();

                    if (!$article->whitelisted) {
                        if (self::$_params->get('save_filter_result')) {
                            $this->_logger->log(
                                \Joomla\CMS\Log\Log::INFO,
                                'FeedProcessorHelper process: '
                                    .$article->title
                                    .' - Not Whitelisted!'
                            );
                        }

                        continue;
                    }
                }
            }

            // Set Creator/Author
            $article->created_by = $this->_getCreatedBy();
            $author = $item->get_author();
            $article->created_by_alias = $this->_getCreatedByAlias($author, $article->created_by, $feedTitle);

            // Process Feed Images
            $article->images = $this->_processImages($permalink, $theText);

            // Enclosures - (!self::$_params->get('create_art', 1
            if (self::$_params->get('process_enc')) {
                $enclosures = $item->get_enclosures();
                $enclosures = $this->_processEnclosures($enclosures);
                $article->enclosures = $enclosures;
            }

            $article->showEnclosureImage = (
                (self::$_params->get('process_enc_images'))
                    && (0 === count($article->images))
                    && (count($article->enclosures))
            );

            if ($article->showEnclosureImage) {
                $article->images = $this->_setDefaultEnclosureImage($article->enclosures);

                // Ups, no image in enclosures
                if (empty($article->images)) {
                    $article->showEnclosureImage = false;
                }
            }

            // Get Image from Text
            $onlyFirstValid = false;
            $article->showImageFromText = false;

            if ((empty($article->images)) && (self::$_params->get('imagefromtext'))) {
                $htmlForImages = $this->_getFullText($permalink, true);

                $this->_logger->log(
                    \Joomla\CMS\Log\Log::INFO,
                    'FeedProcessorHelper imagefromtext: length='.strlen($htmlForImages),
                    $htmlForImages
                );

                if ($htmlForImages) {
                    $article->images = $this->_processImages($permalink, $htmlForImages);

                    $this->_logger->log(
                        \Joomla\CMS\Log\Log::INFO,
                        'FeedProcessorHelper images:',
                        $article->images
                    );

                    $onlyFirstValid = true;
                    $article->showImageFromText = true;
                }
            }

            $article->showDefaultImage = ((self::$_params->get('img')) && (0 === count($article->images)));

            // Set Default Image
            if ($article->showDefaultImage) {
                $article->images = $this->_setDefaultImage();
            }

            $this->_validateImages($article->images, $onlyFirstValid);

            if ((empty($article->images))
                && ($article->featured)
                && (self::$_params->get('featured_with_image'))) {
                $article->featured = 0;
            }

            if ((!empty($article->images))
                && (!$article->featured)
                && (self::$_params->get('featured_with_image'))) {
                $article->featured = 1;
            }

            $article->introtext = $this->_trimText($theText, self::$_params->get('trim_to'), self::$_params->get('trim_type'));

            [$article->introtext, $article->fulltext] = $this->_onlyIntro($article->introtext, $theText);
            $article->introtext = $this->_dotDotDot($article->introtext);

            // Shortlink (or not)
            $article->shortlink = $article->permalink;

            if ((!empty($permalink)) && ($this->_shorturl_always) && (self::$_params->get('shortlink'))) {
                $article->shortlink = ShorturlHelper::getInstance()->getShortUrl($article->permalink);
            }

            // Category
            if ($category = $item->get_category()) {
                $article->metakey .= $category->get_label();
            }

            // Publication state and dates
            $this->_setPublicationState($article, $item->get_date());

            $articles[] = $article;
            ++$i;

            // End Item Processing
        }

        return $articles;
    }

    /**
     * processSolveRedirection.
     *
     * @param string $url         Params
     * @param int    $maxredirect Params
     *
     * @return string
     */
    private function processSolveRedirection($url, $maxredirect = 5)
    {
        if (0 === (int) $maxredirect) {
            return $url;
        }

        $ch = curl_init($url);
        curl_setopt($ch, \CURLOPT_HEADER, true);
        curl_setopt($ch, \CURLOPT_NOBODY, true);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, false);
        $response = curl_exec($ch);

        $mr = $maxredirect;

        $newurl = curl_getinfo($ch, \CURLINFO_EFFECTIVE_URL);

        $rch = curl_copy_handle($ch);
        curl_close($ch);

        curl_setopt($rch, \CURLOPT_HEADER, true);
        curl_setopt($rch, \CURLOPT_NOBODY, true);
        curl_setopt($rch, \CURLOPT_FORBID_REUSE, false);
        curl_setopt($rch, \CURLOPT_RETURNTRANSFER, true);

        do {
            curl_setopt($rch, \CURLOPT_URL, $newurl);
            $header = curl_exec($rch);

            if (curl_errno($rch)) {
                $code = 0;
            } else {
                $code = (int) curl_getinfo($rch, \CURLINFO_HTTP_CODE);

                if (301 === $code || 302 === $code) {
                    preg_match('/Location:(.*?)\n/', $header, $matches);
                    $newurl = trim(array_pop($matches));
                } else {
                    $code = 0;
                }
            }
        } while ($code && --$mr);

        curl_close($rch);

        if (!$mr) {
            if (null === $maxredirect) {
                trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', \E_USER_WARNING);

                return $url;
            }

            $maxredirect = 0;

            return $url;
        }

        return $newurl;
    }

    /**
     * _createFeedText.
     *
     * @param object $item Params
     *
     * @return string
     */
    private function _createFeedText($item)
    {
        // This will get full text if available in feed or return description if no full text
        switch (self::$_params->get('show_html')) {
            case 0:
                $feedText = $item->get_description();

                break;
            case 2:
                $feedText = $item->get_description().$item->get_content();

                break;
            default:
                // case 1:
                $feedText = $item->get_content();

                break;
        }

        $feedText = trim($feedText);
        $feedText = $this->_adjustText($feedText);

        return $feedText;
    }

    /**
     * _textAdjustmentsInitialization.
     */
    private function _textAdjustmentsInitialization()
    {
        $this->_search = [];
        $this->_replace = [];
        $this->_regex = [];
        $this->_regplace = [];

        // Clean out unwanted text
        if (self::$_params->get('text_filter')) {
            if (self::$_params->get('text_filter_remove')) {
                $this->_search = TextUtil::listToArray(self::$_params->get('text_filter_remove'));

                foreach ($this->_search as $s) {
                    $s = str_replace('[[comma]]', ',', $s);
                    $this->_replace[] = '';
                }
            }

            if (self::$_params->get('text_filter_replace')) {
                $pairs = explode("\n", self::$_params->get('text_filter_replace'));

                foreach ($pairs as $pair) {
                    $pair = explode('===', $pair);
                    $this->_search[] = trim($pair[0]);
                    $this->_replace[] = trim($pair[1]);
                }
            }

            if (self::$_params->get('text_filter_regex')) {
                $pairs = explode("\n", self::$_params->get('text_filter_regex'));

                foreach ($pairs as $pair) {
                    $pair = explode('===', $pair);
                    $this->_regex[] = trim($pair[0]);
                    $this->_regplace[] = trim($pair[1]);
                }
            }
        }
    }

    /**
     * _textCleaningInitialization.
     */
    private function _textCleaningInitialization()
    {
        $this->_clean_config = [];
        $this->_clean_whitelistmode = false;

        $remove_by_attrib = self::$_params->get('remove_by_attrib');

        if (empty($remove_by_attrib)) {
            return;
        }

        if (0 === strpos($remove_by_attrib, '+')) {
            $this->_clean_whitelistmode = true;
            $remove_by_attrib = str_replace('+', '', $remove_by_attrib);
        }

        $parts = TextUtil::listToArray($remove_by_attrib);

        if (0 === count($parts)) {
            return;
        }

        foreach ($parts as $part) {
            $p = explode('=', $part);

            if (2 !== count($p)) {
                return;
            }

            [$key, $value] = $p;

            $p = explode(' ', $key);

            if (2 !== count($p)) {
                return;
            }

            [$tag, $attrib] = $p;

            $tag = trim($tag);
            $attrib = trim($attrib);
            $value = trim($value);

            if (($tag) && ($attrib) && ($value)) {
                $this->_clean_config[$tag][$attrib] = $value;
            }
        }
    }

    /**
     * _htmlCleaningInit.
     */
    private function _htmlCleaningInit()
    {
        $this->_getTagsToStrip();

        $this->_spec = 'img=src,height,width;table=border,width,cellspacing,cellpadding;';

        $this->_clean_feed_config = [
            'abs_url' => 1,
            'comment' => 1,
            'elements' => self::$_tags,
            'hook_tag' => 'hookTagCleaning',
            'tidy' => self::$_params->get('tidy'),
            'valid_xhtml' => (self::$_params->get('xhtml_clean') ? 1 : 0),
            'safe' => 1,
        ];

        if (self::$_params->get('link_nofollow')) {
            $this->_clean_feed_config['anti_link_spam'] = [
                '`.`',
                '',
            ];
        }

        if (self::$_params->get('disallow_attribs')) {
            $this->_clean_feed_config['deny_attribute'] = '* -title -href -target -alt';
        }

        if (self::$_params->get('remove_bad')) {
            $this->_clean_feed_config['keep_bad'] = 6;
        }
    }

    /**
     * _adjustText.
     *
     * @param string $text Params
     *
     * @return string
     */
    private function _adjustText($text)
    {
        // Clean out unwanted text
        if (self::$_params->get('text_filter')) {
            if (isset($this->_search)) {
                // It may cause UTF-8 issues but needed to allow capitalisation to propagate
                $text = str_replace($this->_search, $this->_replace, $text);
            }

            if (isset($this->_regex)) {
                $text = preg_replace($this->_regex, $this->_regplace, $text);
            }
        }

        return $text;
    }

    /**
     * _createTitle.
     *
     * @param string $title     Params
     * @param string $feedTitle Params
     * @param string $feedText  Params
     * @param string $hash      Params
     *
     * @return string
     */
    private function _createTitle($title, $feedTitle, $feedText, $hash)
    {
        $title = trim($title);

        if (empty($title)) {
            // See if feed text might have a likely candidate
            $regex = '#<(?:h1|h2|h3|h4|h5|h6)[^>]*>([\s\S]*?)<\/(?:h1|h2|h3|h4|h5|h6)>#i';
            preg_match($regex, $feedText, $matches);
            $title = $matches[1];

            if (empty($title)) {
                $datenow = \Joomla\CMS\Factory::getDate();
                $title = $feedTitle.' - '.$hash.' - '.$datenow->format(JText::_('COM_AUTOTWEET_DATE_FORMAT'));
            }
        }

        // Replace CR LF and Tabs
        $title = str_replace(["\n", "\r", "\t"], ' ', $title);

        // Fix for long titles and htmlentities - Double encoding
        $title = html_entity_decode($title, \ENT_QUOTES, 'UTF-8');
        $title = html_entity_decode($title, \ENT_QUOTES, 'UTF-8');

        // From JFilterOutput::cleanText
        $title = preg_replace("'<script[^>]*>.*?</script>'si", '', $title);
        $title = preg_replace('/<a\s+.*?href="([^"]+)"[^>]*>([^<]+)<\/a>/is', '', $title);
        $title = preg_replace('/<!--.+?-->/', '', $title);
        $title = preg_replace('/{.+?}/', '', $title);

        // Clean Html Tags
        $title = strip_tags($title);

        // One space
        $title = preg_replace('#\s{2,}#', ' ', $title);

        // Text Replacements and adjustments
        $title = $this->_adjustText($title);

        // No more than 255 chars - Joomla article
        $title = substr($title, 0, 255);

        return $title;
    }

    /**
     * _createAlias.
     *
     * @param string $title Params
     *
     * @return string
     */
    private function _createAlias($title)
    {
        $alias = TextUtil::convertUrlSafe($title);

        $custom_translit = self::$_params->get('custom_translit');

        if (!empty($custom_translit)) {
            $alias = FeedTextHelper::transliterate($alias, $custom_translit);
        }

        // Fix for trailing alias dashes
        $length = strlen($alias);

        if (strrpos($alias, '-') === $length - 1) {
            $alias = substr($alias, 0, $length - 1);
        }

        // Fix for long titles and htmlentities
        $alias = substr($alias, 0, 255);

        return $alias;
    }

    /**
     * getFullText.
     *
     * @param string $permalink   Params
     * @param bool   $extractOnly Params
     *
     * @return string
     */
    private function _getFullText($permalink, $extractOnly = false)
    {
        $result = false;

        try {
            $body = (new XTP_BUILD\Extly\Infrastructure\Support\UrlTools\Browser())->extractPage($permalink);

            if (empty($body)) {
                return false;
            }

            if (function_exists('tidy_parse_string')) {
                $tidy = tidy_parse_string($body, [], 'UTF8');
                $tidy->cleanRepair();
                $body = $tidy->value;
            }

            if ($extractOnly) {
                return $body;
            }

            $readability = new \XTS_BUILD\Readability\Readability($body, $permalink);
            $readability->debug = false;
            $readability->convertLinksToFootnotes = self::$_params->get('link_table');

            if (!$readability->init()) {
                return false;
            }

            $this->_cleanSpecifically($readability);
            $innerHTML = $readability->getContent()->innerHTML;

            if ('<p>Sorry, Readability was unable to parse this page for content.</p>' === $innerHTML) {
                // Failed to Get Source Full Text: Readability unable to parse');
                return false;
            }

            if (function_exists('tidy_parse_string')) {
                $tidy = tidy_parse_string(
                    $innerHTML,
                    [
                        'indent' => true,
                        'show-body-only' => true,
                    ],
                    'UTF8'
                );
                $tidy->cleanRepair();
                $innerHTML = $tidy->value;
            }

            // Got Source Full Text
            $result = new StdClass();
            $result->title = $readability->getTitle()->textContent;

            $text = $this->_adjustText($innerHTML);

            // No Ids or readability, classes pls
            $text = str_replace('id="readability-', 'class="joo-', $text);
            $text = str_replace('readability-', 'joo-', $text);
            $text = str_replace('<h3>References</h3>', '<h3>'.JText::_('COM_AUTOTWEET_VIEW_FEED_REFERENCES').'</h3>', $text);

            $result->content = $text;
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            AutotweetLogger::getInstance()->log(\Joomla\CMS\Log\Log::ERROR, 'PerfectPublisher - '.$error_message);
        }

        return $result;
    }

    /**
     * _cleanSpecifically.
     *
     * Apply filtering to a Readability node looking at all elements of type "tag" with attribute(s) set in params
     *
     * @param object $readability Params
     *
     * @return string
     */
    private function _cleanSpecifically($readability)
    {
        $articleContent = $readability->articleContent;

        foreach ($this->_clean_config as $tag => $attribs) {
            $targetList = $articleContent->getElementsByTagName($tag);

            $n = $targetList->length - 1;

            for ($y = $n; $y >= 0; --$y) {
                foreach ($attribs as $k => $v) {
                    $attr = $targetList->item($y)->getAttribute($k);

                    if ((($this->_clean_whitelistmode) && ($attr !== $v))
                        || ((!$this->_clean_whitelistmode) && ($attr === $v))) {
                        $targetList->item($y)->parentNode->removeChild($targetList->item($y));

                        // Current target removed, not further checking required
                        break;
                    }
                }
            }
        }
    }

    /**
     * _processEnclosures.
     *
     * @param array $enclosures Params
     *
     * @return array
     */
    private function _processEnclosures($enclosures)
    {
        $links = [];
        $resulting_enclosures = [];

        foreach ($enclosures as $enclosure) {
            $link = $enclosure->get_link();

            if (($link) && (!isset($links[$link]))) {
                $e = new StdClass();

                // Protects against duplicate enclosures
                $links[$link] = 1;

                $e->link = $link;
                $e->type = $enclosure->get_type();
                $e->real_type = $enclosure->get_real_type();
                $e->title = $enclosure->get_title();
                $e->caption = $enclosure->get_caption();
                $e->duration = $enclosure->get_duration();
                $e->size = $enclosure->get_size();
                $e->thumbnail = $enclosure->get_thumbnail();
                $e->extension = $enclosure->get_extension();

                $resulting_enclosures[] = $e;
            }
        }

        return $resulting_enclosures;
    }

    /**
     * _checkBlackListed.
     *
     * @return bool
     */
    private function _checkBlackListed()
    {
        $blacklists = self::$_params->get('filter_blacklist');

        if (empty($blacklists)) {
            return false;
        }

        $blacklists = TextUtil::listToArray(strtolower($blacklists));

        return $this->_checkList($blacklists);
    }

    /**
     * _checkWhiteListed.
     *
     * @return bool
     */
    private function _checkWhiteListed()
    {
        $whitelists = self::$_params->get('filter_whitelist');

        if (empty($whitelists)) {
            return false;
        }

        $whitelists = explode(',', strtolower($whitelists));

        return $this->_checkList($whitelists);
    }

    /**
     * _checkListed.
     *
     * @param array $list Params
     *
     * @return bool
     */
    private function _checkList($list)
    {
        if (0 === count($list)) {
            return false;
        }

        foreach ($list as $value) {
            if (false !== strpos($this->_alltext, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * _getCreatedBy.
     *
     * @return string
     */
    private function _getCreatedBy()
    {
        // Set Creator/Author
        $created_by = (int) self::$_params->get('default_author') ?: \Joomla\CMS\Factory::getUser()->get('id');

        if (empty($created_by)) {
            // Get first admin user
            $db = \Joomla\CMS\Factory::getDBO();
            $query = 'SELECT id FROM #__users WHERE sendEmail=1 AND block=0 ORDER by id LIMIT 1';
            $db->setQuery($query);
            $created_by = $db->loadResult();
        }

        return $created_by;
    }

    /**
     * _getCreatedByAlias.
     *
     * @param object $author     Params
     * @param int    $created_by Params
     * @param string $feedTitle  Params
     *
     * @return string
     */
    private function _getCreatedByAlias($author, $created_by, $feedTitle)
    {
        $created_by_alias = null;
        $name = null;

        if ($author) {
            $name = $author->get_name();
        }

        switch (self::$_params->get('save_author')) {
            // Use default alias
            case 1:
                $user = \Joomla\CMS\Factory::getUser($created_by);

                if ($user) {
                    $created_by_alias = $user->get('name');
                }

                break;
            // Use custom alias
            case 2:
                $created_by_alias = self::$_params->get('author_alias');

                break;
            // Use feed author alias, or title
            case 3:
                if ($author) {
                    $created_by_alias = (!empty($name) ? $name : $feedTitle);
                } else {
                    $created_by_alias = $feedTitle;
                }

                break;
            // Use feed author alias, or custom
            case 4:
                if ($author) {
                    $created_by_alias = (!empty($name) ? $name : self::$_params->get('author_alias'));
                } else {
                    $created_by_alias = self::$_params->get('author_alias');
                }

                break;
            default:
                // 0 - -Don't Save-
                break;
        }

        return $created_by_alias;
    }

    /**
     * _processImages.
     *
     * @param string $permalink Params
     * @param string $text      Params
     *
     * @return array
     */
    private function _processImages($permalink, $text)
    {
        $uri = \Joomla\CMS\Uri\Uri::getInstance($permalink);
        $uri->setQuery(null);

        $replace = [];
        $regex = '/<img[^>]*>/';

        $dom = new DomDocument();
        $result = @$dom->loadHTML($text);

        if (!$result) {
            return false;
        }

        $imgs = $dom->getElementsByTagName('img');

        $images = [];
        $loaded_images = [];

        foreach ($imgs as $img) {
            // No Source
            $src = $img->getAttribute('src');

            if ((empty($src)) || (0 === strpos($src, 'data:image'))) {
                continue;
            }

            if (0 === strpos($src, '//')) {
                $src = 'https:'.$src;
            }

            if (!$this->routeHelp->isAbsoluteUrl($src)) {
                $uri->setPath($src);
                $src = $uri->toString();
            }

            // Already loaded
            $loaded = (in_array($src, $loaded_images, true));

            if ($loaded) {
                continue;
            }

            // Local image
            if ($this->routeHelp->isLocalUrl($src)) {
                continue;
            }

            // Not allowed
            if ($this->isDisallowed($src)) {
                continue;
            }

            $image = new FeedImage();
            $image->src = $src;
            $image->title = $img->getAttribute('title');
            $image->alt = $img->getAttribute('alt');

            $rmv_img_style = self::$_params->get('rmv_img_style');

            if (!$rmv_img_style) {
                $image->class = $img->getAttribute('class');
                $image->style = $img->getAttribute('style');
                $image->align = $img->getAttribute('align');
                $image->border = $img->getAttribute('border');
                $image->width = $img->getAttribute('width');
                $image->height = $img->getAttribute('height');
            }

            $img_class = self::$_params->get('img_class');

            if ($img_class) {
                $image->class = self::$_params->get('img_class');
            }

            $img_style = self::$_params->get('img_style');

            if ($img_style) {
                $image->style = self::$_params->get('img_style');
            }

            $images[] = $image;
            $loaded_images[] = $src;
        }

        return $images;
    }

    private function isDisallowed($src)
    {
        // String containing disallowed image sources to help prevent small images
        $disalloweds = ['images.pheedo.com', 'arrow.png'];

        foreach ($disalloweds as $disallowed) {
            $isDisallowed = false !== strpos($src, $disallowed);

            if ($isDisallowed) {
                return true;
            }
        }

        return false;
    }

    /**
     * _htmlCleaning.
     *
     * @param string $text Params
     *
     * @return string
     */
    private function _htmlCleaning($text)
    {
        // Clean feedburner
        $text = preg_replace('#[\n| ]+<a href="http://feeds.feedburner.com/[^"]+"><img src="[^"]+" border="0"></a>#', '', $text);
        $text = preg_replace('#<img src="http://feeds.feedburner.com/[^"]+" height="1" width="1" alt="">#', '', $text);
        $text = preg_replace('#<img src="http://feeds.feedburner.com/[^"]+" border="0">#', '', $text);
        $text = preg_replace('#<div class="feedflare">\n</div>#', '', $text);

        if (strpos($text, '<div class="feedflare">') > 0) {
            $parts = explode('<div class="feedflare">', $text);
            $text = $parts[0].'</div>';
        }

        $this->_logger->log(
            \Joomla\CMS\Log\Log::INFO,
            'feedburner=',
            $text
        );

        // Format br's as per HTML (not XHTML)
        $text = str_replace(
            [
                '<br>',
                '<br/>',
            ],
            '<br />',
            $text
        );

        if (self::$_params->get('remove_dups_emp')) {
            $pattern = '%<br />\s*<br />%';

            while (preg_match($pattern, $text)) {
                $text = preg_replace($pattern, '<br />', $text);
            }
        }

        return $this->_trimText($text, self::$_params->get('max_length'), self::$_params->get('max_length_type'));
    }

    /**
     * _onlyIntro.
     *
     * @param string $introtext Params
     * @param string $fulltext  Params
     *
     * @return array
     */
    private function _onlyIntro($introtext, $fulltext)
    {
        $onlyintro = self::$_params->get('onlyintro');

        if ($onlyintro) {
            $fulltext = null;
        }

        return [$introtext, $fulltext];
    }

    /**
     * _trimText.
     *
     * @param string $text     Params
     * @param int    $trimTo   Params
     * @param string $trimType Params
     */
    private function _trimText($text, $trimTo, $trimType)
    {
        $stripHtmlTags = (bool) self::$_params->get('strip_html_tags');

        if ((bool) $trimTo) {
            $text = FeedTextHelper::trimText(
                $text,
                $trimTo,
                $trimType,

                    // Keep Html Tags
                    !$stripHtmlTags
            );
        }

        // Keep Html Tags
        if (!$stripHtmlTags) {
            $text = FeedTextHelper::cleanFeedText($text, $this->_clean_feed_config, $this->_spec);

            if (function_exists('tidy_parse_string')) {
                $tidy = tidy_parse_string(
                    $text,
                    [
                        'show-body-only' => true,
                    ],
                    'UTF8'
                );
                $tidy->cleanRepair();
                $text = $tidy->value;
            }
        }

        return $text;
    }

    /**
     * _dotDotDot.
     *
     * @param string $introtext Params
     *
     * @return string
     */
    private function _dotDotDot($introtext)
    {
        $dotdotdot = self::$_params->get('dotdotdot');

        if ($dotdotdot) {
            if (false === strpos($introtext, '</p>')) {
                $introtext .= '...';
            } else {
                $introtext = FeedTextHelper::str_replace_last('</p>', '...</p>', $introtext);
            }
        }

        return $introtext;
    }

    /**
     * _getTagsToStrip.
     *
     * @return array
     */
    private function _getTagsToStrip()
    {
        $s = self::$_params->get('strip_list');
        $w = '';

        if (0 === strpos($s, '+')) {
            $s = FeedTextHelper::str_replace_first('+', '', $s);
            $w = '+';
        }

        $ts = TextUtil::listToArray($s);
        $ht = [];

        foreach ($ts as $k => $t) {
            if (strpos($t, '=')) {
                $ht[] = $t;
                unset($ts[$k]);
            }
        }

        [$tags, $hook_tag] = [
            $w.implode(',', $ts),
            $w.implode(',', $ht),
        ];

        if ($tags) {
            if (false !== strpos($tags, '+')) {
                $tags = str_replace('+', '', $tags);
            } else {
                $tags = str_replace(' ', '', $tags);
                $tags = '*-'.str_replace(',', ' -', $tags);
            }
        }

        self::$_tags = $tags;
        self::$_hook_tag = $hook_tag;

        return [$tags, $hook_tag];
    }

    /**
     * _setPublicationState.
     *
     * @param object $article Params
     * @param string $date    Params
     *
     * @return object
     */
    private function _setPublicationState($article, $date)
    {
        $itemDate = \Joomla\CMS\Factory::getDate($date);
        $feedItemDate = $itemDate->toSql();
        $today = \Joomla\CMS\Factory::getDate()->format(JText::_('COM_AUTOTWEET_DATE_FORMAT'));

        $zerodate_time = \Joomla\CMS\Factory::getDate('2000-01-01 00:00:00')->toUnix();

        if ($itemDate->toUnix() < $zerodate_time) {
            $feedItemDate = $today;
        }

        $now_time = \Joomla\CMS\Factory::getDate('now')->toUnix();

        if (!self::$_params->get('advance_date')) {
            if ($itemDate->toUnix() > $now_time) {
                $feedItemDate = $today;
            }
        }

        if (($feedItemDate) && (strlen(trim($feedItemDate)) <= 10)) {
            $feedItemDate .= ' 00:00:00';
        }

        $article->created = self::$_params->get('created_date') ? $today : $feedItemDate;
        $article->publish_up = self::$_params->get('pub_date') ? $today : $feedItemDate;

        $article->state = (int) (self::$_params->get('auto_publish'));

        $publishDays = (int) (self::$_params->get('publish_duration'));

        if ($publishDays) {
            switch (self::$_params->get('pub_dur_type', 0)) {
                // Days
                case 0:
                    $publishDays = $publishDays * 24 * 60 * 60;

                    break;
                // Hours
                case 1:
                    $publishDays = $publishDays * 60 * 60;

                    break;
                // Minutes
                case 2:
                    $publishDays = $publishDays * 60;

                    break;
            }

            $publish_down = \Joomla\CMS\Factory::getDate($now_time + $publishDays);
            $publish_down = $publish_down->format(JText::_('COM_AUTOTWEET_DATE_FORMAT'));
            $article->publish_down = $publish_down;
        } else {
            $article->publish_down = null;
        }

        return $article;
    }

    /**
     * _setDefaultImage.
     *
     * @return array
     */
    private function _setDefaultImage()
    {
        $image = new FeedImage();

        $image->src = self::$_params->get('img');
        $image->title = \Joomla\CMS\Factory::getConfig()->get('sitename');
        $image->alt = $image->title;

        $img_class = self::$_params->get('img_class');

        if ($img_class) {
            $image->class = self::$_params->get('img_class');
        }

        $img_style = self::$_params->get('img_style');

        if ($img_style) {
            $image->style = self::$_params->get('img_style');
        }

        return [$image];
    }

    /**
     * _setDefaultEnclosureImage.
     *
     * @param array $enclosures Params
     *
     * @return array
     */
    private function _setDefaultEnclosureImage($enclosures)
    {
        foreach ($enclosures as $enclosure) {
            $real_type = strtolower($enclosure->real_type);

            if (0 === strpos($real_type, 'image/')) {
                $image = new FeedImage();

                $image->src = $enclosure->link;
                $image->title = $enclosure->title;
                $image->alt = $enclosure->title;

                $img_class = self::$_params->get('img_class');

                if ($img_class) {
                    $image->class = self::$_params->get('img_class');
                }

                $img_style = self::$_params->get('img_style');

                if ($img_style) {
                    $image->style = self::$_params->get('img_style');
                }

                return [$image];
            }
        }

        return null;
    }

    /**
     * _isDuplicated.
     *
     * @param object $article Params
     *
     * @return bool
     */
    private function _isDuplicated($article)
    {
        if (self::$_params->get('check_existing')) {
            $contenttype_id = self::$_params->get('contenttype_id');

            // Types: feedcontent
            $method = $contenttype_id.'IsDuplicated';

            return FeedDupCheckerHelper::$method($article, self::$_params->get('compare_existing'));
        }

        return false;
    }

    /**
     * _validateImages.
     *
     * @param array &$images        Params
     * @param bool  $onlyFirstValid Params
     *
     * @return bool
     */
    private function _validateImages(&$images, $onlyFirstValid = false)
    {
        $imgs = [];
        $imageHelper = ImageUtil::getInstance();

        foreach ($images as $image) {
            $imageUrl = $image->src;

            if ($imageHelper->isValidImageUrl($imageUrl)) {
                $imgs[] = $image;

                if ($onlyFirstValid) {
                    break;
                }
            }
        }

        $images = $imgs;
    }
}

/**
 * Hook_tag function for htmLawed.
 *
 * Cleanup and htmLawed text cleaning
 *
 * @param object $element         Param
 * @param array  $attribute_array Param
 *
 * @return string
 */
function hookTagCleaning($element, $attribute_array = [])
{
    return FeedTextHelper::hookTagCleaning($element, $attribute_array);
}
