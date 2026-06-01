<?php
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;

$app                = Factory::getApplication();
$menu               = $app->getMenu();
$menuItem           = $menu->getItems('link', 'index.php?option=com_dpe&view=myassignments', true);
$assignedDocuments  = $displayData['assignedDocuments'];
$completedDocuments = $displayData['completedDocuments'];
$totalAssigned      = count($assignedDocuments);
$params             = ComponentHelper::getParams('com_dpe');
$assignDocLimit     = $params->get('showAssignDocLimit');
$pluginParams       = new Registry(PluginHelper::getPlugin('content', 'jlike_tjlmslesson')->params);

$application = Factory::getApplication();
$sitemenu = $application->getMenu();
$mainmenuItems = $sitemenu->getItems(array('unpublish-menu'), array(''));


foreach ($mainmenuItems as $mainmenuItem) 
{
	if ($mainmenuItem->alias === 'lesson-view') 
	{
		 $menuItemLesson = $mainmenuItem->id;
	}
}
?>
<div class="pb-10">
	<h4><?php echo Text::_('COM_DPE_DASHBOARD_ASSIGNED_DOCUMENTS_HEAD'); ?></h4>
	<?php echo Text::sprintf('COM_DPE_DASHBOARD_ASSIGNED_DOCUMENTS_COUNT', $completedDocuments, $totalAssigned); ?>
</div>

<?php if (empty($assignedDocuments)) 
{ ?>
	<div class="alert alert-warning">
		<?php echo Text::_('COM_DPE_NO_ASSIGNED_DOCUMENTS_MESSAGE');?>
	</div>
<?php
}
else
{ ?>
	<div>
		<div class="pull-right">
			<a class="hasTooltip" target="_blank"
			href="<?php echo Route::_($menuItem->link . '&Itemid=' . $menuItem->id); ?>" target="_blank">
				<?php echo Text::_('COM_DPE_DASHBOARD_VIEW_ALL_DOCUMENTS'); ?>
			</a>
		</div>
	</div>
	<table class="table table-hover">
		<thead>
			<tr>
				<th><?php echo Text::_('COM_DPE_ASSIGN_DOCUMENTS_DASHBOARD_DOCUMENTS_HEAD');?></th>
				<th><?php echo Text::_('COM_DPE_ASSIGN_DOCUMENTS_DASHBOARD_DUE_DATE');?></th>
				<?php
				if ($pluginParams->get('read_interaction') == '1')
				{
				?>
					<th class="text-center">
						<?php echo Text::_('COM_DPE_INTERACTION_READ_UNDERSTOOD'); ?>
					</th>
				<?php
				}

				if ($pluginParams->get('practice_interaction') == '1')
				{
				?>
					<th class="text-center">
						<?php echo Text::_('COM_DPE_INTERACTION_USED'); ?>
					</th>
				<?php
				}
				?>
				<th>
					<?php echo Text::_('COM_DPE_DOCUMENT_STATUS'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php

			// If limit is greater than assigned document then set the limit
			if ($assignDocLimit > count($assignedDocuments))
			{
				$assignDocLimit = count($assignedDocuments);
			}

			for ($i = 0 ; $i < $assignDocLimit ; $i++) 
			{
				$intractions = new Registry($assignedDocuments[$i]->params);
			?>
			<tr>
				<td>
					<a class="hasTooltip" target="_blank"
					href="<?php echo Route::_('index.php?option=com_tjlms&view=lesson&lesson_id=' . $assignedDocuments[$i]->element_id . '&Itemid='.$menuItemLesson ); ?>"
					<?php echo $target;?>
					title="<?php echo Text::_('COM_DPE_DOCUMENT_LAUNCH_HINT'); ?>"	>
						<span class="pull-left"><?php echo $this->escape($assignedDocuments[$i]->title);  ?></span>
					</a>
				</td>
				<td>
					<?php echo HTMLHelper::_('date', $assignedDocuments[$i]->due_date, Text::_('DPE_DATE_FORMAT')); ?>
				</td>
				<?php
				if ($pluginParams->get('read_interaction') == '1')
				{
					if ($intractions['read_interaction'])
					{?>
						<td class="text-center read">
							<?php
							 if ($assignedDocuments[$i]->read) : ?>
								<i class="fa fa-check-circle-o fa-lg" aria-hidden="true"></i>
							<?php else : ?>
								<i class="fa fa-circle-o fa-lg" aria-hidden="true"></i>
							<?php endif; ?>
						</td>
					<?php
					}
					else
					{?>
						<td class="text-center">
							<?php echo Text::_('COM_DPE_NOT_APLLICABLE')?>
						</td>
					<?php
					}
				}

				if ($pluginParams->get('practice_interaction') == '1')
				{
					if ($intractions['practice_interaction'])
					{?>
						<td class="text-center used">
							<?php
							if ($assignedDocuments[$i]->used) : ?>
								<i class="fa fa-check-circle-o fa-lg" aria-hidden="true"></i>
							<?php else : ?>
								<i class="fa fa-circle-o fa-lg" aria-hidden="true"></i>
							<?php endif; ?>
						</td>
					<?php
					}
					else
					{?>
						<td class="text-center">
							<?php echo Text::_('COM_DPE_NOT_APLLICABLE')?>
						</td>
					<?php
					}
				}


				// Calculate status
				$status = Text::_('COM_DPE_DOCUMENT_INCOMPLETE_STATUS');

				if ($intractions['read_interaction'] && $intractions['practice_interaction'] && ($assignedDocuments[$i]->read && $assignedDocuments[$i]->used))
				{
					$status = Text::_('COM_DPE_DOCUMENT_COMPLETE_STATUS');
				}
				elseif ($intractions['read_interaction'] && !$intractions['practice_interaction'] && ($assignedDocuments[$i]->read))
				{
					$status = Text::_('COM_DPE_DOCUMENT_COMPLETE_STATUS');
				}
				elseif ($intractions['read_interaction'] && !$intractions['practice_interaction'] && $assignedDocuments[$i]->read)
				{
					$status = Text::_('COM_DPE_DOCUMENT_COMPLETE_STATUS');
				}
				elseif (!$intractions['read_interaction'] && !$intractions['practice_interaction'])
				{
					$status = Text::_('COM_DPE_NOT_APLLICABLE');
				}
				?>
				<td class="<?php  echo $status; ?>">
					<?php echo $status; ?>
				</td>
			</tr>
			<?php 
			} ?>
		</tbody>
	</table>
<?php } ?>
