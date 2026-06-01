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
<h2><?php echo JText::_('COM_AUTOTWEET_VIEW_FEED_TAB_LANGS'); ?></h2>
<?php

    echo EHtml::textControl(
        $this->item->xtform->get('encoding', ''),
        'xtform[encoding]',
        'COM_AUTOTWEET_VIEW_FEED_FEED_ENCODING',
        'COM_AUTOTWEET_VIEW_FEED_FEED_ENCODING_DESC'
    );

    echo EHtml::textareaControl(
        $this->item->xtform->get('custom_translit', ''),
        'xtform[custom_translit]',
        'COM_AUTOTWEET_VIEW_FEED_CUSTOM_TRANSLIT',
        'COM_AUTOTWEET_VIEW_FEED_CUSTOM_TRANSLIT_DESC'
    );
