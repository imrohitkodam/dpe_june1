<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2022 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

$easySocialTargets = EasySocialChannelHelper::getTargets();

?>
<p>
    <?php echo JText::_('COM_AUTOTWEET_CHANNEL_EASYSOCIAL_DESC'); ?>
</p>

<p>
    <br/>
</p>

<p>
<?php
        $attribs = [];
        $control = SelectControlHelper::easySocialTargets(
            $easySocialTargets,
            'xtform[targetId]',
            $attribs,
            $this->item->xtform->get('targetId'),
            'targetId'
        );

        echo EHtml::genericControl(
            'COM_AUTOTWEET_CHANNEL_EASYSOCIAL_TARGETID',
            'COM_AUTOTWEET_CHANNEL_EASYSOCIAL_TARGETDESC',
            'xtform[targetId]',
            $control
);
?>
</p>

<!-- com_autotweet_OUTPUT_START -->
