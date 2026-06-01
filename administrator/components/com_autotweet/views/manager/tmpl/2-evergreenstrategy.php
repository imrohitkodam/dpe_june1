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
<div id="evergreenstrategy" class="tab-pane fade shortcuts-tab">
    <p><?php
        echo JText::_('COM_AUTOTWEET_VIEW_MANAGERS_EVERGREENSTRAT_DESC');
    ?></p>
<?php

    echo SelectControlHelper::evergreenTypeControl(
        'xtform[evergreen_type]',
        $this->item->xtform->get('evergreen_type', 1),
        'COM_AUTOTWEET_VIEW_MANAGERS_EVERGREENTYPE_LABEL',
        'COM_AUTOTWEET_VIEW_MANAGERS_EVERGREENTYPE_DESC'
    );

    $control = SelectControlHelper::channels(
        $this->item->xtform->get('channelchooser'),
        'xtform[channelchooser][]',
        [
            'multiple' => true,
            'class' => 'xt-editor__channelchooser',
            'data-placeholder' => '-'.JText::_('JSELECT').'-',
        ],
        'channelchooser'
    );

    echo EHtml::genericControl(
        'COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELS',
        'COM_AUTOTWEET_VIEW_ITEMEDITOR_CHANNELS_DESC',
        'channelchooser',
        $control
    );

?>

<hr/>

    <div class="xt-grid">
        <div class="xt-col-span-5">
<?php

        echo EHtml::cronjobExpressionControl(
            $this->item->xtform->get('evergreen_freq_mhdmd', '30 9 * * *'),
            'xtform[evergreen_freq_mhdmd]',
            'COM_AUTOTWEET_VIEW_MANAGERS_EVERGREENFREQ_LABEL',
            'COM_AUTOTWEET_VIEW_MANAGERS_EVERGREENFREQ_DESC',
            'evergreen_freq_mhdmd',
            null,
            $this->get('managerjs')
        );

?>
<br/>
<hr/>
<br/>
            <p class="xt-alert xt-alert-info">
                <?php
                    $force_evergreens = EParameter::getComponentParam(CAUTOTWEETNG, 'force_evergreens', 0);
                    echo JText::_('COM_AUTOTWEET_COMPARAM_FORCE_EVERG_LABEL').': <span class="xt-label xt-label-success">'.
                        ($force_evergreens ? JText::_('JYES') : JText::_('JNO')).'</span>';
                ?>
                <span class="xt-float-right">
                    <a href="index.php?option=com_config&view=component&component=com_autotweet#advanced">
                        <i class="xticon fas fa-cog"></i>
                    </a>
                </span><br/>
                <i><?php echo JText::_('COM_AUTOTWEET_COMPARAM_FORCE_EVERG_DESC'); ?></i>
            </p>

        </div>
        <div class="xt-col-span-5">
<?php
        echo JLayoutHelper::render('pro.field.repeat-shortcuts', null, JPATH_AUTOTWEET_LAYOUTS);
?>
        </div>
    </div>
</div>
