<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::script(Uri::root().'media/com_timelog/js/timelog.js');

$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');

?>

<form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post"
	name="adminForm" id="adminForm">
	<div class="row">
	<div class="col-xs-12">
		<div id="filter-progress-bar" class="row">
			<div class="col-xs-12 col-sm-6 marginb10">
				<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this,'options' => array('filtersHidden' => false)));?>
			</div>
			<div class="col-xxxs-12 col-xs-12 col-sm-6 marginb10 text-right">
				<?php if ($this->canCreate) : ?>
					<a href="<?php echo Route::_('index.php?option=com_timelog&task=activityform.edit&id=0'); ?>"
					   class="btn btn-success btn-small"><i
							class="icon-plus"></i>
						<?php echo Text::_('COM_TIMELOG_ADD_ITEM'); ?></a>
				<?php endif; ?>
             </div>
		</div>
	</div>
	<div class="col-xs-12">
<?php
if (!empty($this->items))
{
?>
	<table class="table table-striped" id="activityList">
		<thead>
				<tr>
				<th class=''>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TIMELOG_ACTIVITIES_CLIENT_ID', 'a.client_id', $listDirn, $listOrder); ?>
				</th>
				<th class=''>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TIMELOG_ACTIVITIES_LOG_DATE', 'a.created_date', $listDirn, $listOrder); ?>
				</th>
				<th class=''>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TIMELOG_ACTIVITIES_LOG_USER', 'created_by.name', $listDirn, $listOrder); ?>
				</th>
				<th class=''>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TIMELOG_ACTIVITIES_ACTIVITY_TYPE_ID', 'a.activity_type_id', $listDirn, $listOrder); ?>
				</th>
				<th class=''>
				<?php echo Text::_('COM_TIMELOG_ACTIVITIES_ACTIVITY_NOTE'); ?>
				</th>
				<th class=''>
				<?php echo HTMLHelper::_('grid.sort',  'COM_TIMELOG_ACTIVITIES_LOG_TIME', 'a.spent_time', $listDirn, $listOrder); ?>
				</th>

				<?php if ($this->canDelete): ?>
					<th class="center">
				<?php echo Text::_('COM_TIMELOG_ACTIVITIES_ACTIONS'); ?>
				</th>
				<?php endif; ?>

		</tr>
		</thead>
		<tbody>
		<?php foreach ($this->items as $i => $item) :
			if (!$this->canEdit && $this->canEditOwn): ?>
					<?php $this->canEdit = $this->user->id == $item->created_by; ?>
				<?php endif; ?>
<!--
Route::_('index.php?option=com_timelog&view=activity&id='.(int) $item->id);
-->
			<tr class="row<?php echo $i % 2; ?>">
				<td>
					<?php if ($this->canEdit) : ?>
					<a href="<?php echo Route::_('index.php?option=com_timelog&task=activityform.edit&id=' . (int) $item->id); ?>">
					<?php echo $this->escape($item->activity_title); ?></a>
					<?php else : ?>
						<?php echo $this->escape($item->activity_title); ?>
					<?php endif; ?>
				</td>
				<td>
					<?php echo $item->created_date; ?>
				</td>
				<td>
					<?php echo $item->created_by; ?>
				</td>
				<td>
					<?php echo $this->escape($item->title); ?>
				</td>
				<td>
					<?php echo (strlen($this->escape($item->activity_note)) > 100 ) ? substr($this->escape($item->activity_note), 0, 100) . '...' : $this->escape($item->activity_note); ?>
				</td>
				<td>
					<?php echo $item->spent_time; ?>
				</td>
				<?php if ($this->canDelete): ?>
					<td class="center">
						<a href="<?php echo Route::_('index.php?option=com_timelog&task=activityform.remove&id=' . $item->id); ?>" class="btn btn-mini delete-button" type="button"><i class="icon-trash" ></i></a>
					</td>
				<?php endif; ?>
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
			<div class="alert alert-info"><?php echo Text::_("COM_TIMELOG_NO_RECORDS_FOUND");?></div>
			<?php
		}
		?>

	<div class="col-xs-12">
		<div class="pull-right">
			<?php  echo $this->pagination->getPagesLinks(); ?>
		</div>
	</div>


	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="boxchecked" value="0"/>
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
	<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</div>
</form>

<?php if($this->canDelete) : ?>
<script type="text/javascript">

	jQuery(document).ready(function () {
		jQuery('.delete-button').click(deleteItem);
	});

	function deleteItem() {

		if (!confirm("<?php echo Text::_('COM_TIMELOG_DELETE_MESSAGE'); ?>")) {
			return false;
		}
	}
</script>
<?php endif; ?>
