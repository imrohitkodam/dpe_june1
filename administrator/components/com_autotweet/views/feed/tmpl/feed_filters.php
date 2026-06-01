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
<div id="filters" class="tab-pane fade">
<?php

    echo EHtmlSelect::yesNoControl(
        $this->item->xtform->get('ignore_empty_intro', 0),
        'xtform[ignore_empty_intro]',
        'COM_AUTOTWEET_VIEW_FEED_IGNORE_EMPTY',
        'COM_AUTOTWEET_VIEW_FEED_IGNORE_EMPTY_DESC'
    );

    require_once 'feed_duplicates.php';
    require_once 'feed_importfilters.php';
    require_once 'feed_htmlfilters.php';
    require_once 'feed_textfilters.php';

?>
</div>
