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
<?php
if (EXTLY_J3) {
    ?>
<ul class="xt-nav-tabs-joomla3 nav nav-tabs" id="fbchannel-tabs">
    <li class="active"><a data-toggle="tab" href="#fbapp" id="fbapp-tab">
         <i class="xticon fab fa-facebook-f"></i>
         1. <?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_TAB_FBAPPDEFINITION'); ?>
    </a></li>
    <li><a data-toggle="tab" href="#fbauth" id="fbauth-tab" class="<?php echo $isNew ? 'disabled' : ''; ?>">
         <i class="xticon fas fa-lock"></i>
         2. <?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_TAB_AUTHORIZATION'); ?></a>
    </li>
    <li><a data-toggle="tab" href="#fbchannel" id="fbchannel-tab" class="<?php echo $isNew ? 'disabled' : ''; ?>">
        <i class="xticon fas fa-bullhorn"></i>
        3. <?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_TAB_CHANNEL'); ?>
    </a></li>
</ul>
    <?php
}

if (EXTLY_J4) {
    ?>
<ul class="xt-nav xt-nav-tabs xt-nav-tabs-joomla4 nav nav-tabs" id="fbchannel-tabs">
    <li class="nav-item" role="presentation"><a class="nav-link active" id="fbapp-tab" data-bs-toggle="tab"
        data-bs-target="#fbapp" type="button" role="tab" aria-controls="fbapp"
        aria-selected="true">
         <i class="xticon fab fa-facebook-f"></i>
         1. <?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_TAB_FBAPPDEFINITION'); ?>
    </a></li>
    <li class="nav-item" role="presentation"><a class="nav-link <?php echo $isNew ? 'disabled' : ''; ?>"
        id="fbauth-tab" data-bs-toggle="tab"
        data-bs-target="#fbauth" type="button" role="tab" aria-controls="fbauth" class="">
         <i class="xticon fas fa-lock"></i>
         2. <?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_TAB_AUTHORIZATION'); ?></a>
    </li>
    <li class="nav-item" role="presentation"><a class="nav-link <?php echo $isNew ? 'disabled' : ''; ?>" id="fbchannel-tab" data-bs-toggle="tab"
        data-bs-target="#fbchannel" type="button" role="tab" aria-controls="fbchannel">
        <i class="xticon fas fa-bullhorn"></i>
        3. <?php echo JText::_('COM_AUTOTWEET_VIEW_CHANNEL_TAB_CHANNEL'); ?>
    </a></li>
</ul>
    <?php
}
