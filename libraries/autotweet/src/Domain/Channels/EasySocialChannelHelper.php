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

include_once JPATH_ROOT.'/administrator/components/com_easysocial/includes/foundry.php';

/**
 * EasySocialChannelHelper.
 *
 * @since       1.0
 */
class EasySocialChannelHelper extends ChannelHelper
{
    /**
     * sendMessage.
     *
     * @param string $message Param
     * @param object $data    Params
     *
     * @return array
     */
    public function sendMessage($message, $data)
    {
        try {
            $mediaMode = $this->getMediaMode();

            $imageUrl = null;

            if ('message' != $mediaMode) {
                $imageUrl = $data->image_url;
            }

            $author = $data->xtform->get('author');
            $authorUserId = \Joomla\CMS\User\UserHelper::getUserId($author);

            if (!class_exists('FD')) {
                throw new Exception('EasySocial is not installed!');
            }

            // Get the current author of the channel.
            $channelAuthor = FD::user($authorUserId);
            $channelTargetId = $this->channel->params->get('targetId');
            $targetType = $this->getDetectTargetType($channelTargetId);
            $story = FD::story(SOCIAL_TYPE_USER);

            // Posted data.
            $post = [];
            $post['content'] = $message;
            $post['attachment'] = 'story';
            $post['privacyCustom'] = '';

            if ($imageUrl) {
                $post['attachment'] = 'links';
                $post['links_title'] = $data->title;
                $post['links_description'] = $data->fulltext;
                $post['links_url'] = $data->url;
                $post['links_image'] = $imageUrl;
            }

            if (SOCIAL_TYPE_GROUP == $targetType) {
                $post['target'] = $authorUserId;
                $post['cluster'] = $channelTargetId;
                $post['clusterType'] = 'group';
                $post['privacy'] = '';
                $postActor = null;
            } elseif (SOCIAL_TYPE_PAGE == $targetType) {
                $post['target'] = $authorUserId;
                $post['cluster'] = $channelTargetId;
                $post['clusterType'] = 'page';
                $post['privacy'] = '';
                $postActor = 'page';
            } else {
                // SOCIAL_TYPE_USER
                $channelAuthor = FD::user($channelTargetId);
                $post['target'] = $channelTargetId;
                $post['cluster'] = '';
                $post['clusterType'] = '';
                $post['privacy'] = 'public';
                $postActor = null;
            }

            // Determine the post types.
            $type = isset($post['attachment']) && !empty($post['attachment']) ? $post['attachment'] : SOCIAL_TYPE_STORY;

            // Check if the content is empty only for story based items.
            if ((!isset($post['content']) || empty($post['content'])) && SOCIAL_TYPE_STORY == $type) {
                throw new Exception(JText::_('COM_EASYSOCIAL_STORY_PLEASE_POST_MESSAGE'));
            }

            // Check if the content is empty and there's no photos.
            if ((!isset($post['photos']) || empty($post['photos'])) && 'photos' == $type) {
                throw new Exception(JText::_('COM_EASYSOCIAL_STORY_PLEASE_ADD_PHOTO'));
            }

            // We need to allow raw because we want to allow <,> in the text but it should be escaped during display
            $content = $message;

            // Check whether the user can really post something on the target
            $privacy = FD::privacy($channelAuthor->id);
            $state = $privacy->validate('profiles.post.status', $post['target'], $targetType);

            if (!$state) {
                throw new Exception(JText::_('COM_EASYSOCIAL_STORY_NOT_ALLOW_TO_POST'));
            }

            $location = null;
            $friends = [];
            $contextIds = 0;

            // For photos that are posted on the story form
            if ('photos' == $type && isset($post['photos'])) {
                $contextIds = $post['photos'];
            }

            // Check if there are mentions provided from the post.
            $mentions = $post['mentions'] ?? [];

            // Process moods here
            $mood = FD::table('Mood');

            // $hasMood = $mood->bindPost($post);

            // Set the privacy for the album
            $privacy = $post['privacy'];
            $customPrivacy = $post['privacyCustom'];
            $privacyRule = ('photos' == $type) ? 'photos.view' : 'story.view';

            $cluster = $post['cluster'] ?? '';
            $clusterType = $post['clusterType'] ?? '';

            // $isCluster = ($cluster) ? true : false;

            $targetId = $post['target'];

            $args = [
                'content' => $content,
                'contextIds' => $contextIds,
                'contextType' => $type,
                'actorId' => $channelAuthor->id,
                'targetId' => $targetId,
                'location' => $location,
                'with' => $friends,
                'mentions' => $mentions,
                'cluster' => $cluster,
                'clusterType' => $clusterType,
                'mood' => $mood,
                'privacyRule' => $privacyRule,
                'privacyValue' => $privacy,
                'privacyCustom' => $customPrivacy,
                'postActor' => $postActor,
                'anywhereId' => '',
            ];

            // Create the stream item
            $stream = $story->create($args);

            if ($imageUrl) {
                $this->_storeLinks($stream, $post['links_url'], $post['links_title'], $post['links_description'], $post['links_image']);
            }

            // @badge: story.create
            // Add badge for the author when a report is created.
            $badge = FD::badges();
            $badge->log('com_easysocial', 'story.create', $channelAuthor->id, JText::_('COM_EASYSOCIAL_STORY_BADGE_CREATED_STORY'));

            // @points: story.create
            // Add points for the author when a report is created.
            $points = FD::points();
            $points->assign('story.create', 'com_easysocial', $channelAuthor->id);

            $result = [
                true,
                'OK - '.$stream->getPermalink(false, true),
            ];
        } catch (Exception $e) {
            return [
                false,
                $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * includeHashTags.
     *
     * @return bool
     */
    public function includeHashTags()
    {
        return $this->channel->params->get('hashtags', true);
    }

    public static function getManagerUsers()
    {
        $dbConnection = \Joomla\CMS\Factory::getDbo();
        $query = 'SELECT DISTINCT a.id, `a`.`username` as title FROM `#__users` AS a LEFT JOIN #__user_usergroup_map AS map2 ON map2.user_id = a.id WHERE map2.group_id in (6, 7, 8) ORDER BY `a`.`username` ASC';
        $dbConnection->setQuery($query);

        return $dbConnection->loadObjectList();
    }

    /**
     * getTargets.
     *
     * @return array
     */
    public static function getTargets()
    {
        $groups = self::getGroups();
        $pages = self::getPages();
        $users = self::getManagerUsers();

        return array_merge($groups, $pages, $users);
    }

    /**
     * getDetectTargetType.
     *
     * @param int $channelTargetId Param
     *
     * @return object
     */
    public static function getDetectTargetType($channelTargetId)
    {
        if (empty($channelTargetId)) {
            return SOCIAL_TYPE_USER;
        }

        $targetObject = ES::group($channelTargetId);

        if ($targetObject->id == $channelTargetId) {
            return SOCIAL_TYPE_GROUP;
        }

        $targetObject = ES::page($channelTargetId);

        if ($targetObject->id == $channelTargetId) {
            return SOCIAL_TYPE_PAGE;
        }

        return SOCIAL_TYPE_USER;
    }

    /**
     * getGroups.
     *
     * @return array
     */
    protected static function getGroups()
    {
        $model = ES::model('Groups');

        // Determine the ordering of the groups
        $ordering = 'title';

        // Default options
        $options = [];

        // Limit the number of groups based on the params
        $options['limit'] = 0;
        $options['ordering'] = $ordering;
        $options['state'] = SOCIAL_STATE_PUBLISHED;
        $options['inclusion'] = null;
        $options['types'] = 'all';
        $groups = $model->getGroups($options);

        return $groups;
    }

    /**
     * getPages.
     *
     * @return array
     */
    protected static function getPages()
    {
        $model = ES::model('Pages');

        // Get the ordering of the pages
        $ordering = 'title';

        // Default options
        $options = [];

        // Limit the number of pages based on the params
        $options['limit'] = 0;
        $options['ordering'] = $ordering;
        $options['state'] = SOCIAL_STATE_PUBLISHED;
        $options['inclusion'] = null;
        $options['types'] = 'all';
        $pages = $model->getPages($options);

        return $pages;
    }

    /**
     * Stores any link assets.
     *
     * @param object $streamItem Param
     * @param string $link       Param
     * @param string $title      Param
     * @param string $content    Param
     * @param string $image      Param
     *
     * @return bool
     */
    private function _storeLinks($streamItem, $link, $title, $content, $image)
    {
        // Get the link information from the request

        /*
        $link = $this->app->input->get('links_url', '', 'default');
        $title = $this->app->input->get('links_title', '', 'default');
        $content = $this->app->input->get('links_description', '', 'default');
        $image = $this->app->input->get('links_image', '', 'default');
        */

        // If there's no data, we don't need to store in the assets table.
        if (empty($title) && empty($content) && empty($image)) {
            return false;
        }

        // Cache the image if necessary
        $links = FD::links();
        $fileName = $links->cacheImage($image);

        $registry = FD::registry();
        $registry->set('title', $title);
        $registry->set('content', $content);
        $registry->set('image', $image);
        $registry->set('link', $link);
        $registry->set('cached', false);

        // Image link should only be modified when the file exists
        if (false !== $fileName) {
            $registry->set('cached', true);
            $registry->set('image', $fileName);
        }

        // Store the link object into the assets table
        $assets = FD::table('StreamAsset');
        $assets->stream_id = $streamItem->uid;
        $assets->type = 'links';
        $assets->data = $registry->toString();

        // Store the assets
        $state = $assets->store();

        return $state;
    }
}
