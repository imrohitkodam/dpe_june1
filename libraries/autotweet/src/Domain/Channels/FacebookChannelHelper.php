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
 * FacebookChannelHelper class.
 * AutoTweet Facebook channel for wall posts.
 * Posts to the wall of profiles, groups, pages, events.
 *
 * @since       1.0
 */
class FacebookChannelHelper extends FacebookBaseChannelHelper
{
    /**
     * sendMessage.
     *
     * @param string $message Params
     * @param object $data    Params
     *
     * @return booleans
     */
    public function sendMessage($message, $data)
    {
        try {
            $this->cleanCacheAllIncluded();
        } catch (Exception $e) {
        }

        // Simulated
        if (0 === (int) $this->channel->params->get('use_own_api')) {
            return $this->testMessage();
        }

        $isUserProfile = $this->isUserProfile();
        $enabledOpenGraphFeatures = $this->channel->params->get('open_graph_features');

        if (($enabledOpenGraphFeatures) && ($isUserProfile)) {
            return $this->sendFacebookOG($message, $data->title, $data->fulltext, $data->url, $data->org_url, $data->image_url, $this->getMediaMode(), $data);
        }

        $name = $data->title;
        $description = $data->fulltext;
        $originalUrl = $data->org_url;
        $picture = $data->image_url;

        $logger = AutotweetLogger::getInstance();

        $postLink = $originalUrl;

        if (('autotweetpost' === $data->plugin) && ($data->id)) {
            $postLink = 'index.php?option=com_autotweet&view=composerpost&id='
                .$data->id.'-'.
                TextUtil::convertUrlSafe($data->title);

            $needles = [];
            $needles['view'] = 'composerpost';
            $itemId = AutotweetBaseHelper::getItemid('com_autotweet', $needles);

            if ($itemId) {
                $postLink = $postLink.'&Itemid='.$itemId;
            }

            $routeHelp = RouteHelp::getInstance();

            if (RouteHelp::isMultilingual()) {
                $postLink = $routeHelp->getAbsoluteRawUrl($postLink);
            } else {
                $postLink = $routeHelp->getAbsoluteUrl($postLink);
            }

            $logger->log(\Joomla\CMS\Log\Log::INFO, 'Composerpost Url: '.$postLink);
        }

        $logger->log(\Joomla\CMS\Log\Log::INFO, 'sendFacebookMessage', $message);

        $fbId = $this->getFbChannelId();
        $accessToken = $this->get('fbchannel_access_token');
        $accessToken = $this->matchToken($accessToken, $fbId);
        $postWithImage = true;

        if ($this->isMediaModeTextOnlyPost()) {
            $postWithImage = false;
        }

        $actions = [];
        $caption = null;

        if (empty($originalUrl)) {
            $postWithImage = false;
        } else {
            // Extract data for action link
            $url_comps = parse_url($originalUrl);
            $caption = $url_comps['host'];

            if (EParameter::getComponentParam(CAUTOTWEETNG, 'include_authors')) {
                $author = $data->xtform->get('author');
                $author = \Joomla\CMS\Factory::getUser($author);
                $authorName = $author->name;

                if (!empty($authorName)) {
                    $caption = $caption.' | '.$authorName;
                }
            }

            $actions['name'] = $caption;
            $actions['link'] = $postLink;
        }

        $name = TextUtil::truncString($name, self::MAX_CHARS_NAME, false, true);

        $arguments = [
            'link' => $postLink,
        ];

        if (!$this->isApi11OrSuperior) {
            $arguments['name'] = $name;
            $arguments['caption'] = $caption;
            $arguments['description'] = $description;
        }

        if (!empty($actions)) {
            $arguments['actions'] = json_encode($actions);
        }

        if (false !== strpos($originalUrl, '//www.youtube.com/')) {
            $source = str_replace('//www.youtube.com/watch?v=', '//www.youtube.com/v/', $originalUrl);
            $arguments['source'] = $source;

            if (empty($picture)) {
                $picture = str_replace('//www.youtube.com/watch?v=', '//img.youtube.com/vi/', $originalUrl).'/0.jpg';
            }
        }

        if ($isUserProfile) {
            $privacy = $this->get('sharedwith', 'EVERYONE');
            $privacy = ['value' => $privacy];
            $arguments['privacy'] = json_encode($privacy);
        }

        if ((empty($description)) && (empty($picture))) {
            $postWithImage = false;
        }

        // Include image tag only, when image url is not empty to avoid error "... must have a valid src..."
        if ((!$this->isApi11OrSuperior) && (!empty($picture)) && ($postWithImage)) {
            $arguments['picture'] = $picture;
        }

        // Message
        $arguments['message'] = $message;

        try {
            $apiUrl = '/'.$fbId.'/feed';
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'FacebookChannelHelper callApiPost '.$apiUrl, $arguments);
            // $logger->log(\Joomla\CMS\Log\Log::INFO, 'FacebookChannelHelper access_token', $accessToken);

            $post = $this->callApiPost($apiUrl, $arguments, $accessToken);

            $msg = 'Facebook id: '.$post['id'].' - '.$postLink;
            $result = [
                true,
                $msg,
            ];
        } catch (Exception $e) {
            $code = $e->getCode();
            $msg = $code.' - '.$e->getMessage().' - '.$postLink;

            $result = [
                false,
                $msg,
            ];
        }

        return $result;
    }

    /**
     * cleanCacheAllIncluded.
     */
    protected function cleanCacheAllIncluded()
    {
        $pluginData = \Joomla\CMS\Plugin\PluginHelper::getPlugin('content', 'autotweetopengraph');

        if (empty($pluginData)) {
            return;
        }

        $params = json_decode($pluginData->params, true);

        if (empty($params)) {
            return;
        }

        if (!isset($params['included_components'])) {
            $params['included_components'] = 'com_content,com_easyblog,com_flexicontent,com_k2,com_zoo';
        }

        $includedComponents = $params['included_components'];
        $includedComponents = explode(',', str_replace(['\n', ' '], [',', ''], $includedComponents));

        if (empty($includedComponents)) {
            return;
        }

        foreach ($includedComponents as $component) {
            $this->cleanCache($component);
        }
    }

    /**
     * Clean the cache.
     *
     * @param string $group     The cache group
     * @param int    $client_id The ID of the client
     *
     * @since   3.0
     */
    protected function cleanCache($group = null, $client_id = 0)
    {
        $conf = \Joomla\CMS\Factory::getConfig();

        $options = [
            'defaultgroup' => $group ?: ($this->option ?? \Joomla\CMS\Factory::getApplication()->input->get('option')),
            'cachebase' => $client_id ? JPATH_ADMINISTRATOR.'/cache' : $conf->get('cache_path', JPATH_SITE.'/cache'),
            'result' => true,
        ];

        try {
            /** @var JCacheControllerCallback $cache */
            $cache = JCache::getInstance('callback', $options);
            $cache->clean();
        } catch (JCacheException $exception) {
            $options['result'] = false;
        }

        // Trigger the onContentCleanCache event.
        \XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\DispatcherHelper::trigger(
            'onContentCleanCache',
            $options
        );
    }
}
