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

// Base class for extension plugins for AutoTweet

/**
 * plgAutotweetBase.
 *
 * @since       1.0
 */
abstract class PlgAutotweetBase extends \Joomla\CMS\Plugin\CMSPlugin implements IAutotweetPlugin
{
    /* Moved to PostShareManager */
    public const POSTTHIS_DEFAULT = 1;
    public const POSTTHIS_NO = 2;
    public const POSTTHIS_YES = 3;
    public const POSTTHIS_IMMEDIATELY = 4;
    public const POSTTHIS_ONLYONCE = 5;
    public const POSTTHIS_YES_ALL = 44;

    // At least 5 minutes to detect new content with the polling query
    public const MIN_POLLING_TIME = 5;

    public const STATIC_TEXT_SOURCE_DISABLED = 0;
    public const STATIC_TEXT_SOURCE_METAKEY = 1;
    public const STATIC_TEXT_SOURCE_STATIC = 2;

    public const STATIC_TEXT_POS_BEGIN = 1;
    public const STATIC_TEXT_POS_END = 2;

    protected $pluginParams;

    protected $autopublish = true;

    protected $show_url;

    protected $advanced_attrs;

    protected $saved_advanced_attrs = false;

    protected $post_featured_only = false;

    protected $published_field = 'state';

    protected $extension_option;

    protected $message = '';

    protected $hashtags = '';

    protected $content_language;

    protected $post_old = 0;

    /**
     * plgAutotweetBase.
     *
     * @param string &$subject Param
     * @param object $params   Param
     */
    public function __construct(&$subject, $params)
    {
        parent::__construct($subject, $params);

        // Load component language file for use with plugin
        $jlang = \Joomla\CMS\Factory::getLanguage();
        $jlang->load('com_autotweet');

        // Since Joomla 1.6 params can be used directly without creating a JParameter object
        $this->pluginParams = $this->params;

        if ((int) $this->pluginParams->get('autopublish', 1)) {
            $this->autopublish = true;
        } else {
            $this->autopublish = false;
        }

        $surl = (int) $this->pluginParams->get('show_url', 2);

        if (2 === $surl) {
            $this->show_url = PostShareManager::SHOWURL_END;
        } elseif (1 === $surl) {
            $this->show_url = PostShareManager::SHOWURL_BEGINNING;
        } else {
            $this->show_url = PostShareManager::SHOWURL_OFF;
        }

        $this->published_field = 'state';
    }

    /**
     * setHashtags.
     *
     * @param string $message Param
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * setHashtags.
     *
     * @param string $hashtags Param
     */
    public function setHashtags($hashtags)
    {
        $this->hashtags = [];

        if (!empty($hashtags)) {
            $this->hashtags[] = $hashtags;
        }
    }

    /**
     * addHashtags.
     *
     * @param string $hashtags Param
     */
    public function addHashtags($hashtags)
    {
        $this->hashtags[] = $hashtags;
    }

    /**
     * renderHashtags.
     *
     * @return string
     */
    public function renderHashtags()
    {
        if (is_array($this->hashtags)) {
            return implode(' ', $this->hashtags);
        }

        return $this->hashtags;
    }

    /**
     * The save event.
     *
     * @param string $context The context
     * @param object $table   The item
     * @param bool   $isNew   Is new item
     * @param array  $data    The validated data
     *
     * @return bool
     *
     * @since   4.0.0
     */
    public function onContentBeforeSave($context, $item, $isNew, $data = [])
    {
        $this->retrieveAdvancedAttributesfromQueryParams();

        return true;
    }

    /**
     * onContentAfterSave.
     *
     * @param object $context the context of the content passed to the plugin
     * @param object $article A JTableContent object
     * @param bool   $isNew   If the content is just about to be created
     *
     * @return bool
     */
    public function onContentAfterSave($context, $article, $isNew)
    {
        if ((isset($article->id)) && ($article->id)) {
            $this->saveAdvancedAttributes($article->id);
        }

        return true;
    }

    /**
     * Checks for new articles in the database (polling!!!).
     *
     * @return bool
     */
    public function onContentPolling()
    {
        if (!class_exists('EParameter')) {
            \Joomla\CMS\Factory::getApplication()->enqueueMessage('Extly Framework is NOT installed.', 'error');

            return;
        }

        $cron_enabled = EParameter::getComponentParam(CAUTOTWEETNG, 'cron_enabled', false);

        // Polling enabled
        if (($this->polling)
            && ((($cron_enabled) && (defined('AUTOTWEET_CRONJOB_RUNNING')))
            || ((!$cron_enabled) && (\Joomla\CMS\Factory::getApplication()->isClient('site'))))) {
            $db = \Joomla\CMS\Factory::getDbo();
            $db->setQuery('SET sql_big_selects=1');
            $db->execute();

            $this->executeContentPolling();
        }

        return true;
    }

    /**
     * retrieveAdvancedAttributesfromQueryParams.
     */
    public function retrieveAdvancedAttributesfromQueryParams()
    {
        if (!PERFECT_PUB_PRO) {
            return;
        }

        // Optimization - This has been already processed by a previous call
        if (($this->advanced_attrs) && ($this->saved_advanced_attrs)) {
            return;
        }

        $input = new \Joomla\CMS\Input\Input($_REQUEST);
        $autotweet_advanced = $input->get('autotweet_advanced_attrs', null, 'string');

        if ($autotweet_advanced) {
            $this->advanced_attrs = AdvancedAttributesHelper::fromQueryParams($autotweet_advanced);

            if (isset($this->advanced_attrs->ref_id)) {
                // Safe to save
                $this->saveAdvancedAttributes($this->advanced_attrs->ref_id);
            }
        }
    }

    /**
     * saveAdvancedAttributes.
     *
     * @param int $id Param
     */
    public function saveAdvancedAttributes($id)
    {
        if (!PERFECT_PUB_PRO) {
            return;
        }

        if (($this->advanced_attrs) && (!$this->saved_advanced_attrs)) {
            // Safe to save
            AdvancedAttributesHelper::save($this->advanced_attrs, $id);
            $this->saved_advanced_attrs = true;
        }
    }

    /**
     * Returns publish mode for plugin (default is true, so this works also for plugin without autopublish option).
     *
     * @return bool true, if autopublishing is enabled for plugin
     */
    public function isAutopublish()
    {
        return $this->autopublish;
    }

    /**
     * Returns url mode for plugin.
     *
     * @return int urlmode (0 =  no url, 1 = show at the beginning, 2 = show at the end of message)
     */
    public function getShowUrlMode()
    {
        return $this->show_url;
    }

    /**
     * getData.
     *
     * @param string $id       param
     * @param string $typeinfo param
     *
     * @return array
     */
    public function getData($id, $typeinfo)
    {
        JError::raiseWarning('5', 'AutoTweet NG Plugin - getData not implemented by plugin.');
    }

    /**
     * getContentCategories.
     *
     * @param array $article_cat param
     *
     * @return array
     */
    public static function getContentCategories($article_cat)
    {
        $cat_ids = [];
        $cat_names = [];
        $cat_alias = [];

        $row = \Joomla\CMS\Table\Table::getInstance('category');

        // JomSocial Conflict Category ?
        if (!method_exists($row, 'load')) {
            include_once JPATH_SITE.'/libraries/legacy/table/category.php';
            $db = \Joomla\CMS\Factory::getDbo();
            $row = new \Joomla\CMS\Table\Category($db);
        }

        $row->load($article_cat);

        while ($row->parent_id > 0) {
            $cat_ids[] = $row->id;
            $cat_names[] = $row->title;
            $cat_alias[] = $row->alias;

            $row->load($row->parent_id);
        }

        return [
            $cat_ids,
            $cat_names,
            $cat_alias,
        ];
    }

    /**
     * Queues a message for posting over AutoTweet.
     *
     * @param int    $id             Param
     * @param date   $publish_up     Param
     * @param string $description    Param
     * @param int    $typeinfo       Param
     * @param string $url            Param
     * @param string $imageUrl       Param
     * @param object &$native_object Param
     * @param object &$params        Param
     *
     * @return bool true, if message is queued for posting
     */
    protected function postStatusMessage($id, $publish_up, $description, $typeinfo = 0, $url = '', $imageUrl = '', &$native_object = null, &$params = null)
    {
        $plug_id = $this->_name;

        $result = AutotweetAPI::insertRequest(
            $id,
            $plug_id,
            $publish_up,
            $description,
            $typeinfo,
            $url,
            $imageUrl,
            $native_object,
            $this->advanced_attrs,
            $params,
            $this->content_language
        );

        return $result;
    }

    /**
     * Cancel pending messages.
     *
     * @param string $ref_id Param
     *
     * @return bool true, if message is queued for posting
     */
    protected function cancelMessages($ref_id)
    {
        $plugin = $this->_name;
        $userid = \Joomla\CMS\Factory::getUser()->id;

        AutotweetAPI::cancelPosts($ref_id, $plugin, $userid);

        if (PERFECT_PUB_PRO) {
            AutotweetAPI::cancelEvergreens($ref_id, $plugin, $userid);
        }

        AutotweetAPI::cancelRequests($ref_id, $plugin, $userid);
    }

    /**
     * check type and range of textcount parameter, and correct if needed.
     *
     * @param int $textcount param
     *
     * @return int
     */
    protected function getTextcount($textcount)
    {
        return AutotweetBaseHelper::getTextcount($textcount);
    }

    /**
     * Use title or text as twitter message.
     *
     * @param bool   $usetext   param
     * @param int    $textcount param
     * @param string $title     param
     * @param string $text      param
     *
     * @return int
     */
    protected function getMessagetext($usetext, $textcount, $title, $text)
    {
        return AutotweetBaseHelper::getMessagetext($usetext, $textcount, $title, $text);
    }

    /**
     * Replaces spaces for hashtags.
     *
     * @param string $word param
     *
     * @return string
     */
    protected function getAsHashtag($word)
    {
        return AutotweetBaseHelper::getAsHashtag($word);
    }

    /**
     * Returns hashtags from comma sperated string (metakey field).
     *
     * @param string $metakey param
     * @param int    $count   param
     *
     * @return array
     */
    protected function getHashtags($metakey, $count = 1)
    {
        return AutotweetBaseHelper::getHashtags($metakey, $count);
    }

    /**
     * Add static text / hashtags to message.
     *
     * @param int    $textpos    param
     * @param string $text       param
     * @param string $statictext param
     *
     * @return string
     */
    protected function addStatictext($textpos, $text, $statictext)
    {
        return AutotweetBaseHelper::addStatictext($textpos, $text, $statictext);
    }

    /**
     * Add category / section to message text.
     *
     * @param int    $show     Param
     * @param int    $section  Param
     * @param int    $category Param
     * @param string $text     Param
     * @param bool   $add_hash Param
     *
     * @return string
     */
    protected function addSectionCategory($show, $section, $category, $text, $add_hash = false)
    {
        return AutotweetBaseHelper::addSectionCategory($show, $section, $category, $text, $add_hash);
    }

    /**
     * Special implementation to ad multiple categories.
     *
     * @param int    $show       Param
     * @param array  $categories Param
     * @param string $text       Param
     * @param bool   $add_hash   Param
     *
     * @return string
     */
    protected function addCategories($show, $categories, $text, $add_hash = false)
    {
        return AutotweetBaseHelper::addCategories($show, $categories, $text, $add_hash);
    }

    /**
     * Database helpers: returns the next free id for the table.
     *
     * @param object $table Param
     *
     * @return string
     */
    protected function getID($table)
    {
        return AutotweetBaseHelper::getID($table);
    }

    /**
     * Better implementation to handle multiple menu entry for component (multiple itemids).
     *
     * @param object $comp_name Param
     * @param object $needles   Param
     *
     * @return int
     */
    protected function getItemid($comp_name, $needles)
    {
        return AutotweetBaseHelper::getItemid($comp_name, $needles);
    }

    /**
     * getImageFromText.
     *
     * @param string $text param
     *
     * @return string
     */
    protected function getImageFromText($text)
    {
        $image = '';

        if (class_exists('DOMDocument') && !empty($text)) {
            $doc = new DOMDocument();
            @$doc->loadHTML($text);
            $imgtags = $doc->getElementsByTagName('img');

            if (0 < $imgtags->length) {
                $imgtag = $imgtags->item(0);
                $image = $imgtag->getAttribute('src');
            }
        } else {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::WARNING, 'Class DOMDocument not found in autotweetcontent.php - text not parsed for image');
        }

        if (empty($image)) {
            $image = TextUtil::getImageFromTextWithBrackets($text);
        }

        if (empty($image)) {
            $image = TextUtil::getImageFromGalleryTag($text);
        }

        if (empty($image)) {
            $image = TextUtil::getImageFromYoutubeWithBrackets($text);
        }

        if (empty($image)) {
            $image = TextUtil::getImageFromTextWithMarkdown($text);
        }

        return $image;
    }

    /**
     * getAuthorUsername.
     *
     * @param int $uid param
     *
     * @return string
     */
    protected function getAuthorUsername($uid)
    {
        return \Joomla\CMS\Factory::getUser($uid)->username;
    }

    /**
     * getArticleAuthor.
     *
     * @param object &$article Param
     *
     * @return string
     */
    protected function getArticleAuthor(&$article)
    {
        $uid = 0;

        if ((isset($article->created_by)) && ($article->created_by > 0)) {
            $uid = $article->created_by;
        }

        /*
                Article author cannot be modified for communty auto-posting
                if ((isset($article->modified_by)) && ($article->modified_by > 0))
                {
                    $uid = $article->modified_by;
                }
                else
        */

        return \Joomla\CMS\Factory::getUser($uid)->username;
    }

    /**
     * disablePostOld.
     *
     * @param string $plugin param
     */
    protected function disablePostOld($plugin = 'autotweetcontent')
    {
        // Get plugin id
        $table = '#__extensions';

        $db = \Joomla\CMS\Factory::getDBO();

        $query = 'SELECT '.$db->quoteName('extension_id').' FROM '
                .$db->quoteName($table)
                .' WHERE '.$db->quoteName('element').' = '.$db->Quote($plugin)
                .' AND '.$db->quoteName('type').' = '.$db->Quote('plugin');

        $db->setQuery($query);
        $id = (int) $db->loadResult();

        // Save parameter
        $this->pluginParams->set('post_old', 0);
        $table = \Joomla\CMS\Table\Table::getInstance('extension');
        $table->load($id);

        $table->params = $this->pluginParams->toString();

        if (!$table->store()) {
            JError::raiseWarning(500, 'Can not save parameter for AutoTweet '.$plugin.'Plugin: '.$table->getError());
        }
    }

    /**
     * executeContentPolling.
     *
     * @return bool
     */
    protected function executeContentPolling()
    {
        $automators = XTF0FModel::getTmpInstance('Automators', 'AutoTweetModel');

        if (!$automators->lastRunCheck('content', $this->interval)) {
            return;
        }

        $check_from = $this->getContentPollingFrom();

        // Set date for posts
        $post_old_mode = false;

        if ($this->post_old) {
            // Special case: posting for old articles is enabled
            $post_old_mode = true;
            $last_post = \Joomla\CMS\Factory::getDate($this->post_old_date);

            // Disable old article posting
            $this->disablePostOld();
        } else {
            $last_post = $check_from;
        }

        // Get new and changed articles form db
        $table_content = '#__content';

        // Get articles only when they are not in the queue and not in the message log for time horizon
        $db = \Joomla\CMS\Factory::getDBO();
        $query = $this->getPollingQuery('autotweetcontent', $table_content, $last_post);

        $db->setQuery($query);
        $articles = $db->loadObjectList();

        $logger = AutotweetLogger::getInstance();
        $logger->log(\Joomla\CMS\Log\Log::INFO, 'PollingQuery: '.$table_content.' found '.count($articles).' tasks.');

        $ids = [];

        // Post articles
        foreach ($articles as $article) {
            $ids[] = $article->id;

            if (PERFECT_PUB_PRO) {
                $this->advanced_attrs = AdvancedAttributesHelper::get($this->extension_option, $article->id);
            }

            $this->postArticle($article);
        }

        $logger->log(\Joomla\CMS\Log\Log::INFO, 'PollingQuery: '.print_r($ids, true).' results.');
    }

    /**
     * getPollingQuery.
     *
     * @param string $plugin        Param
     * @param string $table_content Param
     * @param JDate  $check_from    Param
     *
     * @return string
     */
    protected function getPollingQuery($plugin, $table_content, $check_from)
    {
        $check_until = $this->getContentPollingUntil();
        $table_posts = '#__autotweet_posts';
        $table_requests = '#__autotweet_requests';

        $db = \Joomla\CMS\Factory::getDBO();

        $query = [];

        $query[] = 'SELECT c.* FROM '.$db->quoteName($table_content, 'c');
        $query[] = 'LEFT OUTER JOIN '.$db->quoteName($table_requests, 'r').' ON r.`plugin` = '.$db->Quote($plugin).' AND r.`ref_id` = c.`id`';
        $query[] = 'LEFT OUTER JOIN '.$db->quoteName($table_posts, 'p').' ON p.`plugin` = '.$db->Quote($plugin).' AND p.`ref_id` = c.`id` WHERE';
        $query[] = 'r.`ref_id` IS NULL AND p.`ref_id` IS NULL AND';
        $query[] = 'c.'.$db->quoteName($this->published_field).' = 1 ';

        if ($this->post_featured_only) {
            $query[] = ' AND c.'.$db->quoteName('featured').' = 1';
        }

        if ($this->post_modified) {
            $query[] = 'AND ((c.'.$db->quoteName('created').' > '.$db->Quote($check_from);
            $query[] = 'AND c.'.$db->quoteName('created').' < '.$db->Quote($check_until);
            $query[] = ') OR (c.'.$db->quoteName('modified').' > '.$db->Quote($check_from);
            $query[] = 'AND c.'.$db->quoteName('modified').' < '.$db->Quote($check_until).'))';
        } else {
            $query[] = 'AND ((c.'.$db->quoteName('created').' > '.$db->Quote($check_from);
            $query[] = 'AND c.'.$db->quoteName('created').' < '.$db->Quote($check_until);
            $query[] = ') AND (c.'.$db->quoteName('modified').' > '.$db->Quote($check_from);
            $query[] = 'AND c.'.$db->quoteName('modified').' < '.$db->Quote($check_until).'))';
        }

        if ((isset($this->categories)) && (is_array($this->categories))) {
            $categories = array_filter($this->categories);

            if (count($categories) > 0) {
                $query[] = 'AND c.'.$db->quoteName('catid').' IN ('.implode(',', $categories).')';
            }
        }

        if ((isset($this->excluded_categories)) && (is_array($this->excluded_categories))) {
            $categories = array_filter($this->excluded_categories);

            if (count($categories) > 0) {
                $query[] = 'AND c.'.$db->quoteName('catid').' NOT IN ('.implode(',', $categories).')';
            }
        }

        $query = implode(' ', $query);

        return $query;
    }

    /**
     * getContentPollingUntil.
     *
     * @return JDate
     */
    protected function getContentPollingUntil()
    {
        $check_until = \Joomla\CMS\Factory::getDate()->toUnix() - self::MIN_POLLING_TIME * 60;
        $check_until = \Joomla\CMS\Factory::getDate($check_until);

        return $check_until;
    }

    /**
     * getContentPollingFrom.
     *
     * @return JDate
     */
    protected function getContentPollingFrom()
    {
        $polling_window = EParameter::getComponentParam(CAUTOTWEETNG, 'polling_window_intval', 24);
        $check_from = \Joomla\CMS\Factory::getDate()->toUnix() - ($polling_window * 3600);
        $check_from = \Joomla\CMS\Factory::getDate($check_from);

        return $check_from;
    }

    /**
     * checkIncludedCategoryFilter.
     *
     * @param array $catIds param
     *
     * @deprecated
     */
    protected function checkIncludedCategoryFilter($catIds)
    {
        return $this->isCategoryIncluded($catIds);
    }

    /**
     * isCategoryIncluded.
     *
     * @param mixed $catIds param
     *
     * @return mixed
     */
    protected function isCategoryIncluded($catIds)
    {
        return $this->_setCheck($catIds, $this->categories, true);
    }

    /**
     * isCategoryExcluded.
     *
     * @param mixed $catIds param
     *
     * @return mixed
     */
    protected function isCategoryExcluded($catIds)
    {
        return $this->_setCheck($catIds, $this->excluded_categories, false);
    }

    /**
     * checkExcludedCategoryFilter.
     *
     * @param array $catIds param
     *
     * @deprecated
     */
    protected function checkExcludedCategoryFilter($catIds)
    {
        return $this->isCategoryExcluded($catIds);
    }

    /**
     * enabledAccessLevel.
     *
     * @param mixed $accesslevels param
     *
     * @return mixed
     */
    protected function enabledAccessLevel($accesslevels)
    {
        return $this->_setCheck($accesslevels, $this->accesslevels, true);
    }

    /**
     * checkAccessLevelFilter.
     *
     * @param int $accesslevel param
     *
     * @deprecated
     */
    protected function checkAccessLevelFilter($accesslevel)
    {
        return $this->enabledAccessLevel($accesslevel);
    }

    /**
     * loadRequest.
     *
     * @param int $req_id param
     *
     * @deprecated
     */
    protected function loadRequest($req_id)
    {
        $articles = XTF0FModel::getTmpInstance('Requests', 'AutoTweetModel');
        $articles->set('ref_id', $req_id);
        $article = $articles->getFirstItem();
        $article->xtform = EForm::paramsToRegistry($article);

        return $article;
    }

    /**
     * _setCheck.
     *
     * @param mixed $catIds     param
     * @param array $categories param
     * @param bool  $default    param
     *
     * @return bool
     */
    private function _setCheck($catIds, $categories, $default)
    {
        // $categories as array
        if (($categories) && (!is_array($categories))) {
            $categories = TextUtil::listToArray($categories);
        }

        // Nothing to check
        if (empty($categories)) {
            return $default;
        }

        // Cleaning empty categories - All Categories case
        $categories = array_filter($categories);

        // Nothing to check
        if (empty($categories)) {
            return $default;
        }

        // $catIds as array
        if (!is_array($catIds)) {
            $catIds = (int) $catIds;
            $catIds = [$catIds];
        }

        // Nothing to check
        if (empty($catIds)) {
            return $default;
        }

        // Check
        $result = array_intersect($catIds, $categories);
        $result = !empty($result);

        return $result;
    }
}
