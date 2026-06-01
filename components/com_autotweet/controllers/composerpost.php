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
 * AutotweetControllerComposerPost.
 *
 * @since       1.0
 */
class AutotweetControllerComposerPost extends ExtlyController
{
    /**
     * The tasks for which caching should be enabled by default.
     *
     * @var array
     */
    protected $cacheableTasks = [];

    /**
     * Single record read. The id set in the request is passed to the model and
     * then the item layout is used to render the result.
     *
     * @return bool
     */
    public function read()
    {
        // Sample Url: curl --user-agent "facebookexternalhit/1" https://...../component/autotweet/composerpost.html?id=13-test-2020-08-19

        $this->cacheableTasks = [];

        // Load the model
        $model = $this->getThisModel();

        if (!$model->getId()) {
            $model->setIDsFromRequest();
        }

        $item = $model->getItem();

        if (!FacebookApp::isFacebookBot()) {
            $this->setRedirect($item->org_url);

            return false;
        }

        JHtmlContent::prepare($item->title, null, 'com_autotweet.composerpost');

        return parent::read();
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
        return parent::createModel('Posts');
    }
}
