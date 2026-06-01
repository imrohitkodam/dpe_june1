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

use XTP_BUILD\Extly\Infrastructure\Service\Cms\Joomla\ScriptHelper;

/**
 * AutoTweetDefaultView.
 *
 * @since       1.0
 */
class AutoTweetDefaultView extends XTF0FViewHtml
{
    public const MAX_CHARS_TITLE_SCREEN = 80;
    public const MAX_CHARS_TITLE_SHORT_SCREEN = 32;

    // Options - controller - view - layout - task
    public static $enabledAttrComps = [
        'com_content' => ['-' => ['article' => ['edit' => ['-' => true]]]],

        'com_autotweet' => ['-' => ['request' => ['-' => ['edit' => true]]]],

        'com_easyblog' => ['-' => ['blog' => ['-' => [
            'edit' => true,
            '-' => true, ]]]],
        'com_flexicontent' => ['items' => ['item' => ['-' => [
            'add' => true,
            'edit' => true, ]]]],
        'com_jcalpro' => ['-' => ['event' => [
            'add' => ['-' => true],
            'edit' => ['-' => true], ]]],
        'com_jshopping' => ['products' => ['-' => ['-' => [
            'add' => true,
            'edit' => true, ]]]],
        'com_k2' => ['-' => ['item' => ['-' => ['-' => true]]]],
        'com_sobipro' => ['-' => ['-' => ['-' => [
            'entry.add' => true,
            'entry.edit' => true, ]]]],
        'com_zoo' => [
            'item' => ['-' => ['-' => ['edit' => true]]],
            'submission' => ['submission' => ['submission' => ['save' => true]]],
        ],
        'com_eshop' => ['-' => ['product' => ['-' => ['edit' => true]]]],
    ];

    /**
     * Class constructor.
     *
     * @param array $config Configuration parameters
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $blankImage = \Joomla\CMS\Uri\Uri::root().'media/lib_perfect-publisher/images/Blank.gif';
        $this->assign('blankImage', $blankImage);
    }

    /**
     * Get the renderer object for this view.
     *
     * @return XTF0FRenderAbstract
     */
    public function &getRenderer()
    {
        if (!($this->rendererObject instanceof XTF0FRenderAbstract)) {
            $isBackend = XTF0FPlatform::getInstance()->isBackend();

            if ($isBackend) {
                $this->rendererObject = new AutotweetRenderBack3();
            } else {
                $this->rendererObject = new AutotweetRenderFront3();
            }
        }

        return $this->rendererObject;
    }

    /**
     * addItemeditorHelperApp.
     *
     * @return string
     */
    public static function addItemeditorHelperApp()
    {
        static $link = false;

        if ($link) {
            return $link;
        }

        [$isAdmin, $option, $controller, $task, $view, $layout, $id] = AutotweetBaseHelper::getControllerParams();

        $js = "var autotweetUrlRoot = '".\Joomla\CMS\Uri\Uri::root()."';\n";
        $js .= "var autotweetUrlBase = '".\Joomla\CMS\Uri\Uri::base()."';\n";

        $mediaPath = 'media/com_autotweet/js/itemeditor/templates/';
        $ext = '.txt';
        $joomlaPart = EXTLY_J3 ? '.j3' : '.j4';
        $sitePart = ($isAdmin ? '.admin' : '.site');
        $viewPart = ($view ? '.'.$view : '');

        $tpl0 = $mediaPath.$option.$ext;
        $tpl1 = $mediaPath.$option.$joomlaPart.$ext;
        $tpl2 = $mediaPath.$option.$sitePart.$joomlaPart.$ext;
        $tpl3 = $mediaPath.$option.$sitePart.$ext;
        $tpl4 = $mediaPath.$option.$viewPart.$ext;

        if (is_file(JPATH_ROOT.'/'.$tpl2)) {
            $tpl = $tpl2;
        } elseif (is_file(JPATH_ROOT.'/'.$tpl1)) {
            $tpl = $tpl1;
        } elseif (is_file(JPATH_ROOT.'/'.$tpl4)) {
            $tpl = $tpl4;
        } elseif (is_file(JPATH_ROOT.'/'.$tpl3)) {
            $tpl = $tpl3;
        } elseif (is_file(JPATH_ROOT.'/'.$tpl0)) {
            $tpl = $tpl0;
        } else {
            $tpl = $mediaPath.'com_joocial-default'.$joomlaPart.$ext;
        }

        $tpl = \Joomla\CMS\Uri\Uri::root().$tpl.'?version='.CAUTOTWEETNG_VERSION;

        $js .= "var autotweetPanelTemplate = '".$tpl."';\n";
        ScriptHelper::addScriptDeclaration($js);

        $link = 'index.php?option=com_autotweet&amp;view=itemeditor&amp;layout=modal&amp;tmpl=component&amp;'.JSession::getFormToken().'=1';

        // Add Advanced Attributes
        $params = null;

        // Case Request edit page
        if ((CAUTOTWEETNG === $option) && ('request' === $view) && ('edit' === $task)) {
            $params = AdvancedAttributesHelper::getByRequest($id);
        } elseif ($id > 0) {
            $params = AdvancedAttributesHelper::get($option, $id);
        }

        if (!$params) {
            $params = XTF0FModel::getTmpInstance('Advancedattrs', 'AutoTweetModel')->getAdvancedattrs();
        }

        // Migrating old objects
        if (!isset($params->unix_mhdmd)) {
            $params->unix_mhdmd = '';
        }

        if (!isset($params->repeat_until)) {
            $params->repeat_until = '';
        }

        if (!isset($params->description)) {
            $params->description = '';
        }

        // Migrating old objects
        if (!isset($params->hashtags)) {
            $params->hashtags = '';
        }

        // Migrating old objects
        if (!isset($params->fulltext)) {
            $params->fulltext = '';
        }

        // Migrating old objects
        if (!isset($params->image_url)) {
            $params->image_url = '';
        }

        $params->editorTitle = JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_TITLE');
        $params->postthisLabel = JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_POSTTHIS');
        $params->evergreenLabel = JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_EVERGREEN');
        $params->agendaLabel = JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_SCHEDULER');
        $params->unix_mhdmdLabel = JText::_('COM_XTCRONJOB_TASKS_FIELD_UNIX_MHDMD');
        $params->repeat_untilLabel = JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_UNTIL');
        $params->imageLabel = JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_IMAGES');
        $params->channelLabel = JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELS');

        $params->postthisDefaultLabel = '<img style="width: 0.8em" class="xticon-fa xticon-arrow-alt-circle-up" src="../media/com_autotweet/images/icons/regular/arrow-alt-circle-up.svg"> '.
            JText::_('COM_AUTOTWEET_DEFAULT_LABEL');
        $params->postthisYesLabel = '<img style="width: 0.8em" class="xticon-fa xticon-check" src="../media/com_autotweet/images/icons/solid/check.svg"> '.
            JText::_('JYES');
        $params->postthisNoLabel = '<img style="width: 0.8em" class="xticon-fa xticon-times" src="../media/com_autotweet/images/icons/solid/times.svg">  '.
            JText::_('JNO');
        $params->postthisImmediatelyLabel = '<img style="width: 0.8em" class="xticon-fa xticon-bolt" src="../media/com_autotweet/images/icons/solid/bolt.svg"> '.
            JText::_('COM_AUTOTWEET_POSTTHIS_IMMEDIATELY');
        $params->postthisOnlyOnceLabel = '<img style="width: 0.8em" class="xticon-fa xtdice-one" src="../media/com_autotweet/images/icons/solid/dice-one.svg"> '.
            JText::_('COM_AUTOTWEET_POSTTHIS_ONLYONCE');

        $params->descriptionLabel = JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_MSG');
        $params->hashtagsLabel = JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_HASHTAGS');
        $params->fulltextLabel = JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_FULLTEXT_DESC');

        if (!isset($params->channels_text)) {
            $params->channels_text = '';
        }

        AutotweetBaseHelper::convertUTCLocalAgenda($params->agenda);

        $js = 'var autotweetAdvancedAttrs = '.json_encode($params).";\n";
        ScriptHelper::addScriptDeclaration($js);

        $root = \Joomla\CMS\Uri\Uri::root();

        $attribs = ['defer' => false, 'async' => false];

        JHtml::_('jquery.framework');
        ScriptHelper::addScript($root.'media/lib_perfect-publisher/js/backbone/underscore.min.js', [], $attribs);
        ScriptHelper::addScript($root.'media/lib_perfect-publisher/js/angular/angular.min.js', [], $attribs);
        ScriptHelper::addScriptVersion($root.'media/com_autotweet/js/itemeditor-tab.min.js', [], $attribs);

        return $link;
    }

    /**
     * addItemeditorHelperApp.
     *
     * @return string
     */
    public static function showWorldClockLink()
    {
        $offset = EParameter::getTimezone();

        $buffer = JText::_('COM_AUTOTWEET_SERVER_TIMEZONE_LABEL').': '.trim($offset->getName());
        $buffer .= '<input id="Timezone_Name" type="hidden" value="'.htmlentities($offset->getName(), \ENT_COMPAT, 'UTF-8').'">';
        $buffer .= '<input id="Timezone_Offset" type="hidden" value="'.EParameter::getTimezoneOffset().'">';
        $buffer .= '<a onclick="window.open(this.href,\'World%2520Clock%2520%2526%2520Time%2520Zone%2520Map\',\'scrollbars=yes,resizable=yes,location=no,menubar=no,status=no,toolbar=no,left=0,top=0,width=800,height=500\');return false;" href="https://www.extly.com/timezone/tmz-201410.html" target="_blank" data-original-title="'
                    .JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_WORLDCLOCK')
                    .'" rel="tooltip"> <i class="xticon fas fa-globe"></i></a>';

        return $buffer;
    }

    /**
     * isEnabledAttrComps.
     *
     * @return bool
     */
    public static function isEnabledAttrComps()
    {
        $input = new \Joomla\CMS\Input\Input($_REQUEST);

        $option = $input->get('option');
        $controller = $input->get('controller', '-');
        $view = $input->get('view', '-');
        $layout = $input->get('layout', '-');
        $task = $input->get('task', '-');

        if (isset(self::$enabledAttrComps[$option][$controller][$view][$layout])) {
            return self::$enabledAttrComps[$option][$controller][$view][$layout][$task];
        }

        return false;
    }

    /**
     * Runs before rendering the view template, echoing HTML to put before the
     * view template's generated HTML.
     */
    protected function preRender()
    {
        $view = $this->input->getCmd('view', 'cpanel');
        $task = $this->getModel()->getState('task', 'browse');

        // Don't load the toolbar on CLI

        if (!XTF0FPlatform::getInstance()->isCli()) {
            $toolbar = XTF0FToolbar::getAnInstance($this->input->getCmd('option', 'com_foobar'), $this->config);

            // Channel Restriction
            if ((PERFECT_PUB_FREE)
                && (('channels' === $view) || ('channel' === $view))) {
                $channels = XTF0FModel::getTmpInstance('Channels', 'AutoTweetModel');
                $c = $channels->getTotal();

                if ($c >= 2) {
                    $this->perms->create = false;
                    $toolbar->perms->create = false;
                }
            }

            if ((!PERFECT_PUB_PRO)
                && (('feeds' === $view) || ('feed' === $view))) {
                $feeds = XTF0FModel::getTmpInstance('Feeds', 'AutoTweetModel');
                $c = $feeds->getTotal();

                if ($c >= 2) {
                    $this->perms->create = false;
                    $toolbar->perms->create = false;
                }
            }

            // ---
            $toolbar->renderToolbar($view, $task, $this->input);
        }

        $renderer = $this->getRenderer();

        if (!($renderer instanceof XTF0FRenderAbstract)) {
            $this->renderLinkbar();
        } else {
            $renderer->preRender($view, $task, $this->input, $this->config);
        }

        $freeFlavour = VersionHelper::isFreeFlavour();
        $update_dlid = EParameter::getComponentParam(CAUTOTWEETNG, 'update_dlid');
        $needsdlid = ((!$freeFlavour) && (empty($update_dlid)));

        if ($needsdlid) {
            echo JText::sprintf(
                'COM_AUTOTWEET_LBL_CPANEL_NEEDSDLID',
                VersionHelper::getFlavourName(),
                'https://www.extly.com/live-update-your-download-id.html'
            );
        }

        if (!EParameter::getComponentParam(CAUTOTWEETNG, 'cron_enabled')) {
            echo JText::_('COM_AUTOTWEET_LBL_CPANEL_NEEDSCRON');
        }
    }

    /**
     * Executes before rendering the page for the Add task.
     *
     * @param string $tpl Subtemplate to use
     *
     * @return bool Return true to allow rendering of the page
     */
    protected function onAdd($tpl = null)
    {
        $result = parent::onAdd($tpl);

        if ((isset($this->item->id)) && (0 === (int) $this->item->id) && (isset($this->item->published))) {
            $this->item->published = $this->perms->editstate;
        }

        return $result;
    }
}
