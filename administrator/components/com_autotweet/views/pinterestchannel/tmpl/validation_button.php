<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$channeltypeId = $this->input->get('channeltype_id', AutotweetModelChanneltypes::TYPE_PINTEREST_CHANNEL, 'cmd');

if (is_array($accessToken)) {
    $accessToken = (object) $accessToken;
}

if (is_object($accessToken)) {
    $accessToken = json_encode($accessToken);
}

$accessTokenEncoded = htmlentities($accessToken);

?>
<input type="hidden" maxlength="255" value='<?php echo $accessTokenEncoded; ?>' id="access_token" name="xtform[access_token]">

<div id="validationGroup" class=" <?php echo $validationGroupStyle; ?>">

	<div class="control-group">

		<label class="control-label">

		<a class="btn btn-info" id="pinterestvalidationbutton"><?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_VALIDATEBUTTON'); ?></a>&nbsp;

		</label>

		<div id="validation-notchecked" class="controls">
			<span class="lead"><i class="xticon far fa-question-circle"></i> </span><span class="loaderspinner">&nbsp;</span>
		</div>

		<div id="validation-success" class="controls" style="display: none">
			<span class="lead"><i class="xticon fas fa-check"></i> <?php echo JText::_('COM_AUTOTWEET_STATE_PUBSTATE_SUCCESS'); ?></span><span class="loaderspinner">&nbsp;</span>
		</div>

		<div id="validation-error" class="controls" style="display: none">
			<span class="lead"><i class="xticon fas fa-exclamation"></i> <?php echo JText::_('COM_AUTOTWEET_STATE_PUBSTATE_ERROR'); ?></span><span class="loaderspinner">&nbsp;</span>
		</div>

	</div>

	<div id="validation-errormsg" class="xt-alert xt-alert-block alert-error" style="display: none">
		<!-- Removed button close data-dismiss="alert" -->
		<div id="validation-theerrormsg">
			<?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_AUTH_MSG'); ?>
		</div>
	</div>

	<div class="control-group">
		<label class="required control-label" for="raw_access_token" id="access_token_raw-lbl"><?php echo JText::_('COM_AUTOTWEET_CHANNEL_PINTEREST_ACCESS_TOKEN'); ?> <span class="star">&nbsp;*</span>
		</label>
		<div class="controls">
			<input type="text" maxlength="255" value="<?php echo $accessTokenEncoded; ?>" id="raw_access_token" name="xtform[access_token_raw]" readonly="readonly" class="required" required="required">
		</div>
	</div>
<?php

    $boards = [];
    $boards['items'] = [];

    if ($isAuth) {
        $boards = $pinterestChannelHelper->getBoards();
    }

    $attribs = [
        'class' => 'required',
        'required' => 'required',
    ];
    $control = SelectControlHelper::pinterestBoards(
        $boards,
        'xtform[boardid]',
        $attribs,
        $this->item->xtform->get('boardid'),
        'boards'
    );

    echo EHtml::genericControl(
        'COM_AUTOTWEET_CHANNEL_PINTEREST_BOARDID',
        'COM_AUTOTWEET_CHANNEL_PINTEREST_BOARDID_DESC',
        'xtform[boardid]',
        $control
    );
?>

	<div class="control-group">
		<label class="required control-label" for="raw_user_id" id="user_id-lbl"><?php echo JText::_('COM_AUTOTWEET_CHANNEL_PINTEREST_USERID_TITLE'); ?> <span class="star">&nbsp;*</span>
		</label>
		<div class="controls">
			<input type="text" maxlength="255" value="<?php echo $userId; ?>" id="raw_user_id" name="xtform[user_id]" readonly="readonly" class="required" required="required">
<?php

        require __DIR__.'/../../channel/tmpl/social_url.php';

?>
		</div>
	</div>
</div>
