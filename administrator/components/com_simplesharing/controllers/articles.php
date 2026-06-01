<?php

/**
 * @version     1.0.34
 * @package     com_simplesharing
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @author      NYC HelpDesk.co LLC <support@nychelpdesk.co> - nychelpdesk.co
 */
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Articles list controller class.
 */
class SimplesharingControllerArticles extends JControllerAdmin {

    /**
     * Proxy for getModel.
     * @since	1.6
     */
    public function getModel($name = 'article', $prefix = 'SimplesharingModel', $config = Array()) {
        $model = parent::getModel($name, $prefix, array('ignore_request' => true));
        return $model;
    }

    public function share() {

        require_once JPATH_COMPONENT . '/helpers/rest.php';
        require_once JPATH_COMPONENT . '/helpers/simplesharing.php';
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_content/models');

        // Get the input
        $input = JFactory::getApplication()->input;
        $article_ids = $input->post->get('cid', array(), 'array');
        $website_ids = $input->post->get('wcid', array(), 'array');

        // Sanitize the input
        JArrayHelper::toInteger($article_ids);
        JArrayHelper::toInteger($website_ids);

        $params = JComponentHelper::getParams('com_simplesharing');
        $createCopy = $params->get('createCopy');

        // Loop through selected websites to prepare for REST requests
        foreach ($website_ids as $wid) {
            $successfullPosts = 0;
            $model = $this->getModel('slavewebsite');
            $website = $model->getItem($wid);
		$destination_cat = $input->post->getInt('destCat_'.$wid,0);
            //Loop through selected items to be shared, and send REST request for each one
            for ($i = 0; $i < count($article_ids); $i++) {
                $model = $this->getModel('Article', 'ContentModel');
                $item = $model->getItem($article_ids[$i]);
                $item->set('catid', $destination_cat);

                //prepare tags to be shared
                $tagsHelper = new JHelperTags();
                $tagIds = empty($item->tags->tags) ? array() : explode(",", $item->tags->tags);
                $itemTags = $tagsHelper->getTagNames($tagIds);
                for ($tag_index = 0; $tag_index < count($itemTags); $tag_index++) {
                    $itemTags[$tag_index] = '#new#' . $itemTags[$tag_index];
                }
                $item->tags = $itemTags;

                //Prepare images to be shared
                if (isset($item->images['image_intro']) && $imageFileContents = file_get_contents(JPATH_SITE . '/' . $item->images['image_intro'])) {
                    $item->images['image_intro_binary'] = base64_encode($imageFileContents);
                }

                if (isset($item->images['image_fulltext']) && $imageFileContents = file_get_contents(JPATH_SITE . '/' . $item->images['image_fulltext'])) {
                    $item->images['image_fulltext_binary'] = base64_encode($imageFileContents);
                }
                // @TODO: find all images in the content with relative path and attach them as well.
                if (!empty($item->introtext)){
                    $item->introtext = SimpleSharingHelper::relToAbs('img', $item->introtext);
                    $item->introtext = SimpleSharingHelper::relToAbs('audio',$item->introtext);
                    $item->introtext = SimpleSharingHelper::relToAbs('video',$item->introtext);
                    $item->introtext = SimpleSharingHelper::relToAbs('source',$item->introtext);
                }
                if (!empty($item->fulltext)){
                    $item->fulltext = SimpleSharingHelper::relToAbs('img', $item->fulltext);
                    $item->fulltext = SimpleSharingHelper::relToAbs('audio',$item->fulltext);
                    $item->fulltext = SimpleSharingHelper::relToAbs('video',$item->fulltext);
                    $item->fulltext = SimpleSharingHelper::relToAbs('source',$item->fulltext);
                }
                try {
                    //Attach parameters of existing articles management
                    $item->create_copy = $createCopy;                    
                    $result = SimplesharingRestHelper::processRestRequest($website, 'createItem', $item);
                    if ($result != '401')
                        $successfullPosts++;
                } catch (Exception $e) {
                    $msg = "Something went wrong when sharing: %s to %s check the log file at %s";
                    JFactory::getApplication()->enqueueMessage(JText::sprintf($msg, $item->title, $website->website_name, "<a href='" . $website->website_url . "logs/plg.simplesharing.php' parent='_blank'>View Log</a>"), "error");
                }
            }
            JFactory::getApplication()->enqueueMessage("Successfully posted " . $successfullPosts . " of " . count($article_ids) . " article(s) to " . $website->website_name . " category id=" . $destination_cat);
        }

        return $this->setRedirect(JRoute::_('index.php?option=com_simplesharing&view=articles', false));
    }
}
