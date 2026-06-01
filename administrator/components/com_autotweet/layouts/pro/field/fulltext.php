<?php
/**
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see        https://www.extly.com
 */
defined('_JEXEC') || exit;

// Fulltext
?>

<div class="control-group">
    <textarea aria-invalid="false" class="xt-edit__fulltext" rows="4"
        id="fulltext" name="fulltext"
        ng-model="<?php echo $displayData['controller']; ?>.fulltext_value"
        placeholder="<?php

        echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_FULLTEXT');

    ?>">
    </textarea>
</div>

