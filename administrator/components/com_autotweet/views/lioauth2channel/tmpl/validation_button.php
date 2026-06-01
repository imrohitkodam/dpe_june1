<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$accessTokenEncoded = htmlentities($accessToken);
$expires_date = $this->item->xtform->get('expires_date', '-');

if (($expires_date) && ('-' !== $expires_date)) {
    $expires_date = EParameter::convertUTCLocal($expires_date);
}

if ((!empty($accessTokenEncoded)) && ($isCompanyChannel)) {
    ?>

<div class="control-group">
	<label class="required control-label" for="xtformlioauth2company_id" id="lioauth2company_id-lbl"><?php echo JText::_('COM_AUTOTWEET_CHANNEL_LINKEDIN_COMPANY_ID'); ?> <span class="star">&nbsp;*</span></label>
	<div class="controls">
		<a class="btn btn-info" id="lioauth2companyloadbutton"><?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_LOADBUTTON_TITLE'); ?></a>
		<?php echo SelectControlHelper::lioauth2companies(
        $this->item->xtform->get('company_id'),
        'xtform[company_id]',
        [],
        $this->item->id
    ); ?>
	</div>
</div>
<?php
}
?>

<div id="validationGroup" class=" <?php echo $validationGroupStyle; ?>">

	<div class="control-group">

		<label class="control-label">
			<a class="btn btn-info" id="lioauth2validationbutton"><?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_VALIDATEBUTTON'); ?></a>&nbsp;
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
		<label class="required control-label" for="raw_user_id" id="user_id-lbl"><?php echo JText::_('COM_AUTOTWEET_CHANNEL_LIOAUTH2_USERID_TITLE'); ?> <span class="star">&nbsp;*</span>
		</label>
		<div class="controls">
			<input type="text" maxlength="255" value="<?php echo $userId; ?>" id="raw_user_id" name="xtform[user_id]" readonly="readonly" class="required" required="required">
<?php

        require __DIR__.'/../../channel/tmpl/social_url.php';

        if ((!empty($accessTokenEncoded)) && (AutotweetModelChanneltypes::TYPE_LIOAUTH2COMPANY_CHANNEL === (int) $channeltypeId)) {
            $social_target = 'social_url_lioauth2company';
            require __DIR__.'/../../channel/tmpl/social_url.php';
        }

?>

		</div>
	</div>

	<div class="control-group">
		<label class="required control-label" for="access_token" id="access_token-lbl"><?php echo JText::_('COM_AUTOTWEET_CHANNEL_LIOAUTH2_ACCESS_TOKEN'); ?> <span class="star">&nbsp;*</span>
		</label>
		<div class="controls">
			<input type="text" maxlength="255" value="<?php echo $accessTokenEncoded; ?>" id="access_token" name="xtform[access_token]" readonly="readonly" class="required disabled" required="required">
		</div>
	</div>

	<div class="control-group">
		<label class="required control-label" for="expires_date" id="expires_date-lbl"><?php echo JText::_('COM_AUTOTWEET_CHANNEL_LIOAUTH2_EXPIRES_DATE'); ?> <span class="star">&nbsp;*</span>
		</label>
		<div class="controls">
			<input type="text" maxlength="255" value="<?php echo $expires_date; ?>" id="expires_date" readonly="readonly" class="required" required="required">

			<a id="authorizeButton" href="#" onclick="document.location='<?php	echo $authUrl;
            ?>';" class="btn btn-info"><?php echo JText::_('COM_AUTOTWEET_CHANNEL_LIOAUTH2_REFRESH'); ?></a>
		</div>
	</div>
</div>
