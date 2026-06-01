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
    <div id="fbapp" class="<?php echo AutotweetToolbar::tabPaneActive(); ?>">

        <div class="j9-deprecated" style="display:none">
        <?php

        $options = [];

        $options[] = ['name' => 'COM_AUTOTWEET_VIEW_CHANNEL_USEOWNAPP_YES', 'value' => 2];
        $options[] = ['name' => 'COM_AUTOTWEET_VIEW_CHANNEL_USEOWNAPP_YESSSL', 'value' => 1];
        echo EHtmlSelect::btnGroupListControl(
            $useownapi,
            'xtform[use_own_api]',
            'COM_AUTOTWEET_VIEW_CHANNEL_USEOWNAPP_TITLE_LABEL',
            'COM_AUTOTWEET_VIEW_CHANNEL_USEOWNAPP_TITLE_DESC',
            $options,
            'use_own_api'
        );

        ?>
        </div>

        <div id="own-app-testing" <?php

            // No
            if (0 !== (int) $useownapi) {
                echo 'style="display: none;"';
            }

            ?>>
            <div class="xt-alert xt-alert-info">
                <?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_FBTESTINGAPP_DESC'); ?>
            </div>
        </div>

        <div id="own-app-details"
            <?php

            // No
            if (!(bool) $useownapi) {
                echo 'style="display: none;"';
            }

            ?>>
            <p class="lead"><?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_APPDATA_TITLE'); ?></p>

            <div class="control-group">
                <label class="control-label <?php

                echo $required;

                ?>"
                    for="app_id" id="app_id-lbl"
                    rel="tooltip" data-original-title="<?php

                    echo JText::_('COM_AUTOTWEET_CHANNEL_FACEBOOK_FIELD_APIKEY_DESC');

                    ?>"><?php

                    echo JText::_('COM_AUTOTWEET_CHANNEL_FACEBOOK_FIELD_APIKEY_LABEL');

                    ?> <span class="star">&nbsp;*</span></label>
                <div class="controls">
                    <input type="text" maxlength="255"
                        value="<?php

                        $app_id = $this->item->xtform->get('app_id');
                        echo $app_id;

                        ?>"
                        id="app_id" name="xtform[app_id]"
                        class="<?php

                        echo $required.$requiredId;

                        ?>"
                        <?php

                        echo $requiredTag;

                        ?>>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label <?php

                echo $required;

                ?>"
                    for="secret" id="secret-lbl" rel="tooltip" data-original-title="<?php

                    echo JText::_('COM_AUTOTWEET_CHANNEL_FACEBOOK_FIELD_APISECRET_DESC');

                    ?>"><?php

                    echo JText::_('COM_AUTOTWEET_CHANNEL_FACEBOOK_FIELD_APISECRET_LABEL');

                    ?> <span class="star">&nbsp;*</span></label>
                <div class="controls">
                    <input type="password" maxlength="255"
                        value="<?php

                        echo $this->item->xtform->get('secret');

                        ?>"
                        id="secret" name="xtform[secret]"
                        class="<?php

                        echo $required.$requiredToken;

                        ?>"
                        <?php

                        echo $requiredTag;

                        ?>>
                </div>
            </div>
<?php

echo '<input type="hidden" id="fb_api7_perms" name="fb_api7_perms" value="'.FacebookApp::PERMS_API7.'">';
echo '<input type="hidden" id="fb_api7_perms_groups_detail" name="fb_api7_perms_groups_detail" value="'.FacebookApp::PERMS_API7_PAGES_AND_GROUPS.'">';

echo '<input type="hidden" id="fb_api_version" name="xtform[fb_api_version]" value="7">';
echo '<input type="hidden" id="fb_api_perms_groups" name="xtform[fb_api_perms_groups]" value="0">';

?>
            <div id="own-app-details-canvas-page" class="control-group" <?php

            // Yes (no Canvas Page)
            if (!$authorizeCanvas) {
                echo 'style="display: none;"';
            }

            ?>>
                <label class="control-label" for="canvas_page" id="canvas_page-lbl"rel="tooltip" data-original-title="<?php

                    echo JText::_('COM_AUTOTWEET_CHANNEL_FACEBOOK_FIELD_APIAUTHURL_DESC');

                    ?>"><?php

                    echo JText::_('COM_AUTOTWEET_CHANNEL_FACEBOOK_FIELD_APIAUTHURL_LABEL');

                    ?> <span class="star">&nbsp;*</span></label>
                <div class="controls">
                    <input type="text" maxlength="255"
                        value="<?php

                        echo $this->item->xtform->get('canvas_page');

                        ?>"
                        id="canvas_page" name="xtform[canvas_page]"
                        class="<?php

                        echo $requiredCanvasPage;

                        ?>">
                </div>
            </div>
        </div>

        <hr />
        <div class="xt-alert xt-alert-info">
            <!-- Removed button close data-dismiss="alert" -->

            <ul class="unstyled">
                <li>
                    <i class="xticon far fa-thumbs-up"></i>
                    <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')">
                        Tutorial: How to publish to Facebook</a>
                </li>
                <li>
                    <i class="xticon fab fa-youtube"></i>
                    <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')"> How to publish from Joomla to Facebook - Video</a>
                </li>
                <li>
                    <i class="xticon far fa-thumbs-up"></i>
                    <a href="#"
                onclick="window.open('https://www.extly.com/docs/perfect_publisher/user_guide/tutorials/', '_blank')"> How to publish from Joomla</a>
                </li>
            </ul>
        </div>

    </div>
