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
 * Helper functions for AutoTweet plugins.
 *
 * @since       1.0
 */
abstract class AutotweetBaseHelper
{
    public const TAG_AUTHOR = '[author]';

    public const TAG_AUTHOR_NAME = '[author-name]';

    public const TAG_HASHTAGS = '[hashtags]';

    public const TAG_MAINCAT = '[maincat]';

    public const TAG_MAINCAT_LIT = '[maincat-lit]';

    public const TAG_LASTCAT = '[lastcat]';

    public const TAG_LASTCAT_LIT = '[lastcat-lit]';

    public const TAG_ALLCATS = '[allcats]';

    public const TAG_ALLCATS_LIT = '[allcats-lit]';

    /**
     * AutotweetBaseHelper.
     */
    private function __construct()
    {
        // Static class
    }

    // Check type and range of textcount parameter, and correct if needed

    /**
     * getTextcount.
     *
     * @param string $textcount Param
     *
     * @return int
     */
    public static function getTextcount($textcount)
    {
        return SharingHelper::MAX_CHARS_TITLE;
    }

    /**
     * Use title or text as twitter message.
     *
     * @param bool   $usetext   Param
     * @param int    $textcount Param
     * @param string $title     Param
     * @param string $text      Param
     *
     * @return string
     */
    public static function getMessagetext($usetext, $textcount, $title, $text)
    {
        $message = '';

        switch ($usetext) {
            // Show title only
            case 0:
                $message = $title;

                break;
            // Show text only
            case 1:
                if (!empty($text)) {
                    $message = $text;
                } else {
                    $message = $title;
                }

                break;
            // Show title and text
            case 2:
                if (!empty($text)) {
                    $message = $title.': '.$text;
                } else {
                    $message = $title;
                }

                break;
            default:
                $message = $title;
        }

        $message = TextUtil::cleanText($message);
        $message = TextUtil::truncString($message, $textcount);

        return $message;
    }

    /**
     * Replaces spaces for hashtags.
     *
     * @param string $word Param
     *
     * @return string
     */
    public static function getAsHashtag($word)
    {
        if (!empty($word)) {
            $word = TextUtil::cleanText($word);
            $word = str_ireplace(' ', '', $word);
            $word = str_ireplace('-', '', $word);
            $hash = '#'.$word;
        } else {
            $hash = '';
        }

        return $hash;
    }

    /**
     * Returns hashtags from comma seperated string.
     *
     * @param string $tags  Param
     * @param int    $count Param
     *
     * @return string
     */
    public static function getHashtags($tags, $count = 1)
    {
        $h = [];

        if (!empty($tags)) {
            $i = 0;
            $words = TextUtil::listToArray($tags);

            foreach ($words as $word) {
                $h[] = self::getAsHashtag($word);

                ++$i;

                if ($i >= $count) {
                    break;
                }
            }
        }

        $h = implode(' ', $h);

        return $h;
    }

    /**
     * Add static text / hashtags to message.
     *
     * @param int    $textpos    Param
     * @param string $text       Param
     * @param string $statictext Param
     *
     * @return string
     */
    public static function addStatictext($textpos, $text, $statictext)
    {
        if (PostShareManager::STATICTEXT_BEGINNING === $textpos) {
            $textpos = 1;
        } elseif (PostShareManager::STATICTEXT_END === $textpos) {
            $textpos = 2;
        }

        switch ($textpos) {
            // Dont use static_text, use original text
            case 0:
                $result_text = $text;

                break;
            // Position at the beginning of message text
            case 1:
                $result_text = $statictext;

                if (!empty($text)) {
                    $result_text .= ' '.$text;
                }

                break;
            // Position at the end of message text
            case 2:
                $result_text = $text;

                if (!empty($result_text)) {
                    $result_text .= ' ';
                }

                $result_text .= $statictext;

                break;
            default:
                $result_text = $text;
        }

        return $result_text;
    }

    /**
     * Apply the text pattern in the data array.
     *
     * @param string $pattern Param
     * @param object &$post   Param
     */
    public static function applyTextPattern($pattern, &$post)
    {
        $author = $post->xtform->get('author');
        $pattern = str_replace(self::TAG_AUTHOR, $author, $pattern);

        if ((false !== strpos($pattern, self::TAG_AUTHOR_NAME)) && (!empty($author))) {
            $author_userId = \Joomla\CMS\User\UserHelper::getUserId($author);
            $author_user = \Joomla\CMS\Factory::getUser($author_userId);
            $pattern = str_replace(self::TAG_AUTHOR_NAME, $author_user->name, $pattern);
        }

        $pattern = self::processTextPattern($pattern, 'message', $post->message);
        $pattern = self::processTextPattern($pattern, 'introtext', $post->message);
        $pattern = self::processTextPattern($pattern, 'text', $post->message);

        $pattern = self::processTextPattern($pattern, 'title', $post->title);
        $pattern = self::processTextPattern($pattern, 'fulltext', $post->fulltext);

        if (false !== strpos($pattern, self::TAG_HASHTAGS)) {
            $hashtags = $post->xtform->get('hashtags');
            $post->xtform->set('hashtags', null);
            $pattern = str_replace(self::TAG_HASHTAGS, $hashtags, $pattern);
        }

        $cats = $post->cat_names;
        $count_cats = count($cats);

        if (0 === $count_cats) {
            $post->message = $pattern;

            return;
        }

        if (false !== strpos($pattern, self::TAG_MAINCAT)) {
            $maincat = self::hashtize($cats[0]);
            $pattern = str_replace(self::TAG_MAINCAT, $maincat, $pattern);
        }

        if (false !== strpos($pattern, self::TAG_MAINCAT_LIT)) {
            $maincat = $cats[0];
            $pattern = str_replace(self::TAG_MAINCAT_LIT, $maincat, $pattern);
        }

        if (false !== strpos($pattern, self::TAG_LASTCAT)) {
            $lastcat = self::hashtize($cats[$count_cats - 1]);
            $pattern = str_replace(self::TAG_LASTCAT, $lastcat, $pattern);
        }

        if (false !== strpos($pattern, self::TAG_LASTCAT_LIT)) {
            $lastcat = $cats[$count_cats - 1];
            $pattern = str_replace(self::TAG_LASTCAT_LIT, $lastcat, $pattern);
        }

        if (false !== strpos($pattern, self::TAG_ALLCATS)) {
            array_walk($cats, 'AutotweetBaseHelper::hashtize');
            $allcats = implode(' ', $cats);
            $pattern = str_replace(self::TAG_ALLCATS, $allcats, $pattern);
        }

        if (false !== strpos($pattern, self::TAG_ALLCATS_LIT)) {
            $allcats = implode(' ', $cats);
            $pattern = str_replace(self::TAG_ALLCATS_LIT, $allcats, $pattern);
        }

        $post->message = $pattern;
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
        $text = ucwords($text);

        // Replaces every non-letter and non-digit
        $text = preg_replace('/(?=\P{Nd})\P{L}/u', '', $text);

        return '#'.$text;
    }

    /**
     * Add category / section to message text.
     *
     * @param bool   $show     Param
     * @param int    $section  Param
     * @param int    $category Param
     * @param string $text     Param
     * @param bool   $add_hash Param
     *
     * @return array
     */
    public static function addSectionCategory($show, $section, $category, $text, $add_hash = false)
    {
        $result_text = $text;
        $hashtags = '';

        if ($add_hash) {
            // Show as hashtags
            $section = self::getAsHashtag($section);
            $category = self::getAsHashtag($category);

            switch ($show) {
                // Do nothing, use original text
                case 0:
                    break;
                // Show section only
                case 1:
                    $hashtags = $section;

                    break;
                // Show section and category
                case 2:
                    $hashtags = $section.' '.$category;

                    break;
                // Show category only (new feature since 3.0 stable)
                case 3:
                    $hashtags = $category;

                    break;
            }
        } else {
            switch ($show) {
                // Show as pretext (part of message)
                // Do nothing, use original text
                case 0:
                    break;
                // Show section only
                case 1:
                    $result_text = $section.': '.$text;

                    break;
                // Show section and category
                case 2:
                    $result_text = $section.'/'.$category.': '.$text;

                    break;
                // Show category only (new feature since 3.0 stable)
                case 3:
                    $result_text = $category.': '.$text;

                    break;
            }
        }

        $result = [
            'text' => $result_text,
            'hashtags' => $hashtags,
        ];

        return $result;
    }

    /**
     * Special implementation to ad multiple categories.
     *
     * @param bool   $show       Param
     * @param array  $categories Param
     * @param string $text       Param
     * @param bool   $add_hash   Param
     *
     * @return array
     */
    public static function addCategories($show, $categories, $text, $add_hash = false)
    {
        $result_text = $text;
        $hashtags = '';

        if (!empty($categories)) {
            if ($add_hash) {
                switch ($show) {
                    // Do nothing, use original text
                    case 0:
                        break;
                    // Show first category only
                    case 1:
                        $hashtags = self::getAsHashtag($categories[0]);

                        break;
                    // Show all categories
                    case 2:
                        $hashtags = self::getHashtags(implode(',', $categories), count($categories));

                        break;
                }
            } else {
                switch ($show) {
                    // Do nothing, use original text
                    case 0:
                        break;
                    // Show first category only
                    case 1:
                        $result_text = $categories[0].': '.$text;

                        break;
                    // Show all categories
                    case 2:
                        $result_text = trim(implode('/', $categories)).': '.$text;

                        break;
                }
            }
        }

        $result = [
            'text' => $result_text,
            'hashtags' => $hashtags,
        ];

        return $result;
    }

    /**
     * Database helpers: returns the next free id for the table.
     *
     * @param string $table Param
     *
     * @return int
     */
    public static function getID($table)
    {
        $db = \Joomla\CMS\Factory::getDBO();

        $prefix = $db->getPrefix();
        $table = str_replace('#__', $prefix, $table);

        $query = 'SHOW TABLE STATUS LIKE '.$db->Quote($table);
        $db->setQuery($query);
        $result = $db->loadAssoc();

        $next_key = (int) $result['Auto_increment'];

        return $next_key;
    }

    /**
     * Better implementation to handle multiple menu entry for component (multiple itemids).
     *
     * @param string $compName Param
     * @param array  $needles  Param
     *
     * @return int
     */
    public static function getItemid($compName, $needles = [])
    {
        $component = \Joomla\CMS\Component\ComponentHelper::getComponent($compName);

        if (!isset($component->id)) {
            return null;
        }

        $menus = \Joomla\CMS\Factory::getApplication()->getMenu('site');
        $items = $menus->getItems('component_id', $component->id);

        if (empty($items)) {
            return null;
        }

        $matches = [];

        foreach ($items as $item) {
            $matches[$item->id] = 0;

            $url = parse_url($item->link);

            if (!isset($url['query'])) {
                continue;
            }

            parse_str($url['query'], $query);

            foreach ($needles as $needle => $id) {
                if ((isset($query[$needle]))
                    && (('*' === $id) || ($query[$needle] === $id))) {
                    ++$matches[$item->id];
                }
            }
        }

        asort($matches);
        $keys = array_keys($matches);
        $match = array_pop($keys);

        return (int) $match;
    }

    /**
     * convertLocalUTCAgenda.
     *
     * @param array &$agendas Param
     */
    public static function convertLocalUTCAgenda(&$agendas)
    {
        $result = [];

        if (($agendas) && (is_array($agendas))) {
            foreach ($agendas as $agenda) {
                $result[] = EParameter::convertLocalUTC($agenda);
            }
        }

        $agendas = $result;
    }

    /**
     * convertUTCLocalAgenda.
     *
     * @param array &$agendas Param
     */
    public static function convertUTCLocalAgenda(&$agendas)
    {
        $result = [];

        if (($agendas) && (is_array($agendas))) {
            foreach ($agendas as $agenda) {
                $result[] = EParameter::convertUTCLocal($agenda);
            }
        }

        $agendas = $result;
    }

    /**
     * getControllerParams.
     *
     * @param object $autotweet_advanced Param
     *
     * @return array
     */
    public static function getControllerParams($autotweet_advanced = null)
    {
        [$isCli, $isAdmin] = XTF0FDispatcher::isCliAdmin();

        $input = new \Joomla\CMS\Input\Input($_REQUEST);

        $option = $input->get('option');
        $controller = $input->get('controller');
        $task = $input->get('task');
        $view = $input->get('view');
        $layout = $input->get('layout');
        $id = $input->get('id', null, 'int');

        if (!$id) {
            $cid = $input->get('cid', [], 'ARRAY');

            if ((is_array($cid)) && (1 === count($cid))) {
                $id = $cid[0];
            } elseif ((is_numeric($cid)) && ($cid > 0)) {
                $id = $cid;
            }
        }

        // EasyBlog
        if (!$id) {
            $id = $input->get('blogid', null, 'int');
        }

        // EasyBlog 5
        if (!$id) {
            $id = $input->get('uid');

            if ((!empty($id)) && (preg_match('/([0-9]+)\.([0-9]+)/', $id, $matches))) {
                $id = $matches[1];
            }
        }

        // JoomShopping
        if (!$id) {
            $id = $input->get('product_id', null, 'int');
        }

        // Content - Front
        if (!$id) {
            $id = $input->get('a_id', null, 'int');
        }

        // SobiPro
        if (!$id) {
            $id = $input->get('sid', null, 'int');
        }

        // Zoo - Front
        if (!$id) {
            $id = $input->get('item_id', null, 'int');
        }

        // Joocial - Composer
        if (!$id) {
            // Joomla Composer, by Url
            $id = $input->get('ref_id', null, 'cmd');

            // App Composer, by attrs
            if ((empty($id)) && isset($autotweet_advanced->ref_id)) {
                $id = $autotweet_advanced->ref_id;
            }
        }

        return [$isAdmin, $option, $controller, $task, $view, $layout, $id];
    }

    /**
     * getHash.
     *
     * @return string
     */
    public static function getHash()
    {
        return md5(md5(md5(\Joomla\CMS\Factory::getDate()->toUnix().rand()).rand()).rand());
    }

    /**
     * Apply the text pattern in the data array.
     *
     * @param string $text    Param
     * @param array  $tag     Param
     * @param array  $subject Param
     */
    private static function processTextPattern($text, $tag, $subject)
    {
        $pattern = '/\['.$tag.'\,?([0-9]+)?\]/i';

        if (preg_match($pattern, $text, $matches)) {
            if (count($matches) > 1) {
                $limit = $matches[1];
                $subject = TextUtil::truncString($subject, $limit);
            }

            $text = preg_replace($pattern, $subject, $text);
        }

        return $text;
    }
}
