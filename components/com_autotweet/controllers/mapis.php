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

use Joomla\Registry\Registry as CMSRegistry;
use XTS_BUILD\Illuminate\Support\Str;

/**
 * AutotweetControllerMapis.
 *
 * @since       1.0
 */
class AutotweetControllerMapis extends ExtlyController
{
    /**
     * The tasks for which caching should be enabled by default.
     *
     * @var array
     */
    protected $cacheableTasks = [];

    protected $myMethods = [
        // JWT API
        'check_token' => 'checkToken',

        // Global Parameters API
        'globals_read' => 'globalsRead',

        // Stats API
        'posts_chart' => 'postsChartData',
        'posts_timeline' => 'postsChartTimeline',
        'requests_chart' => 'requestsChart',

        // Composer API
        'menuitems_read' => 'menuItemsRead',
        'sef_url_read' => 'sefUrlRead',

        // Requests API
        'requests_browse' => 'requestsBrowse',
        'requests_cancel' => 'requestCancel',
        'requests_publish' => 'requestsPublish',
        'requests_read' => 'requestsRead',
        'requests_save_ownpost' => 'requestsSaveOwnPost',
        'requests_save_pluginpost' => 'requestsSavePluginPost',

        // Posts API
        'posts_browse' => 'postsBrowse',
        'posts_cancel' => 'postsCancel',
        'posts_publish' => 'postsPublish',
        'posts_read' => 'postsRead',

        // Images API
        'images_save' => 'imagesSave',
    ];

    protected $stats;

    /**
     * execute.
     */
    public function run()
    {
        try {
            $method = $this->input->get('method', null, 'cmd');

            if (!$method) {
                // Just to keep CORS happy
                // Header set Access-Control-Allow-Origin "*"
                // Header set Access-Control-Allow-Headers "X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding, X-Access-Token"

                return XTF0FPlatform::getInstance()->setHeader('Status', '200 OK', true);
            }

            $logger = AutotweetLogger::getInstance();
            $logger->log(\Joomla\CMS\Log\Log::INFO, 'Mapis run: method '.$method);

            // Do not play around
            if ($method && array_key_exists($method, $this->myMethods)) {
                $localMethod = $this->myMethods[$method];
                $response = $this->{$localMethod}();
                echo TextUtil::encodeJsonSuccessPackage($response);

                return;
            }

            $resultMessage = JText::_('COM_AUTOTWEET_METHOD_NOT_FOUND');
            echo TextUtil::encodeJsonErrorPackage($resultMessage);
            XTF0FPlatform::getInstance()->setHeader('Status', '501 Not Implemented', true);
        } catch (\JwtTokenException $e) {
            $resultMessage = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($resultMessage);
            XTF0FPlatform::getInstance()->setHeader('Status', '401 Unauthorized', true);
        } catch (\UnexpectedValueException $e) {
            // Firebase/JWT exceptions
            $resultMessage = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($resultMessage);
            XTF0FPlatform::getInstance()->setHeader('Status', '401 Unauthorized', true);
        } catch (\Exception $e) {
            $resultMessage = $e->getMessage();
            echo TextUtil::encodeJsonErrorPackage($resultMessage);
            XTF0FPlatform::getInstance()->setHeader('Status', '500 Internal Server Error', true);
        }
    }

    /**
     * globalsRead.
     *
     * @return object
     */
    protected function globalsRead()
    {
        $this->getJwtHelper()->checkToken();

        $response = new StdClass();
        $response->flavour = VersionHelper::getFlavour();
        $response->list_limit = \Joomla\CMS\Factory::getConfig()->get('list_limit');
        $response->offset = \Joomla\CMS\Factory::getConfig()->get('offset');
        $response->channels = SelectControlHelper::appChannels();

        return $response;
    }

    /**
     * checkToken.
     *
     * @return bool
     */
    protected function checkToken()
    {
        $this->getJwtHelper()->checkToken();

        return true;
    }

    /**
     * requestsChart.
     *
     * @return bool
     */
    protected function requestsChart()
    {
        $this->getJwtHelper()->checkToken();

        if (empty($this->data)) {
            $this->data = new JRegistry();
            GridHelper::loadStats($this->data);
        }

        $requestsData = [
            (object) ['label' => JText::_('COM_AUTOTWEET_TITLE_REQUESTS'),
                'value' => (int) $this->data->get('requests'), ],
            (object) ['label' => JText::_('COM_AUTOTWEET_TITLE_POSTS'),
                'value' => (int) $this->data->get('posts'), ],
        ];

        $stats = new StdClass();
        $stats->data = $requestsData;

        return $stats;
    }

    /**
     * postsChartData.
     *
     * @return bool
     */
    protected function postsChartData()
    {
        $this->getJwtHelper()->checkToken();

        if (empty($this->data)) {
            $this->data = new JRegistry();
            GridHelper::loadStats($this->data);
        }

        $postsData = [
            (object) ['label' => SelectControlHelper::getTextForEnum('success'),
                'value' => (int) $this->data->get('p_success'), ],
            (object) ['label' => SelectControlHelper::getTextForEnum('cronjob'),
                'value' => (int) $this->data->get('cronjob'), ],
            (object) ['label' => SelectControlHelper::getTextForEnum('approve'),
                'value' => (int) $this->data->get('p_approve'), ],
            (object) ['label' => SelectControlHelper::getTextForEnum('cancelled'),
                'value' => (int) $this->data->get('p_cancelled'), ],
            (object) ['label' => SelectControlHelper::getTextForEnum('error'),
                'value' => (int) $this->data->get('p_error'), ],
        ];

        $stats = new StdClass();
        $stats->data = $postsData;

        return $stats;
    }

    /**
     * postsChartTimeline.
     *
     * @return bool
     */
    protected function postsChartTimeline()
    {
        $this->getJwtHelper()->checkToken();

        $data = new JRegistry();
        GridHelper::loadStatsTimeline($data);

        $stats = new StdClass();
        $stats->data = $data->get('timeline');

        return $stats;
    }

    /**
     * menuItemsRead.
     *
     * @return string
     */
    protected function menuItemsRead()
    {
        $this->getJwtHelper()->checkToken();

        \Joomla\CMS\Factory::getLanguage()->load('joomla', JPATH_ADMINISTRATOR);

        return EHtmlSelect::menuitemlist(
            null,
            'selectedMenuItem'
        );
    }

    /**
     * sefUrlRead.
     *
     * @return string
     */
    protected function sefUrlRead()
    {
        $this->getJwtHelper()->checkToken();

        $itemId = $this->input->get('itemId', 0, 'int');
        $url = 'index.php?Itemid='.$itemId;
        $url = RouteHelp::getInstance()->getAbsoluteUrl($url);

        return $url;
    }

    /**
     * requestsSavePluginPost.
     *
     * @return bool
     */
    protected function requestsSavePluginPost()
    {
        $this->getJwtHelper()->checkToken();

        $request = $this->input->getArray();
        $isNew = $this->isNewRequest($request);

        $collection = new CMSRegistry();
        $collection->loadArray($request);

        $data = RequestHelper::getAjaxData($collection);

        $controller = XTF0FController::getTmpInstance('com_autotweet', 'requests');
        $id = $controller->callPluginAction($data);

        if (!$id) {
            $errors = \Joomla\CMS\Factory::getSession()->get('last_req_errors');

            throw new Exception(JText::sprintf('COM_AUTOTWEET_UNABLETO', 'requestsSavePluginPost - '.$errors));
        }

        $message = [
            'request_id' => $isNew ? $id : $request['id'],
            'message' => JText::_('COM_AUTOTWEET_COMPOSER_MESSAGE_SAVED'),
        ];

        $this->setSuccessHeader($isNew);

        return $message;
    }

    /**
     * requestsSaveOwnPost.
     *
     * @return bool
     */
    protected function requestsSaveOwnPost()
    {
        $this->getJwtHelper()->checkToken();

        $request = $this->input->getArray();
        $isNew = $this->isNewRequest($request);

        $collection = new CMSRegistry();
        $collection->loadArray($request);
        $data = RequestHelper::getAjaxData($collection);

        $controller = XTF0FController::getTmpInstance('com_autotweet', 'requests');
        $id = $controller->callAjaxOwnAction($data);

        if (!$id) {
            $errors = \Joomla\CMS\Factory::getSession()->get('last_req_errors');

            throw new Exception(JText::sprintf('COM_AUTOTWEET_UNABLETO', 'requestsSaveOwnPost - '.$errors));
        }

        $message = [
            'request_id' => $id,
            'message' => JText::_('COM_AUTOTWEET_COMPOSER_MESSAGE_SAVED'),
        ];

        $this->setSuccessHeader($isNew);

        return $message;
    }

    /**
     * requestsBrowse.
     *
     * @return $data
     */
    protected function requestsBrowse()
    {
        $this->getJwtHelper()->checkToken();

        $params = $this->input->getArray();
        $config = [
            'option' => 'com_autotweet',
            'view' => 'requests',
            'format' => 'json',
            'input' => $params,
        ];

        $controller = XTF0FController::getTmpInstance('com_autotweet', 'requests', $config);
        $response = $this->callControllerBrowse($controller);
        $response->data = AdvancedAttributesHelper::generateRequestsForComposerApp($response->data);

        return $response;
    }

    /**
     * requestsRead.
     *
     * @return $data
     */
    protected function requestsRead()
    {
        $this->getJwtHelper()->checkToken();

        $params = $this->input->getArray();
        $params['task'] = 'read';

        $config = [
            'option' => 'com_autotweet',
            'view' => 'requests',
            'format' => 'json',
            'input' => $params,
        ];

        $controller = XTF0FController::getTmpInstance('com_autotweet', 'requests', $config);
        $response = $this->callControllerRead($controller);

        if (PERFECT_PUB_PRO) {
            AdvancedAttributesHelper::generateForComposerApp($response->data);
        }

        return $response;
    }

    /**
     * requestsPublish.
     *
     * @return bool
     */
    protected function requestsPublish()
    {
        $this->getJwtHelper()->checkToken();

        $request = $this->input->getArray();
        $config = [
            'option' => 'com_autotweet',
            'view' => 'requests',
            'format' => 'json',
            'input' => $request,
        ];

        $controller = XTF0FController::getTmpInstance('com_autotweet', 'requests', $config);
        $status = $controller->callPublishAjaxAction();

        return $this->setStatusHeader($status);
    }

    /**
     * requestCancel.
     *
     * @return bool
     */
    protected function requestCancel()
    {
        $this->getJwtHelper()->checkToken();

        $request = $this->input->getArray();
        $config = [
            'option' => 'com_autotweet',
            'view' => 'requests',
            'format' => 'json',
            'input' => $request,
        ];

        $controller = XTF0FController::getTmpInstance('com_autotweet', 'requests', $config);
        $published = 1;
        $status = $controller->callMoveToState($published);

        return $this->setStatusHeader($status);
    }

    /**
     * postsBrowse.
     *
     * @return $data
     */
    protected function postsBrowse()
    {
        $this->getJwtHelper()->checkToken();

        $params = $this->input->getArray();
        $config = [
            'option' => 'com_autotweet',
            'view' => 'posts',
            'format' => 'json',
            'input' => $params,
        ];

        $controller = XTF0FController::getTmpInstance('com_autotweet', 'posts', $config);
        $response = $this->callControllerBrowse($controller);
        $response->data = AdvancedAttributesHelper::generatePostsForComposerApp($response->data);

        return $response;
    }

    /**
     * postsRead.
     *
     * @return $data
     */
    protected function postsRead()
    {
        $this->getJwtHelper()->checkToken();

        $params = $this->input->getArray();
        $params['task'] = 'read';

        $config = [
            'option' => 'com_autotweet',
            'view' => 'posts',
            'format' => 'json',
            'input' => $params,
        ];

        $controller = XTF0FController::getTmpInstance('com_autotweet', 'posts', $config);

        return $this->callControllerRead($controller);
    }

    /**
     * postsPublish.
     *
     * @return bool
     */
    protected function postsPublish()
    {
        $this->getJwtHelper()->checkToken();

        $post = $this->input->getArray();
        $config = [
            'option' => 'com_autotweet',
            'view' => 'posts',
            'format' => 'json',
            'input' => $post,
        ];

        $controller = XTF0FController::getTmpInstance('com_autotweet', 'posts', $config);
        $status = $controller->publishAjaxAction();

        return $this->setStatusHeader($status);
    }

    /**
     * postsCancel.
     *
     * @return bool
     */
    protected function postsCancel()
    {
        $this->getJwtHelper()->checkToken();

        $post = $this->input->getArray();
        $config = [
            'option' => 'com_autotweet',
            'view' => 'posts',
            'format' => 'json',
            'input' => $post,
        ];

        $controller = XTF0FController::getTmpInstance('com_autotweet', 'posts', $config);
        $status = $controller->cancelAjaxAction();

        return $this->setStatusHeader($status);
    }

    /**
     * Creates a new model object.
     *
     * @param string $name   The name of the model class, e.g. Items
     * @param string $prefix The prefix of the model class, e.g. FoobarModel
     * @param array  $config The configuration parameters for the model class
     *
     * @return XTF0FModel The model object
     */
    protected function createModel($name, $prefix = '', $config = [])
    {
        $modelName = 'Requests';
        $classPrefix = 'AutoTweetModel';

        $result = XTF0FModel::getAnInstance($modelName, $classPrefix, $config);

        return $result;
    }

    /**
     * imagesSave.
     *
     * @return bool
     */
    protected function imagesSave()
    {
        $this->getJwtHelper()->checkToken();

        $imageurl = null;

        $receivedFile = $this->input->files->get('file');
        $file = $receivedFile['tmp_name'];

        if ($file && file_exists($file) && ImageUtil::isValidImageFile($file)) {
            $filename = $this->retrieveFilenameFromReceivedFile($receivedFile);
            $image = JPATH_JOOCIAL_APP_MEDIA.'/'.$filename;

            if (JFile::upload($file, $image)) {
                $imageurl = str_replace(JPATH_ROOT, '', $image);
                $imageurl = RouteHelp::getInstance()->getAbsoluteUrl($imageurl, true);
                $this->setSuccessHeader(true);

                return $imageurl;
            }
        }

        throw new Exception(JText::sprintf('COM_AUTOTWEET_UNABLETO', 'imagesSave'));
    }

    private function callControllerBrowse($controller)
    {
        ob_start();
        $status = $controller->browse();
        $items = ob_get_contents();
        ob_end_clean();

        if ($status) {
            return $this->wrapCollection($items);
        }

        return false;
    }

    private function callControllerRead($controller)
    {
        ob_start();
        $status = $controller->read();
        $item = ob_get_contents();
        ob_end_clean();

        if ($status) {
            $data = json_decode($item);

            if (!$data) {
                return false;
            }

            $packet = new StdClass();
            $packet->data = $data;

            return $packet;
        }

        return false;
    }

    private function wrapCollection($items)
    {
        $data = json_decode($items);

        if (null !== $data) {
            $collection = new StdClass();
            $collection->data = $data;

            return $collection;
        }

        $data = TextUtil::decodeJsonPackage($items);

        if (null !== $data) {
            $collection = new StdClass();
            $collection->data = $data;

            return $collection;
        }

        return null;
    }

    private function isNewRequest($request)
    {
        return isset($request['id']) && 0 === (int) $request['id'];
    }

    private function setSuccessHeader($isNew)
    {
        XTF0FPlatform::getInstance()->setHeader('Status', $isNew ? '201 Created' : '202 Accepted', true);
    }

    private function setStatusHeader($status)
    {
        if ((bool) $status) {
            $this->setSuccessHeader(false);

            return true;
        }

        XTF0FPlatform::getInstance()->setHeader('Status', '400 Bad Request', true);

        return false;
    }

    private function getJwtHelper()
    {
        return new JwtHelperForJoomla(EParameter::getComponentParam(CAUTOTWEETNG, 'api_token'));
    }

    private function retrieveFilenameFromReceivedFile($receivedFile)
    {
        $file = $receivedFile['tmp_name'];

        [, , $type] = getimagesize($file);
        $extension = image_type_to_extension($type, true);

        $filename = $receivedFile['name'];
        $lFilename = Str::lower($filename);

        if (Str::endsWith($lFilename, $extension)) {
            return $filename;
        }

        if ('.jpeg' === $extension) {
            if (Str::endsWith($lFilename, '.jpg')) {
                return $filename;
            }

            $extension = '.jpg';
        }

        return $filename.$extension;
    }
}
