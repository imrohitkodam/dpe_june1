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
 * Helper for posts form AutoTweet to channels (twitter, Facebook, ...).
 *
 * @since       1.0
 */
abstract class FeedTextHelper
{
    // HookTagCleaning - Start

    protected static $open_tag = null;

    protected static $hasHookTags = null;

    protected static $whitemode = null;

    protected static $tags_attrs = null;

    /**
     * transliterate.
     *
     * Adapted from http://docs.joomla.org/Making_a_Language_Pack_for_Joomla_1.6#Example_of_the_function_to_add_when_custom_transliteration_is_desired
     * Returns a lowercase transliterated string - for use in aliases (SEF)
     *
     * @param string $string Param
     * @param string $custom Param
     *
     * @return string
     */
    public static function transliterate($string, $custom)
    {
        static $basic_glyph_array = [
            'a' => 'à,á,â,ã,ä,å,ā,ă,ą,ḁ,α,ά',
            'ae' => 'æ',
            'b' => 'β,б',
            'c' => 'ç,ć,ĉ,ċ,č,ч,ћ,ц',
            'ch' => 'ч',
            'd' => 'ď,đ,Ð,д,ђ,δ,ð',
            'dz' => 'џ',
            'e' => 'è,é,ê,ë,ē,ĕ,ė,ę,ě,э,ε,έ',
            'f' => 'ƒ,ф',
            'g' => 'ğ,ĝ,ğ,ġ,ģ,г,γ',
            'h' => 'ĥ,ħ,Ħ,х',
            'i' => 'ì,í,î,ï,ı,ĩ,ī,ĭ,į,и,й,ъ,ы,ь,η,ή',
            'ij' => 'ĳ',
            'j' => 'ĵ',
            'ja' => 'я',
            'ju' => 'яю',
            'k' => 'ķ,ĸ,κ',
            'l' => 'ĺ,ļ,ľ,ŀ,ł,л,λ',
            'lj' => 'љ',
            'm' => 'μ',
            'n' => 'ñ,ņ,ň,ŉ,ŋ,н,ν',
            'nj' => 'њ',
            'o' => 'ò,ó,ô,õ,ø,ō,ŏ,ő,ο,ό,ω,ώ',
            'oe' => 'œ,ö',
            'p' => 'п,π',
            'ph' => 'φ',
            'ps' => 'ψ',
            'r' => 'ŕ,ŗ,ř,р,ρ,σ,ς',
            's' => 'ş,ś,ŝ,ş,š,с',
            'ss' => 'ß,ſ',
            'sh' => 'ш',
            'shch' => 'щ',
            't' => 'ţ,ť,ŧ,τ,т',
            'th' => 'θ',
            'u' => 'ù,ú,û,ü,ũ,ū,ŭ,ů,ű,ų,у',
            'v' => 'в',
            'w' => 'ŵ',
            'x' => 'χ,ξ',
            'y' => 'ý,þ,ÿ,ŷ',
            'z' => 'ź,ż,ž,з,ж,ζ',
        ];

        $glyph_array = [];

        if ($custom) {
            $array = explode("\n", $custom);

            foreach ($array as $v) {
                $v = explode('=', $v);
                $glyph_array[$v[0]] = $v[1];
            }
        } else {
            $glyph_array = $basic_glyph_array;
        }

        foreach ($glyph_array as $letter => $glyphs) {
            $glyphs = TextUtil::listToArray($glyphs);
            $string = str_replace($glyphs, $letter, $string);
        }

        return $string;
    }

    /**
     * encodeUrl.
     *
     * Based on a function by Nitin at http://publicmind.in/blog/url-encoding/ - proper urlencode
     *
     * @param string $url Param
     *
     * @return string
     */
    public static function encodeUrl($url)
    {
        $reserved = [
            ':' => '!%3A!ui',
            '/' => '!%2F!ui',
            '?' => '!%3F!ui',
            '#' => '!%23!ui',
            '[' => '!%5B!ui',
            ']' => '!%5D!ui',
            '@' => '!%40!ui',
            '!' => '!%21!ui',
            '$' => '!%24!ui',
            '&' => '!%26!ui',
            "'" => '!%27!ui',
            '(' => '!%28!ui',
            ')' => '!%29!ui',
            '*' => '!%2A!ui',
            '+' => '!%2B!ui',
            ',' => '!%2C!ui',
            ';' => '!%3B!ui',
            '=' => '!%3D!ui',
            '%' => '!%25!ui',
        ];

        // Removes nasty whitespace
        $url = str_replace(
            [
                '%09',
                '%0A',
                '%0B',
                '%0D',
            ],
            '',
            $url
        );
        $url = rawurlencode($url);
        $url = preg_replace(array_values($reserved), array_keys($reserved), $url);

        return $url;
    }

    /**
     * cleanFeedText.
     *
     * Cleanup and htmLawed text cleaning
     *
     * @param string $text        Param
     * @param string $cleanConfig Param
     * @param string $spec        Param
     *
     * @return string
     */
    public static function cleanFeedText($text, $cleanConfig, $spec)
    {
        // Hacky fix for crap sites that really can't be cleaned up well - this confuses the html parsers!
        $text = str_replace('<sup> </sup>', ' ', $text);
        $text = str_replace('<sub> </sub>', ' ', $text);

        $text = XTS_Htmlawed::filter($text, $cleanConfig, $spec);

        return $text;
    }

    /**
     * cleanMeta.
     *
     * @param string &$content Param
     *
     * @return string
     */
    public static function cleanMeta(&$content)
    {
        // Only process if not empty
        if (!empty($content['metakey'])) {
            // Array of characters to remove
            $bad_characters = [
                "\n",
                "\r",
                '"',
                '<',
                '>',
            ];

            // Remove bad characters
            $after_clean = str_ireplace($bad_characters, '', $content['metakey']);

            // Create array using commas as delimiter
            $keys = TextUtil::listToArray($after_clean);
            $clean_keys = [];

            foreach ($keys as $key) {
                // Ignore blank keywords
                if (trim($key)) {
                    $clean_keys[] = trim($key);
                }
            }

            // Put array back together delimited by ", "
            $content['metakey'] = implode(', ', $clean_keys);
        }

        // Clean up description -- eliminate quotes and <> brackets
        if (!empty($content['metadesc'])) {
            // Only process if not empty
            $bad_characters = [
                '"',
                '<',
                '>',
            ];
            $content['metadesc'] = str_ireplace($bad_characters, '', $content['metadesc']);
        }
    }

    /**
     * trimText.
     *
     * @param string $text      Param
     * @param string $trimTo    Param
     * @param string $type      Param
     * @param string $keep_tags Param
     *
     * @return string
     */
    public static function trimText($text, $trimTo, $type = 'char', $keep_tags = true)
    {
        if (!$keep_tags) {
            $text = strip_tags($text);
        }

        if (!$trimTo) {
            return $text;
        }

        $text = preg_replace('/\s\s+/', ' ', $text);

        // Html safe split text function
        $regex = '#<[^<^>]*>|[^<]*|<[^<^>]*#u';

        preg_match_all($regex, $text, $matches);

        switch ($type) {
            case 'char':
                $text = self::_trimTextChar($matches[0], $trimTo);

                break;
            case 'word':
                $text = self::_trimTextWord($matches[0], $trimTo);

                break;
            case 'sent':
                $text = self::_trimTextSent($matches[0], $trimTo);

                break;
        }

        return $text;
    }

    /**
     * in_array_recursive.
     *
     * @param string $needle   Param
     * @param string $haystack Param
     *
     * @return string
     */
    public static function inArrayRecursive($needle, $haystack)
    {
        foreach ($haystack as &$value) {
            if (is_array($value)) {
                if (self::inArrayRecursive($needle, $value)) {
                    return true;
                }
            } else {
                if ($value === $needle) {
                    return true;
                }
            }
        }
    }

    /**
     * str_replace_first.
     *
     * @param string $search  Param
     * @param string $replace Param
     * @param string $subject Param
     *
     * @return string
     */
    public static function str_replace_first($search, $replace, $subject)
    {
        $pos = strpos($subject, $search);

        if (false !== $pos) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }

    /**
     * str_replace_last.
     *
     * @param string $search  Param
     * @param string $replace Param
     * @param string $str     Param
     *
     * @return string
     */
    public static function str_replace_last($search, $replace, $str)
    {
        if (false !== ($pos = strrpos($str, $search))) {
            $search_length = strlen($search);
            $str = substr_replace($str, $replace, $pos, $search_length);
        }

        return $str;
    }

    /**
     * getUrl.
     *
     * If file_path given, will automatically save resource to file
     *
     * @param string $url             Param
     * @param string $expected_result Param
     * @param string $file_path       Param
     * @param string $parts           Param
     *
     * @return string
     */
    public static function getUrl($url, $expected_result = 'html', $file_path = null, $parts = null)
    {
        $page = false;

        try {
            if (strpos($url, '//')) {
                $url = implode('/', array_slice(explode('/', $url), 2));
            }

            $url = html_entity_decode(trim($url), \ENT_QUOTES);

            // Are these url cleaning methods really necessary??
            $url = utf8_encode(strip_tags($url));

            $ch = curl_init();
            curl_setopt($ch, \CURLOPT_URL, $url);
            curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, \CURLOPT_AUTOREFERER, true);

            if (!ini_get('open_basedir')) {
                curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, 1);
            }

            switch ($expected_result) {
                case 'html':
                    curl_setopt($ch, \CURLOPT_HEADER, 1);

                    break;
                case 'noheader':
                    // This is same as html above but with no header
                    curl_setopt($ch, \CURLOPT_HEADER, 0);

                    break;
                case 'header':
                    // Returns headers only
                    curl_setopt($ch, \CURLOPT_HEADER, 1);
                    curl_setopt($ch, \CURLOPT_NOBODY, 1);

                    break;
                case 'images':
                default:
                    curl_setopt($ch, \CURLOPT_HEADER, 0);
                    curl_setopt($ch, \CURLOPT_BINARYTRANSFER, 1);

                    break;
            }

            if ($file_path) {
                // Saving File (fopen)
                $fp = fopen($file_path, 'w');
                curl_setopt($ch, \CURLOPT_FILE, $fp);
            }

            // Accessing URL (cURL)
            // UTF-8 and cleaning done later
            $page = curl_exec($ch);
            $status = curl_getinfo($ch);

            // Close cURL resource, and free up system resources
            curl_close($ch);

            if (isset($fp)) {
                fclose($fp);
            }

            if ((301 === (int) $status['http_code']) || (302 === (int) $status['http_code']) || (303 === (int) $status['http_code'])) {
                $url_redirect = self::extractUrlRedirect($page);

                if ((!empty($url_redirect)) && ($url !== $url_redirect)) {
                    return self::getUrl($url_redirect, $expected_result, $file_path, $parts);
                }
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            AutotweetLogger::getInstance()->log(\Joomla\CMS\Log\Log::ERROR, 'PerfectPublisher - '.$error_message);
        }

        return $page;
    }

    /**
     * extractUrlRedirect.
     *
     * @param string $page Param
     *
     * @return string
     */
    public static function extractUrlRedirect($page)
    {
        $header_text = preg_split('/[\r\n]+/', $page);

        $data = [];
        $data['headers'] = [];

        foreach ($header_text as $h) {
            preg_match('/^(.+?):\s+(.*)$/', $h, $matches);

            if ($matches) {
                $data['headers'][$matches[1]] = $matches[2];
            }
        }

        if (isset($data['headers']['Location'])) {
            return $data['headers']['Location'];
        }

        return null;
    }

    /**
     * getImageName.
     *
     * @param string $title         Params
     * @param string $alt           Params
     * @param string $src           Params
     * @param string $name_type     Params
     * @param string $image_details Params
     * @param bool   $add_ext       Params
     *
     * @return string
     */
    public static function getImageName($title, $alt, $src, $name_type, $image_details, $add_ext = 1)
    {
        preg_match('#[/?&]([^/?&]*)(\.jpg|\.jpeg|\.gif|\.png)#i', $src, $matches);
        $ext = isset($matches[2]) ? trim(strtolower($matches[2])) : '';

        if (!$ext && !empty($image_details)) {
            switch ($image_details['mime']) {
                case 'image/pjpeg':
                case 'image/jpeg':
                case 'image/jpg':
                    $ext = '.jpg';

                    break;
                case 'image/x-png':
                case 'image/png':
                    $ext = '.png';

                    break;
                case 'image/gif':
                    $ext = '.gif';

                    break;
                case 'image/bmp':
                    $ext = '.bmp';

                    break;
            }
        }

        switch ($name_type) {
            case 0:
                [$name] = $title ? self::splitText($title, 50, 'char', false) : self::splitText($alt, 50, 'char', false);

                break;
            case 1:
                if (isset($matches[1])) {
                    $name = $matches[1];
                }

                break;
            case 2:
                $name = md5($src);

                break;
            case 3:
                jexit('Image name error');

                break;
        }

        ++$name_type;

        if (empty($name)) {
            $name = self::getImageName($title, $alt, $src, $name_type, $image_details, 0);
        }

        $name = JFile::makeSafe(TextUtil::convertUrlSafe($name));

        return $add_ext ? $name.$ext : $name;
    }

    /**
     * generateTags.
     *
     * Use a simple frequency algorithm to compute meta tags
     *
     * @param string $text     Params
     * @param string $max_tags Params
     *
     * @return string
     */
    public static function generateTags($text, $max_tags = 3)
    {
        $text = strtolower(html_entity_decode(strip_tags($text), \ENT_QUOTES));

        if (!trim($text)) {
            return '';
        }

        $words = explode(' ', $text);

        array_walk(
            $words,
            [
                'FeedTextHelper',
                'trimTags',
            ]
        );

        $words = array_filter(
            $words,
            [
                'FeedTextHelper',
                'filterTerms',
            ]
        );
        $words = self::removeIgnoreWords($words);
        $words = array_count_values($words);
        arsort($words);
        $words = is_array($words) ? array_slice($words, 0, $max_tags) : [];
        $words = implode(',', array_keys($words));

        return $words;
    }

    /**
     * trimTags.
     *
     * @param string &$term Params
     * @param string $key   Params
     *
     * @return string
     */
    public static function trimTags(&$term, $key)
    {
        $term = trim($term);

        $term = str_replace(
            [
                "\n",
                "\r",
            ],
            ' ',
            $term
        );
        $term = preg_replace('/[,.?:;!()=\\*\']/', '', $term);
    }

    /**
     * filterTerms.
     *
     * @param string $var           Params
     * @param int    $min_tag_chars Params
     *
     * @return bool
     */
    public static function filterTerms($var, $min_tag_chars = 3)
    {
        $keep = !empty($var) && !empty($var) && null !== $var && !preg_match('/^\s*$/', $var);

        if ((!empty($min_tag_chars)) && ($min_tag_chars > 0)) {
            $keep = (($keep) && (strlen($var) >= $min_tag_chars));
        }

        return $keep;
    }

    /**
     * splitArticleText.
     *
     * @param string $articletext Params
     *
     * @return array
     */
    public static function splitArticleText($articletext)
    {
        $pattern = '#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i';
        $tagPos = preg_match($pattern, $articletext);

        if (0 === $tagPos) {
            return [$articletext, ''];
        }

        return preg_split($pattern, $articletext, 2);
    }

    /**
     * joinArticleText.
     *
     * @param string $introtext Params
     * @param string $fulltext  Params
     *
     * @return bool
     */
    public static function joinArticleText($introtext, $fulltext)
    {
        if (empty($fulltext)) {
            return $introtext;
        }

        return $introtext.'<hr id="system-readmore" />'.$fulltext;
    }

    /**
     * generateAuthor.
     *
     * @param string $created_by       Params
     * @param string $created_by_alias Params
     *
     * @return bool
     */
    public static function generateAuthor($created_by, $created_by_alias)
    {
        return \Joomla\CMS\Factory::getUser($created_by)->username
                .(empty($created_by_alias) ? '' : ' ('.$created_by_alias.')');
    }

    /**
     * Hook_tag function for htmLawed initialization.
     *
     * @return string
     */
    public static function hookTagCleaningInit()
    {
        self::getHookTagList();
    }

    /**
     * Hook_tag function for htmLawed initialization.
     *
     * @return string
     */
    public static function hookTagCleaningItemInit()
    {
        self::$open_tag = [];
    }

    /**
     * getHookTagList.
     */
    public static function getHookTagList()
    {
        static $regex = '/([\S]+)\s*?([^=]*)?=?([\S]*)?/';

        $hook_tag = FeedProcessorHelper::getHookTag();
        $whitemode = (0 === strpos($hook_tag, '+')) ? 1 : 0;

        if ($whitemode) {
            $hook_tag = self::str_replace_first('+', '', $hook_tag);
        }

        $parts = TextUtil::listToArray($hook_tag);
        $tags_attrs = [];

        foreach ($parts as $part) {
            preg_match($regex, $part, $matches);

            if (4 === count($matches)) {
                $element = trim($matches[1]);
                $attr = trim($matches[2]);
                $value = trim($matches[3]);

                $tags_attrs[$element.'-'.$attr] = $value;
            }
        }

        self::$hasHookTags = !empty($tags_attrs);
        self::$whitemode = $whitemode;
        self::$tags_attrs = $tags_attrs;
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
    public static function hookTagCleaning($element, $attribute_array = [])
    {
        static $empty_elements = [
            'area' => 1,
            'br' => 1,
            'col' => 1,
            'embed' => 1,
            'hr' => 1,
            'img' => 1,
            'input' => 1,
            'isindex' => 1,
            'param' => 1,
        ];

        // It's a closing tag
        if (!array_key_exists($element, $empty_elements)) {
            if ((array_key_exists($element, self::$open_tag))
                && (self::$open_tag[$element])) {
                self::$open_tag[$element] = false;

                return "</${element}>";
            }

            self::$open_tag[$element] = true;
        }

        if (self::$hasHookTags) {
            foreach ($attribute_array as $k => $v) {
                $key = $element.'-'.$k;

                if ((array_key_exists($key, self::$tags_attrs)) && (0 === strpos($v, self::$tags_attrs[$key]))) {
                    if (self::$whitemode) {
                        // It's ok, proceed
                        break;
                    }

                    // Blacklisted!
                    return '';
                }
            }
        }

        $params = FeedProcessorHelper::getParams();
        $link_target = $params->get('link_target', 0);

        if (('a' === $element) && ($link_target)) {
            $attribute_array['target'] = $link_target;
        }

        $string = '';

        foreach ($attribute_array as $k => $v) {
            $string .= " {$k}=\"{$v}\"";
        }

        return "<{$element}{$string}>";
    }

    /**
     * _trimTextChar.
     *
     * @param string $parts  Param
     * @param string $trimTo Param
     *
     * @return string
     */
    private static function _trimTextChar($parts, $trimTo)
    {
        $result = [];
        $len = 0;
        $end = false;
        $firstWord = true;

        foreach ($parts as $part) {
            $m = strlen($part);

            // It's a tag
            if ((0 === strpos($part, '<')) || (strpos($part, '>') === ($m - 1))) {
                $result[] = $part;
                $firstWord = true;
            } else {
                $words = explode(' ', $part);

                foreach ($words as $word) {
                    $l = strlen($word);

                    if (!$firstWord) {
                        ++$l;
                    }

                    if ($len + $l < $trimTo) {
                        $len += $l;

                        if ($firstWord) {
                            $result[] = $word;
                            $firstWord = false;
                        } else {
                            $result[] = ' '.$word;
                            ++$len;
                        }
                    } else {
                        $end = true;

                        break;
                    }
                }
            }

            if ($end) {
                break;
            }
        }

        return trim(implode('', $result));
    }

    /**
     * _trimTextWord.
     *
     * @param string $parts  Param
     * @param string $trimTo Param
     *
     * @return string
     */
    private static function _trimTextWord($parts, $trimTo)
    {
        $result = [];
        $len = 0;
        $end = false;
        $firstWord = true;

        foreach ($parts as $part) {
            $m = strlen($part);

            // It's a tag
            if ((0 === strpos($part, '<')) || (strpos($part, '>') === ($m - 1))) {
                $result[] = $part;
                $firstWord = true;
            } else {
                $words = explode(' ', $part);

                foreach ($words as $word) {
                    if ($len < $trimTo) {
                        ++$len;

                        if ($firstWord) {
                            $result[] = $word;
                            $firstWord = false;
                        } else {
                            $result[] = ' '.$word;
                        }
                    } else {
                        $end = true;

                        break;
                    }
                }
            }

            if ($end) {
                break;
            }
        }

        return trim(implode('', $result));
    }

    /**
     * _trimTextSent.
     *
     * @param string $parts  Param
     * @param string $trimTo Param
     *
     * @return string
     */
    private static function _trimTextSent($parts, $trimTo)
    {
        $result = [];
        $len = 0;
        $end = false;
        $firstSent = true;

        $pattern = '/(?<=[.?!;:])\s+/';

        foreach ($parts as $part) {
            $m = strlen($part);

            // It's a tag
            if ((0 === strpos($part, '<')) || (strpos($part, '>') === ($m - 1))) {
                $result[] = $part;
            } else {
                $sentences = preg_split($pattern, $part, -1, \PREG_SPLIT_NO_EMPTY);

                foreach ($sentences as $sent) {
                    if ($len < $trimTo) {
                        ++$len;
                        $result[] = $sent;
                    } else {
                        $end = true;

                        break;
                    }
                }
            }

            if ($end) {
                break;
            }
        }

        return trim(implode('', $result));
    }

    // HookTagCleaning - End
}
