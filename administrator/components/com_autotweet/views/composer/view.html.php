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
 * AutotweetViewComposer.
 *
 * @since       1.0
 */
class AutotweetViewComposer extends AutoTweetDefaultView
{
    /**
     * onAdd.
     *
     * @param string $tpl Param
     */
    public function onAdd($tpl = null)
    {
        $result = parent::onAdd($tpl);

        Extly::loadAwesome();
        JHtml::stylesheet('lib_extly/ng-table.min.css', ['version' => 'auto', 'relative' => true]);

        $root = \Joomla\CMS\Uri\Uri::root();
        ScriptHelper::addScriptDeclaration('var PERFECT_PUB_PRO = '
            .(PERFECT_PUB_PRO ? 'true' : 'false').';');

        $attribs = ['defer' => false, 'async' => false];
        ScriptHelper::addScript($root.'media/lib_perfect-publisher/js/backbone/underscore.min.js', [], $attribs);
        ScriptHelper::addScript($root.'media/lib_perfect-publisher/js/angular/angular.min.js', [], $attribs);
        ScriptHelper::addScript($root.'media/lib_perfect-publisher/js/angular/angular-resource.min.js', [], $attribs);
        ScriptHelper::addScript($root.'media/lib_perfect-publisher/js/angular/ng-table.min.js', [], $attribs);
        ScriptHelper::addScript($root.'media/lib_perfect-publisher/js/angular/ui-bootstrap-buttons.min.js', [], $attribs);
        ScriptHelper::addScript($root.'media/lib_perfect-publisher/js/utils/md5.min.js', [], $attribs);

        ScriptHelper::addScriptVersion($root.'media/com_autotweet/js/cryptojslib/core-min.js', [], $attribs);
        ScriptHelper::addScriptVersion($root.'media/com_autotweet/js/cryptojslib/enc-base64-min.js', [], $attribs);

        // Load it in advance
        JHtml::script(
            'lib_extly/utils/xtcronjob-expression-field-ng.js',
            [
                'version' => 'auto',
                'relative' => true
            ]
        );

        ScriptHelper::addScriptVersion($root.'media/lib_perfect-publisher/js/extlycoreng.min.js', [], $attribs);
        ScriptHelper::addScriptVersion($root.'media/com_autotweet/js/composer.min.js', [], $attribs);

        $platform = XTF0FPlatform::getInstance();
        $this->assign('editown', $platform->authorise('core.edit.own', $this->input->getCmd('option', 'com_foobar')));
        $this->assign('editstate', $platform->authorise('core.edit.state', $this->input->getCmd('option', 'com_foobar')));

        return $result;
    }
}
