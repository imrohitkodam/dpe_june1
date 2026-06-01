<?php
/* This file has been prefixed by <PHP-Prefixer> for "XT Platform" */

/*
 * @package     Extly Infrastructure Support
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2021 Extly, CB. All rights reserved.
 * @license     http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @see         https://www.extly.com
 */

namespace XTP_BUILD\Extly\Infrastructure\Support;

use XTP_BUILD\Stringy\Stringy as S;

class Estring extends S implements \ArrayAccess, \Countable, \IteratorAggregate
{
    const DELIMITER = ',';

    const ASSIGN_DELIMITER = '=';

    const ATTR_DELIMITER = ' ';

    const ESCAPED_DELIMITER = '[[comma]]';

    const DOTS = '...';

    const TRIM_TYPE_CHAR = 'char';

    const TRIM_TYPE_WORD = 'word';

    const TRIM_TYPE_SENT = 'sent';

    const TIDY_MODE_NONE = 0;

    const TIDY_MODE_COMPRESS = -1;

    const TIDY_MODE_BEAUTIFY = 1;

    const NO_PHEEDO_IMAGES = 'images.pheedo.com';

    const HTMLRETRIEVEIMAGES_REMOVE_STYLE = 'remove_style';

    const HTMLRETRIEVEIMAGES_IMG_CLASS = 'img_class';

    const HTMLRETRIEVEIMAGES_IMG_STYLE = 'img_style';

    private static $truncateWithDots = true;

    public static function setTruncateWithDots($value)
    {
        static::$truncateWithDots = $value;
    }

    public static function isList($value)
    {
        return (\is_array($value)) || (!empty($value));
    }

    public function convertListToArray($pattern = ',', $limit = null, $includeCommas = false)
    {
        return self::listToArray($this->str, $pattern, $limit, $includeCommas);
    }

    public static function listOfLinesToArray($value)
    {
        if (\is_array($value)) {
            return $value;
        }

        return self::create($value)->listSplit("\n");
    }

    public static function listToArray($value, $pattern = ',', $limit = null, $replaceEscapedCommas = false)
    {
        if (\is_array($value)) {
            return $value;
        }

        $list = self::create($value)->listSplit($pattern, $limit);

        if ($replaceEscapedCommas) {
            $newList = [];

            foreach ($list as $item) {
                $value = self::create($item);
                $newList[] = (string) $value->replace(self::ESCAPED_DELIMITER, self::DELIMITER);
            }

            $list = $newList;
        }

        return $list;
    }

    public static function doubleListToArray($value, $pattern1 = "\n", $pattern2 = '===', $doubleOptional = false)
    {
        if (\is_array($value)) {
            return $value;
        }

        $list = self::create($value)->listSplit($pattern1);

        $values = [];

        foreach ($list as $item) {
            $keyValue = self::create($item);

            if (($doubleOptional) && (!$keyValue->contains($pattern2))) {
                $keyValue = $keyValue->append($pattern2);
            }

            if (false === $keyValue->indexOf($pattern2)) {
                throw new SupportException('doubleListToArray parsing error: ('.$keyValue.','.$pattern2.')');
            }

            list($key, $value) = $keyValue->split($pattern2);

            if ((empty($key)) || (empty($value))) {
                throw new SupportException('doubleListToArray parsing error: ('.$pattern1.','.$pattern2.')');
            }

            $values[(string) $key] = (string) $value;
        }

        return $values;
    }

    public static function listToJSON($value)
    {
        $arr = self::listToArray($value);

        if (empty($arr)) {
            throw new \Exception('Empty list');
        }

        array_walk($arr, function (&$val) {
            $val = (string) $val;
        });

        return json_encode($arr);
    }

    public static function arrayToAttrs($attributeArray)
    {
        $attrs = [];

        foreach ($attributeArray as $k => $v) {
            $attrs[] = "{$k}=\"{$v}\"";
        }

        return implode(' ', $attrs);
    }

    public function listSplit($pattern = ',', $limit = null)
    {
        $values = $this->split($pattern, $limit);
        $values = array_filter(array_map('trim', $values));

        return $values;
    }

    /**
     * clean.
     *
     * @param string $text   Param
     * @param mixed  $anyTag
     *
     * @return HString
     */
    public function clean($anyTag = true)
    {
        $text = (string) $this
            ->htmlDecode()
            ->toSpaces()
            ->collapseWhitespace()
            ->stripHtmlTags()
            ->trim();

        // Strip out any url-encoded stuff
        $text = urldecode($text);

        // Line breaks and Tabs
        $text = str_replace(
            [
                "\r\n",
                "\r",
                "\n",
                "\t",
            ],
            ' ',
            $text
        );

        // Replace Multiple spaces with single space
        $text = preg_replace('/ +/', ' ', $text);

        if (!$anyTag) {
            return self::create($text);
        }

        // Removing [img]...[/img]
        $pattern = '/\[[^[]+\][^\[]+\[\/[^[]+\]/is';
        $text = preg_replace($pattern, '', $text);

        // Removing unmatched [img], [/img]
        $pattern = '/\[[^[]+\]/is';
        $text = preg_replace($pattern, '', $text);

        $pattern = '/\[\/[^[]+\]/is';
        $text = preg_replace($pattern, '', $text);

        // Removing {img}...{/img}
        $pattern = '/\{[^{]+\}[^\{]+\{\/[^{]+\}/is';
        $text = preg_replace($pattern, '', $text);

        // Removing unmatched {img}, {/img}
        $pattern = '/\{[^{]+\}/is';
        $text = preg_replace($pattern, '', $text);

        $pattern = '/\\{\/[^{]+\}/is';
        $text = preg_replace($pattern, '', $text);

        // !(/images/sample.png)
        $text = preg_replace('/!\(([^\n\)]*)\)/', '', $text);

        return self::create($text);
    }

    /**
     * generateCr.
     *
     * @return object
     */
    public function generateCr()
    {
        $text = (string) $this;
        $text = str_replace('|CR|', "\n", $text);

        return self::create($text);
    }

    /**
     * truncate.
     *
     * @param string $text     Param
     * @param int    $maxChars Param
     * @param bool   $withDots Param
     *
     * @return string
     */
    public function truncateWithDots($maxChars, $withDots = null)
    {
        if ($this->length() < $maxChars) {
            return $this;
        }

        if (null === $withDots) {
            $withDots = static::$truncateWithDots;
        }

        if ($withDots) {
            if ($maxChars < 5) {
                $truncatedWithDots = $this->safeTruncate($maxChars);
            } else {
                $truncatedWithDots = $this->safeTruncate($maxChars, self::DOTS);
            }

            if (self::DOTS === $truncatedWithDots) {
                return $this->truncate($maxChars - 3).self::DOTS;
            }

            return $truncatedWithDots;
        }

        return $this->truncate($maxChars);
    }

    public function toTitleCase()
    {
        $str = mb_convert_case($this->str, MB_CASE_TITLE, $this->encoding);

        return self::create($str, $this->encoding);
    }

    /**
     * Hashtize.
     *
     * @param string $text Param
     *
     * @return string
     */
    public static function hashtize($text)
    {
        if (\is_array($text)) {
            return self::hashtizeArray($text);
        }

        return self::create($text)
            ->toTitleCase()
            ->regexReplace('(?=\P{Nd})\P{L}', '')
            ->prepend('#');
    }

    /**
     * Hashtize.
     *
     * @param string $text Param
     *
     * @return string
     */
    public static function hashtizeArray($text)
    {
        $arr = [];

        foreach ($text as $word) {
            $arr[] = self::hashtize($word);
        }

        return implode(' ', $arr);
    }

    /**
     * Gracefully appends params to the URL.
     *
     * @param string $url       the URL that will receive the params
     * @param array  $newParams the params to append to the URL
     *
     * @return string
     */
    public static function appendParamsToUrl($url, array $newParams = [])
    {
        if (empty($newParams)) {
            return $url;
        }

        if (false === strpos($url, '?')) {
            return $url.'?'.http_build_query($newParams, null, '&');
        }

        list($path, $query) = explode('?', $url, 2);
        $existingParams = [];
        parse_str($query, $existingParams);

        // Favor params from the original URL over $newParams
        $newParams = array_merge($newParams, $existingParams);

        // Sort for a predicable order
        ksort($newParams);

        return $path.'?'.http_build_query($newParams, null, '&');
    }

    public static function isSslUrl($url)
    {
        if (empty($url)) {
            return false;
        }

        return 0 === strpos($url, 'https://');
    }

    public static function securizeUrl($url)
    {
        return (string) self::create($url)->replace('http://', 'https://');
    }

    public function encodeJson()
    {
        return json_encode($this->str);
    }

    public function decodeJson()
    {
        return Collection::create(json_decode($this->str, true));
    }

    public function stripHtmlTags()
    {
        return self::create(strip_tags($this->str));
    }

    public function smartTrim($trimTo, $trimType = 'char', $withDots = null)
    {
        switch ($trimType) {
            case self::TRIM_TYPE_CHAR:
                return $this->noLines()->smartTrimWithCharCounting($trimTo, $withDots);

                break;
            case self::TRIM_TYPE_WORD:
                return $this->noLines()->smartTrimWithWordCounting($trimTo, $withDots);

                break;
            case self::TRIM_TYPE_SENT:
                return $this->noLines()->smartTrimWithSentCounting($trimTo, $withDots);

                break;
        }

        return $this;
    }

    /**
     * smartTrimWithCharCounting.
     *
     * @param string     $trimTo   Param
     * @param null|mixed $withDots
     *
     * @return string
     */
    public function smartTrimWithCharCounting($trimTo, $withDots = null)
    {
        if (0 === $trimTo) {
            return $this;
        }

        $parts = $this->splitHtmlParts();
        $result = [];
        $totalLength = 0;

        foreach ($parts as $part) {
            $s = self::create($part);

            // It's a tag
            if (($s->startsWith('<')) || ($s->endsWith('>'))) {
                $result[] = $part;
            } else {
                $l = $s->length();

                if (($totalLength + $l) <= $trimTo) {
                    $result[] = $part;
                    $totalLength += $l;
                } else {
                    $remaining = $trimTo - $totalLength;
                    $result[] = $s->truncateWithDots($remaining, $withDots);

                    break;
                }
            }
        }

        return self::create(implode('', $result));
    }

    public function words()
    {
        $array = $this->split('\s+', $this->str);

        return $this->arrayToArrayofStrings($array);
    }

    /**
     * smartTrimWithWordCounting.
     *
     * @param string     $trimTo   Param
     * @param null|mixed $withDots
     *
     * @return string
     */
    public function smartTrimWithWordCounting($trimTo, $withDots = null)
    {
        if (0 === $trimTo) {
            return $this;
        }

        $parts = $this->splitHtmlParts();
        $result = [];
        $totalLength = 0;
        $addDots = false;

        foreach ($parts as $part) {
            $s = self::create($part);

            // It's a tag
            if (($s->startsWith('<')) || ($s->endsWith('>'))) {
                $result[] = $part;
            } else {
                $words = $s->words();
                $wc = \count($words);

                if (($totalLength + $wc) <= $trimTo) {
                    $result[] = $part;
                    $totalLength += $wc;
                } else {
                    $remaining = $trimTo - $totalLength;
                    $firstWord = true;

                    foreach ($words as $word) {
                        $word = self::create($word);

                        if ($word->isEmpty()) {
                            continue;
                        }

                        if (0 === $remaining) {
                            $addDots = $withDots ? true : false;

                            break;
                        }

                        if ($firstWord) {
                            $result[] = $word;
                            $firstWord = false;
                        } else {
                            $result[] = $word->prepend(' ');
                        }

                        --$remaining;
                    }

                    break;
                }
            }
        }

        return self::create(implode('', $result))->trimRight()->append($addDots ? self::DOTS : '');
    }

    public function sentences()
    {
        $array = $this->split('(?<=[.?!;:])\s+', $this->str);

        return $this->arrayToArrayofStrings($array);
    }

    public function noLines()
    {
        return $this->regexReplace('[\r\n]+', '');
    }

    /**
     * smartTrimWithSentCounting.
     *
     * @param string     $trimTo   Param
     * @param null|mixed $withDots
     *
     * @return string
     */
    public function smartTrimWithSentCounting($trimTo, $withDots = null)
    {
        if (0 === $trimTo) {
            return $this;
        }

        $parts = $this->splitHtmlParts();
        $result = [];
        $totalLength = 0;
        $addDots = false;

        foreach ($parts as $part) {
            $s = self::create($part);

            // It's a tag
            if (($s->startsWith('<')) || ($s->endsWith('>'))) {
                $result[] = $part;
            } else {
                $sentences = $s->sentences();
                $sc = \count($sentences);

                if (($totalLength + $sc) <= $trimTo) {
                    $result[] = $part;
                    $totalLength += $sc;
                } else {
                    $remaining = $trimTo - $totalLength;

                    for ($i = 0; $i < $sc; ++$i) {
                        $sentence = $sentences[$i];

                        $sentence = self::create($sentence);

                        if ($sentence->isEmpty()) {
                            continue;
                        }

                        if (0 === $remaining) {
                            $addDots = $withDots ? true : false;

                            break;
                        }

                        $s = $s->removeLeft($sentence);

                        $nextSentIndex = $i + 1;

                        if ($nextSentIndex < $sc) {
                            $nextSent = $sentences[$nextSentIndex];

                            $nextSent = self::create($nextSent);

                            if ($nextSent->isEmpty()) {
                                $moreText = '';
                            } else {
                                $j = $s->indexOf($nextSent);

                                // More text to add 'a sentence separator', not a sentence.
                                $moreText = $s->first($j);
                                $s = $s->removeLeft($moreText);
                            }
                        } else {
                            // The last text, not a sentence.
                            $moreText = $s;
                        }

                        $result[] = $sentence->append($moreText);
                        --$remaining;
                    }

                    break;
                }
            }
        }

        return self::create(implode('', $result))->trimRight()->append($addDots ? self::DOTS : '');
    }

    /**
     * tidyParseString.
     *
     * @param mixed $mode
     *
     * @return object
     */
    public function tidyParseString($mode = 1)
    {
        if (self::TIDY_MODE_NONE === $mode) {
            return $this;
        }

        if (!\function_exists('tidy_parse_string')) {
            return $this;
        }

        $config = [
            'show-body-only' => true,
        ];

        if (self::TIDY_MODE_BEAUTIFY === $mode) {
            $config['indent'] = true;
        }

        $tidy = tidy_parse_string($this->str, $config, 'UTF8');

        $tidy->cleanRepair();

        return self::create($tidy->value);
    }

    public static function listOfTagsWithAttrsToArray($list, $delimiter = ',', $assignDelimiter = '=')
    {
        $parts = self::doubleListToArray($list, $delimiter, $assignDelimiter, true);

        if (empty($parts)) {
            throw new SupportException('Wrong listOfTagsWithAttrsToArray.');
        }

        $config = [];

        foreach ($parts as $tagattrib => $value) {
            if ((false === strpos($tagattrib, self::ATTR_DELIMITER))) {
                $tagattrib .= self::ATTR_DELIMITER;
            }

            list($tag, $attrib) = explode(' ', $tagattrib);

            $tag = trim($tag);
            $attrib = trim($attrib);
            $value = trim($value);

            if (($tag) && (null !== $attrib)) {
                if ($value) {
                    $config[$tag][$attrib] = $value;
                } else {
                    $config[$tag] = \is_array($attrib) ? $attrib : [];
                }
            }
        }

        return $config;
    }

    /**
     * checkBlackListed.
     *
     * @param mixed $blacklists
     *
     * @return bool
     */
    public function checkBlackListed($blacklists)
    {
        if (empty($blacklists)) {
            return false;
        }

        $blacklists = self::create($blacklists)->toLowerCase()->convertListToArray();

        return $this->checkListContains($blacklists);
    }

    /**
     * checkWhiteListed.
     *
     * @param mixed $whitelists
     *
     * @return bool
     */
    public function checkWhiteListed($whitelists)
    {
        if (empty($whitelists)) {
            return true;
        }

        $whitelists = self::create($whitelists)->toLowerCase()->convertListToArray();

        return $this->checkListContains($whitelists);
    }

    /**
     * guessTitle.
     *
     * @return object
     */
    public function guessTitle()
    {
        if (preg_match('#<(?:h1|h2|h3|h4|h5|h6)[^>]*>([\s\S]*?)<\/(?:h1|h2|h3|h4|h5|h6)>#i', $this->str, $matches)) {
            return self::create($matches[1]);
        }

        return self::create();
    }

    /**
     * isEmpty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->str);
    }

    /**
     * retrieveImagesFromHtml.
     *
     * @param mixed $options
     *
     * @return bool
     */
    public function retrieveImagesFromHtml($options = [])
    {
        $dom = new \DomDocument();
        $result = @$dom->loadHTML($this->str);

        if (!$result) {
            return false;
        }

        $imgs = $dom->getElementsByTagName('img');

        $images = Collection::create();
        $loadedImages = [];

        foreach ($imgs as $img) {
            $src = self::create($img->getAttribute('src'));

            if (($src->isEmpty()) || ($src->startsWith('data:image'))) {
                continue;
            }

            if (\in_array((string) $src, $loadedImages, true)) {
                continue;
            }

            if ($src->contains(self::NO_PHEEDO_IMAGES)) {
                continue;
            }

            // The image is OK

            $image = Image::create();
            $image->src = (string) $src;
            $image->title = $img->getAttribute('title');
            $image->alt = $img->getAttribute('alt');

            $rmvImgStyle = isset($options[self::HTMLRETRIEVEIMAGES_REMOVE_STYLE]) ?
                (bool) $options[self::HTMLRETRIEVEIMAGES_REMOVE_STYLE] : false;

            if (!$rmvImgStyle) {
                $image->class = $img->getAttribute('class');
                $image->style = $img->getAttribute('style');
                $image->align = $img->getAttribute('align');
                $image->border = $img->getAttribute('border');
                $image->width = $img->getAttribute('width');
                $image->height = $img->getAttribute('height');
            }

            $imgClass = isset($options[self::HTMLRETRIEVEIMAGES_IMG_CLASS]) ?
                $options[self::HTMLRETRIEVEIMAGES_IMG_CLASS] : null;

            if ($imgClass) {
                $image->class = $imgClass;
            }

            $imgStyle = isset($options[self::HTMLRETRIEVEIMAGES_IMG_STYLE]) ?
                $options[self::HTMLRETRIEVEIMAGES_IMG_STYLE] : null;

            if ($imgStyle) {
                $image->style = $imgStyle;
            }

            $images[] = $image;
            $loadedImages[] = (string) $src;
        }

        return $images;
    }

    public function retrieveFirstImage()
    {
        $images = $this->retrieveImagesFromHtml();

        if ((!$images) || ($images->isEmpty())) {
            return null;
        }

        return $images->first()->src;
    }

    /**
     * retrieveImageSrcFromBrackets.
     *
     * @return string
     */
    public function retrieveImageSrcFromBrackets()
    {
        $pattern = '/\[img\]([^\[]+)\[\/img\]/is';

        if (preg_match($pattern, $this->str, $match)) {
            return self::create($match[1]);
        }

        return null;
    }

    /**
     * retrieveImageSrcFromMarkdown.
     *
     * @return string
     */
    public function retrieveImageSrcFromMarkdown()
    {
        $pattern = '/!\[[^\n\]]*\]\(([^\n\)]*)\)/';

        if (preg_match($pattern, $this->str, $match)) {
            return self::create($match[1]);
        }

        return null;
    }

    /**
     * isValidCronjobExpr.
     *
     * @param string $expr Param
     *
     * @return bool
     */
    public function isValidCronjobExpr()
    {
        return (bool) preg_match('/^(((([0-9]+)(\,[0-9]+)*)|\*) ){4}((([0-9]+)(\,[0-9]+)*)|\*)$/', $this->str);
    }

    /**
     * retrieveImageSrcFromGallery.
     *
     * @param string $text param
     *
     * @return string
     */
    public function retrieveImageSrcFromGallery()
    {
        $pattern = '/{gallery}([^\:]+)\:\:\:[0-9]+\:[0-9]+{\/gallery}/';

        if (preg_match($pattern, $this->str, $match)) {
            return self::create($match[1]);
        }

        return null;
    }

    /**
     * decodeJsonPackage.
     *
     * @param string $text Param
     *
     * @return string
     */
    public static function decodeJsonPackage($text)
    {
        $parts = explode(EJSON_START, $text);

        if (2 !== \count($parts)) {
            return null;
        }

        $text = $parts[1];

        $parts = explode(EJSON_END, $text);

        if (2 !== \count($parts)) {
            return null;
        }

        $text = $parts[0];

        return json_decode($text);
    }

    /**
     * encodeJsonPackage.
     *
     * @param mixed  $message  Param
     * @param string $callback Param
     *
     * @return string
     */
    public static function encodeJsonPackage($message, $callback = null)
    {
        $result = json_encode($message);

        if (!$result) {
            throw new \Exception('JSON encoding error');
        }

        if ($callback) {
            $document = F0FPlatform::getInstance()->getDocument();
            $document->setMimeEncoding('application/javascript');

            // $message = EJSON_START . $callback . '(' . $result . ');' . EJSON_END;
            $message = $callback.'('.$result.');';

            return self::create($message);
        }

        return self::create(EJSON_START.$result.EJSON_END);
    }

    /**
     * encodeJsonSuccessPackage.
     *
     * @param mixed  $message  Param
     * @param string $callback Param
     *
     * @return string
     */
    public static function encodeJsonSuccessPackage($message, $callback = null)
    {
        $result = [
            'status' => true,
            'messageType' => 'success',
            'hash' => AutotweetBaseHelper::getHash(),
        ];

        if ((\is_string($message)) || (\is_object($message))) {
            $result['message'] = $message;
        }

        if (\is_array($message)) {
            $result = array_merge($result, $message);
        }

        return self::encodeJsonPackage($result, $callback);
    }

    /**
     * encodeJsonErrorPackage.
     *
     * @param mixed  $message  Param
     * @param string $callback Param
     *
     * @return string
     */
    public static function encodeJsonErrorPackage($message, $callback = null)
    {
        $result = [
            'status' => false,
            'messageType' => 'error',
            'hash' => AutotweetBaseHelper::getHash(),
        ];

        if ((\is_string($message)) || (\is_object($message))) {
            $result['message'] = $message;
        }

        if (\is_array($message)) {
            $result = array_merge($result, $message);
        }

        return self::encodeJsonPackage($result, $callback);
    }

    public function splitArticleText()
    {
        $pattern = '#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i';

        if (preg_match($pattern, $this->str)) {
            return preg_split($pattern, $this->str, 2);
        }

        return [$this->str, null];
    }

    public function mimeToExtension()
    {
        list($type, $extension) = $this->split('/');

        if ('jpeg' === $extension) {
            $extension = 'jpg';
        }

        return self::create($extension);
    }

    public function getClassShortName()
    {
        $str = $this->str;
        $arr = explode('\\', $str);
        $classShortName = array_pop($arr);

        return self::create($classShortName);
    }

    public function getClassRootLevel()
    {
        $str = $this->str;
        $arr = array_filter(explode('\\', $str));
        $rootLevel = array_shift($arr);

        return self::create($rootLevel);
    }

    public static function toClassRootLevel($object)
    {
        return self::create(\get_class($object))->getClassRootLevel();
    }

    public static function toClassShortName($object)
    {
        return self::create(\get_class($object))->getClassShortName();
    }

    public static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, \count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    public function cleanHtmlTags()
    {
        $this->str = (string) HtmlTagsCleaner::create($this->str);

        return $this->trim();
    }

    public function sanitize()
    {
        $this->str = filter_var($this->str, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

        return $this;
    }

    /**
     * checkListed.
     *
     * @param array $list Params
     *
     * @return bool
     */
    public function checkListContains($list)
    {
        foreach ($list as $value) {
            if ($this->contains($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Converts the string into an URL slug. This includes replacing non-ASCII
     * characters with their closest ASCII equivalents, removing remaining
     * non-ASCII and non-alphanumeric characters, and replacing whitespace with
     * $replacement. The replacement defaults to a single dash, and the string
     * is also converted to lowercase. The language of the source string can
     * also be supplied for language-specific transliteration.
     *
     * @param string $replacement The string used to replace whitespace
     * @param string $language    Language of the source string
     *
     * @return static Object whose $str has been converted to an URL slug
     */
    public function slugify($replacement = '-', $language = 'en')
    {
        $stringy = $this->toAscii($language);

        $stringy->str = str_replace('@', $replacement, $stringy);
        $quotedReplacement = preg_quote($replacement);
        $pattern = "/[^a-zA-Z\\d\\s\\-_${quotedReplacement}]/u";
        $stringy->str = preg_replace($pattern, '', $stringy);

        return $stringy->toLowerCase()->delimit($replacement)
            ->removeLeft($replacement)->removeRight($replacement);
    }

    public function smartChunkSplit($length)
    {
        $parts = $this->splitHtmlParts();

        $result = [];
        $buffer = [];
        $bufferLength = 0;

        foreach ($parts as $part) {
            $sPart = self::create($part);
            $cleanedText = (string) $sPart->clean();
            $textLength = \strlen($cleanedText);

            // It's a tag, emit it
            if (($sPart->startsWith('<')) || ($sPart->endsWith('>'))) {
                $buffer[] = $cleanedText;
                $bufferLength += $textLength;

                continue;
            }

            // If it is within the length, add it to the buffer
            if (($bufferLength + $textLength) <= $length) {
                $buffer[] = $cleanedText;
                $bufferLength += $textLength;

                continue;
            }

            // If it beyond the length limit, emit it and prepare the next buffer
            $result[] = implode(' ', $buffer);

            $buffer = [$cleanedText];
            $bufferLength = $textLength;
        }

        // Release the final buffer
        if (!empty($buffer)) {
            $result[] = implode(' ', $buffer);
        }

        return $result;
    }

    private function splitHtmlParts()
    {
        $text = (string) $this->trim()
            ->regexReplace('\s\s+', ' ');

        preg_match_all('#<[^<^>]*>|[^<]*|<[^<^>]*#u', $text, $matches);

        return array_filter($matches[0]);
    }

    private function arrayToArrayofStrings($array)
    {
        for ($i = 0, $n = \count($array); $i < $n; ++$i) {
            $array[$i] = (string) self::create($array[$i], $this->encoding);
        }

        return $array;
    }
}
