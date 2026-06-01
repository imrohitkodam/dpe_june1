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
<div class="xt-editor__shortcuts">
	<p><strong><?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_SHORTCUTS'); ?></strong></p>
	<ul class="shortcuts">
		<li><a class="reset" ng-click="cronjobExprCtlr.reset()"><?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_NONE'); ?></a></li>
		<li><a class="example1" ng-click="cronjobExprCtlr.example1()"><?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_EXAMPLE1'); ?></a></li>
		<li><a class="example2" ng-click="cronjobExprCtlr.example2()"><?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_EXAMPLE2'); ?></a></li>
		<li><a class="example3" ng-click="cronjobExprCtlr.example3()"><?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_EXAMPLE3'); ?></a></li>
		<li><a class="example4" ng-click="cronjobExprCtlr.example4()"><?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_EXAMPLE4'); ?></a></li>
		<li><a class="example5" ng-click="cronjobExprCtlr.example5()"><?php echo JText::_('COM_AUTOTWEET_VIEW_ITEMEDITOR_REPEAT_EXAMPLE5'); ?></a></li>
	</ul>
</div>
