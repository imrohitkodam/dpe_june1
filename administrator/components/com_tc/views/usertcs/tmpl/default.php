<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tc
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2016-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/');
use Joomla\CMS\HTML\HTMLHelper;
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');
use Joomla\CMS\Layout\LayoutHelper;
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
?>
<script type="text/javascript">
	Joomla.orderTable = function () {
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $listOrder; ?>') {
			dirn = 'asc';
		} else {
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, '');
	};

	jQuery(document).ready(function () {
		jQuery('#clear-search-button').on('click', function () {
			jQuery('#filter_search').val('');
			jQuery('#adminForm').submit();
		});
	});
</script>

<form action="<?php echo JRoute::_('index.php?option=com_tc&view=usertcs'); ?>" method="post" name="adminForm" id="adminForm">
	<?php
	if (!empty($this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
	<?php
	else : ?>
		<div id="j-main-container">
	<?php
	endif;
	echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));?>
	<?php
	if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
				</div>
	<?php
	else : ?>
			<div class="clearfix"></div>
	<table class="table table-striped" id="contentList">
		<thead>
			<tr>
				<th class='left'>
				<?php echo JHtml::_('searchtools.sort',  'COM_TC_USERTCS_NAME', 'uc.`name`', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('searchtools.sort',  'COM_TC_USERTCS_TITLE', 'c.`title`', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('searchtools.sort',  'COM_TC_USERTCS_TC_CODE', 'c.`client`', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('searchtools.sort',  'COM_TC_CONTENTS_VERSION', 'c.`version`', $listDirn, $listOrder); ?>
				</th>
				<th class='left'>
				<?php echo JHtml::_('searchtools.sort',  'COM_TC_USERTCS_ACCEPTED_DATE', 'a.`accepted_date`', $listDirn, $listOrder); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
				<?php
			foreach ($this->items as $i => $item) :

					$acceptedDate = HTMLHelper::_('date', $item->accepted_date, $this->dateFormat, true);
				?>
				<tr class="row<?php echo $i % 2; ?>">
					<td>
						<?php echo $this->escape($item->name); ?>
					</td>
					<td>
						<?php echo $this->escape($item->title); ?>
					</td>
					<td>
						<?php echo $item->client; ?>
					</td>
					<td>
						<?php echo $item->version; ?>
					</td>
					<td>
						<?php echo $acceptedDate; ?>
					</td>
				</tr>
			<?php
			endforeach; ?>
		</tbody>
	</table>
	<?php
	endif; ?>
			<input type="hidden" name="task" value=""/>
			<?php echo JHtml::_('form.token'); ?>
		</div>
</form>
