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
 * OGSmartLoader.
 *
 * @since       1.0
 */
class OGSmartLoader
{
    public $item;

    /**
     * __construct.
     *
     * @param object $item Params
     */
    public function __construct($item = null)
    {
        if (!$item) {
            $item = new OGItem();
        }

        $this->item = $item;
    }

    /**
     * getItemByPost.
     *
     * @param object $postId Params
     *
     * @return object
     */
    public function getItemByPost($postId)
    {
        $post = XTF0FModel::getTmpInstance('Posts', 'AutoTweetModel')->getTable();
        $post->reset();
        $post->load($postId);
        $post->xtform = EForm::paramsToRegistry($post);

        if ($postId === (int) $post->id) {
            if ('autotweetcontent' === $post->plugin && $this->assignByArticle($post)) {
                return $this->item;
            }

            $this->assignPost($post);
        }

        return $this->item;
    }

    /**
     * getItem - Deprecated.
     *
     * @return object
     */
    public function getItem()
    {
        return $this->getItemByUrl();
    }

    /**
     * getItemByUrl.
     *
     * @return object
     */
    public function getItemByUrl()
    {
        $currentBaseUrl = \Joomla\CMS\Uri\Uri::current();

        $db = \Joomla\CMS\Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*')
            ->from('#__autotweet_posts')
            ->where('org_url like '.$db->q($currentBaseUrl.'%'))
            ->order('ID desc')
            ->setLimit(1);
        $db->setQuery($query);
        $post = $db->loadObject();

        if ($post) {
            $this->assignPost($post);
        }

        return $this->item;
    }

    /**
     * assignPost.
     *
     * @param object $post Params
     */
    private function assignPost($post)
    {
        $currentUrl = \Joomla\CMS\Uri\Uri::getInstance()->toString();
        $post->xtform = EForm::paramsToRegistry($post);

        if (empty($this->item->title)) {
            $this->item->title = $post->title;
        }

        if (empty($this->item->introtext)) {
            $this->item->introtext = $post->fulltext;
        }

        if (empty($this->item->publish_up)) {
            $this->item->publish_up = $post->created;
        }

        if (empty($this->item->modified)) {
            $this->item->modified = $post->modified;
        }

        if (empty($this->item->firstContentImage)) {
            $this->item->firstContentImage = $post->image_url;
            $this->item->introImage = $post->image_url;
            $this->item->fullTextImage = $post->image_url;
            $this->item->imageArray = [];
            $this->item->imageArray[] = $post->image_url;
        }

        if (empty($this->item->url)) {
            $this->item->url = $currentUrl;
        }
    }

    private function assignByArticle($post)
    {
        $id = $post->ref_id;

        if (empty($id)) {
            return false;
        }

        $nativeObject = $post->xtform->get('native_object');
        $articleHelper = OGArticleFactory::getHelper('com_content', 'com_content.article', $nativeObject);
        $this->item = $articleHelper->getArticle();

        return $this->item;
    }
}
