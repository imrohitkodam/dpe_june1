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
<!-- com_autotweet_OUTPUT_START -->
<p style="text-align:center;">
    <span class="loaderspinner">&nbsp;</span>
</p>

<?php echo JText::_('COM_AUTOTWEET_CHANNEL_PAGESPEED_DESC'); ?>
<hr>

<div class="control-group">
    <label class="required control-label" for="api_key" id="api_key-lbl">
        <?php echo JText::_('COM_AUTOTWEET_CHANNEL_PAGESPEED_FIELD_API_KEY'); ?>
        <span class="star">&#160;*</span>
    </label>
    <div class="controls">
        <input type="text" maxlength="255" value="<?php echo $this->item->xtform->get('api_key'); ?>"
            id="api_key" name="xtform[api_key]" class="required" required="required">
    </div>
</div>

<?php
    require_once JPATH_COMPONENT_ADMINISTRATOR.'/views/mailchannel/tmpl/mail-channel-fields.php';

    echo '<br><br>';

    require_once 'validation_button.php';
?>

<hr>
<div class="xt-alert xt-alert-info">
    <!-- Removed button close data-dismiss="alert" -->

    <ul class="unstyled">
        <li>
            <i class="xticon far fa-thumbs-up"></i>
            <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                How to publish from Joomla</a>
        </li>
    </ul>
</div>

<!-- com_autotweet_OUTPUT_START -->
