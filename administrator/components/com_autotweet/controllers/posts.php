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
 * AutotweetControllerPosts.
 *
 * @since       1.0
 */
class AutotweetControllerPosts extends AutotweetControllerDefault
{
    /**
     * publish.
     */
    public function publish()
    {
        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $this->_approveposts();
    }

    /**
     * publish.
     */
    public function unpublish()
    {
        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $this->_cancelposts();
    }

    /**
     * batch.
     */
    public function batch()
    {
        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $model = $this->getThisModel();

        if (!$model->getId()) {
            $model->setIDsFromRequest();
        }

        $batch_pubstate = $this->input->get('batch_pubstate', null, 'cmd');
        $status = true;

        if ($batch_pubstate) {
            $status = $model->moveToState($batch_pubstate);
        }

        // Check if i'm using an AJAX call, in this case there is no need to redirect
        $format = $this->input->get('format', '', 'string');

        if ('json' === $format) {
            echo json_encode($status);

            return;
        }

        // Redirect
        if ($customUrl = $this->input->get('returnurl', '', 'string')) {
            $customUrl = base64_decode($customUrl, true);
        }

        $url = !empty($customUrl) ? $customUrl : 'index.php?option='.$this->component.'&view='.XTF0FInflector::pluralize($this->view);

        if (!$status) {
            $this->setRedirect($url, $model->getError(), 'error');
        } else {
            $this->setRedirect($url);
        }
    }

    /**
     * purge.
     */
    public function purge()
    {
        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $model = $this->getThisModel();
        $status = $model->purge();

        // Check if i'm using an AJAX call, in this case there is no need to redirect
        $format = $this->input->get('format', '', 'string');

        if ('json' === $format) {
            echo json_encode($status);

            return;
        }

        // Redirect
        if ($customUrl = $this->input->get('returnurl', '', 'string')) {
            $customUrl = base64_decode($customUrl, true);
        }

        $url = !empty($customUrl) ? $customUrl : 'index.php?option='.$this->component.'&view='.XTF0FInflector::pluralize($this->view);

        if (!$status) {
            $this->setRedirect($url, $model->getError(), 'error');
        } else {
            $this->setRedirect($url);
        }
    }

    /**
     * publishAjaxAction.
     */
    public function publishAjaxAction()
    {
        $model = $this->getThisModel();

        if (!$model->getId()) {
            $model->setIDsFromRequest();
        }

        $status = $model->approve();

        return $status;
    }

    /**
     * cancelAjaxAction.
     */
    public function cancelAjaxAction()
    {
        $model = $this->getThisModel();

        if (!$model->getId()) {
            $model->setIDsFromRequest();
        }

        $status = $model->cancel();

        return $status;
    }

    /**
     * _approveposts.
     */
    private function _approveposts()
    {
        $model = $this->getThisModel();

        if (!$model->getId()) {
            $model->setIDsFromRequest();
        }

        $status = $model->approve();

        // Check if i'm using an AJAX call, in this case there is no need to redirect
        $format = $this->input->get('format', null, 'string');

        if ('json' === $format) {
            echo json_encode($status);

            return;
        }

        // Redirect
        $customUrl = $this->input->get('returnurl', null, 'string');

        if ($customUrl) {
            $customUrl = base64_decode($customUrl, true);
        }

        $url = !empty($customUrl) ? $customUrl : 'index.php?option='.$this->component.'&view='.XTF0FInflector::pluralize($this->view);

        if (!$status) {
            $this->setRedirect($url, $model->getError(), 'error');
        } else {
            $this->setRedirect($url);
        }
    }

    /**
     * _cancelposts.
     */
    private function _cancelposts()
    {
        $model = $this->getThisModel();

        if (!$model->getId()) {
            $model->setIDsFromRequest();
        }

        $status = $model->cancel();

        // Check if i'm using an AJAX call, in this case there is no need to redirect
        $format = $this->input->get('format', null, 'string');

        if ('json' === $format) {
            echo json_encode($status);

            return;
        }

        // Redirect
        $customUrl = $this->input->get('returnurl', null, 'string');

        if ($customUrl) {
            $customUrl = base64_decode($customUrl, true);
        }

        $url = !empty($customUrl) ? $customUrl : 'index.php?option='.$this->component.'&view='.XTF0FInflector::pluralize($this->view);

        if (!$status) {
            $this->setRedirect($url, $model->getError(), 'error');
        } else {
            $this->setRedirect($url);
        }
    }
}
