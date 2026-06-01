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
<div class="control-group">
    <textarea id="description" rows="4" class="xt-editor__description"
        placeholder="<?php echo JText::_('COM_AUTOTWEET_COMPOSER_TYPE_MESSAGE_LABEL'); ?>"
        ng-model="messageCtrl.description_value"
        ng-change="messageCtrl.countRemaining()"></textarea>
    <br /> <span class="xt-editor__counter">
    <sub class="{{messageCtrl.remainingCountClass}}">
    {{messageCtrl.remainingCount}}</sub></span>
</div>
