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
<div class="xt-grid import-progress hide">
	<div class="xt-col-span-3">
	</div>

	<div class="xt-col-span-6">
		<div class="xt-alert xt-alert-info alert-block">
            <!-- Removed button close data-dismiss="alert" -->
            <h4 class="alert-heading"><?php echo JText::_('COM_AUTOTWEET_VIEW_FEEDS_IMPORT_PROGRESS'); ?></h4>

            <p>&nbsp;</p>

		    <div class="progress progress-striped active">
    			<div class="bar" style="width: 0%;"></div>
    		</div>

    		<p>
    			<span class="xt-label xt-label-info"><?php echo JText::_('COM_AUTOTWEET_VIEW_FEEDS_IMPORT_FEED_NAME'); ?></span>
    			<input type="text" readonly="readonly" class="feed xt-col-span-4" value="">
    			<span class="xt-label xt-label-info"><?php echo JText::_('COM_AUTOTWEET_VIEW_FEEDS_IMPORT_ARTICLE_LABEL'); ?></span>
    			<input type="text" readonly="readonly" class="total xt-col-span-2" value="">
    		</p>

    		<p class="text-center success-message hide" style="text-align: center;">
    			<span class="xt-label xt-label-success success-message"><?php echo JText::_('COM_AUTOTWEET_VIEW_FEEDS_IMPORT_SUCCESS'); ?></span><br/><br/>
    		</p>

         </div>
	</div>

	<div class="xt-col-span-3">
	</div>

</div>
