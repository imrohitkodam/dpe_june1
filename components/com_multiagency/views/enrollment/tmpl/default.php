<?php
/**
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2020 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.modal', 'a.tjmodal');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::script('media/com_multiagency/js/multiagency.js' );

Text::script('COM_MULTIAGENCY_ENROLMENT_SELECT_ENROLL_ITEMS');

JLoader::import('components.com_tjlms.helpers.main', JPATH_SITE);
$tjlmsHelper = new ComtjlmsHelper;

$app = Factory::getApplication();

$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
?>
<form action="<?php echo Route::_('index.php?option=com_multiagency&view=enrollment&license='. $this->license); ?>" method="post" name="adminForm" id="adminForm">

	<div class="modal-content">
		<legend><?php echo Text::sprintf('COM_MULTIAGENCY_ENROLMENT_AGENCY_FOR', $this->agencyData->title); ?><button type="button" class="close" onclick="closeAssignRecommendPopups();">&times;</button></legend>

	</div>
	<div class="filter-search btn-group"><?php
		if ($this->licenseType == 'all')
		{
			echo HTMLHelper::_('select.genericlist', $this->courseoptions, 'selectedcourse[]', 'class="btn input-medium" size="10" name="groupfilter"  	onchange="this.form.submit();"',"value", "text", $this->courseFilter);
		}
		?>
		</div>
	<div id="filter-progress-bar" class="btn-toolprogress-bar">

		<div class="filter-search input-append col-lg-4" style="margin-top:10px">
<input type="text" name="filter_search" id="filter_search" class="span2" style="padding-top:0;padding-bottom:0"
					placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>"
					value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
					title="<?php echo Text::_('JSEARCH_FILTER'); ?>"/>
										<button class="btn btn-default" type="submit"
					title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
				<i class="icon-search"></i>
			<button class="btn btn-default" id="clear-search-button" type="button"
					title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><?php echo Text::_('COM_MULTIAGENCY_SEARCH_FILTER_CLEAR'); ?>
			    </div>

		<div class="btn-group pull-right hidden-xm hidden-phone">

			<?php echo $this->pagination->getLimitBox(); ?>
		</div>
	</div>
		<?php

		if(empty($this->courseFilter) && $this->licenseType === 'all')
		{
						?>
			<div class="clearfix">&nbsp;</div>
			<div class="alert alert-info"><?php echo Text::_("COM_MULTIAGENCY_SELECT_COURSE");?></div>
			<?php
		}
		else
		{
		if (!empty($this->items))
		{
		?>
		<table class="table table-striped" id="multiagencyList">
			<thead>
				<tr>
					<th width="1%">
						<?php
							$disableCheck = '';
							if (empty($this->items)):
								$disableCheck = 'disabled'; ?>
							<?php endif;?>
						<input type="checkbox" name="checkall-toggle" value=""
							title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>"
							onclick="Joomla.checkAll(this)" <?php echo $disableCheck;?>/>
					</th>
					<th class=''>
						<?php echo HTMLHelper::_( 'grid.sort', 'COM_MULTIAGENCY_ENROLMENT_USER_NAME', 'u.name', $listDirn, $listOrder); ?>
					</th>
					<th class=''>
						<?php echo HTMLHelper::_( 'grid.sort', 'COM_MULTIAGENCY_ENROLMENT_USER_USERNAME', 'u.email', $listDirn, $listOrder); ?>
					</th>
					<th class=''>
						<?php echo HTMLHelper::_( 'grid.sort', 'COM_MULTIAGENCY_ENROLMENT_ROLE', 'rolename', $listDirn, $listOrder); ?>
					</th>
					<th class=''>
						<?php echo HTMLHelper::_( 'grid.sort', 'COM_MULTIAGENCY_ENROLMENT_USERID', 'subusers.user_id', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
<!--
			<tfoot>
				<tr class="pagers">
					<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 5; ?>">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
-->
			<tfoot>
				<tr>
					<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 5; ?>">
						<a class="btn btn-primary enroll-btn pull-right" name="enroll" onclick="enroll();return false;" value=""><?php echo Text::_('COM_MULTIAGENCY_ENROLMENT_BTN'); ?></a>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach ($this->items as $i => $item) : ?>
				<td class="center">
							<?php echo HTMLHelper::_('grid.id', $i, $item->user_id, false, 'uid'); ?>
						</td>
					<td><?php echo $item->name; ?> </td>
					<td><?php echo $item->email; ?> </td>
					<td><?php echo $item->rolename; ?> </td>
					<td><?php echo $item->user_id; ?> </td>

				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		}
		else
		{
			?>
			<div class="clearfix">&nbsp;</div>
			<div class="alert alert-info"><?php echo Text::_("COM_MULTIAGENCY_NO_RECORDS_FOUND");?></div>
			<?php
		}
		}

		?>
	<input type="hidden" name="task" id="task" value="" />
	<input type="hidden" name="license" value="<?php  echo $this->license; ?>" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="tmpl" value="component" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />

	<?php echo HTMLHelper::_( 'form.token'); ?>
</form>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#clear-search-button').on('click', function () {
			jQuery('#filter_search').val('');
			jQuery('#adminForm').submit();
		});
	});
function closeAssignRecommendPopups() {
	window.parent.document.location.reload(true);
	window.parent.SqueezeBox.close();
}
</script
