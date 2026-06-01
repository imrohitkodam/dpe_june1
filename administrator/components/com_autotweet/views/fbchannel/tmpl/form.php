<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$this->loadHelper('select');

$isNew = (0 === (int) $this->item->id);
$layout = $this->input->get('channeltype_id', AutotweetModelChanneltypes::TYPE_FB_CHANNEL, 'cmd');
$channeltypeId = $layout;

$channeltypes = XTF0FModel::getTmpInstance('Channeltypes', 'AutoTweetModel');
$channeltype = $channeltypes->setId($channeltypeId)->getItem();

$useownapi = (int) $this->item->xtform->get('use_own_api', 2);
$authorizeCanvas = (2 !== $useownapi);

$required = '';
$requiredTag = '';
$requiredToken = '';
$requiredId = '';
$requiredCanvasPage = '';

if ($useownapi) {
    // Check required="required"';
    $requiredTag = '';

    $required = ' required';
    $requiredToken = ' validate-token';
    $requiredId = ' validate-numeric';
}

if (($useownapi) && ($authorizeCanvas)) {
    $requiredCanvasPage = 'required validate-facebookapp';
}

?>
<!-- com_autotweet_OUTPUT_START -->
<span class="loaderspinner72"><?php echo JText::_('COM_AUTOTWEET_LOADING'); ?></span>

<?php echo JText::_('COM_AUTOTWEET_CHANNEL_FACEBOOK_DESC'); ?>
<hr>
<?php

    require_once JPATH_ADMINISTRATOR.'/components/com_autotweet/views/fbchannel/tmpl/xt-nav-tabs.php';

?>
<div class="tab-content" id="fbchannel-tabsContent">
<?php

    require_once 'step1.php';
    require_once 'step2.php';
    require_once 'step3.php';

?>
</div>
<script type="text/javascript">
var autotweet_canvas_app_url='<?php

echo $channeltype->get('auth_url');

?>';
</script>
<!-- com_autotweet_OUTPUT_START -->
