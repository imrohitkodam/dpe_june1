<?php

/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (C) 2007 - 2017 Extly, CB. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        http://www.extly.com http://support.extly.com
 */
// No direct access
defined('_JEXEC') || exit('Restricted access');

use Joomla\CMS\Factory as CMSFactory;

/**
 * This is the base class for the Extly framework.
 *
 * @since       1.0
 */
class Extly
{
    public const JS_LIB = 'media/lib_perfect-publisher/js/';

    /**
     * loadMeta.
     */
    public static function loadMeta()
    {
        $document = JFactory::getDocument();
        $document->setMetaData('X-UA-Compatible', 'IE=edge,chrome=1');
    }

    /**
     * loadStyle.
     *
     * @param bool $frontendMode Param
     * @param bool $loadChosen   Param
     */
    public static function loadStyle($frontendMode = false, $loadChosen = true)
    {
        JHtml::_('bootstrap.framework');

        if (!$frontendMode) {
            if (EXTLY_J3 && $loadChosen) {
                JHtml::_('formbehavior.chosen', 'select');
            }

            JHtml::_('bootstrap.tooltip');
        }

        JHtml::stylesheet(
            'lib_perfect-publisher/extly-base-'.EXTLY_BASE.'.css',
            [
                'version' => 'auto',
                'relative' => true
            ]
        );
    }

    /**
     * loadStyle.
     */
    public static function loadAwesome()
    {
        CMSFactory::getDocument()->addScript('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js');
    }

    /**
     * getSimpleScriptManager.
     *
     * @return object
     */
    public static function getSimpleScriptManager()
    {
        return new SimpleScriptManager();
    }

    /**
     * getScriptManager.
     *
     * @param bool $loadExtlyAdminMode Param
     * @param bool $ownJqueryDisabled  Param
     * @param bool $loadBootstrap      Param
     *
     * @return object
     */
    public static function getScriptManager($loadExtlyAdminMode = null, $ownJqueryDisabled = false, $loadBootstrap = false)
    {
        static $scriptManager = null;

        if (!$scriptManager) {
            $scriptManager = new SiteDependencyManager($loadExtlyAdminMode, $ownJqueryDisabled, $loadBootstrap);
        }

        return $scriptManager;
    }

    /**
     * loadHtml.
     */
    public static function loadHtml()
    {
        JHtml::addIncludePath(JPATH_ROOT.'/libraries/extly/helpers/html');
    }

    /**
     * initApp.
     *
     * @param string $version         Param
     * @param string $extensionmainjs Param
     * @param array  $dependencies    {key2 => {key1, keyi}}
     * @param array  $paths           {key1 => pathjs1, key2 => pathjs2}
     */
    public static function initApp($version = null, $extensionmainjs = null, $dependencies = [], $paths = [])
    {
        self::getScriptManager()->initApp($version, $extensionmainjs, $dependencies, $paths);
    }

    /**
     * hasApp.
     *
     * @return bool
     */
    public static function hasApp()
    {
        return self::getScriptManager()->hasApp();
    }

    /**
     * getAppName.
     *
     * @param string $file Param
     *
     * @return string
     */
    public static function getAppName($file)
    {
        return self::getScriptManager()->getAppName($file);
    }

    /**
     * addAppDependency.
     *
     * @param string $extensionmainjs Param
     * @param array  $dependencies    {key2 => {key1, keyi}}
     * @param array  $paths           {key1 => pathjs1, key2 => pathjs2}
     */
    public static function addAppDependency($extensionmainjs, $dependencies = [], $paths = [])
    {
        self::getScriptManager()->addAppDependency($extensionmainjs, $dependencies, $paths);
    }

    /**
     * insertDependencyManager.
     *
     * @param string &$body Param
     *
     * @deprecated
     */
    public static function insertDependencyManager(&$body)
    {
        return self::insertApp($body);
    }

    /**
     * insertApp.
     *
     * @param string &$body Param
     */
    public static function insertApp(&$body)
    {
        return self::getScriptManager()->insertApp($body);
    }

    /**
     * addPostRequireScript.
     *
     * @param string $script Param
     */
    public static function addPostRequireScript($script)
    {
        return self::getScriptManager()->addPostRequireScript($script);
    }

    /**
     * getFormId.
     */
    public static function getFormId()
    {
        return 'adminForm';
    }

    /**
     * showInvalidFormAlert.
     */
    public static function showInvalidFormAlert()
    {
        ?>
<div id="invalid-form" class="xt-alert xt-alert-block alert-error"
	style="display: none;">
	<!-- Removed button close data-dismiss="alert" -->
	<h4 class="alert-heading">
		<?php echo JText::_('JGLOBAL_VALIDATION_FORM_FAILED'); ?>
	</h4>
</div>
<?php
    }

    /**
     * getHost.
     *
     * @return string
     */
    public static function getHost()
    {
        $baseurl = JUri::root();

        $uri = new JUri();

        if ($uri->parse($baseurl)) {
            $host = $uri->toString(
                [
                    'scheme',
                    'host',
                    'port',
                ]
            );

            return $host;
        }

        return null;
    }

    /**
     * _getDirectory.
     *
     * @return string
     */
    public static function getDirectory()
    {
        $uri = \Joomla\CMS\Uri\Uri::getInstance();
        $host = $uri->getHost();
        $root = $uri->root();
        $parts = explode($host, $root);
        $path = $parts[1];

        return $path;
    }

    /**
     * loadComponentLanguage.
     *
     * @param string $option Param
     */
    public static function loadComponentLanguage($option)
    {
        // Component Language Load
        $jlang = JFactory::getLanguage();
        $paths = [
            JPATH_ADMINISTRATOR,
            JPATH_ROOT,
        ];
        $jlang->load($option, $paths[0], 'en-GB', true);
        $jlang->load($option, $paths[0], null, true);
        $jlang->load($option, $paths[1], 'en-GB', true);
        $jlang->load($option, $paths[1], null, true);

        return $jlang;
    }
}
