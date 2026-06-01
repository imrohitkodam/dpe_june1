<?php
/**
 * @package     JTicketing
 * @subpackage  com_jticketing
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
/** @var $this JLikeViewExtendedTodos */

use Joomla\CMS\Language\Text;

?>
<div class="techjoomla-bootstrap">
	<!--page header-->
	<form action="" method="post" name="adminForm" id="adminForm">
		<div class="searchtools">
			<div class="btn-wrapper input-append">
				<input type="text" name="filter[search]" id="filter_search" value="" onchange="applyFilters(this);" class="" placeholder="<?php echo Text::_("COM_JLIKE_ASSIGNMENT_SEARCH_PLACEHOLDER"); ?>" title="<?php echo Text::_("COM_JLIKE_ASSIGNMENT_SEARCH_TITLE"); ?>">
			</div>
			 <select name="filter[read]" onchange="applyFilters(this);">
				  <option value=""><?php echo Text::_("COM_JLIKE_ASSIGNMENT_FILTER_READ_SELECT_OPTION"); ?></option>
				  <option value="1"><?php echo Text::_("COM_JLIKE_ASSIGNMENT_FILTER_READ_OPTION_READ"); ?></option>
				  <option value="0"><?php echo Text::_("COM_JLIKE_ASSIGNMENT_FILTER_READ_OPTION_NOT_READ"); ?></option>
			</select>
			 <select name="filter[used]" onchange="applyFilters(this);">
				  <option value=""><?php echo Text::_("COM_JLIKE_ASSIGNMENT_FILTER_USED_SELECT_OPTION"); ?></option>
				  <option value="1">Used</option>
				  <option value="0">Not Used</option>
			</select>
		</div>

		<div id="assignmentTodos">
			<?php
			if (!empty($this->items))
			{
				echo $this->loadTemplate('todos');
			}
			?>
		</div>

		<div><button type="button" class="btn btn-primary assignmentLoadmore"><?php echo Text::_("COM_JLIKE_ASSIGNMENT_LOAD_MORE"); ?></button></div>
		<input type="hidden" name="option" value="com_jlike" />
		<input type="hidden" name="view" value="extendedtodos" />
		<input type="hidden" name="controller" value="extendedtodos" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="total" value="<?php echo $this->get('Total');?>" />
		<input type="hidden" name="filter[contentId]" value="<?php echo $this->escape($this->state->get('filter.contentId'));?>" />
		<input type="hidden" name="limit" value="<?php echo $this->escape($this->state->get('list.limit')); ?>" />
		<input type="hidden" name="limitstart" value=0 />
		<input type="hidden" name="filter_order" value="<?php echo $this->escape($this->state->get('list.ordering')); ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $this->escape($this->state->get('list.direction')); ?>" />
	</form>
</div>
