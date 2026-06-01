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
use Joomla\CMS\Factory;


HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
jimport( 'joomla.html.html.select' );
JLoader::import('components.com_cluster.includes.cluster', JPATH_ADMINISTRATOR);
$user = Factory::getUser();
?>

<div class="tj-page checklist_dashboard print-view checklist-print-view">
	<div class ="container">
		<div class="row">
			<form action="<?php echo Route::_('index.php?option=com_dpe&view=dashboardchecklist'); ?>" method="post" name="dashboard" id="dashboard">
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
							if ((count($this->clusterList) > 1 )&& $user->authorise('core.manageall', 'com_cluster'))
							{
								// unset($this->clusterList[0]);


							}
							 echo HTMLHelper::_('select.genericlist', $this->clusterList, "filter[cluster_id]", 'class="input-medium" size="1" onchange="this.form.submit();"', "value", "text", $this->state->get('filter.cluster_id', '', 'INT'));
							?>
						</div>
						<?php
					}
					
					$params     			   = ComponentHelper::getParams('com_multiagency');
					$multiagency_trustee_group = (int) $params->get('multiagency_trustee_group');
					$isTrustee 				   = in_array($multiagency_trustee_group, $user->groups);
					$orgAdminRoleId            = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
					$orgAdminRoleId 		   = in_array($orgAdminRoleId, $user->groups);

					// Hide the Tags filter for non-Super Admin users
					if ($user->authorise('core.manageall', 'com_cluster') || $user->authorise('core.admin'))
					{
						FormHelper::addFieldPath(JPATH_SITE . '/components/com_tjucm/models/fields/');
						$dpeTags = FormHelper::loadFieldType('dpetags', false);
						$dpeTag  = $dpeTags->getOptions(); 

						JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
						$dpeModel = DPE::model('school', array('ignore_request' => true));

						$trusteeTags = ($isTrustee)?$dpeModel->getAgencyTags($multiagency_trustee_group):$dpeModel->getAgencyTags($orgAdminRoleId);
						?>


						<div class="btn-group mr-10 md-w-200px float-end">

							<!-- <label style="margin-right: 50px;display:none !important;margin-top: 20px;" id="tag_label">Tag</label> -->
							<fieldset id="filter-bar">
								<div class="filter-select fltrt">
									<select name="filter_tags[]" id = "filter_tags"  data-placeholder="<?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>" class="chosen-select" multiple="multiple" onchange="this.form.submit()">
										<option value=""><?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?></option>
										<?php if ($user->authorise('core.manageall', 'com_cluster'))
										{
											echo HTMLHelper::_('select.options', $dpeTag, 'value', 'text', $this->state->get('filter.tags'));
										}else{ 
											echo HTMLHelper::_('select.options', $trusteeTags, 'value', 'text', $this->state->get('filter.tags'));
										}?>
									</select>
								</div>
							</fieldset>
						</div>
						<?php
					}
					?>
				</div>
				<div class="today-date visible-print print-view-date">
					<?php echo Text::_("COM_DPE_PRINT_DATE"); ?>:
					<span class="font-bold">
						<?php echo HTMLHelper::date('now', Text::_('COM_DPE_DATE_ONLY_FORMAT'), false);?>
					</span>
				</div>
				<div class="print-hide text-end">
					<button type="button" class="btn btn-primary print-hide-btn" onclick="print_profile()"><i class="fa fa-print"></i> <?php echo Text::_("COM_DPE_PRINT"); ?></button>
				</div>
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
			<div class="row">

				<?php
				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_dpe/models');
				$dashboardModel = BaseDatabaseModel::getInstance('Dashboardchecklist', 'DpeModel', array('ignore_request' => true));
				$agencyTag = array_filter((array) $this->state->get('filter.tags'));  // DPE HACK



				if (is_array($agencyTag))
					{
						foreach($agencyTag as $key => $agencyTags)
						{

							if (!is_int($agencyTags))
							{ 
								$agencyTag[$key] = (int) $agencyTags;
							}
						}
					 }

				if (!empty($agencyTag))
				{	
					JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_dpe/models', 'DpeModel');
					$dashBoardModel = JModelLegacy::getInstance('Dashboard', 'DpeModel');
					$this->clusterId = $dashBoardModel->getClusterIdsByTags($agencyTag);
				}
				

				if (!$this->user->authorise('core.manageall', 'com_cluster'))
				{
					foreach ($this->clusterId as $key => $cluster)
					{
						// Remove cluster if user doesn't have permission for that cluster ( User role is staff)
						if (!RBACL::check($this->user->id, 'com_cluster', 'core.viewChecklist', 'com_multiagency', $cluster))
						{
							unset($this->clusterId[$key]);
						}
					}
				}
				
				if (!is_array($this->clusterId))
				{
					$this->clusterId = (array) $this->clusterId;
				}

				if($user->authorise('core.manageall', 'com_cluster') && empty($agencyTag) && empty($this->state->get('filter.cluster_id')))
				{
					$this->clusterId = 0;
				}

				foreach ($this->clusterId as $clusterId) { 

					$this->items = $dashboardModel->getlistData($clusterId);

					$clusterTabel = ClusterFactory::table('Clusters');
								$clusterTabel->load(array('id' =>$clusterId));

								if (property_exists($clusterTabel, 'name'))
								{
									$clusterTabel->name;
								}
					$link = Route::_('index.php?option=com_dpe&view=dashboard'. '&cluster_id='. $clusterId, true);
					?>


					<div class="table-responsive border-0 checklist-table col-md-4 col-sm-6 col-12">
						<div class="checklistorganisation"><a href="<?php echo $link;?>" style="color: white;"><?php echo $clusterTabel->name;?></a> </div>
						<table class="table" style="border: 1px solid #34b8f0f5;">
							<tbody>
								<?php
								$barShow = 0;
								$isFirstTime = $dashboardModel->checkAllowCount('',$clusterId);

								foreach ($this->items as $item)
								{

									$classTodo = '';
									$classInprogress = '';
									$classDone = '';

									if ($item->modified_date)
									{
										$dpeUtility      = DPE::utilities();

										$barShow = 1;

							//$link = Route::_('index.php?option=com_tjucm&view=itemform&client=' .$item->unique_identifier. '&id=' .$item->id. '&cluster_id='. $clusterId, true);

							//$status ='<a class="font-bold print-hide" href="'. $link .'">'. Text::_('COM_DPE_CHECKLIST_UPDATE') . '  &lrm;| &lrm; </a><span class="checklistDate print-view-fs-14">'. Text::_('COM_DPE_CHECKLIST_LAST_UPDATE') . $dpeUtility->toLapsed($item->modified_date) . '</span>';


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

							//$link = Route::_('index.php?option=com_tjucm&view=itemform&client=' . $item->unique_identifier . '&cluster_id='. $this->clusterId, true);

							//$status = '<a class="font-bold print-hide" href="'. $link .'">'. Text::_('COM_DPE_CHECKLIST_START') . '</a>';
									}
									echo '<tr><td class="col-sm-8 border-top-0 align-top"style="padding:0px;"><h4 class="" style="margin-top:-10px;padding: 11px 0px 5px 19px;font-size: 18px;">' . $item->title .'</hr></td>';
									if ($isFirstTime)
									{
										echo '<td class="col-sm-4 border-top-0 align-top white-space pb-20">
										<div class="progress progresschecklist">
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
							if ($this->user->authorise('core.manageall', 'com_cluster') || RBACL::check($this->user->id, 'com_cluster', 'core.createitem.' . $item->type_id, 'com_tjucm', $clusterId))
							{
								// echo '<td class="border-top-0 align-top pt-0 pb-20 col-sm-4 print-text-right">
								// '. $status .'</td></tr>';
							}
						}
					}
					?>

				</tbody>
			</table>
		</div>
	<?php } ?>
</div>

</form>
</div>
</div>
</div>

<script>

	function print_profile()
	{
		if(jQuery('#filter_tags').val() == '')
		{
			jQuery('.chosen-choices').css('display','none');
		}

		if(jQuery('#filtercluster_id').val() == '')
		{
			jQuery('#filtercluster_id_chosen').remove();
		}
 
		if(jQuery('#filtercluster_id').val() != '' || jQuery('#filter_tags').val() != '')
		{	jQuery('.chosen-single').css({'border':'0px solid white','float':'right'});
			jQuery('.chosen-choices').css('border','0px solid white');
			jQuery('.search-choice').css({'border' : '0px solid white', 'font-size':'25px'});
			jQuery('#tag_label').show();
			jQuery('.search-choice-close').css('display','none');
		}
		jQuery('.checklisttextarea').css('border','none');
		jQuery('#notificationlistwidget').css('display','none !important');
		jQuery('#notificationwidget').css('display','none !important');
		jQuery('.notificationwidget').css('display','none !impotant');
		jQuery('.notificationlistwidget').css('display','none !impotant');
		jQuery('.timelogwidget').css('display','none !impotant');
		jQuery('.fa-history').css('display','none !impotant');
		jQuery('.fa-edit').css('display','none !impotant');
		jQuery('.fa-list').css('display','none !impotant');

		window.print();
	}
jQuery('#filter_tags').change(function(e) {    
			var selectData = jQuery("#filter_tags").chosen().val(); 
    	// Check the tag filter is set or not  and set accordingly
    	if ((selectData == null )|| (selectData == ''))
    	{ 
    		jQuery("#filter_tags").val(jQuery("#filter_tags option:first").val());
    	}
    }); 

setTimeout(function(){

	jQuery('#filter_tags_chosen').css('width','250px');
},400)


	jQuery(document).ready(function() {
		jQuery('#filtercluster_id_chosen').css('width','300px');

			jQuery('#filter_tags').change(function(e) {

				// check the tag filter is empty or not and set the tag filter
		        var selectData = jQuery("#filter_tags").chosen().val();       	       
		        if ((selectData == null) || (selectData == ""))
		        {	
		        	jQuery("#filter_tags").val(jQuery("#filter_tags option:first").val());
		        }
	   		}); 
		// Check dpe admin
			var isDpeAdmin = "<?php echo $user->authorise('core.manageall', 'com_cluster'); ?>";
			var isorgAdmin = "<?php echo $orgAdminRoleId; ?>";


		if (!isDpeAdmin || !isorgAdmin)
			{
				// jQuery('#filter_tags_chosen').hide();
			}

		jQuery('#filter_tags').on('change', function() {
			jQuery("#filtercluster_id").val(jQuery("#filtercluster_id option:first").val());
	    });
	    jQuery('#filtercluster_id').on('change', function() {	
			jQuery("#filter_tags").val(jQuery("#filter_tags option:first").val());
	    });
})
</script>
