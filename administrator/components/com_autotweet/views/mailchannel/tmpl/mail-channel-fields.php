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
	<label class="control-label required" for="mail_sender_email" id="mail_sender_email-lbl"><?php echo JText::_('COM_AUTOTWEET_CHANNEL_MAIL_SENDER_MAIL'); ?> <span class="star">&#160;*</span></label>
	<div class="controls">
		<input type="text" maxlength="255" value="<?php echo $this->item->xtform->get('mail_sender_email'); ?>" id="mail_sender_email" name="xtform[mail_sender_email]" class="required validate-email" required="required">
	</div>
</div>
<div class="control-group required">
	<label class=" control-label" for="mail_sender_name" id="mail_sender_name-lbl"><?php echo JText::_('COM_AUTOTWEET_CHANNEL_MAIL_SENDER_NAME'); ?> <span class="star">&#160;*</span></label>
	<div class="controls">
		<input type="text" maxlength="255" value="<?php echo $this->item->xtform->get('mail_sender_name'); ?>" id="mail_sender_name" name="xtform[mail_sender_name]" class="required" required="required">
	</div>
</div>
<div class="control-group required">
	<label class=" control-label" for="mail_recipient_email" id="mail_recipient_email-lbl"><?php echo JText::_('COM_AUTOTWEET_CHANNEL_MAIL_RECIPIENT_MAIL'); ?> <span class="star">&#160;*</span></label>
	<div class="controls">
		<input type="text" maxlength="255" value="<?php echo $this->item->xtform->get('mail_recipient_email'); ?>" id="mail_recipient_email" name="xtform[mail_recipient_email]" class="required validate-email" required="required">
	</div>
</div>
