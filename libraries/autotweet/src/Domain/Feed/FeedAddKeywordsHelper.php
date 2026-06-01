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
 * FeedAddKeywordsHelper class.
 *
 * @since       1.0
 */
class FeedAddKeywordsHelper
{
    private $addkeyParams;

    private $akProcessDesc;

    private $akProcessKeys;

    /**
     * generateMeta.
     *
     * @param object $article        Params
     * @param string $non_object     Params
     * @param bool   $doDesc         Params
     * @param bool   $processGlobals Params
     *
     * @return string
     */
    public function generateMeta($article, $non_object, $doDesc, $processGlobals = true)
    {
        // Check $non-object to ascertain whether to treat $article as an object or variable
        // Params redefined as this is a call from outside the plugin but able to set whether to do description, $doDesc
        if ($non_object) {
            if ($this->addkeyParams->processPlugins) {
                $article = JHTML::_('content.prepare', $article);
            }

            $getText = strip_tags($article);
            $getKeys = '';
            $getDesc = '';

            $keywords = $this->generateKeywords($getKeys, $getText, $article, $processGlobals, $author = false, $cat_enabled = false);

            $doDesc ? $description = $this->generateDescription($getDesc, $getText, $processGlobals) : $description = '';
        } else {
            // Check if we should exclude this
            $endNow = $this->categoryCheck($article->sectionid, $article->catid);

            if ($endNow) {
                return;
            }

            // Set up variables
            $getKeys = $article->metakey;
            $getDesc = $article->metadesc;

            $getTextDesc = $article->introtext.' '.$article->fulltext;

            if ($this->addkeyParams->processPlugins) {
                if (\Joomla\CMS\Factory::getApplication()->isClient('site')) {
                    $getTextDesc = JHTML::_('content.prepare', $getTextDesc);
                }
            }

            $getTextDesc = strip_tags($getTextDesc);
            $this->addkeyParams->useTitle ? $getText = strip_tags($article->title).' '.$getTextDesc : $getText = $getTextDesc;

            if (1 === (int) $this->addkeyParams->doKeys || 1 === (int) $this->addkeyParams->doDesc) {
                // See if keywords and/or description should be replaced/updated
                // We're keeping all the existing metadata
                if ((bool) strpos($getKeys, '@KEEP') && true === (bool) strpos($getDesc, '@KEEP')) {
                    $description = trim(str_replace('@KEEP', '', $getDesc));
                    $keywords = trim(str_replace('@KEEP', '', $getKeys));
                } elseif ((bool) strpos($getKeys, '@KEEP')) {
                    // Keep the keywords but replace the description, if set
                    $keywords = trim(str_replace('@KEEP', '', $getKeys));

                    if (1 === (int) $this->addkeyParams->doDesc) {
                        $description = $this->generateDescription($getDesc, $getTextDesc, $processGlobals);
                    }
                } elseif ((bool) strpos($getDesc, '@KEEP')) {
                    // Keep the description but replace the keywords, if set
                    $description = trim(str_replace('@KEEP', '', $getDesc));

                    if (1 === (int) $this->addkeyParams->doKeys) {
                        $keywords = $this->generateKeywords($getKeys, $getText, $article, $processGlobals);
                    }
                } else {
                    // Process whole article
                    if (1 === (int) $this->addkeyParams->doDesc) {
                        if (1 === $this->addkeyParams->doEmptyDesc) {
                            if (empty($getDesc)) {
                                $description = $this->generateDescription($getDesc, $getTextDesc, $processGlobals);
                            } else {
                                $description = $getDesc;
                            }
                        } else {
                            $description = $this->generateDescription($getDesc, $getTextDesc, $processGlobals);
                        }
                    }

                    if (1 === (int) $this->addkeyParams->doKeys) {
                        if (1 === (int) $this->addkeyParams->doEmptyKeys) {
                            if (empty($getKeys)) {
                                $keywords = $this->generateKeywords($getKeys, $getText, $article, $processGlobals);
                            } else {
                                $keywords = $getKeys;
                            }
                        } else {
                            $keywords = $this->generateKeywords($getKeys, $getText, $article, $processGlobals);
                        }
                    }
                }
            } else {
                // Not processing - see if this should be overridden
                // See if keywords and/or description should be replaced/updated

                // We're processing all metadata
                if ((bool) strpos($getKeys, '@PROCESS') && true === (bool) strpos($getDesc, '@PROCESS')) {
                    $description = trim(str_replace('@PROCESS', '', $getDesc));
                    $description = $this->generateDescription($getDesc, $getTextDesc, $processGlobals);
                    $keywords = trim(str_replace('@PROCESS', '', $getKeys));
                    $keywords = $this->generateKeywords($getKeys, $getText, $article, $processGlobals);
                } elseif ((bool) strpos($getKeys, '@PROCESS')) {
                    // Process keywords but keep the description
                    $keywords = trim(str_replace('@PROCESS', '', $getKeys));
                    $keywords = $this->generateKeywords($getKeys, $getText, $article, $processGlobals);
                    $description = $article->metadesc;
                } elseif ((bool) strpos($getDesc, '@PROCESS')) {
                    // Process the description but keep the keywords
                    $description = trim(str_replace('@PROCESS', '', $getDesc));
                    $description = $this->generateDescription($getDesc, $getTextDesc, $processGlobals);
                    $keywords = $article->metakey;
                } else {
                    // Don't change anything
                    $description = $article->metadesc;
                    $keywords = $article->metakey;
                }
            }
        }

        $meta_data = [];
        $meta_data['keywords'] = $keywords;
        $meta_data['description'] = $description;

        return $meta_data;
    }

    /**
     * import.
     *
     * @param object $article  Params
     * @param string $keywords Params
     */
    private function addAuthor($article, $keywords)
    {
        if (!empty($article->created_by_alias)) {
            if (empty($keywords)) {
                $keywords .= $article->created_by_alias;
            } else {
                $keywords .= ','.$article->created_by_alias;
            }
        } else {
            $db = \Joomla\CMS\Factory::getDBO();
            $query = 'SELECT '.$db->nameQuote('name').' FROM '
                    .$db->nameQuote('#__users')
                    .' WHERE '.$db->nameQuote('id').' = '.$db->Quote($article->created_by);

            $db->setQuery($query);
            $author = $db->loadResult();

            if ($author) {
                if (empty($keywords)) {
                    $keywords .= $author;
                } else {
                    $keywords .= ','.$author;
                }
            }
        }

        return $keywords;
    }

    /**
     * import.
     *
     * @param object $article  Params
     * @param string $keywords Params
     * @param string $type     Params
     */
    private function addCategory($article, $keywords, $type)
    {
        $db = \Joomla\CMS\Factory::getDBO();

        switch ($type) {
            case 'section':
                $query = 'SELECT '.$db->nameQuote('title')
                    .' FROM '.$db->nameQuote('#__sections')
                    .' WHERE '.$db->nameQuote('id').' = '.$db->Quote($article->sectionid);

                break;
            case 'category':
                $query = 'SELECT '.$db->nameQuote('title')
                    .' FROM '.$db->nameQuote('#__categories')
                    .' WHERE '.$db->nameQuote('id').' = '.$db->Quote($article->catid);

                break;
            case 'both':
                $query1 = 'SELECT '.$db->nameQuote('title')
                    .' FROM '.$db->nameQuote('#__sections')
                    .' WHERE '.$db->nameQuote('id').' = '.$db->Quote($article->sectionid);

                $query2 = 'SELECT '.$db->nameQuote('title')
                    .' FROM '.$db->nameQuote('#__categories')
                    .' WHERE '.$db->nameQuote('id').' = '.$db->Quote($article->catid);

                break;
        }

        if ('both' === $type) {
            $db->setQuery($query1);
            $sect = $db->loadResult();

            if (null === $sect) {
                $sect = 'Uncategorised';
            }

            $db->setQuery($query2);
            $cat = $db->loadResult();

            if (null === $cat) {
                $cat = 'Uncategorised';
            }

            if ($sect && $cat) {
                $cat_enabled = $sect.','.$cat;
            } elseif ($sect && !$cat) {
                $cat_enabled = $sect;
            } elseif ($cat && !$sect) {
                $cat_enabled = $cat;
            }

            if ($cat_enabled) {
                if (empty($keywords)) {
                    $keywords .= $cat_enabled;
                } else {
                    $keywords .= ','.$cat_enabled;
                }
            }
        } else {
            $db->setQuery($query);
            $cat_enabled = $db->loadResult();

            if (null === $cat_enabled) {
                $cat_enabled = 'Uncategorised';
            }

            if ($cat_enabled) {
                if (empty($keywords)) {
                    $keywords .= $cat_enabled;
                } else {
                    $keywords .= ','.$cat_enabled;
                }
            }
        }

        return $keywords;
    }

    /**
     * categoryCheck.
     *
     * @param int $catid Params
     *
     * @return bool
     */
    private function categoryCheck($catid)
    {
        // If this is an excluded section or category, return 0
        if (isset($this->addkeyParams->akCategories)) {
            if (is_array($this->addkeyParams->akCategories) && in_array($catid, $this->addkeyParams->akCategories, true)) {
                return true;
            }
            if ($catid === $this->addkeyParams->akCategories) {
                return true;
            }
        }

        // Otherwise 0 to continue
        return false;
    }

    /**
     * cleanWhitespace.
     *
     * @param string &$text Params
     */
    private function cleanWhitespace(&$text)
    {
        $text = str_replace(
            [
                "\t','\n','\r','\0','\x0B",
            ],
            ' ',
            $text
        );

        while (strpos($text, '  ')) {
            $text = str_ireplace('  ', ' ', $text);
        }
    }

    /**
     * generateKeywords.
     *
     * @param string $oldKeys        Params
     * @param string $text           Params
     * @param string $article        Params
     * @param string $processGlobals Params
     * @param string $author         Params
     * @param string $cat_enabled    Params
     */
    private function generateKeywords($oldKeys, $text, $article, $processGlobals, $author = true, $cat_enabled = true)
    {
        // Keywords to preserve
        if (1 === $this->addkeyParams->preserveKeys) {
            $oldKeys = html_entity_decode($oldKeys, \ENT_QUOTES, 'UTF-8');

            if (preg_match('#{([\s\S]*)}#u', $oldKeys, $matches)) {
                $savedKeys = $matches[1];
            }
        } else {
            $savedKeys = null;
        }

        $text = html_entity_decode($text, \ENT_QUOTES, 'UTF-8');

        // Get rid of &nbsp; - deprecated but kept for pre-PHP5.2 support
        if ($this->addkeyParams->oldphp) {
            $replace = [
                "&nbsp;','&bdquo;','&rdquo;','&rsquo;','&Idquo;','&Isquo;','&ndash;','&quot;",
            ];
            $text = str_ireplace($replace, ' ', $text);
        }

        // Start cleaning up the article text
        // Cleans up plugin calls
        $text = preg_replace('#{[^}]*?}(?(?=[^{]*?{\/[^}]*?})[^{]*?{\/[^}]*?})#u', '', $text);

        // Cleans any numbers or punctuation/newlines etc which were causing blanks/dashes etc in the final output
        if ($this->addkeyParams->oldphp) {
            $text = preg_replace('#[\d\W]#u', ' ', $text);
        } else {
            // New syntax more forgiving for hyphenated words but may still break them and does not work with PHP <5.2
            // Non-English character safe!
            $text = preg_replace('#\\P{L}#u', ' ', $text);
        }

        // More efficient to change entire string to lower case here than via array_map
        $text = preg_replace('#[\s]{2,}#u', ' ', $text);

        $text = strtolower($text);

        // Get rid of undefined variables errors
        $whiteToAdd = '';
        $whiteToAddArray = [];
        $multiWordWhiteToAddArray = [];
        $keywords = '';

        if (isset($this->addkeyParams->multiWordWhiteList)) {
            strtolower($this->addkeyParams->multiWordWhiteList);
            $multiWordWhiteArray = TextUtil::listToArray($this->addkeyParams->multiWordWhiteList);

            foreach ($multiWordWhiteArray as $multiWordWhiteWord) {
                $multiWordWhiteWord = trim($multiWordWhiteWord);

                if ($multiWordWhiteWord) {
                    if ($multiWordCount = substr_count($text, $multiWordWhiteWord)) {
                        $multiWordCount *= $this->addkeyParams->multiWordWeighting;
                        $multiWordWhiteToAddArray[$multiWordWhiteWord] = $multiWordCount;

                        if ($this->addkeyParams->unsetMultiWord) {
                            str_ireplace($multiWordWhiteWord, '', $text);
                        }
                    }
                }
            }
        }

        if (isset($this->addkeyParams->whiteList)) {
            strtolower($this->addkeyParams->whiteList);
            $whiteArray = TextUtil::listToArray($this->addkeyParams->whiteList);

            foreach ($whiteArray as $whiteWord) {
                $whiteWord = trim($whiteWord);

                if ($whiteWord) {
                    if ($whiteWordCount = substr_count($text, $whiteWord)) {
                        $whiteWordCount *= $this->addkeyParams->whiteWordWeighting;
                        $whiteToAddArray[$whiteWord] = $whiteWordCount;
                        str_ireplace($whiteWord, '', $text);
                    }
                }
            }
        }

        if ($this->addkeyParams->whiteListOnly) {
            $textArray = [];
        } else {
            $textArray = explode(' ', $text);
            $textArray = array_count_values($textArray);

            // Remove blacklisted words
            strtolower($this->addkeyParams->blackList);
            $blackArray = TextUtil::listToArray($this->addkeyParams->blackList);

            foreach ($blackArray as $blackWord) {
                if (isset($textArray[trim($blackWord)])) {
                    unset($textArray[trim($blackWord)]);
                }
            }
        }

        $textArray = array_merge($textArray, $whiteToAddArray, $multiWordWhiteToAddArray);

        // Sort by frequency
        arsort($textArray);

        $i = 1;

        foreach ($textArray as $word => $instances) {
            if ($i > $this->addkeyParams->keyCount) {
                break;
            }

            if (strlen(trim($word)) >= $this->addkeyParams->minLength) {
                if (!isset($keywordsIn)) {
                    $keywordsIn = [];
                }

                $keywordsIn[] = trim($word);
                ++$i;
            }
        }

        // Make the vars whiteToAdd and keywords, add in the whitelist words
        if (isset($keywordsIn)) {
            $keywords = implode(',', $keywordsIn);
        }

        // Add in the preserved meta keywords
        if (isset($savedKeys)) {
            $keywords .= ', '.$savedKeys;
        }

        // Add the author or author alias as a keyword if desired
        if ($author) {
            if (1 === $this->addkeyParams->addAuthor) {
                $keywords = $this->addAuthor($article, $keywords);
            }
        }

        // Add section/category if set
        if ($cat_enabled) {
            if ($this->addkeyParams->addSectCat) {
                $keywords = $this->addCategory($article, $keywords, $this->addkeyParams->addSectCat);
            }
        }

        if ($processGlobals) {
            $this->akProcessKeys = 1;
        }

        // Do we need to revert encoding for non-English characters?
        return trim(strtolower($keywords));
    }

    /**
     * generateDescription.
     *
     * @param string $oldDesc        Params
     * @param string $text           Params
     * @param string $processGlobals Params
     */
    private function generateDescription($oldDesc, $text, $processGlobals)
    {
        // Description to preserve

        if (1 === $this->addkeyParams->preserveDesc) {
            $oldDesc = html_entity_decode($oldDesc, \ENT_QUOTES, 'UTF-8');
            $this->cleanWhitespace($oldDesc);

            if (preg_match('#{([^}][\s\S]*)}#u', $oldDesc, $matches)) {
                $savedDesc = $matches[1];

                if (strpos($savedDesc, '[start]')) {
                    $position = 'start';
                    $savedDesc = str_ireplace('[start]', '', $savedDesc);
                } else {
                    $position = 'end';
                }
            }
        }

        $text = html_entity_decode($text, \ENT_QUOTES, 'UTF-8');

        // Start cleaning up the article text
        // Cleans up plugin calls
        $text = preg_replace('#{[^}]*?}(?(?=[^{]*?{\/[^}]*?})[^{]*?{\/[^}]*?})#u', '', $text);

        // Get rid of all forms of whitespace except single spaces
        $text = preg_replace('#[\s]{2,}#u', ' ', $text);

        // Use sentence, word or char count to make description
        // Char count is now the fallback method
        if ('sentence' === $this->addkeyParams->descPrimary) {
            // Setup pattern to find sentences and create description depending on defined number of sentences
            $description = '';
            $pattern = '#\b(.+?[\.|\!|\?])#u';

            for ($i = 0; $i < $this->addkeyParams->descSentCount; ++$i) {
                $offset = '';

                if (preg_match($pattern, $text, $matches)) {
                    $match = $matches[1];
                } else {
                    break;
                }

                $description .= ' '.$match;

                $offset = strpos($text, $match);
                $offset += strlen($match);
                $text = substr($text, $offset);
            }
        }

        if ('word' === $this->addkeyParams->descPrimary) {
            $explode = explode(' ', trim($text));
            $string = '';

            for ($i = 0; $i < $this->addkeyParams->descWordCount; ++$i) {
                if (isset($explode[$i])) {
                    $string .= $explode[$i].' ';
                } else {
                    break;
                }
            }

            $description = trim($string);
        }

        // If description is null, fallback to char count
        if ('char' === $this->addkeyParams->descPrimary || empty($description)) {
            $description = substr(trim($text), 0, $this->addkeyParams->descCharCount);
        }

        // Add in the preserved description
        if (isset($savedDesc)) {
            if ('start' === $position) {
                $description = trim($savedDesc).' '.trim($description);
            } elseif ('end' === $position) {
                $description = trim($description).' '.trim($savedDesc);
            }
        }

        if ($this->addkeyParams->dotdotdot) {
            if (!strpos($description, '...')) {
                $description .= '...';
            }
        }

        if ($processGlobals) {
            $this->akProcessDesc = 1;
        }

        return trim($description);
    }
}
