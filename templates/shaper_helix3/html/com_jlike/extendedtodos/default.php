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
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

$noresultClass = 'hide';

// Get content table object to show/hide the filters, table columns as per available interaction
Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_jlike/tables');
$jlikeContentTable = Table::getInstance('Content', 'JlikeTable');

foreach ($this->items as $item)
{
	$jlikeContentTable->load(array('id' => $item->content_id , 'element' => 'com_tjlms.lesson'));
}

$intractions = new Registry($jlikeContentTable->params);
$practice = $intractions['practice_interaction'];
$read = $intractions['read_interaction'];
?>
	<!--page header-->
	<form action="" method="post" name="adminForm" id="adminForm" onsubmit="return false;">

<div class="techjoomla-bootstrap">
	<div class="">
		<div class="searchtools btn-group border-bottom w-100">
			<div class="btn-wrapper filter-container input-append my-3 w-100">
				<input type="text" name="filter[search]" id="filter_search" value=""
				onchange="applyFilters(this);" class="w-80 pl-30 b-0 br-10 mr-20" 
				placeholder="<?php echo Text::_("COM_JLIKE_ASSIGNMENT_SEARCH_PLACEHOLDER"); ?>" 
				title="<?php echo Text::_("COM_JLIKE_ASSIGNMENT_SEARCH_TITLE"); ?>">
				<i class="fa fa-search fs-18 search-logo"></i>
				<div class="d-inline-block">
				<?php if ($practice || $read)
						{ ?>
					<div  class="dropdown-toggle mt-10" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<i class="fa fa-filter fs-18 position-relative cursor-pointer"><span class="notification-dot bg-skyblue hide"></span></i>
					</div>
				<?php } ?>
				<div class="dropdown-menu filter-wrapper p-10">
				<?php if ($read) { ?>
					<select name="filter[read]" onchange="applyFilters(this);"class="br-0">
						<option value=""><?php echo Text::_("COM_JLIKE_ASSIGNMENT_FILTER_READ_SELECT_OPTION"); ?></option>
						<option value="1"><?php echo Text::_("COM_JLIKE_ASSIGNMENT_FILTER_READ_OPTION_READ"); ?></option>
						<option value="0"><?php echo Text::_("COM_JLIKE_ASSIGNMENT_FILTER_READ_OPTION_NOT_READ"); ?></option>
					</select>
				<?php } ?>
				<?php if ($practice)
				 { ?>
					<select name="filter[used]" onchange="applyFilters(this);"class="br-0">
						<option value=""><?php echo Text::_("COM_JLIKE_ASSIGNMENT_FILTER_USED_SELECT_OPTION"); ?></option>
						<option value="1"><?php echo Text::_("COM_JLIKE_ASSIGNMENT_FILTER_USED_OPTION_USED"); ?></option>
						<option value="0"><?php echo Text::_("COM_JLIKE_ASSIGNMENT_FILTER_USED_OPTION_NOT_USED"); ?></option>
					</select>
				<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

		<div id="assignmentTodos" class="assignmentTodos w-100">
			<?php
				if (!empty($this->items))
				{
				?>
			<table class="table table-striped table-hover">
				<thead>
					<tr>
						<th>
							<?php echo Text::_("COM_JLIKE_ASSIGNMENTS_USERNAME");?>
						</th>
						<th>
							<?php echo Text::_("COM_JLIKE_ASSIGNMENTS_DUEDATE");?>
						</th>
						<th>
							<?php echo Text::_("COM_JLIKE_ASSIGNMENTS_DUE_LBL");?>
						</th>
						<?php if ($read) { ?>
							<th>
								<?php echo Text::_("COM_JLIKE_ASSIGNMENTS_READ_CONSENT");?>
							</th>
						<?php } ?>
						<?php if ($practice) { ?>
							<th>
								<?php echo Text::_("COM_JLIKE_ASSIGNMENTS_USED_CONSENT");?>
							</th>
						<?php 
}
						?>
					</tr>
				</thead>
				<tbody>
					<?php
						echo $this->loadTemplate('todos');
						?>
				</tbody>
			</table>
			<?php 
				}
				else
				{
					$noresultClass = '';
				}
				?>
				<div class="alert alert-no-items assignment-todos-noresult <?php echo $noresultClass;?>">
					<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
				</div>

		</div>
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

		<input type="hidden" name="filter_cluster_id" id="cluster_id" value="" />		
	</form>
</div>
<script type="text/javascript">
	var cluster_id = jQuery("#cluster_id").val();

	jQuery("#cluster_id").val(cluster_id);
</script>
<script>
    jQuery(document).ready(function() {
        jQuery(".fa-filter").click(function() {
            jQuery(".d-inline-block").toggleClass("open");
        });
    });
</script>