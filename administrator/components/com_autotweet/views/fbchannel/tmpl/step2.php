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
    <div id="fbauth" class="tab-pane fade">

        <p class="lead"><?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_AUTH_TITLE'); ?></p>

        <div class="control-group">
            <label class="control-label" rel="tooltip"
                data-original-title="<?php

                echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_AUTH_DESC');

                ?>">
                <?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_AUTHBUTTON_TITLE'); ?>
            </label>
            <div class="controls">
            <script>
function checkLoginState() {
  FB.getLoginStatus(function(response) {
    fbValidationView.fbStatusChangeCallback(response);
  });
}
</script>
                <p class="xt-alert xt-alert-info">Please, click on "Log In" to authorize.</p>

                <fb:login-button id="fbLoginButton" scope="public_profile,publish_pages,manage_pages" onlogin="checkLoginState();">
                </fb:login-button>

                <br><br>
                <div id="fbStatus"></div>
            </div>
        </div>

        <div class="xt-alert xt-alert-info">
            <!-- Removed button close data-dismiss="alert" -->
            <?php
            echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_AUTH_DESC');
            ?>
        </div>

        <div class="control-group">
            <label class="required control-label" for="access_token"
                id="access_token-lbl" rel="tooltip" data-original-title="<?php

                    echo JText::_('COM_AUTOTWEET_VIEW_FBWACCOUNT_USERTOKEN_DESC');

                    ?>"><?php

                    echo JText::_('COM_AUTOTWEET_VIEW_FBWACCOUNT_USERTOKEN_TITLE');

                    ?><span
                class="star">&nbsp;*</span> </label>
            <div class="controls">
                <input type="text" maxlength="255"
                    value="<?php echo $this->item->xtform->get('access_token'); ?>"
                    id="access_token" name="xtform[access_token]"
                    class="required validate-token disabled" required="required">
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">

                <a class="btn btn-info"
                    id="fbextendbutton"<?php

                // Yes (No Canvas Page)
                if ($authorizeCanvas) {
                    echo 'style="display: none;"';
                }

                ?>><?php
                    echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_VALIDATEBUTTON_TITLE'); ?></a>

                <a class="btn btn-info"
                    id="fbvalidationbutton"<?php

                // No or Yes, with Canvas Page
                if (!$authorizeCanvas) {
                    echo 'style="display: none;"';
                }

                ?>><?php
                    echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_VALIDATEBUTTON_TITLE'); ?></a>

            </label>

            <div id="validation-notchecked" class="controls">
                <span class="lead"><i class="xticon far fa-question-circle"></i> </span>
                <span class="loaderspinner72"><?php echo JText::_('COM_AUTOTWEET_LOADING'); ?></span>
            </div>

            <div id="validation-success" class="controls" style="display: none">
                <span class="lead"><i class="xticon fas fa-check"></i> <?php
                echo JText::_('COM_AUTOTWEET_STATE_PUBSTATE_SUCCESS');
                ?> - <?php
                echo JText::_('COM_AUTOTWEET_CHANNEL_SELECTFBCHANNEL');
                ?></span>
                <span class="loaderspinner72"><?php echo JText::_('COM_AUTOTWEET_LOADING'); ?></span>
            </div>

            <div id="validation-error" class="controls" style="display: none">
                <span class="lead"><i class="xticon fas fa-exclamation"></i> <?php echo JText::_('COM_AUTOTWEET_STATE_PUBSTATE_ERROR'); ?></span>
                <span class="loaderspinner72"><?php echo JText::_('COM_AUTOTWEET_LOADING'); ?></span>
            </div>

        </div>

        <div id="validation-errormsg" class="xt-alert xt-alert-block alert-error"
            style="display: none">
            <!-- Removed button close data-dismiss="alert" -->
            <div id="validation-theerrormsg">
                <?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_AUTH_FBMSG'); ?>
            </div>
        </div>

        <div class="control-group">
            <label class=" required control-label" for="user_id" id="user_id-lbl" rel="tooltip" data-original-title="<?php

                    echo JText::_('COM_AUTOTWEET_VIEW_FBWACCOUNT_ACCOUNTID_DESC');

                    ?>"><?php

            echo JText::_('COM_AUTOTWEET_VIEW_FBWACCOUNT_ACCOUNTID_TITLE');

            ?><span
                class="star">&nbsp;*</span> </label>
            <div class="controls">
                <input type="text" maxlength="255"
                    value="<?php echo $this->item->xtform->get('user_id'); ?>"
                    id="user_id" name="xtform[user_id]"
                    class="required validate-numeric" required="required"
                    readonly="readonly">
            </div>
        </div>

        <div class="control-group" style="display:none">
            <label class="required control-label" for="issued_at" id="issued_at-lbl"  rel="tooltip" data-original-title="<?php

            echo JText::_('COM_AUTOTWEET_VIEW_FBWACCOUNT_ISSUEDAT_DESC');

            ?>"><?php

            echo JText::_('COM_AUTOTWEET_VIEW_FBWACCOUNT_ISSUEDAT_TITLE');

            ?><span
                class="star">&nbsp;*</span> </label>
            <div class="controls">
                <input type="text" maxlength="255"
                    value="<?php echo $this->item->xtform->get('issued_at'); ?>"
                    id="issued_at" name="xtform[issued_at]"
                    class="required" required="required"
                    readonly="readonly">
            </div>
        </div>

        <div class="control-group">
            <label class=" required control-label" for="expires_at" id="expires_at-lbl" rel="tooltip" data-original-title="<?php

            echo JText::_('COM_AUTOTWEET_VIEW_FBWACCOUNT_EXPIRESAT_DESC');

            ?>"><?php

            echo JText::_('COM_AUTOTWEET_VIEW_FBWACCOUNT_EXPIRESAT_TITLE');

            ?><span
                class="star">&nbsp;*</span> </label>
            <div class="controls">
                <input type="text" maxlength="255"
                    value="<?php echo $this->item->xtform->get('expires_at'); ?>"
                    id="expires_at" name="xtform[expires_at]"
                    class="required" required="required"
                    readonly="readonly">
            </div>
        </div>

    </div>
