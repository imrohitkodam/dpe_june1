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

require_once 'default.php';

/**
 * AutotweetControllerFeeds.
 *
 * @since       1.0
 */
class AutotweetControllerFeeds extends AutotweetControllerDefault
{
    /**
     * import.
     */
    public function import()
    {
        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $cid = $this->input->get('cid', [], 'ARRAY');

        if (empty($cid)) {
            $id = $this->input->getInt('id', 0);

            if ($id) {
                $cid = [$id];
            }
        }

        FeedLoaderHelper::importFeeds($cid);

        // Redirect
        if ($customURL = $this->input->get('returnurl', '', 'string')) {
            $customURL = base64_decode($customURL, true);
        }

        $url = !empty($customURL) ? $customURL : 'index.php?option='.$this->component.'&view='.XTF0FInflector::pluralize($this->view);
        $this->setRedirect($url);

        ELog::showMessage('COM_AUTOTWEET_VIEW_FEEDS_IMPORT_SUCCESS', \Joomla\CMS\Log\Log::INFO);
    }

    /**
     * getImportBegin.
     */
    public function getImportBegin()
    {
        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        try {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'getImportBegin');

            $feedsModel = XTF0FModel::getTmpInstance('Feeds', 'AutoTweetModel');
            $feedsModel->set('published', 1);
            $feedsModel->set('filter_order', 'id');
            $feeds = $feedsModel->getItemList(true);

            $message = $this->_getImportBeginMessage($feeds);
            echo TextUtil::encodeJsonSuccessPackage($message);
        } catch (Exception $e) {
            $result_message = ' Start '.$e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getImportStatus.
     */
    public function getImportStatus()
    {
        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $message = [
            'status' => false,
            'error_message' => JText::_('COM_AUTOTWEET_VIEW_FEEDS_IMPORT_FAILED_ERR'),
        ];

        try {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'getImportStatus');

            $feed_id = $this->input->get('feedId', null, 'int');
            $continue = $this->input->get('isContinue', 0, 'int');

            $cid = [$feed_id];
            $result = FeedLoaderHelper::importFeeds($cid);

            $message = [
                'completed' => true,
                'total' => number_format($result),
            ];
            echo TextUtil::encodeJsonSuccessPackage($message);
        } catch (Exception $e) {
            $result_message = ' Status '.$e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    /**
     * getImportBegin.
     */
    public function getImportEnd()
    {
        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $message = [
            'status' => false,
            'error_message' => JText::_('COM_AUTOTWEET_VIEW_FEEDS_IMPORT_FAILED_ERR'),
        ];

        try {
            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'getImportEnd');
            echo TextUtil::encodeJsonSuccessPackage(JText::_('COM_AUTOTWEET_OK'));
        } catch (Exception $e) {
            $result_message = ' End '.$e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }

        flush();
        \Joomla\CMS\Factory::getApplication()->close();
    }

    protected function onBeforeImport()
    {
        return $this->onBeforeAccessspecial();
    }

    /**
     * _getImportBeginMessage.
     *
     * @param array $feeds Params
     *
     * @return object
     */
    private function _getImportBeginMessage($feeds)
    {
        $message = [
            'message' => JText::_('COM_AUTOTWEET_OK'),
        ];

        $results = [];

        foreach ($feeds as $feed) {
            // $object =

            $result = new StdClass();
            $result->id = $feed->id;
            $result->name = $feed->name;

            $results[] = $result;
        }

        $message['feeds'] = $results;

        return $message;
    }
}
