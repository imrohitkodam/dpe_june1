<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

?>
<div class="xt-grid cronjob-expression-form" ng-controller="CronjobExprController as cronjobExprCtlr">
    <div class="xt-col-span-10">
    <?php

        $unix_mhdmd = '';
        echo EHtml::ngCronjobExpressionControl(
            $unix_mhdmd,
            'unix_mhdmd',
            'COM_XTCRONJOB_TASKS_FIELD_UNIX_MHDMD',
            'COM_XTCRONJOB_TASKS_FIELD_UNIX_MHDMD_DESC',
            'unix_mhdmd',
            null
        );

        $attrs = [
            'ng-model' => $displayData['controller'].'.repeat_until_value',
        ];

        $attrs = array_merge($attrs, $displayData['classes']);

        $repeat_untilControl = EHtml::datePickerField(
            null,
            'repeat_until',
            'repeat_until',
            $attrs
        );

        echo EHtml::genericControl(
            'COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_UNTIL',
            'COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_UNTIL_DESC',
            'repeat_until',
            $repeat_untilControl
        );

    ?>
    </div>
    <div class="xt-col-span-2">
    <?php

        echo JLayoutHelper::render('pro.field.repeat-shortcuts', null, JPATH_AUTOTWEET_LAYOUTS);

    ?>
    </div>
</div>
