<?php
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Table\Table;

$app            = Factory::getApplication();
$menu           = $app->getMenu();
$menuItem       = $menu->getItems('link', 'index.php?option=com_jlike&view=recommendations', true);
$detailMenu     = $menu->getItems('link', 'index.php?option=com_jlike&view=recommendationform', true);
$todos          = $displayData['todos'];
$totalTodos     = $displayData['totalTodos'];
$completedTodos = $displayData['completedTodos'];

$user           = Factory::getUser();
$params         = DPE::config();
$dateTimeFormat = (String) $params->get('dateTimeFormat');

$options['relative'] = true;
HTMLHelper::_('script', 'com_jlike/jlikeService.js', $options);
HTMLHelper::_('script', 'com_jlike/jlike.js', $options);
?>
<div class="row">
	<div class="col-xs-6 pb-10">
		<h4><?php echo Text::_('COM_DPE_DASHBOARD_ASSIGN_TODOS_HEAD'); ?></h4>
		<?php echo Text::sprintf('COM_DPE_DASHBOARD_ASSIGN_TODOS_COUNT', $completedTodos, $totalTodos); ?>
	</div>
	<div class="col-xs-6 pt-5">
		<div class="pull-right">
			<a class="hasTooltip" target="_blank"
			href="<?php echo Route::_($menuItem->link . '&Itemid=' . $menuItem->id); ?>" target="_blank">
				<?php echo Text::_('COM_DPE_DASHBOARD_VIEW_ALL_TODOS'); ?>
			</a>
		</div>
	</div>
</div>
<div class="clearfix"></div>
<?php
if (empty($todos))
{?>
	<div class="alert alert-warning">
		<?php echo Text::_('COM_DPE_TODOS_DASHBOARD_NO_TODOS');?>
	</div>
<?php
}
else
{
?>
	<table class="table table-hover">
		<thead>
			<th></th>
			<th><?php echo Text::_('COM_DPE_ASSIGNED_TASK_TITLE');?></th>
			<th><?php echo Text::_('COM_DPE_ASSIGNED_TASK_DUE_DATE');?></th>
			<th><?php echo Text::_('COM_DPE_ASSIGNED_TASK_STATUS');?></th>
		</thead>
		<tbody>
		<?php 
		foreach($todos as $todo)
		{
			// get the element from the content id 
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_jlike/tables');
			$contentTable = Table::getInstance('Content', 'JlikeTable');
			$contentTable->load(array('id' => $todo->content_id));
			$element = $contentTable->element;

			$detailViewLink = $detailMenu->link . '&id=' . $todo->id . '&Itemid=' . $detailMenu->id;

			$currentDate = Factory::getDate()->format('Y-m-d');
			$dueDate     = Factory::getDate($todo->due_date)->format('Y-m-d');
			$class       = "";

			if ($dueDate < $currentDate)
			{
				$class = "overdue-todo";
			}
		?>
			<tr class="<?php echo $class;?>">
				<td>
					<a class="complete-mark" onclick="jlike.markComplete('<?php echo $todo->id; ?>', this)" title="<?php echo Text::_('COM_JLIKE_COMPLETE_TODO');?>" type="button" disabled>
					
				<?php	if (($element != 'com_tjlms.lesson') && ($element != 'com_tjlms.course')){
					 		?>
							 <i class="fa fa-circle-o fa-lg"></i>
							 <i class="fa fa-check-circle fa-lg"></i>
							 </a>
				<?php } ?>
					
				</td>
				<td><a href="<?php echo Route::_($detailViewLink); ?>" target="_blank"><?php echo $todo->title;?></a></td>
				<td>
					<?php
						echo HTMLHelper::_('date', $this->escape($todo->due_date), $dateTimeFormat, false);
					?>
				</td>
				<td>
					<?php 
					if ($todo->status === 'S')
					{
						echo Text::_('COM_JLIKE_FORM_TODO_STATUS_STARTED');
					}
					elseif ($todo->status === 'I')
					{
						echo Text::_('COM_JLIKE_FORM_TODO_STATUS_INCOMPLETED');
					}
					elseif ($todo->status === 'C')
					{
						echo Text::_('COM_JLIKE_FORM_TODO_STATUS_COMPLETED');
					}
					?>
				</td>
			</tr>
		<?php
		}
		?>
		</tbody>
	</table>
<?php
}
?>
