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

namespace PerfectPublisher\Infrastructure\Service\Cms\Joomla\ContentGenerator;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use XTP_BUILD\Extly\Infrastructure\Service\Cms\Contracts\ContentGeneratorInterface;

final class Joomla4Article implements ContentGeneratorInterface
{
    use FireOnAfterSave;

    public function getTemplateKey()
    {
        return 'joomla4-article';
    }

    public function save(object $feedContent)
    {
        $app = \Joomla\CMS\Factory::getApplication();
        $mvcFactory = $app->bootComponent('com_content')->getMVCFactory();

        $articleModel = $mvcFactory->createModel('Article', 'Administrator', ['ignore_request' => true]);

        if (!$articleModel) {
            throw new \Exception('ContentGenerator: Article model is unavailable');
        }

        // Set values from language strings.
        $title = (string) $feedContent->title;
        $alias = ApplicationHelper::stringURLSafe($title);

        $article['catid'] = $feedContent->catid;

        $article['title'] = $title;
        $article['introtext'] = $feedContent->introtext;
        $article['fulltext'] = $feedContent->fulltext;

        $article['publish_up'] = $feedContent->publish_up;
        $article['publish_down'] = $feedContent->publish_down;

        // Set values which are always the same.
        $article['id'] = 0;
        $article['ordering'] = 0;
        $article['created_by'] = $feedContent->created_by;
        $article['created_by_alias'] = $feedContent->created_by_alias;
        $article['alias'] = $alias;

        // Set unicodeslugs if alias is empty
        if (trim('' === str_replace('-', '', $alias))) {
            $unicode = $app->set('unicodeslugs', 1);
            $article['alias'] = ApplicationHelper::stringURLSafe($article['title']);
            $app->set('unicodeslugs', $unicode);
        }

        $article['language'] = $feedContent->language;
        $article['associations'] = [];
        $article['metakey'] = '';
        $article['metadesc'] = '';

        $article['featured'] = $feedContent->featured;
        $article['state'] = $feedContent->state;
        $article['images'] = '';
        $article['access'] = $feedContent->access;

        if (!$articleModel->save($article)) {
            throw new \Exception('ContentGenerator: '.Text::_($articleModel->getError()));
        }

        // Get ID from article we just added
        $ids[] = $articleModel->getItem()->id;

        if ($article['featured']
            && ComponentHelper::isEnabled('com_workflow')
            && PluginHelper::isEnabled('workflow', 'featuring')
            && ComponentHelper::getParams('com_content')->get('workflow_enabled')) {
            // Set the article featured in #__content_frontpage
            $query = $this->db->getQuery(true);

            $featuredItem = (object) [
                'content_id' => $articleModel->getItem()->id,
                'ordering' => 0,
                'featured_up' => null,
                'featured_down' => null,
            ];

            $this->db->insertObject('#__content_frontpage', $featuredItem);
        }

        // Just in case to load it in a consistent state
        $this->onAfterSave($ids[0], 'com_content.article');

        return $ids[0];
    }
}
