<?php
/**
 * @package     TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('formbehavior.chosen', 'select');

$listOrder     = $this->escape($this->state->get('list.ordering'));
$listDirn      = $this->escape($this->state->get('list.direction'));

JFactory::getDocument()->addScriptDeclaration("
	Joomla.submitbutton = function(task)
	{
		if (task == 'groups.delete')
		{
			if (!confirm('" . Text::_('COM_TJGOPHISH_ITEM_DELETE_CONFIRMATION') . "'))
			{
				return false;
			}
		}

		Joomla.submitform(task, document.getElementById('adminForm'));
	};
");
?>
<form action="index.php?option=com_tjgophish&view=groups" method="post" id="adminForm" name="adminForm">
	<div id="tjgophish-wrapper">
		<div id="j-main-container">
			<div class="row">
				<div class="col-sm-12">
					<?php
						echo LayoutHelper::render(
							'joomla.searchtools.default',
							array('view' => $this)
						);
					?>
				</div>
			</div>
			<div>&nbsp;</div>
			<div class="pull-right">
					<button type="button" onclick="Joomla.submitbutton('group.add');" class="btn btn-success">
					<span class="icon-plus"></span><?php echo Text::_("COM_TJGOPHISH_ADD_GROUP");?></button>
					<?php
					if (!empty($this->items))
					{
						?>
						<button type="button" onclick="Joomla.submitbutton('groups.delete');" class="btn btn-error">
						<span class="icon-delete"></span><?php echo Text::_("COM_TJGOPHISH_DELETE_RECORDS");?></button>
						<?php
					}
					?>
			</div>
			<div>&nbsp;</div>
			<div>&nbsp;</div>
			<?php
			if (!empty($this->items))
			{
			?>
			<table class="table table-bordered table-striped table-hover">
				<thead>
					<tr>
						<th width="2%">
							<?php echo HTMLHelper::_('grid.checkall'); ?>
						</th>
						<th width="8%">
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_TJGOPHISH_GROUPS_ID', 'gophish_group_id', $listDirn, $listOrder); ?>
						</th>
						<th width="30%">
							<?php echo Text::_('COM_TJGOPHISH_GROUPS_NAME'); ?>
						</th>
						<th width="30%">
							<?php echo Text::_('COM_TJGOPHISH_GROUPS_CLUSTER_TITLE'); ?>
						</th>
						<th width="30%">
							<?php echo Text::_('COM_TJGOPHISH_GROUPS_CREATED_BY'); ?>
						</th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ($this->items as $i => $row)
				{
					$link = 'index.php?option=com_tjgophish&task=group.edit&id=' . $row->id;
					?>
					<tr>
						<td>
							<?php echo HTMLHelper::_('grid.id', $i, $row->id); ?>
						</td>
						<td>
							<?php echo $row->id; ?>
						</td>
						<td>
							<a href="<?php echo $link; ?>" title="<?php echo Text::_('COM_TJGOPHISH_EDIT_GROUP'); ?>">
								<?php echo $row->group_name; ?>
							</a>
						</td>
						<td>
							<?php echo $row->clustertitle; ?>
						</td>
						<td>
							<?php echo Factory::getuser($row->created_by)->name; ?>
						</td>
					</tr>
				<?php
				}
				?>
				</tbody>
			</table>
			<div class="pull-right">
				<?php echo $this->pagination->getPagesLinks(); ?>
			</div>
			<?php
			}
			else
			{
				?>
				<div class="alert alert-info"><?php echo Text::_("COM_TJGOPHISH_NO_RECORDS_FOUND");?></div>
				<?php
			}
			?>
			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="boxchecked" value="0"/>
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</div>
</form>
