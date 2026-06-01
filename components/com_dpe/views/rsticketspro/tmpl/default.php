<?php
/**
 * @package     DPE
 * @subpackage  com_dpe
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\OutputFilter;

use Joomla\CMS\User\User;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', '.multiplestatus', null, array('placeholder_text_multiple' => Text::_('COM_DPE_RSTICKET_CHOOSE_STATUS')));
HTMLHelper::_('formbehavior.chosen', '.multipletags', null, array('placeholder_text_multiple' => Text::_('COM_DPE_RSTICKET_CHOOSE_TAGS')));

HTMLHelper::_('formbehavior.chosen', 'select');


$document	= Factory::getDocument();
$document->addScript(Uri::root() . '/media/jui/js/chosen.jquery.min.js');
$document->addStyleSheet(Uri::root() . '/media/jui/css/jquery.searchtools.css');
$document->addStyleSheet(Uri::root() . '/media/jui/css/chosen.css');

$listOrder = $this->escape($this->state->get('list.ordering', 'a.id'));
$listDirn  = $this->escape($this->state->get('list.direction', 'DESC'));
$rsTicketShowUserInfo = RSTicketsProHelper::getConfig('show_user_info');

$lang = Factory::getLanguage();
$lang->load('com_rsticketspro', JPATH_SITE);

// Get create ticket Edit MenuItem
$mainframe          = Factory::getApplication();
$input = $mainframe->input;

$ticketStatuses = $input->get('filter')['ticketStatus'];
$rsTicketStatus = RSTicketsProHelper::getStatuses();


$check = (($this->state->get('filter.myTickets') === "on") || $input->get('filter')['myticket'] ) ? 'checked': '';

$menu               = $mainframe->getMenu();
$ticketEditMenuItem = $menu->getItems('link', 'index.php?option=com_rsticketspro&view=submit', true );
$user               = Factory::getUser();

// Get Current url for notification manager widget
$extraParams = Uri::getInstance()->toString(array('query'));
$extraParams = str_replace('?', '&', $extraParams);

$currentUrl =  'index.php?option=' . $mainframe->input->get('option') . '&view=' . $mainframe->input->get('view') . $extraParams .'&Itemid=' . $mainframe->input->get('Itemid');
$ticketListId = ComponentHelper::getParams('com_dpe')->get('ticket_list');

// Hide the Tags filter for non-Super Admin users
if (!$this->user->authorise('core.admin'))
{
	if ($this->filterForm->getField('tags', 'filter')) {
		$this->filterForm->removeField('tags', 'filter');
	}
}
?> 
<div class="my-tickets">
	<!-- Header-->
	<div class ="row">
<!--
		<div class="col-xs-8">
			<legend><?php echo Text::_('COM_DPE_TITLE_TICKETS');?></legend>
		</div>
	-->
</div>

<div class ="row">
	<form action="<?php echo Route::_('index.php?option=com_dpe&view=rsticketspro');?>" method="post" name="adminForm" id="adminForm" class="">
		<!--Filter-->
		<div class="col-xs-12 dp-pagination-dropdown ticket-searchtool">
			<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this, 'options' => array('filterButton' => true, 'filtersHidden' => false)));?>
		</div>
		<div class="col-sm-12 py-20">
			<input type="checkbox" name="filter[myTickets]" id="filter_myTickets" onchange="this.form.submit();" <?php echo $check;?> class="font-600">
			<label for="filter_myTickets" class="font-600"><?php echo Text::_('COM_DPE_MY_TICKETS');?></label>
			<div class="pull-right btn-add">
				<?php echo $this->getToolbar();?>
			</div>
		</div>
		<!-- <hr class="hr-condensed"/> -->

		<!--Table Records-->
		<?php
		if (empty($this->items))
		{
			$statusTexts = [];

				// Loop through selected statuses and map to their text
				foreach ($rsTicketStatus as $status) {
				    if (in_array($status->value, (array)$ticketStatuses)) {
				        $statusTexts[] = $status->text;
				    }
				}

				// Join multiple statuses with comma
				$statusText = implode(', ', $statusTexts);

			?>
			<div class="clearfix"></div>
			<div class="alert alert-info" role="alert"><?php echo Text::sprintf('COM_DPE_MY_TICKETS_NO_DATA', $statusText);?></div>
			<?php
		}
		else
		{
			?>
			<div class="col-xs-12 table-responsive" id="no-more-tables">
				<table class="table table-striped table-bordered1 table-hover" id="rsticketsproList">
					<thead>
						<tr>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_MY_TICKETS_ID', 'a.id', $listDirn, $listOrder);?>
							</th>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', Text::sprintf('COM_DPE_MY_TICKETS_AGEANCY_NAME', Text::_('COM_MULTIAGENCY_ORGANISATION')), 'cluster.name', $listDirn, $listOrder);?>
							</th>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_MY_TICKETS_SUBJECT', 'a.subject', $listDirn, $listOrder);?>
							</th>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_MY_TICKETS_CUSTOMER_NAME', 'c.name', $listDirn, $listOrder);?>
							</th>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_MY_TICKETS_ASSIGNED_TO', 's.name', $listDirn, $listOrder);?>
							</th>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_MY_TICKETS_PRIORITY', 'pr.name', $listDirn, $listOrder);?>
							</th>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_MY_TICKETS_STATUS', 'st.name', $listDirn, $listOrder);?>
							</th>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_MY_TICKETS_CREATED_DATE', 'a.date', $listDirn, $listOrder);?>
							</th>
							<th class="center">
								<?php echo HTMLHelper::_('searchtools.sort', 'COM_DPE_MY_TICKETS_LAST_REPLY', 'a.last_reply', $listDirn, $listOrder);?>
							</th>
<!--
								<th class="center">
									<?php //echo HTMLHelper::_('searchtools.sort', 'COM_DPE_MY_TICKETS_TIME_SPENT', 'a.time_spent', $listDirn, $listOrder);?>
								</th>
								<th class="center">
									<?php //echo HTMLHelper::_('searchtools.sort', 'COM_DPE_MY_TICKETS_ID', 'a.id', $listDirn, $listOrder);?>
								</th>
							-->
							<?php
							if (!empty($this->permissions) && $this->permissions->delete_ticket)
							{
								?>
								<th id="rst_head_delete" class="center" width="5%">
									<?php echo HTMLHelper::image('com_rsticketspro/icon8.png', '', null, true); ?>
								</th>
								<?php
							}
							?>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($this->items as $i => $item)
						{
							?>
							<tr class="row<?php echo $i % 2; ?>">
								<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_ID');?>">
									<?php echo (int) $item->id;?>
								</td>
								<td data-title="<?php echo Text::sprintf('COM_DPE_MY_TICKETS_AGEANCY_NAME', Text::_('COM_MULTIAGENCY_ORGANISATION'));?>">
									<?php

									if ($item->agencyTitle)
									{
										echo $this->escape($item->agencyTitle);
									}
									else
									{
										echo Text::_('COM_DPE_BLANK_VALUE');
									}
									?>
									<!-- Show Kb, g platform icon to dpe admin only -->
									<?php if ($user->authorise('core.manageall', 'com_cluster')) { ?>
										<?php if ($item->platform) { ?>
											<span class="badge btn-primary pull-right"><?php echo $item->platform;?></span>
										<?php } ?>
									<?php } ?>
								</td>
								<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_SUBJECT');?>">
									<?php
											// File Attachment Section
									if ($item->has_files)
									{
										?>
										<i class="fa fa-paperclip" aria-hidden="true" title="<?php echo Text::_('COM_DPE_MY_TICKETS_ATTACHMENT');?>"></i>
										<?php
									}?>
									<a href="<?php echo RSTicketsProHelper::route('index.php?option=com_rsticketspro&view=ticket&id=' . $item->id . ':' .
									OutputFilter::stringURLSafe($this->escape($item->subject)) .'&Itemid=' . $ticketEditMenuItem->id
									); ?>">
									<?php
												// Code and Replies section
												// Subject and Replies Section
									echo $this->escape($item->subject) . '(' . $item->replies . ')';?>
								</a>
								<?php 
								if ($item->note != null)
									{?>
										<i class="icon-file"></i>
									<?php }
									?>
								</td>
								<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_CUSTOMER_NAME');?>">
									<?php echo $this->escape($item->customer); ?>
								</td>
								<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_ASSIGNED_TO');?>">
									<?php echo $item->staff_id ? $this->escape($item->staff) : Text::_('COM_DPE_MY_TICKETS_UNASSIGNED'); ?>
								</td>
								<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_PRIORITY');?>">
									<?php echo $this->escape($item->priority); ?>
								</td>
								<td data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_STATUS');?>">
									<?php echo $this->escape($item->status); ?>
								</td>
								<td class="text-nowrap" data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_CREATED_DATE');?>">
									<?php echo $this->escape(HTMLHelper::_('date', $item->date, $this->rsTicketdateFormat));?>
								</td>
								<td class="text-nowrap" data-title="<?php echo Text::_('COM_DPE_MY_TICKETS_LAST_REPLY');?>">
									<?php echo $this->escape(HTMLHelper::_('date', $item->last_reply, $this->rsTicketdateFormat)); ?>
								</td>
	<!--
										<td data-title="<?php //echo Text::_('COM_DPE_MY_TICKETS_TIME_SPENT');?>">
											<?php //echo $this->escape($item->time_spent); ?>
										</td>
										<td data-title="<?php //echo Text::_('COM_DPE_MY_TICKETS_ID');?>">
											<?php //echo $this->escape($item->id); ?>
										</td>
									-->
									<?php
									if (!empty($this->permissions) && $this->permissions->delete_ticket)
									{
										?>
										<td align="center" class="rst_cell_delete_ticket center">
											<?php
											$url = Route::_('index.php?option=com_rsticketspro&task=ticket.delete&cid=' . $item->id);
											$img = HTMLHelper::image('com_rsticketspro/delete.png', Text::_('RST_TICKET_DELETE'), null, true);
											?>
											<a
											class="rst_delete_ticket <?php echo RSTicketsProHelper::tooltipClass();?>"
											title="<?php echo RSTicketsProHelper::tooltipText(Text::_('RST_TICKET_DELETE_DESC')); ?>"
											href="<?php echo $url; ?>"
											onclick="return confirm('<?php echo Text::_('RST_DELETE_TICKET_CONFIRM', true); ?>')">
											<?php echo $img; ?>
										</a>
									</td>
									<?php
								} ?>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>

			<div class="col-xs-12">
				<div class="pager">
					<?php echo $this->pagination->getPagesLinks(); ?>
				</div>
			</div>

			<?php
		}
		$params     			  = ComponentHelper::getParams('com_multiagency');
		$multiagencyTrusteeRoleId = (int) $params->get('multiagency_trustee_group');
		$isTrustee = in_array($multiagencyTrusteeRoleId, $user->groups);
		$trusteeTags = '';
		$trusteeTags = json_encode($this->trusteeTags);
		$orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
		$orgAdminRoleId = in_array($orgAdminRoleId, $user->groups);

		JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
		$dpeModel = DPE::model('school', array('ignore_request' => true));
		if ($orgAdminRoleId)
		{
			$trusteeTags = json_encode($dpeModel->getAgencyTags($orgAdminRoleId)); 
		}
		

		?>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<input type="hidden" name="url" id="url" value="<?php echo $currentUrl;?>"/>

		<!-- DPE Hack start to add hidden fields to create content for notification manager -->
		<input type="hidden" name="element" id="element" value="com_rsticket.ticket"/>
		<input type="hidden" name="element_id" id="element_id" value="<?php echo $ticketListId;?>"/>
		<input type="hidden" name="cluster_id_rsticket" id="cluster_id_rsticket" value=""/>
		<!-- DPE Hack end-->
<!--
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		-->
		<?php echo HTMLHelper::_('form.token');?>
	</form>
</div>
</div>
<script type="text/javascript">
	<!-- Following js added to set cluster dropdown value to hidden 'cluster_id' field -->

	jQuery( document ).ready(function()
	{
		var clusterID = jQuery('#filter_agencies').val();
		jQuery('#cluster_id_rsticket').val(clusterID);
	});
</script>

<!-- js for tags filter -->
<script>
	jQuery(document).ready(function(){

		var isDpeAdminOrTrustee = "<?php  echo ($user->authorise('core.manageall', 'com_cluster') || $isTrustee || $orgAdminRoleId)? 1 :'' ?>";
		var selectData = jQuery("#filter_tags").chosen().val();   
		jQuery("#filter_tags").attr("data-placeholder", "<?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>");
		jQuery("#filter_tags").trigger("liszt:updated"); 

		if (isDpeAdminOrTrustee )
		{	
			var tags = jQuery("#filter_tags").chosen().val();
			var trusteeTags = <?php echo $trusteeTags; ?>;

			jQuery("#filter_tags").empty();

			trusteeTags.forEach(function(key)
			{
				jQuery("#filter_tags").append('<option value="'+key.value+'">'+key.text+'</option>');  
				
			});
			jQuery("#filter_tags").trigger("liszt:updated"); 
			jQuery("#filter_tags").val(tags);
			jQuery('#filter_tags').trigger("liszt:updated");
		}		

		// check dpe admin
		if (!isDpeAdminOrTrustee)
		{
			jQuery('#filter_tags_chosen').hide();
		}

		jQuery('#filter_tags').on('change', function() {
			jQuery("#filter_agencies").val(jQuery("#filter_agencies option:first").val());
		});
		jQuery('#filter_agencies').on('change', function() {	
			jQuery("#filter_tags").val('');
		});

	})



</script>

<!-- //DPE hack -->
	<script>
			$(document).ready(function() {
    function adjustOffCanvasMenu() {
        var screenWidth = $(window).width();

        if(jQuery('#rsticketsproList').length > 0 && ((screenWidth < 414 || screenWidth > 430) && (screenWidth != 375)))
        {
        	$('.offcanvas-menu').css('right', '47%');
        }
        if( screenWidth == 430 || screenWidth == 414 || screenWidth == 390)
        {
        	$('.offcanvas-menu').css('right', '37%');
        }
        else if( screenWidth == 375)
        {
        	
        	$('.offcanvas-menu').css('right', '42%');
        }
        
    }

    // Call the function initially when the page loads
    adjustOffCanvasMenu();

    // Also call it on window resize to adjust as per the new screen size
    $(window).resize(function() {
        adjustOffCanvasMenu();
    });
});


		setTimeout(function(){

			jQuery('.close-icon').click(function() { 
				var target = jQuery(this).closest('span').data('bs-target');
				jQuery(this).closest('span').addClass('collapsed');
				var ulElementId = target.replace('#', '');
             jQuery('#'+ulElementId).removeAttr('style');

             

setTimeout(function(){
	jQuery('#'+ulElementId).removeClass('show');
}, 1000);

});
		},3000);
		




	</script>