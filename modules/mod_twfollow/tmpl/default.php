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
use Joomla\CMS\Language\Text;

$live_site = \Joomla\CMS\Uri\Uri::base();

if (!$twData) {
    echo 'Error: no data';
} else {
    $twShowDate = (int) $params->get('twShowDate', 0);
    $twParseLinks = (int) $params->get('twParseLinks', 0);
    $twLinkText = $params->get('twLinkText', 'more...');
    $twShowFollowLink = (int) $params->get('twShowFollowLink', 1);
    $twFollowText = $params->get('twFollowText', '');

    $urlParseRule = '/.*?((?:http|https)(?::\/{2}[\w]+)(?:[\/|\.]?)(?:[^\s"]*))/is';
    $html = [];

    // Tweets (status timeline)

    if (count($twData['timeline']['tweets']) > 0) {
        $html[] = '<ul class="twtweets">';
        $now = \Joomla\CMS\Factory::getDate();

        foreach ($twData['timeline']['tweets'] as $tweet) {
            $tweet_url = '';
            preg_match_all($urlParseRule, $tweet['text'], $matches);

            foreach ($matches[1] as $url) {
                switch ($twParseLinks) {
                    // Do not show link
                    case 0:
                        $tweet['text'] = str_replace($url, '', $tweet['text']);

                        break;
                    // Show link as text
                    case 1:
                        // Nothing to do, show message as it is
                        break;
                    // Show link as link
                    case 2:
                        $tweet['text'] = str_replace($url, JHTML::_('link', $url, $url, ['target' => '_blank']), $tweet['text']);

                        break;
                    // Show whole message as link
                    case 3:
                    // Show text entered bellow as the link
                    case 4:
                        $tweet_url = $url;
                        $tweet['text'] = str_replace($url, '', $tweet['text']);

                        break;
                }
            }

            $html[] = '<li class="twfitem">';

            if ($twShowDate) {
                $html[] = '<span class="twffollowDate">'.JHTML::_('date', $date->toUnix(), Text::_('DATE_FORMAT_LC3')).'</span>';
            }

            if ((3 === (int) $twParseLinks) && !empty($tweet_url)) {
                // Show whole message as link
                $tweet['text'] = JHTML::_('link', $tweet_url, $tweet['text'], ['target' => '_blank']);
            } elseif ((4 === (int) $twParseLinks) && !empty($tweet_url)) {
                // Show text entered bellow as the link
                $tweet['text'] .= '- '.JHTML::_('link', $tweet_url, $twLinkText, ['target' => '_blank']);
            }

            $html[] = $tweet['text'];
            $html[] = '</li>';
        }

        $html[] = '</ul>';
    }

    // Follow link
    if ($twShowFollowLink) {
        $html[] = '<hr />';
        $html[] = '<p class="twffollow">';
        $html[] = '<a class="twflink" href="'.$twData['follow_link'].'" target="_blank">'.$twFollowText.'</a>';

        if (1 === (int) $twShowFollowLink) {
            $html[] = '<a class="twfimg" href="'.$twData['follow_link'].'" target="_blank"><img src="media/com_autotweet/images/twitter.png"></a>';
        }

        $html[] = '</p>';
    }

    echo implode("\n", $html);
}
