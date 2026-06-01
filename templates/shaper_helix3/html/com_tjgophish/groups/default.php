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
use Joomla\CMS\Router\Route;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

 HTMLHelper::_('formbehavior.chosen', 'select');


$listOrder     = $this->escape($this->state->get('list.ordering'));
$listDirn      = $this->escape($this->state->get('list.direction'));
$user = Factory::getUser();

// Get Active Item ID
$app = Factory::getApplication();
$itemId = $app->getMenu()->getActive()->id;

// Get create group Item ID
$app  = Factory::getApplication();
$menu = $app->getMenu();
$menuItem = $menu->getItems( 'link', 'index.php?option=com_tjgophish&view=group&layout=edit', true );

// Get campaigns URL
$campaignsLink     = 'index.php?option=com_tjgophish&view=campaigns';
$campaignsMenuItem = $menu->getItems('link', $campaignsLink, true);
$campaignsURL      = Route::_($campaignsLink .'&Itemid=' . $campaignsMenuItem->id, false);

?>
<form action="<?php echo Route::_('index.php?option=com_tjgophish&view=groups&Itemid=' . $itemId, false); ?>" method="post" id="adminForm" name="adminForm">
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
					<a href="<?php echo $campaignsURL; ?>" target="_blank">
						<button type="button" class="btn btn-primary"><span class="fa fa-external-link"></span>
							<?php echo Text::_('COM_TJGOPHISH_CAMPAIGN_CAMPAIGNS_BUTTON'); ?>
						</button>
					</a>
				<?php if ($this->createGroup) { ?>
					<a  href="<?php echo Route::_('index.php?option=com_tjgophish&view=group&layout=edit&Itemid='.$menuItem->id, false); ?>">
						<button type="button" class="btn btn-primary">
							<span class="icon-plus"></span>
							<?php echo Text::_('COM_TJGOPHISH_ADD_GROUP'); ?>
						</button>
					</a>
				<?php } ?>
					<?php
					if (!empty($this->items))
					{
						if ($this->deleteGroup)
						{
						?>
							<button onclick="if (document.adminForm.boxchecked.value == 0) { alert('<?php echo Text::_('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');?>');  return false;} else { if (confirm('<?php echo Text::_('COM_TJGOPHISH_ITEM_DELETE_CONFIRMATION');?>')) { Joomla.submitbutton('groups.delete'); } }" class="btn btn-danger">
							<span class="icon-delete"></span><?php echo Text::_("COM_TJGOPHISH_DELETE_RECORDS");?></button>
						<?php
						}
					}
					?>
			</div>
			<div>&nbsp;</div>
			<div>&nbsp;</div>
			<?php
			if (!empty($this->items))
			{
			?>
			<table class="table table-striped">
				<thead>
					<tr>
						<?php 
						if ($this->deleteGroup)
						{?>
							<th width="2%">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</th>
						<?php
						}
						?>
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
					$link = Route::_('index.php?option=com_tjgophish&view=group&layout=edit&id=' . (int) $row->id.'&Itemid=' . $menuItem->id, false);
					?>
					<tr>
						<?php 
						if ($this->deleteGroup) 
						{
						?>
							<td>
								<?php echo HTMLHelper::_('grid.id', $i, $row->id); ?>
							</td>
						<?php
						}
						?>
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
			<div class="text-center pager" id="pagination">
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

			$multiagencyParams = ComponentHelper::getParams('com_multiagency');
			$orgAdminRoleId    = (int) $multiagencyParams->get('multiagency_school_admin_group', '0', 'INT');
		   $orgAdminRoleId 	 = in_array($orgAdminRoleId, $user->groups);
			?>
			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="boxchecked" value="0"/>
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</div>   
		
</form>
<!--js added for tags filter-->
<script>
	jQuery(document).ready(function(){
		jQuery("#filter_tags").attr("data-placeholder", "<?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>");
		jQuery("#filter_tags").trigger("liszt:updated");
		
		// checked added for dpe admin
		var isDpeAdmin = "<?php echo $user->authorise('core.manageall', 'com_cluster'); ?>";
		var isorgAdmin = "<?php echo $orgAdminRoleId; ?>";


		if (!isDpeAdmin && !isorgAdmin)
		{
			jQuery('#filter_tags_chosen').hide();
		}
	
	jQuery('#filter_tags').on('change', function() {
		jQuery("#filter_cluster_id").val(jQuery("#filter_cluster_id option:first").val());
    });
    jQuery('#filter_cluster_id').on('change', function() {	
		jQuery("#filter_tags").val('');
    });
})
</script>
