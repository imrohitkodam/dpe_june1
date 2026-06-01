<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Test123
 * @author     Parth Lawate <contact@techjoomla.com>
 * @copyright  2017 Parth Lawate
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Form\FormHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
jimport( 'joomla.html.html.select' );

?>

<div class="tj-page checklist_dashboard print-view checklist-print-view">
<div class ="row">
	<div class="col-12">

		<form action="<?php echo Route::_('index.php?option=com_dpe&view=dashboard'); ?>" method="post" name="dashboard" id="dashboard">
			<!--Filterbar start-->
			<div class="checklist_outer p-2 d-inline-block w-100">
				<?php
				// Check if com_cluster component is installed
				if (ComponentHelper::getComponent('com_cluster', true)->enabled)
				{
					FormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_tjfields/models/fields/');
					$cluster           = FormHelper::loadFieldType('cluster', false);
					$this->clusterList = $cluster->getOptionsExternally();
					?>
					<div class="btn-group pull-right">
						<?php
							if (count($this->clusterList) > 1)
							{
								unset($this->clusterList[0]);
							}
							echo HTMLHelper::_('select.genericlist', $this->clusterList, "filter[cluster_id]", 'class="input-medium" size="1" onchange="this.form.submit();"', "value", "text", $this->state->get('filter.cluster_id', '', 'INT'));
						?>
					</div>
					<?php
				}
				?>
			</div>
			
			<br><br>
			<div class="today-date visible-print print-view-date">
			<?php echo Text::_("COM_DPE_PRINT_DATE"); ?>:
				<span class="font-bold">
					<?php echo HTMLHelper::date('now', Text::_('COM_DPE_DATE_ONLY_FORMAT'), false);?>
				</span>
			</div>
			<div class="print-hide text-end">
				<?php if (count($this->clusterList) > 1){?>
				<a type="button" href="<?php echo Route::_('index.php?option=com_dpe&view=dashboardchecklist', true);?>"class="btn btn-primary float-start mb-4" ><i class="fa fa-arrow-left"></i> <?php echo Text::_("COM_DPE_BACK"); ?></a>
			<?php } ?>

				<button type="button" class="btn btn-primary print-hide-btn" onclick="print_profile()"><i class="fa fa-print"></i> <?php echo Text::_("COM_DPE_PRINT"); ?></button>
			</div>
			<br><br>
			<!--Filterbar end-->
			<!--title start-->
			<h3 class="mt-0 font-700 txt-gray"><?php echo Text::_('COM_DPE_CHECKLIST_BEST_PRACTICE_CHECKLIST'); ?></h3>
			<!-- <div class="today-date visible-print">
				<?php echo HTMLHelper::date('now', Text::_('COM_DPE_DATE_ONLY_FORMAT'), false);?>
			</div> -->
			<!--Filterbar start-->
			<!--option-color start-->
			<div class="row my-10">
				<div class="col-xs-12 fs-12 font-bold print-view-fs-14 print-view-mt-10">
					<span class="mr-10"><i class="fa fa-circle txt-green mr-5" aria-hidden="true"></i><?php echo Text::_('COM_DPE_CHECKLIST_DONE_TITLE'); ?></span>
					<span class="mr-10"><i class="fa fa-circle txt-orange mr-5" aria-hidden="true"></i><?php echo Text::_('COM_DPE_CHECKLIST_INPROGRESS_TITLE'); ?></span>
					<span><i class="fa fa-circle txt-red mr-5" aria-hidden="true"></i><?php echo Text::_('COM_DPE_CHECKLIST_TODO_TITLE'); ?></span>
				</div>
			</div>
			<!--option-color end-->
			<!--list start-->
			<?php
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models');
			$dashboardModel = BaseDatabaseModel::getInstance('Dashboard', 'DpeModel', array('ignore_request' => true));
					?>
			<div class="table-responsive border-0 checklist-table">
			<table class="col-xs-12 mt-10 table">
				<tbody>
					<?php
					$barShow = 0;
					$isFirstTime = $dashboardModel->checkAllowCount('',$this->clusterId);

					foreach ($this->items as $item)
					{
						$classTodo = '';
						$classInprogress = '';
						$classDone = '';

						if ($item->modified_date)
						{
							$dpeUtility      = DPE::utilities();

							$barShow = 1;

							$link = Route::_('index.php?option=com_tjucm&view=itemform&client=' .$item->unique_identifier. '&id=' .$item->id. '&cluster_id='. $this->clusterId, true);

							$status ='<a class="font-bold print-hide" href="'. $link .'">'. Text::_('COM_DPE_CHECKLIST_UPDATE') . '  &lrm;| &lrm; </a><span class="checklistDate print-view-fs-14">'. Text::_('COM_DPE_CHECKLIST_LAST_UPDATE') . $dpeUtility->toLapsed($item->modified_date) . '</span>';


							//. HTMLHelper::_('date.relative', $item->modified_date) .'</span>';

							$barData = $dashboardModel->getBarData($item->unique_identifier, $item->id);

							$todo = ($barData->todo * 100) / $barData->total;
							$todoHover = $barData->todo .'/'. $barData->total;
							$done = ($barData->done * 100) / $barData->total;
							$doneHover = $barData->done .'/'. $barData->total;
							$inprogress = ($barData->inprogress * 100) / $barData->total;
							$inprogressHover = $barData->inprogress .'/'. $barData->total;

							if ($todo > 0)
							{
								$classTodo="red";
							}

							if ($done > 0)
							{
								$classDone = 'green';
							}

							if ($inprogress > 0)
							{
								$classInprogress = 'orange';
							}
						}

						else
						{
							$todo = $done = $inprogress = $inprogressHover= $doneHover = $todoHover = 0;

							$link = Route::_('index.php?option=com_tjucm&view=itemform&client=' . $item->unique_identifier . '&cluster_id='. $this->clusterId, true);

							$status = '<a class="font-bold print-hide" href="'. $link .'">'. Text::_('COM_DPE_CHECKLIST_START') . '</a>';
						}
						echo '<tr><td class="col-sm-4 border-top-0 align-top pl-0 pt-0 pb-20"><h4 class="mt-0">' . $item->title .'</hr></td>';
						if ($isFirstTime)
						{
							echo '<td class="col-sm-4 border-top-0 align-top white-space pb-20">
							<div class="progress">
							<div class="progress-bar progress-bar-info progress-green '.$classDone.'" role="progressbar" data-toggle="tooltip" title="'.$doneHover.'" aria-valuenow="'.$done.'" aria-valuemin="0" aria-valuemax="100" style="width:'.$done.'%"></div>
							<div class="progress-bar progress-bar-warning
							'.$classInprogress.'" role="progressbar"  data-toggle="tooltip" title="'.$inprogressHover.'"  aria-valuenow="'.$inprogress.'" aria-valuemin="0" aria-valuemax="100" style="width:'.$inprogress.'%"></div>
							<div class="progress-bar progress-bar-danger
							'.$classTodo.'" role="progressbar"  data-toggle="tooltip" title="'.$todoHover.'"  aria-valuenow="'.$todo.'" aria-valuemin="0" aria-valuemax="100" style="width:'.$todo.'%"></div></div>
							</td>';
						}
						
						// Get com_subusers component status
						$subUserExist = ComponentHelper::getComponent('com_subusers', true)->enabled;

						// Check user have permission to edit record of assigned cluster
						if ($subUserExist)
						{
							JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);

							/*
							 *  @Todo migration for client 'com_cluster' in dpe
							 *  Com_dpe - Hack - start
							 *  Original Code : RBACL::authorise($user->id, 'com_cluster', 'core.manage', 'com_cluster', $this->clusterId)
							 */

							// Check user has permission for mentioned cluster
							if ($this->user->authorise('core.manageall', 'com_cluster') || RBACL::check($this->user->id, 'com_cluster', 'core.createitem.' . $item->type_id, 'com_tjucm', $this->clusterId))
							{
								echo '<td class="border-top-0 align-top pt-0 pb-20 col-sm-4 print-text-right">
								'. $status .'</td></tr>';
							}
						}
					}
					?>

				</tbody>
			</table>
			</div>
		</form>
	</div>
</div>
</div>

<script>

	function print_profile()
	{	
		
		window.print();
	}
	jQuery(document).ready(function(){

		jQuery('.chosen-single').css({
    'border': '0px solid white',
    'float': 'right'
});

	})

</script>
