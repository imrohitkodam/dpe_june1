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
final class TextUtil
{
    private function __construct()
    {
    }

    /**
     * truncString.
     *
     * @param string $text             Param
     * @param int    $max_chars        Param
     * @param bool   $withDots         Param
     * @param bool   $withByteCounting Param
     *
     * @return string
     */
    public static function truncString($text, $max_chars, $withDots = null, $withByteCounting = null)
    {
        if ($withByteCounting) {
            return self::truncStringMb($text, $max_chars, $withDots);
        }

        if (null === $withDots) {
            $withDots = !EParameter::getComponentParam(CAUTOTWEETNG, 'donot_add_dots');
        }

        $length = strlen($text);

        if ($length > $max_chars) {
            if ($withDots) {
                // -Dots
                // -2 Utf8 case
                $max_chars = $max_chars - 3 - 2;
            }

            $text = substr($text, 0, $max_chars);

            // Yes, it can return more characters, but utf8_strlen($text) is Ok, so ...
            $l = $max_chars;

            // Strlen shorter than strlen for UTF-8  - 2 char languages E.g. Hebrew
            while (strlen($text) > $max_chars) {
                --$l;
                $text = substr($text, 0, $l);
            }

            if ($withDots) {
                $text = $text.'...';
            }
        }

        return $text;
    }

    /**
     * truncString.
     *
     * @param string $text      Param
     * @param int    $max_chars Param
     * @param bool   $withDots  Param
     *
     * @return string
     */
    public static function truncStringMb($text, $max_chars, $withDots = null)
    {
        if (null === $withDots) {
            $withDots = !EParameter::getComponentParam(CAUTOTWEETNG, 'donot_add_dots');
        }

        $length = mb_strlen($text);

        if ($length > $max_chars) {
            if ($withDots) {
                // -Dots
                // -2 Utf8 case
                $max_chars = $max_chars - 3 - 2;
            }

            $text = mb_strcut($text, 0, $max_chars);

            // Yes, it can return more characters, but utf8_strlen($text) is Ok, so ...
            $l = $max_chars;

            // Strlen shorter than strlen for UTF-8  - 2 char languages E.g. Hebrew
            while (mb_strlen($text) > $max_chars) {
                --$l;
                $text = mb_strcut($text, 0, $l);
            }

            if ($withDots) {
                $text = $text.'...';
            }
        }

        return $text;
    }

    /**
     * cleanText.
     *
     * @param string $text Param
     *
     * @return string
     */
    public static function cleanText($text)
    {
        // Replace &nbsp;, to avoid #160
        $text = str_replace('&nbsp;', ' ', $text);

        // Strip HTML Tags
        $text = strip_tags($text);

        // Clean up things like &amp;
        $text = html_entity_decode($text);

        // Strip out any url-encoded stuff
        $text = urldecode($text);

        // Replace non-AlNum characters with space - TOO Strict
        // $clear = preg_replace('/[^A-Za-z0-9]/', ' ', $clear);

        // Trim the string of leading/trailing space
        $text = trim($text);

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

        // Replace Multiple spaces with single space
        $text = preg_replace('/ +/', ' ', $text);

        // !(/images/sample.png)
        $text = preg_replace('/!\(([^\n\)]*)\)/', '', $text);

        return $text;
    }

    /**
     * generateCr.
     *
     * @param string $text Param
     * @param string $str  Param
     *
     * @return string
     */
    public static function generateCr($text, $str = null)
    {
        if ($str) {
            // |CR| - Just replace them with str
            $text = str_replace('|CR|', $str, $text);
        } else {
            // |CR| - To create specific \n
            $text = str_replace('|CR|', "\n", $text);
        }

        return $text;
    }

    /**
     * getMessageWithUrl.
     *
     * @param object &$channel        Param
     * @param object &$post           Param
     * @param string $short_url       Param
     * @param bool   $shorturl_always Param
     *
     * @return array
     */
    public static function getMessageWithUrl(&$channel, &$post, $short_url, $shorturl_always)
    {
        $includeHashTags = $channel->includeHashTags();
        $hashtags = null;
        $message = $post->message;

        if ($includeHashTags) {
            $hashtags = $post->xtform->get('hashtags');

            if ($hashtags) {
                $hashtags = trim($hashtags);
                $hashtags = self::cleanText($hashtags);
                $message = $message.' '.$hashtags;
            }
        }

        $channel_show_url = $channel->showUrl();

        if ($channel_show_url) {
            $post->show_url = $channel_show_url;
        }

        $message = self::cleanText($message);
        $message = self::generateCr($message);
        $message_len = strlen($message);

        $long_url = $post->org_url;
        $is_showing_url = ((PostShareManager::SHOWURL_OFF !== $post->show_url) && (!empty($long_url)));
        $has_media = (($channel->isMediaModePostWithImage()) && (isset($post->image_url)));
        $has_weight = $channel->hasWeight();
        $max_chars = $channel->getMaxChars();

        $url = null;
        $url_len = 0;
        $totalmsg_len = $message_len;

        // Url Required and there's a Long Url
        if ($is_showing_url) {
            // Let's try with the long url
            $url = $long_url;
            $url_len = self::processUrlLength($long_url, $has_weight, $is_showing_url);
            $totalmsg_len = $message_len + $url_len;

            // If always use ShortUrl or message len > channel max
            if (($shorturl_always) || ($totalmsg_len > $max_chars)) {
                $url = $short_url;
                $url_len = self::processUrlLength($short_url, $has_weight, $is_showing_url);
                $totalmsg_len = $message_len + $url_len;
            }
        }

        $max_chars = self::processMaxCharsAvailable($max_chars, $has_weight, $is_showing_url, $has_media);

        // Trunc text if needed, when Message Len > Max Channel Chars
        if ($totalmsg_len > $max_chars) {
            // Available chars for Message text
            $available_chars = $max_chars - $url_len;
            $withDots = !EParameter::getComponentParam(CAUTOTWEETNG, 'donot_add_dots');

            if ($withDots) {
                // Needs 3 chars for replacement with 3 dots
                $available_chars = $available_chars - 3;
            }

            // And, the final cut
            // $message = substr($message, 0, $available_chars);
            $words = explode(' ', $message);
            $n = count($words);
            $composed = [];
            $i = 0;
            $j = 0;

            while (($i < $n) && ($j < $available_chars)) {
                $word = $words[$i];
                $composed[] = $word;
                ++$i;
                $j += strlen($word) + 1;
            }

            if ($j > $available_chars) {
                array_pop($composed);
            }

            $message = implode(' ', $composed);

            if ($withDots) {
                $message .= '...';
            }
        }

        // Construct status message
        switch ($post->show_url) {
            case PostShareManager::SHOWURL_OFF:
                // Dont show url, do nothing
                break;
            case PostShareManager::SHOWURL_BEGINNING:
                // Show url at beginning of message
                $message = $url.' '.$message;

                break;
            case PostShareManager::SHOWURL_END:
                // Show url at end of message
                $message = $message.' '.$url;

                break;
        }

        return [
            'url' => $url,
            'message' => $message,
        ];
    }

    /**
     * adminNotification.
     *
     * @param string $channel Param
     * @param string $msg     Param
     * @param object &$post   Param
     *
     * @return bool
     */
    public static function adminNotification($channel, $msg, &$post)
    {
        if (!EParameter::getComponentParam(CAUTOTWEETNG, 'admin_notification', 0)) {
            return;
        }

        $emailSubject = 'PerfectPublisher Notification - Error on Channel "'.$channel.'"';
        $emailBody = "<h2>Hi,</h2>
		<p>This is an <b>PerfectPublisher</b> Notification, about an error on channel \"{$channel}\".</p>
		<h3>Error Message</h3>
		<p>{$msg}</p>
		<h3>Post details</h3>
		<p>{$post->message}</p>
		<p>If you are working in the configuration, it must be related with your work. However, if the site is stable, you should check if there's any problem (E.g. an expired token).</p>
		<p><br></p>
		<p>If you have any question, the support team is ready to answer!</p>
		<p>Best regards,</p>
		<p>Support Team<br> <b>Extly.com</b> - Extensions<br> Support: <a target=\"_blank\" href=\"http://support.extly.com\">http://support.extly.com</a><br> Twitter @extly | Facebook <a target=\"_blank\" href=\"http://www.facebook.com/extly\">www.facebook.com/extly</a></p>";
        Notification::mailToAdmin($emailSubject, $emailBody);
    }

    /**
     * listToArray.
     *
     * @param string $list Param
     *
     * @return array
     */
    public static function listToArray($list)
    {
        $cleanedList = preg_replace('/,\s+/', ',', trim($list));

        return array_filter(explode(',', $cleanedList));
    }

    /**
     * cleanListOfNumerics.
     *
     * @param string $list Param
     *
     * @return array
     */
    public static function cleanListOfNumerics($list)
    {
        return preg_replace('/[^,0-9]/', '', $list);
    }

    /**
     * getImageFromTextWithBrackets.
     *
     * @param string $text param
     *
     * @return string
     */
    public static function getImageFromTextWithBrackets($text)
    {
        $pattern = '/\[img\]([^\[]+)\[\/img\]/is';

        if (preg_match($pattern, $text, $match)) {
            return $match[1];
        }

        return null;
    }

    /**
     * getImageFromYoutubeWithBrackets.
     *
     * @param string $text param
     *
     * @return string
     */
    public static function getImageFromYoutubeWithBrackets($text)
    {
        $image = null;
        $pattern = '/\{youtube\}([^\{]+)\{\/youtube\}/';

        if (preg_match($pattern, $text, $match)) {
            $youtube = $match[1];

            // {youtube}V3_WLFvoIxc|600|450|1{/youtube}
            $youtube = explode('|', $youtube);

            if (!empty($youtube)) {
                $youtube = $youtube[0];
                $image = 'http://img.youtube.com/vi/'.$youtube.'/0.jpg';
            }
        }

        return $image;
    }

    /**
     * getImageFromTextWithMarkdown.
     *
     * @param string $text param
     *
     * @return string
     */
    public static function getImageFromTextWithMarkdown($text)
    {
        $image = null;
        $pattern = '/!\[[^\n\]]*\]\(([^\n\)]*)\)/';

        if (preg_match($pattern, $text, $match)) {
            $image = $match[1];

            if (!RouteHelp::getInstance()->isAbsoluteUrl($image)) {
                // Guessing host
                $pattern = '/href=["|\'](.*)["|\']/';

                if (preg_match($pattern, $text, $match)) {
                    $url = $match[1];
                    $uri = \Joomla\CMS\Uri\Uri::getInstance($url);
                    $uri->setQuery('');
                    $uri->setPath($image);
                    $image = $uri->toString();
                }
            }
        }

        return $image;
    }

    /**
     * getImageFromGalleryTag.
     *
     * @param string $text param
     *
     * @return string
     */
    public static function getImageFromGalleryTag($text)
    {
        if (!preg_match('/{gallery}([^\:]+)\:\:\:[0-9]+\:[0-9]+{\/gallery}/', $text, $matches)) {
            return null;
        }

        $folder = $matches[1];

        $media = 'images/'.$folder;
        $galpath = JPATH_ROOT.'/'.$media;

        try {
            foreach (new DirectoryIterator($galpath) as $file) {
                $img = $galpath.'/'.$file->getFilename();

                if (($file->isFile()) && (ImageUtil::isImage($img))) {
                    return $media.'/'.$file->getFilename();
                }
            }
        } catch (Exception $e) {
        }
    }

    /**
     * renderUrl.
     *
     * @param string $url param
     *
     * @return string
     */
    public static function renderUrl($url)
    {
        return htmlspecialchars(JStringPunycode::urlToUTF8($url), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * convertUrlSafe.
     *
     * @param string $string Param
     *
     * @return string
     */
    public static function convertUrlSafe($string)
    {
        $urlSafe = JApplicationHelper::stringURLSafe($string);
        $urlSafe = str_replace('&', '-', $urlSafe);

        return $urlSafe;
    }

    /**
     * nextScheduledDate.
     *
     * @param string $unix_mhdmd Param
     * @param string $now        Param
     *
     * @return object
     */
    public static function nextScheduledDate($unix_mhdmd, $now = 'now')
    {
        try {
            $cron = Scheduler::getParser($unix_mhdmd);

            if ('now' === $now) {
                $now = \Joomla\CMS\Factory::getDate();
            }

            // From Unix GMT to User timezone
            $tz = EParameter::getTimeZone();
            $now->setTimezone($tz);

            // From JDate to DateTime
            $date = new DateTime();
            $date->setTimestamp($now->getTimestamp());
            $nextDate = $cron->getNextRunDate($date);

            // From User timezone back to Unix GMT
            $nextDateTimestamp = $nextDate->getTimestamp();
            $nextDate = \Joomla\CMS\Factory::getDate($nextDateTimestamp);

            return $nextDate;
        } catch (Exception $e) {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::ERROR, 'nextScheduledDate: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Replace links in text with html links.
     *
     * @param string $text Param
     *
     * @return string
     */
    public static function autoLink($text)
    {
        // Subdomain must be taken into consideration too
        $pattern = '~(
					  (
					   #(?<=([^[:punct:]]{1})|^)			# that must not start with a punctuation (to check not HTML)
					   	(https?://)|(www)[^-][a-zA-Z0-9-]*?[.]	# normal URL lookup
					   )
					   [^\s()<>]+						# characters that satisfy SEF url
					   (?:								# followed by
					   		\([\w\d]+\)					# common character
					   		|							# OR
					   		([^[:punct:]\s]|/)			# any non-punctuation character followed by space OR forward slash
					   )
					 )~x';

        return preg_replace_callback($pattern, function ($matches) {
            $url = array_shift($matches);

            $text = parse_url($url, \PHP_URL_HOST).parse_url($url, \PHP_URL_PATH);

            $last = -(strlen(strrchr($text, '/'))) + 1;

            if ($last < 0) {
                $text = substr($text, 0, $last).'...';
            }

            if (false !== strpos($url, 'www') && false === strpos($url, 'http://') && false === strpos($url, 'https://')) {
                $url = 'http://'.$url;
            }

            $isInternal = \Joomla\CMS\Uri\Uri::isInternal($url) ? '' : 'target="_blank" ';

            return sprintf('<a rel="nofollow" '.$isInternal.' href="%s">%s</a>', $url, $text);
        }, $text);
    }

    /**
     * createChannelsText.
     *
     * @param array $ids Param
     *
     * @return string
     */
    public static function createChannelsText($ids)
    {
        $model = XTF0FModel::getTmpInstance('Channels', 'AutoTweetModel');
        $model->setState('channel_ids', $ids);
        $channels = $model->getList(true);

        $names = array_map(
            function ($o) {
                return $o->name;
            },
            $channels
        );

        return implode(', ', $names);
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

        if (2 !== count($parts)) {
            return null;
        }

        $text = $parts[1];

        $parts = explode(EJSON_END, $text);

        if (2 !== count($parts)) {
            return null;
        }

        $text = base64_decode($parts[0], true);

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
            throw new Exception('JSON encoding error');
        }

        if ($callback) {
            $document = XTF0FPlatform::getInstance()->getDocument();
            $document->setMimeEncoding('application/javascript');

            $message = $callback.'('.$result.');';

            return $message;
        }

        return EJSON_START.base64_encode($result).EJSON_END;
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

        if ((is_string($message)) || (is_object($message))) {
            $result['message'] = $message;
        }

        if (is_array($message)) {
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

        if ((is_string($message)) || (is_object($message))) {
            $result['message'] = $message;
        }

        if (is_array($message)) {
            $result = array_merge($result, $message);
        }

        return self::encodeJsonPackage($result, $callback);
    }

    /**
     * isValidCronjobExpr.
     *
     * @param string $expr Param
     *
     * @return bool
     */
    public static function isValidCronjobExpr($expr)
    {
        return (empty($expr))
            || (preg_match('/^(((([0-9]+)(\,[0-9]+)*)|\*) ){4}((([0-9]+)(\,[0-9]+)*)|\*)$/', $expr));
    }

    /**
     * json_decode.
     *
     * @param string $expr    Param
     * @param mixed  $json
     * @param mixed  $assoc
     * @param mixed  $depth
     * @param mixed  $options
     *
     * @return mixed
     */
    public static function json_decode($json, $assoc = false, $depth = 512, $options = 0)
    {
        if (empty($json)) {
            return null;
        }

        $json = rtrim($json, chr(0));

        if (empty($json)) {
            return null;
        }

        $json = str_replace('\u0000', '', $json);

        if (empty($json)) {
            return null;
        }

        // Make sure it looks like a valid JSON string and is at least 12 characters (minimum valid message length)
        if ((strlen($json) < 12) || ('{' !== substr($json, 0, 1)) || ('}' !== substr($json, -1))) {
            return null;
        }

        // PHP 5.3 compatible - , $options
        $json = json_decode($json, $assoc, $depth);

        return $json;
    }

    /**
     * telegramPhotoCaptionFilter.
     *
     * @param string $text Param
     *
     * @return string
     */
    public static function telegramPhotoCaptionFilter($text)
    {
        $text = html_entity_decode($text, \ENT_QUOTES, 'UTF-8');
        $text = str_replace('<br>', \PHP_EOL, $text);
        $text = preg_replace('/\n[\n\r\s]*\n[\n\r\s]*\n/u', "\n\n", $text);
        $text = strip_tags($text);

        return $text;
    }

    /**
     * truncateHtml.
     *
     * Kudos to https://github.com/urodoz/truncateHTML
     *
     * @param string $text         Param
     * @param int    $length       Param
     * @param string $ending       Param
     * @param bool   $exact        Param
     * @param bool   $considerHtml Param
     *
     * @return string
     */
    public static function truncateHtml(
        $text,
        $length = 100,
        $ending = '...',
        $exact = false,
        $considerHtml = true
    ) {
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, \PREG_SET_ORDER);
            $total_length = strlen($ending);
            $open_tags = [];
            $truncate = '';
            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                        // do nothing
                        // if tag is a closing tag
                    } elseif (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        // delete tag from $open_tags list
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if (false !== $pos) {
                            unset($open_tags[$pos]);
                        }
                        // if tag is an opening tag
                    } elseif (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                        // add tag to the beginning of $open_tags list
                        array_unshift($open_tags, strtolower($tag_matchings[1]));
                    }
                    // add html-tag to $truncate'd text
                    $truncate .= $line_matchings[1];
                }
                // calculate the length of the plain text part of the line; handle entities as one character
                $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
                if ($total_length + $content_length > $length) {
                    // the number of characters which are left
                    $left = $length - $total_length;
                    $entities_length = 0;
                    // search for html entities
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, \PREG_OFFSET_CAPTURE)) {
                        // calculate the real length of all entities in the legal range
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entities_length <= $left) {
                                --$left;
                                $entities_length += strlen($entity[0]);
                            } else {
                                // no more characters left
                                break;
                            }
                        }
                    }
                    $truncate .= substr($line_matchings[2], 0, $left + $entities_length);
                    // maximum lenght is reached, so get off the loop
                    break;
                } else {
                    $truncate .= $line_matchings[2];
                    $total_length += $content_length;
                }
                // if the maximum length is reached, get off the loop
                if ($total_length >= $length) {
                    break;
                }
            }
        } else {
            if (strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = substr($text, 0, $length - strlen($ending));
            }
        }
        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurance of a space...
            $spacepos = strrpos($truncate, ' ');
            if (isset($spacepos)) {
                // ...and cut the text in this position
                $truncate = substr($truncate, 0, $spacepos);
            }
        }
        // add the defined ending to the text
        $truncate .= $ending;
        if ($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $truncate .= '</'.$tag.'>';
            }
        }

        return $truncate;
    }

    /**
     * processUrlLength.
     *
     * @param string $url            Param
     * @param bool   $has_weight     Param
     * @param bool   $is_showing_url Param
     *
     * @return int
     */
    private static function processUrlLength($url, $has_weight, $is_showing_url)
    {
        // If channel has Weight or Show_Url is off
        if ($has_weight) {
            // Url len does not count, we must substract the Weight to $max_chars
            // Url has a fixed "Weight", not the usual len
            $url_len = 0;
        } else {
            if ($is_showing_url) {
                // Simplest case: Len Url plus a space
                $url_len = strlen($url) + 1;
            } else {
                // Url not required
                $url_len = 0;
            }
        }

        return $url_len;
    }

    /**
     * processMaxCharsAvailable.
     *
     * @param int  $max_chars      Param
     * @param bool $has_weight     Param
     * @param bool $is_showing_url Param
     * @param bool $has_media      Param
     *
     * @return int
     */
    private static function processMaxCharsAvailable($max_chars, $has_weight, $is_showing_url, $has_media)
    {
        $url_weight = EParameter::getComponentParam(CAUTOTWEETNG, 'url_weight', 23);

        if (($has_weight) && ($is_showing_url)) {
            $max_chars = $max_chars - $url_weight;
        }

        return $max_chars;
    }
}
