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

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Contracts\ContentGeneratorInterface;

final class Joomla3Article implements ContentGeneratorInterface
{
    use FireOnAfterSave;

    public const PLUGIN_NAME = 'autotweetcontent';

    public const PLUGIN_SAVE_EVENT = 'onContentAfterSave';

    public const PLUGIN_CONTEXT = 'com_content.article';

    public function getTemplateKey()
    {
        return 'joomla3-article';
    }

    public function save(object $feedContent)
    {
        $jarticle = new \stdClass();
        $jarticle->title = (string) $feedContent->title;
        $jarticle->alias = (string) $feedContent->alias;
        $jarticle->introtext = $feedContent->introtext;
        $jarticle->fulltext = $feedContent->fulltext;
        $jarticle->state = $feedContent->state;
        $jarticle->catid = $feedContent->catid;
        $jarticle->created = $feedContent->created;
        $jarticle->created_by = $feedContent->createdBy;
        $jarticle->publish_up = $feedContent->publishUp;
        $jarticle->publish_down = $feedContent->publishDown;
        $jarticle->access = $feedContent->access;
        $jarticle->featured = $feedContent->featured;
        $jarticle->language = $feedContent->language;

        $images = new \stdClass();
        $images->image_intro = '';
        $images->float_intro = '';
        $images->image_intro_alt = '';
        $images->image_intro_caption = '';
        $images->image_fulltext = '';
        $images->float_fulltext = '';
        $images->image_fulltext_alt = '';
        $images->image_fulltext_caption = '';
        $jarticle->images = json_encode($images);

        $metadata = new \stdClass();
        $metadata->robots = '';
        $metadata->author = $feedContent->createdByAlias;
        $metadata->rights = '';
        $metadata->xreference = '';
        $metadata->page_title = $jarticle->title;
        $jarticle->metadata = json_encode($metadata);

        $table = \JTable::getInstance('content', 'JTable');
        $data = (array) $jarticle;

        // Bind data
        if (!$table->bind($data)) {
            //Handle the errors here however you like (log, display error message, etc.)
            throw new \Exception('Content not binded.');
        }

        // Check the data.
        if (!$table->check()) {
            //Handle the errors here however you like (log, display error message, etc.)
            throw new \Exception('Content check fail.');
        }

        // Store the data.
        if (!$table->store()) {
            //Handle the errors here however you like (log, display error message, etc.)
            throw new \Exception('Content store fail.');
        }

        $feedContent->id = $table->id;

        // Check if the article was featured and update the #__content_frontpage table
        if (1 === $feedContent->featured) {
            $db = \Joomla\CMS\Factory::getDbo();
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__content_frontpage'))
                ->values($table->id.', 0');
            $db->setQuery($query);
            $db->execute();
        }

        // Just in case to load it in a consistent state
        $this->onAfterSave($table->id, self::PLUGIN_CONTEXT);

        return $feedContent;
    }
}
