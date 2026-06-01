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

// No direct access
defined('_JEXEC') || exit('Restricted access');

/**
 * DependencyManager.
 *
 * @since       1.0
 */
class DependencyManager
{
    public const JS_BODY = '</body>';
    public const JS_HTML = '</html>';

    public const JS_SCRIPT_BEGIN = "<script>document.addEventListener('DOMContentLoaded', function() {\n";
    public const JS_SCRIPT_END = "\n})</script>";

    public const JS_BACKBONE = 0;

    public const JS_ANGULAR = 1;
    protected $version = '1.0.0';

    protected $appAvailable = false;

    protected $isRendered = false;

    protected $postRequireStatements = [];

    private $loadExtlyAdminMode = false;

    private $ownJqueryDisabled = false;

    private $loadBootstrap = false;

    private $framework = 0;

    /**
     * __construct.
     *
     * @param bool $loadExtlyAdminMode Param
     * @param bool $ownJqueryDisabled  Param
     * @param bool $loadBootstrap      Param
     */
    public function __construct($loadExtlyAdminMode = null, $ownJqueryDisabled = false, $loadBootstrap = false)
    {
        if (null === $loadExtlyAdminMode) {
            $this->loadExtlyAdminMode = JFactory::getApplication()->isClient('administrator');
        } else {
            $this->loadExtlyAdminMode = $loadExtlyAdminMode;
        }

        $this->ownJqueryDisabled = $ownJqueryDisabled;
        $this->loadBootstrap = $loadBootstrap;

        $this->framework = self::JS_BACKBONE;
    }

    /**
     * setFramework.
     *
     * @param int $selected Param
     */
    public function setFramework($selected)
    {
        $this->framework = $selected;
    }

    /**
     * hasApp.
     *
     * @return bool
     */
    public function hasApp()
    {
        $key = $this->getAppKey();
        $hasApp = XTF0FPlatform::getInstance()->getCache($key);

        // It has an App (or a cached script), and it's not rendered
        return (($this->appAvailable) || ($hasApp)) && (!$this->isRendered);
    }

    /**
     * getAppName.
     *
     * @param string $file Param
     *
     * @return string
     */
    public function getAppName($file)
    {
        $file = basename($file);

        // $file = str_replace('.min.js', '', $file);
        // $file = str_replace('.min.js', '', $file);

        $file = preg_replace('/(\.min)?\.js$/', '', $file);

        return $file;
    }

    /**
     * addAppDependency.
     *
     * @param string $extensionmainjs Param
     * @param array  &$dependencies   {key2 => {key1, keyi}}
     * @param array  &$paths          {key1 => pathjs1, key2 => pathjs2}
     *
     * @return string
     */
    public function addAppDependency($extensionmainjs, &$dependencies = [], &$paths = [])
    {
        $appName = 'extlycore';

        // Module dependencies must be added
        if ($extensionmainjs) {
            $appName = $this->getAppName($extensionmainjs);
        }

        // App conditional to all dependencies
        if ((!array_key_exists($appName, $dependencies)) && (!empty($paths))) {
            $dependencies[$appName] = array_keys($paths);
        }

        return $appName;
    }

    /**
     * insertApp.
     *
     * @param string &$body Param
     */
    public function insertApp(&$body)
    {
        if ($this->hasApp()) {
            $this->isRendered = true;

            $jsapp = $this->getApp();

            $this->injectScript($body, $jsapp);
        }
    }

    /**
     * addPostRequireScript.
     *
     * @param string $script Param
     */
    public function addPostRequireScript($script)
    {
        $this->postRequireStatements[] = $script;
    }

    /**
     * initApp.
     *
     * @param string $version         Param
     * @param string $extensionmainjs Param
     * @param array  &$dependencies   {key2 => {key1, keyi}}
     * @param array  &$paths          {key1 => pathjs1, key2 => pathjs2}
     */
    protected function _initApp($version = null, $extensionmainjs = null, &$dependencies = [], &$paths = [])
    {
        if (!$this->loadExtlyAdminMode) {
            throw new Exception('DependencyManager has been deprecated for front-end usage.');
        }

        $this->appAvailable = true;
        $this->version = $version;

        $appName = 'extlycore';

        // Module dependencies must be added
        if ($extensionmainjs) {
            $host = Extly::getHost();

            $appName = $this->getAppName($extensionmainjs);

            // App conditional to all dependencies
            if ((!array_key_exists($appName, $dependencies)) && (!empty($paths))) {
                $dependencies[$appName] = array_keys($paths);
            }

            // $extensionmainjs = str_replace('.js', '', $extensionmainjs);
            $extensionmainjs = preg_replace('/\.js$/', '', $extensionmainjs);

            $paths[$appName] = $this->addAppPath($extensionmainjs);
        }

        static $initialized = false;

        if (!$initialized) {
            $initialized = true;

            if (self::JS_ANGULAR === (int) $this->framework) {
                $this->initPlatformNg($dependencies, $paths);
            } else {
                $this->initPlatform($dependencies, $paths);
            }
        }
    }

    /**
     * _getAppKey.
     *
     * @return string
     */
    protected function getAppKey()
    {
        static $appKey = null;

        if ($appKey) {
            return $appKey;
        }

        $getInput = new JInput('GET');
        $uri = (string) \Joomla\CMS\Uri\Uri::getInstance();

        // Routed by GET
        if ($getInput->get('option')) {
            $appKey = md5($uri);

            return $appKey;
        }

        // Routed by POST
        // option - view - task - Itemid - lang
        $postInput = new JInput('POST');

        if ($postInput->get('option')) {
            $buffer = [];
            $buffer[] = $postInput->get('option');
            $buffer[] = $postInput->get('view');
            $buffer[] = $postInput->get('task');
            $buffer[] = $postInput->get('Itemid');
            $buffer[] = $postInput->get('lang');
            $appKey = md5(implode('', $buffer));

            return $appKey;
        }

        $appKey = md5($uri);

        return $appKey;
    }

    /**
     * initPlatform.
     *
     * @param array &$dependencies Param
     * @param array &$paths        Param
     */
    protected function initPlatform(&$dependencies, &$paths)
    {
        // Dependencies and Paths => Extlycore
        $paths['underscore'] = Extly::JS_LIB.'backbone/underscore.min';
        $paths['backbone'] = Extly::JS_LIB.'backbone/backbone.min';
        $paths['extlycore'] = Extly::JS_LIB.'extlycore.min';

        $dependencies['backbone'] = ['underscore'];

        // Joomla 3.0 or superior
        // JQuery - Bootstrap
        JHtml::_('jquery.framework');
        JHtml::_('bootstrap.framework');

        // Chosen - tooltip
        if ($this->loadExtlyAdminMode) {
            if (EXTLY_J3) {
                JHtml::_('formbehavior.chosen', 'select');
            }

            JHtml::_('bootstrap.tooltip');
        }

        $dependencies['extlycore'] = ['backbone'];
    }

    /**
     * initPlatform.
     *
     * @param array &$dependencies Param
     * @param array &$paths        Param
     */
    protected function initPlatformNg(&$dependencies, &$paths)
    {
        // Dependencies and Paths => Extlycore
        $paths['underscore'] = Extly::JS_LIB.'backbone/underscore.min';
        $paths['angular'] = Extly::JS_LIB.'angular/angular.min';
        $paths['extlycore'] = Extly::JS_LIB.'extlycoreng.min';

        // Joomla 3.0 or superior
        // JQuery - Bootstrap
        JHtml::_('jquery.framework');
        JHtml::_('bootstrap.framework');

        // Chosen - tooltip
        if ($this->loadExtlyAdminMode) {
            JHtml::_('bootstrap.tooltip');
        }
    }

    /**
     * injectScript.
     *
     * @param string &$body Param
     * @param string $jsapp Param
     */
    protected function injectScript(&$body, $jsapp)
    {
        $pos = strrpos($body, self::JS_BODY);

        if (false !== $pos) {
            $body = substr($body, 0, $pos).$jsapp.substr($body, $pos);
        } else {
            $pos = strrpos($body, self::JS_HTML);

            if (false !== $pos) {
                $body = substr($body, 0, $pos).$jsapp.substr($body, $pos);
            }
        }
    }

    /**
     * addAppPath.
     *
     * @param string $appPath Param
     *
     * @return string
     */
    protected function addAppPath($appPath)
    {
        return $appPath;
    }

    /**
     * addPostRequireHook.
     *
     * @param string &$app Param
     */
    protected function addPostRequireHook(&$app)
    {
        if (!empty($this->postRequireStatements)) {
            $app .= self::JS_SCRIPT_BEGIN.'function postRequireHook() {'
                .implode('', $this->postRequireStatements)
                .'};'.self::JS_SCRIPT_END;
        }
    }
}
