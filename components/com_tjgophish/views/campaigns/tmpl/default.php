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
use Joomla\CMS\Router\Route;

use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('formbehavior.chosen', 'select');

$listOrder     = $this->escape($this->state->get('list.ordering'));
$listDirn      = $this->escape($this->state->get('list.direction'));

// Add language constant to the JS so that it can be accessed from JavaScript
Text::script("COM_TJGOPHISH_CAMPAIGNS_CAMPAIGN_CONCLUDE_CONFIRMATION");

JFactory::getDocument()->addScriptDeclaration("
	Joomla.submitbutton = function(task)
	{
		if (task == 'campaigns.delete')
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
<form action="index.php?option=com_tjgophish&view=campaigns" method="post" id="adminForm" name="adminForm">
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
					<button type="button" onclick="Joomla.submitbutton('campaign.add');" class="btn btn-success">
					<span class="icon-plus"></span><?php echo Text::_("COM_TJGOPHISH_ADD_CAMPAIGN"); ?></button>
					<?php
					if (!empty($this->items))
					{
						?>
						<button type="button" onclick="Joomla.submitbutton('campaigns.delete');" class="btn btn-error">
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
							<?php echo HTMLHelper::_('searchtools.sort', 'COM_TJGOPHISH_CAMPAIGNS_ID', 'gophish_campaign_id', $listDirn, $listOrder); ?>
						</th>
						<th width="30%">
							<?php echo Text::_('COM_TJGOPHISH_CAMPAIGNS_NAME'); ?>
						</th>
						<th width="20%">
							<?php echo Text::_('COM_TJGOPHISH_CAMPAIGNS_STATUS'); ?>
						</th>
						<th width="20%">
							<?php echo Text::_('COM_TJGOPHISH_CAMPAIGNS_CLUSTER_TITLE'); ?>
						</th>
						<th width="15%">
							<?php echo Text::_('COM_TJGOPHISH_CAMPAIGNS_CREATED_BY'); ?>
						</th>
						<th width="5%">
							<?php echo Text::_('COM_TJGOPHISH_CAMPAIGNS_ACTIONS'); ?>
						</th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ($this->items as $i => $row)
				{
					$link = 'index.php?option=com_tjgophish&task=campaign.edit&id=' . $row->id;
					?>
					<tr>
						<td>
							<?php echo HTMLHelper::_('grid.id', $i, $row->id); ?>
						</td>
						<td>
							<?php echo $row->id; ?>
						</td>
						<td>
							<a href="<?php echo Route::_($link, false); ?>" title="<?php echo Text::_('COM_TJGOPHISH_EDIT_CAMPAIGN'); ?>">
								<?php echo $row->gophish_campaign_title; ?>
							</a>
						</td>
						<td>
							<?php echo $row->gophish_campaign_status; ?>
						</td>
						<td>
							<?php echo $row->clustertitle; ?>
						</td>
						<td>
							<?php echo Factory::getuser($row->created_by)->name; ?>
						</td>
						<td align="center">
							<?php
								$reportLink = 'index.php?option=com_tjgophish&view=campaignreport&id=' . $row->id;
							?>
							<a type="button" title="<?php echo Text::_('COM_TJGOPHISH_CAMPAIGNS_REPORT');?>" href="<?php echo Route::_($reportLink, false);?>" >
								<span class="icon-chart"></span>
							</a>
							<?php
								if ($row->gophish_campaign_status != 'Completed')
								{
									?>
									<a type="button" href="javascript:void(0);" onclick="TjGoPhishCampaigns.concludeCampaign('<?php echo $row->id?>');" title="<?php echo Text::_('COM_TJGOPHISH_CAMPAIGNS_MARK_AS_COMPLETED');?>" >
										| <span class="icon-checkmark-2"></span>
									</a>
									<?php
								}
							?>
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
			<input type="hidden" name="ccid" value=""/>
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</div>
</form>
