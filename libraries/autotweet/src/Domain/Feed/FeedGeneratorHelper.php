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

use PerfectPublisher\Infrastructure\Service\Cms\Joomla\ContentGenerator\ContentGeneratorFactory;

/**
 * FeedGeneratorHelper class.
 *
 * @since       1.0
 */
class FeedGeneratorHelper
{
    private $contentGenerator;

    /**
     * removeDuplicates.
     *
     * @param array &$contents Params
     */
    public function removeDuplicates(&$contents)
    {
        if (!count($contents)) {
            return;
        }

        $keys = [];
        $output = [];

        foreach ($contents as $content) {
            $k = $content->hash;

            if (!array_key_exists($k, $keys)) {
                $keys[$k] = $k;
                $output[] = $content;
            }
        }

        $contents = $output;
    }

    /**
     * generate.
     *
     * @param array  &$content Params
     * @param object &$params  Params
     *
     * @return int
     */
    public function generateContent(&$content, &$params)
    {
        $i = 0;
        $logger = AutotweetLogger::getInstance();
        $articles = [];

        foreach ($content as $article) {
            try {
                $output = $this->renderLayout($article, $params);

                [$introtext, $fulltext] = FeedTextHelper::splitArticleText($output);
                $article->introtext = $introtext;
                $article->fulltext = $fulltext;
                $articles[] = $article;

                ++$i;
            } catch (Exception $e) {
                $logger->log(\Joomla\CMS\Log\Log::ERROR, 'FeedGeneratorHelper: save '.$e->getMessage());
            }
        }

        $content = $articles;

        return $i;
    }

    /**
     * save.
     *
     * @param object &$content Params
     * @param object &$params  Params
     *
     * @return int
     */
    public function save(&$content, &$params)
    {
        $counter = 0;
        $logger = AutotweetLogger::getInstance();
        $this->contentGenerator = (new ContentGeneratorFactory())->get();

        foreach ($content as $article) {
            try {
                $result = $this->saveArticle($article, $params);

                if ($result) {
                    ++$counter;
                    continue;
                }

                $logger->log(\Joomla\CMS\Log\Log::WARNING, 'FeedGeneratorHelper: Title='.$article->title);
            } catch (Exception $e) {
                $logger->log(\Joomla\CMS\Log\Log::ERROR, 'FeedGeneratorHelper: save '.$e->getMessage());
            }
        }

        return $counter;
    }

    /**
     * saveEnclosure.
     *
     * @param string $name Params
     * @param string $type Params
     * @param string $src  Params
     */
    public function saveEnclosure($name, $type, $src)
    {
        if ('images' === $type) {
            $savepath = $this->_params->get('save_enc_image_as_img', 1) ? $this->_params->get('imgsavepath') : $this->_params->get('savepath').$type.'/';

            if (!JFolder::exists($savepath)) {
                JFolder::create($savepath);
            }

            $file_path = $savepath.$name;
        } else {
            $savepath = $this->_params->get('savepath').$type.'/';

            if (!JFolder::exists($savepath)) {
                JFolder::create($savepath);
            }

            $file_path = $savepath.$name;
        }

        if (!file_exists($file_path)) {
            if (!$contents = TextUtil::getUrl(TextUtil::encode_url($src), $this->_params->get('scrape_type'), $type, $file_path)) {
                // Enclosure Not Saved');
                return false;
            }

            // Enclosure Saved');
        }

        // Enclosure Already Saved');

        return true;
    }

    /**
     * formatEnclosures.
     *
     * @return string
     */
    public static function formatEnclosures($article)
    {
        $result = [];

        $result[] = '<div class="perfect-publisher-enclosures"><h3>'.JText::_('COM_AUTOTWEET_VIEW_FEED_ENCLOSURES').'</h3><ol class="enclosures-list">';
        $i = 1;

        foreach ($article->enclosures as $enclosure) {
            $result[] = '<li><small><sup>';

            $result[] = "<a title='{$enclosure->title}' href='{$enclosure->link}'>^</a></sup></small> ";

            // Thesite.com
            $uri = \Joomla\CMS\Uri\Uri::getInstance($enclosure->link);
            $host = $uri->getHost();
            $parts = explode('.', $host);
            $o = count($parts) - 2;
            $dotcom = array_splice($parts, $o);
            $host = implode('.', $dotcom);

            $result[] = "<a name='enclosureLink-{$i}' rel='' href='{$enclosure->link}'>{$enclosure->title}</a><small> ({$host})";

            $result[] = '</small></li>';
        }

        $result[] = '</ol></div>';

        return implode('', $result);
    }

    /**
     * formatReadonLink.
     *
     * @return string
     */
    public static function formatReadonLink($article, $params)
    {
        $target = null;

        if ('none' === $params->get('target_frame')) {
            $target = '';
        } elseif ('custom' === $params->get('target_frame')) {
            $target = 'target="'.$params->get('custom_frame').'"';
        } else {
            $target = 'target="'.$params->get('target_frame').'"';
        }

        $linkTitle = trim(substr($article->title, 0, 50));

        if ($params->get('shortlink')) {
            $readonPattern = '<a class="shortlink %s" rel="%s" title="%s" href="%s" %s>%s</a>';
            $readonlink = sprintf($readonPattern, $params->get('trackback_class'), $params->get('trackback_rel'), $linkTitle, $article->shortlink, $target, $params->get('orig_link_text'));
        } else {
            $readonPattern = '<span class="permalink-label">%s</span> <a class="permalink %s" rel="%s" title="%s" href="%s" %s>%s</a>';
            $readonlink = sprintf($readonPattern, $params->get('orig_link_text'), $params->get('trackback_class'), $params->get('trackback_rel'), $linkTitle, $article->permalink, $target, $article->permalink);
        }

        $readonlink = '<p class="trackback">'.$readonlink.'</p>';

        return $readonlink;
    }

    /**
     * renderLayout.
     *
     * @param object $article Params
     * @param object $params  Params
     *
     * @return string
     */
    private function renderLayout($article, $params)
    {
        $data = [
            'article' => $article,
            'params' => $params,
        ];

        return JLayoutHelper::render('free.feed.article', $data, JPATH_AUTOTWEET_LAYOUTS);
    }

    /**
     * saveArticle.
     *
     * @param array  &$article Params
     * @param object &$params  Params
     *
     * @return int
     */
    private function saveArticle(&$article, &$params)
    {
        $data = (object) $this->getData($article);

        return $this->contentGenerator->save($data);
    }

    /**
     * getData.
     *
     * @param object &$article Params
     *
     * @return array
     */
    private function getData(&$article)
    {
        $data = [
            'id' => $article->id,
            'title' => $article->title,
            'catid' => $article->cat_id,
            'articletext' => $article->introtext,
            'introtext' => $article->introtext,
            'fulltext' => $article->fulltext,
            'images' => '',
            'urls' => '',
            'alias' => $article->alias,
            'created_by' => $article->created_by,
            'created_by_alias' => $article->created_by_alias,
            'created' => $article->created,
            'publish_up' => $article->publish_up,
            'publish_down' => $article->publish_down,
            'modified_by' => null,
            'modified' => null,
            'version' => null,
            'attribs' => '',
            'metadesc' => null,
            'metakey' => $article->metakey,
            'xreference' => null,
            'metadata' => '',
            'metadesc' => '',
            'rules' => '',
            'state' => $article->state,
            'access' => $article->access,
            'featured' => $article->featured,
            'language' => $article->language,
            'xreference' => $article->hash,
        ];

        return $data;
    }
}
