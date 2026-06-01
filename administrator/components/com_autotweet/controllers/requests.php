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
 * AutotweetControllerRequests.
 *
 * @since       1.0
 */
class AutotweetControllerRequests extends AutotweetControllerDefault
{
    /**
     * Public constructor of the Controller class.
     *
     * @param array $config Optional configuration parameters
     */
    public function __construct($config = [])
    {
        // No JInputJSON in J2.5
        $raw = file_get_contents('php://input');
        $data = TextUtil::json_decode($raw, true);

        if (($data) && (array_key_exists('ajax', $data)) && (1 === (int) $data['ajax'])) {
            $input = new \Joomla\CMS\Input\Input($_REQUEST);
            $param = array_merge($input->getArray(), $data);
            $config['input'] = $param;
        }

        parent::__construct($config);
    }

    /**
     * Default task. Assigns a model to the view and asks the view to render
     * itself.
     *
     * YOU MUST NOT USETHIS TASK DIRECTLY IN A URL. It is supposed to be
     * used ONLY inside your code. In the URL, use task=browse instead.
     *
     * @param bool   $cachable  Is this view cacheable?
     * @param bool   $urlparams Add your safe URL parameters (see further down in the code)
     * @param string $tpl       The name of the template file to parse
     *
     * @return bool
     */
    public function display($cachable = false, $urlparams = false, $tpl = null)
    {
        return parent::display(false, $urlparams, $tpl);
    }

    /**
     * publish.
     */
    public function publish()
    {
        $this->process();
    }

    /**
     * process.
     */
    public function process()
    {
        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $model = $this->getThisModel();

        if (!$model->getId()) {
            $model->setIDsFromRequest();
        }

        $status = $model->process();

        // Check if i'm using an AJAX call, in this case there is no need to redirect
        $format = $this->input->get('format', null, 'string');

        if ('json' === $format) {
            echo json_encode($status);

            return;
        }

        // Redirect
        $customUrl = $this->input->get('returnurl', null, 'string');

        if ($customUrl) {
            $customURL = base64_decode($customURL, true);
        }

        $url = !empty($customURL) ? $customURL : 'index.php?option='.$this->component.'&view='.XTF0FInflector::pluralize($this->view);

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
        $format = $this->input->get('format', null, 'string');

        if ('json' === $format) {
            echo json_encode($status);

            return;
        }

        // Redirect
        $customUrl = $this->input->get('returnurl', null, 'string');

        if ($customUrl) {
            $customURL = base64_decode($customURL, true);
        }

        $url = !empty($customURL) ? $customURL : 'index.php?option='.$this->component.'&view='.XTF0FInflector::pluralize($this->view);

        if (!$status) {
            $this->setRedirect($url, $model->getError(), 'error');
        } else {
            $this->setRedirect($url);
        }
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

        $batch_published = $this->input->get('batch_published', null, 'int');
        $status = true;
        $status = $model->moveToState($batch_published);

        // Check if i'm using an AJAX call, in this case there is no need to redirect
        $format = $this->input->get('format', '', 'string');

        if ('json' === $format) {
            echo json_encode($status);

            return;
        }

        // Redirect
        if ($customURL = $this->input->get('returnurl', '', 'string')) {
            $customURL = base64_decode($customURL, true);
        }

        $url = !empty($customURL) ? $customURL : 'index.php?option='.$this->component.'&view='.XTF0FInflector::pluralize($this->view);

        if (!$status) {
            $this->setRedirect($url, $model->getError(), 'error');
        } else {
            $this->setRedirect($url);
        }
    }

    /**
     * batchEvergreen.
     */
    public function batchEvergreen()
    {
        // CSRF prevention
        if ($this->csrfProtection) {
            $this->_csrfProtection();
        }

        $model = $this->getThisModel();

        if (!$model->getId()) {
            $model->setIDsFromRequest();
        }

        $batch_evergreen = $this->input->get('batch_evergreen', null, 'int');
        $status = true;
        $status = $model->moveToEvergeen($batch_evergreen);

        // Check if i'm using an AJAX call, in this case there is no need to redirect
        $format = $this->input->get('format', '', 'string');

        if ('json' === $format) {
            echo json_encode($status);

            return;
        }

        // Redirect
        if ($customURL = $this->input->get('returnurl', '', 'string')) {
            $customURL = base64_decode($customURL, true);
        }

        $url = !empty($customURL) ? $customURL : 'index.php?option='.$this->component.'&view='.XTF0FInflector::pluralize($this->view);

        if (!$status) {
            $this->setRedirect($url, $model->getError(), 'error');
        } else {
            $this->setRedirect($url);
        }
    }

    /**
     * applyAjaxPluginAction.
     */
    public function applyAjaxPluginAction()
    {
        try {
            // CSRF prevention
            if ($this->csrfProtection) {
                $this->_csrfProtection();
            }

            $data = RequestHelper::getAjaxData($this->input);
            $status = $this->callPluginAction($data);

            if (!$status) {
                $errors = \Joomla\CMS\Factory::getSession()->get('last_req_errors');

                throw new Exception($errors);
            }

            $id = \Joomla\CMS\Factory::getSession()->get('last_req_id');
            $message = [
                'request_id' => $id,
                'message' => JText::_('COM_AUTOTWEET_COMPOSER_MESSAGE_SAVED'),
            ];
            echo TextUtil::encodeJsonSuccessPackage($message);
        } catch (Exception $e) {
            echo TextUtil::encodeJsonErrorPackage($e->getMessage());
        }
    }

    /**
     * callPluginAction.
     *
     * @param array $data Param
     *
     * @return bool
     */
    public function callPluginAction($data)
    {
        if ((!(bool) $data['id']) || (!(bool) $data['ref_id'])) {
            throw new Exception('Unknown Plugin Action (id/ref_id)');
        }

        $attr_id = null;

        // Autotweet_advanced_attrs
        if ((PERFECT_PUB_PRO) && ($data['autotweet_advanced_attrs'])) {
            $advanced_attrs = AdvancedAttributesHelper::fromQueryParams($data['autotweet_advanced_attrs']);

            if (isset($advanced_attrs->ref_id)) {
                if (($agenda = $advanced_attrs->agenda) && (count($agenda) > 0)) {
                    // The first date, it's the next date
                    $publish_up = AdvancedAttributesHelper::getNextAgendaDate($agenda);

                    if (!empty($publish_up)) {
                        $publish_up = EParameter::convertUTCLocal($publish_up);
                        $data['publish_up'] = $publish_up;
                    }
                }

                // Safe to save
                $attr_id = AdvancedAttributesHelper::save($advanced_attrs, $advanced_attrs->ref_id);
                unset($data['autotweet_advanced_attrs']);
            }
        }

        // Load the model
        $model = $this->getThisModel();

        if (!$model->getId()) {
            $model->setIDsFromRequest();
        }

        $id = $model->getId();

        if (!$this->onBeforeApplySave($data)) {
            return false;
        }

        // Set the layout to form, if it's not set in the URL

        if (null === $this->layout) {
            $this->layout = 'form';
        }

        // Do I have a form?
        $model->setState('form_name', 'form.'.$this->layout);

        $status = $model->save($data);

        if ($status && (0 !== (int) $id)) {
            XTF0FPlatform::getInstance()->setHeader('Status', '201 Created', true);

            // Try to check-in the record if it's not a new one
            $status = $model->checkin();
        }

        if ($status) {
            $status = $this->onAfterApplySave();
        }

        $req_id = $model->getId();

        if ($attr_id) {
            AdvancedAttributesHelper::assignRequestId($attr_id, $req_id);
        }

        // Share to all
        \Joomla\CMS\Factory::getSession()->set('last_req_id', $req_id);

        if (!$status) {
            $msg = implode('', $model->getErrors());

            // Share to all
            \Joomla\CMS\Factory::getSession()->set('last_req_errors', $msg);
        }

        return $status;
    }

    /**
     * applyAjaxOwnAction.
     */
    public function applyAjaxOwnAction()
    {
        try {
            // CSRF prevention
            if ($this->csrfProtection) {
                $this->_csrfProtection();
            }

            $data = RequestHelper::getAjaxData($this->input);
            $status = $this->callAjaxOwnAction($data);
            $id = \Joomla\CMS\Factory::getSession()->get('last_req_id');

            if ($status) {
                $message = [
                    'request_id' => $id,
                    'message' => JText::_('COM_AUTOTWEET_COMPOSER_MESSAGE_SAVED'),
                ];
                echo TextUtil::encodeJsonSuccessPackage($message);
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_UNABLETO', 'addAjaxAction');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }
    }

    /**
     * callAjaxOwnAction.
     *
     * @param array $data Param
     */
    public function callAjaxOwnAction($data)
    {
        // On Before Save
        $data['params'] = EForm::paramsToString($data);

        if (array_key_exists('publish_up', $data)) {
            $data['publish_up'] = EParameter::convertLocalUTC($data['publish_up']);
        } else {
            $data['publish_up'] = \Joomla\CMS\Factory::getDate()->toSql();
        }

        // Cleaning annoying spaces
        $data = array_map('trim', $data);

        // Ready to Save
        $pluginObject = \XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\DispatcherHelper::getPlugin(
            'autotweet',
            'autotweetpost'
        );
        $status = $pluginObject->postArticle($data);

        $id = null;

        if (!(bool) $status) {
            $id = $status;
            $status = true;
        }

        // Share to all
        \Joomla\CMS\Factory::getSession()->set('last_req_id', $id);

        return $status;
    }

    /**
     * routeAjaxItemId.
     */
    public function routeAjaxItemId()
    {
        try {
            // CSRF prevention
            if ($this->csrfProtection) {
                $this->_csrfProtection();
            }

            $itemId = $this->input->get('itemId', null, 'int');
            $url = 'index.php?Itemid='.$itemId;
            $url = RouteHelp::getInstance()->getAbsoluteUrl($url);

            $message = [
                'status' => true,
                'url' => $url,
            ];
            echo TextUtil::encodeJsonSuccessPackage($message);
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }
    }

    /**
     * publishAjaxAction.
     */
    public function publishAjaxAction()
    {
        try {
            // CSRF prevention
            if ($this->csrfProtection) {
                $this->_csrfProtection();
            }

            $status = $this->callPublishAjaxAction();

            if ($status) {
                echo TextUtil::encodeJsonSuccessPackage(JText::_('COM_AUTOTWEET_COMPOSER_MESSAGE_PROCESSED'));
            } else {
                echo TextUtil::encodeJsonErrorPackage(
                    JText::sprintf('COM_AUTOTWEET_UNABLETO', 'publishAjaxAction')
                );
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }
    }

    /**
     * callPublishAjaxAction.
     */
    public function callPublishAjaxAction()
    {
        $model = $this->getThisModel();

        if (!$model->getId()) {
            $model->setIDsFromRequest();
        }

        $status = $model->process();

        return $status;
    }

    /**
     * cancelAjaxAction.
     */
    public function cancelAjaxAction()
    {
        $published = 1;

        try {
            // CSRF prevention
            if ($this->csrfProtection) {
                $this->_csrfProtection();
            }

            $this->callMoveToState($published);

            if ($status) {
                echo TextUtil::encodeJsonSuccessPackage(JText::_('COM_AUTOTWEET_COMPOSER_MESSAGE_PROCESSED'));
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_UNABLETO', 'cancelAjaxAction');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }
    }

    /**
     * backtoQueueAjaxAction.
     */
    public function backtoQueueAjaxAction()
    {
        $published = 0;

        try {
            // CSRF prevention
            if ($this->csrfProtection) {
                $this->_csrfProtection();
            }

            $this->callMoveToState($published);

            if ($status) {
                echo TextUtil::encodeJsonSuccessPackage(JText::_('COM_AUTOTWEET_COMPOSER_MESSAGE_PROCESSED'));
            } else {
                $result_message = JText::sprintf('COM_AUTOTWEET_UNABLETO', 'cancelAjaxAction');
                echo TextUtil::encodeJsonErrorPackage($result_message);
            }
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }
    }

    /**
     * callMoveToState.
     *
     * @param int $published Param
     *
     * @return bool
     */
    public function callMoveToState($published)
    {
        $model = $this->getThisModel();

        if (!$model->getId()) {
            $model->setIDsFromRequest();
        }

        $status = $model->moveToState($published);

        return $status;
    }

    /**
     * readAjaxAction.
     */
    public function readAjaxAction()
    {
        try {
            $this->task = 'read';
            $this->getThisModel()->setState('task', $this->task);

            parent::read();
        } catch (Exception $e) {
            $result_message = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($result_message);
        }
    }

    protected function onBeforeApplyAjaxOwnAction()
    {
        return $this->onBeforeApply();
    }

    protected function onBeforeApplyAjaxPluginAction()
    {
        return $this->onBeforeApply();
    }

    protected function onBeforeBacktoQueueAjaxAction()
    {
        return $this->onBeforePublish();
    }

    protected function onBeforeCallAjaxOwnAction($data)
    {
        return $this->onBeforePublish();
    }

    protected function onBeforeCallPublishAjaxAction()
    {
        return $this->onBeforePublish();
    }

    protected function onBeforeCancelAjaxAction()
    {
        return $this->onBeforePublish();
    }

    protected function onBeforePublishAjaxAction()
    {
        return $this->onBeforePublish();
    }

    protected function onBeforeReadAjaxAction()
    {
        return $this->onBeforeEdit();
    }

    protected function onBeforeRouteAjaxItemId()
    {
        return $this->onBeforeEdit();
    }
}
