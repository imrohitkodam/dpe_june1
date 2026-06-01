<?php
/**
 * @version     1.0.0
 * @package     com_jlike
 * @copyright   Copyright (C) 2015. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */
// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('jquery.token');

$user       = Factory::getUser();
$userId     = $user->id;
$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
$canCreate  = $user->authorise('core.create', 'com_jlike');
$canEdit    = $user->authorise('core.edit', 'com_jlike');
$canCheckin = $user->authorise('core.manage', 'com_jlike');
$canChange  = $user->authorise('core.edit.state', 'com_jlike');
$canDelete  = $user->authorise('core.delete', 'com_jlike');

$app        = Factory::getApplication();
$tmpl       = $app->input->getString('tmpl', '');
$popupClass = '';
$tmplComponent = '';

// Check template component set or not.
if (!empty($tmpl))
{
	$doc = Factory::getDocument();
	$doc->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
	$doc->addStyleSheet('templates/shaper_helix3/css/font-awesome-v4-shims.min.css');
	$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
	$tmplComponent = 'tmpl=component&';
	$formClass = "p-20 recommendations-listview-popup";
}
$doc = Factory::getDocument();
$doc->getWebAssetManager()->useAsset('script', 'messages');
$doc->addScript(Uri::root() . 'media/com_jlike/js/jlike.js');

Text::script('COM_JLIKE_CHANGE_STATUS_OF_MULTIPLETODO');
Text::script('COM_JLIKE_DELETE_MULTPLE_TODO');
Text::script('COM_JLIKE_SELECT_ANY_MULTPLE_TODO');
Text::script('COM_JLIKE_SELECT_ANY_AUTOMULTPLE_TODO');
Text::script('COM_JLIKE_CHANGE_STATUS_OF_AUTOCOMPLETED_MULTIPLETODO');
Text::script('COM_JLIKE_CHANGE_STATUS_OF_AUTOINCOMPLETED_MULTIPLETODO');
Text::script('COM_JLIKE_CANT_COMPLETE_TODO');
Text::script('COM_JLIKE_CANT_INCOMPLETE_TODO');
Text::script('COM_JLIKE_RESEND_TODO_OF_MULTIPLETODO');



// DPE - Hack  - Start
JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
$params = DPE::config();
$dateTimeFormat = (String) $params->get('dateTimeFormat');

$menu       = $app->getMenu();
$detailMenu = $menu->getItems('link', 'index.php?option=com_jlike&view=recommendationform', true);

// DPE - Hack  - End

?>
<?php if (!empty($tmpl)) 
{ ?>
<div class="modal-header mb-20">
	<h3 class="fs-20">
		<?php
			echo Text::_('COM_JLIKE_MY_RECOMMENDATIONS');
		?>
	</h3>
	<button type="button" data-refresh="" class="close closepopup">&times;</button>
</div>
<?php 
}
?>

<div id="system-message-container"></div>
<form action="<?php echo Route::_('index.php?option=com_jlike&' . $tmplComponent . 'view=recommendations'); ?>" method="post" name="adminForm" id="adminForm" class="<?php echo $formClass;?>">
	<div class="mb-20 searchtool-calendar timelog-activities-search">
		<?php
		// Search tools bar
		echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this, 'options' => array('filtersHidden' => false)));
		?>
	</div>
								<a class=""
										href="javascript:void(0);"
										onclick="actionOnMultipleTodos(this);"
										id=""
										data-value="C"
										data-task='recommendation.updateTodoStatus'
										title="<?php echo TEXT::_('COM_JLIKE_COMPLETE_TODO');?>" >
										<i class="fa fa-check-circle  fa-lg"></i><?php echo ' ' . TEXT::_('COM_JLIKE_COMPLETE_TODO')?>
									</a> &nbsp
									<a class=""
										href="javascript:void(0);"
										onclick="actionOnMultipleTodos(this);"
										id=""
										data-value="I"
										data-task='recommendation.updateTodoStatus'
										title="<?php echo TEXT::_('COM_JLIKE_INCOMPLETE_TODO');?>" >
										<i class="fa fa-circle fa-lg"></i><?php echo ' ' . TEXT::_('COM_JLIKE_INCOMPLETE_TODO')?>
									</a> &nbsp
								<?php 

								JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
  								$dpeAdmin = RBACL::getRoleByUser($user->id, 'com_multiagency', 0);
  								$params     			   = ComponentHelper::getParams('com_multiagency');
							   $orgAdminRoleId           = (int) $params->get('multiagency_school_admin_group', '0', 'INT');
							   $orgAdminRoleId 		   = in_array($orgAdminRoleId, $user->groups);

  								if ($dpeAdmin || $orgAdminRoleId){?>
								<a class=""
										href="javascript:void(0);"
										onclick="actionOnMultipleTodos(this);"
										id=""
										data-task='recommendation.delete'
										title="<?php echo TEXT::_('COM_JLIKE_DELETE');?>" >
										<i class="fa fa-trash fa-lg"></i><?php echo ' ' . TEXT::_('COM_JLIKE_DELETE')?>
									</a>
									&nbsp
									<a class=""
										href="javascript:void(0);"
										onclick="actionOnMultipleTodos(this);"
										id=""
										data-value="resend"
										data-task='recommendation.addToQueue'
										title="<?php echo TEXT::_('COM_JLIKE_TODO_RESEND');?>" >
										<i class="fa fa-repeat  fa-lg"></i><?php echo ' ' . TEXT::_('COM_JLIKE_TODO_RESEND')?>
									</a> &nbsp
								<?php }?>
<?php
	if (empty($this->items))
	{
	?>
		<div class="alert alert-info alert-no-items ">
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php
	}
	else 
	{ ?>
		<div class="table-responsive">
			<table class="table table-striped" id = "todosList" >
				<thead >
					<tr>
						<?php if (isset($this->items[0]->id)): ?>
							<th width="1%" class="hidden-phone">
                                    <input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this, 'wcb')" />
                                </th> 
                                <th width="5%" class="nowrap center hidden-phone">
								<?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
							</th>
						<?php endif; ?>

						<th class='left'>
						<?php echo HTMLHelper::_('searchtools.sort',  'COM_JLIKE_RECOMMENDATIONS_TITLE', 'a.title', $listDirn, $listOrder); ?>
						</th>

						<th class='left'>
						<?php echo Text::_('COM_JLIKE_RECOMMENDATIONS_AGENCY_NAME'); ?>
					</th>

						<th class='left'>
						<?php echo HTMLHelper::_('searchtools.sort',  'COM_JLIKE_RECOMMENDATIONS_DUE_DATE', 'a.due_date', $listDirn, $listOrder); ?>
						</th>
						<th class='left'>
						<?php echo HTMLHelper::_('searchtools.sort',  'COM_JLIKE_RECOMMENDATIONS_DONE_DATE', 'a.done_date', $listDirn, $listOrder); ?>
						</th>
						<?php if (!$tmpl) { ?>
							<th class='left'>
							<?php echo HTMLHelper::_('searchtools.sort',  'COM_JLIKE_RECOMMENDATIONS_ASSIGNED_TO', 'a.assigned_to', $listDirn, $listOrder); ?>
							</th>
						<?php } ?>

						<th class='left'>
						<?php echo HTMLHelper::_('searchtools.sort',  'COM_JLIKE_RECOMMENDATIONS_ASSIGNED_BY', 'a.assigned_by', $listDirn, $listOrder); ?>
						</th>

						<th class='left'>
						<?php echo Text::_('COM_JLIKE_RECOMMENDATIONS_STATUS'); ?>
						</th>

						<th>
						<?php echo Text::_('COM_JLIKE_RECOMMENDATIONS_ACTION'); ?>
						</th>
					</tr>
				</thead>
				<!--
					<tfoot>
					<tr>
						<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
					</tfoot>
				-->
				<tbody>
					<?php 
					
						foreach ($this->items as $i => $item)
						{
							$deleteAllowed = true;
							$editAllowed   = true;

							if (!$this->user->authorise('core.manageall', 'com_cluster'))
							{
								if (!in_array($item->cluster_id, (array) $this->deleteAllowedClusters))
								{
									$deleteAllowed = false;
								}

								if (!in_array($item->cluster_id, $this->allowedClusters))
								{
									$editAllowed = false;
								}
							}

							$detailViewLink = $detailMenu->link . '&id=' . $item->id . '&Itemid=' . $detailMenu->id;
							?>

							<tr class="row<?php echo $i % 2; ?>">
								<td class="hide">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id, false); ?>
								</td>
								<?php if (isset($this->items[0]->id)): ?>
									<td class="center hidden-phone">
									<input type="checkbox" id="wcb<?php echo $i; ?>" name="wcid[]" value="<?php echo (int)$item->id; ?>" onclick="Joomla.isChecked(this.checked);" />                                    
									</td>
									<td class="center hidden-phone">
										<?php echo (int)$item->id; ?>
									</td>
								<?php endif; ?>

								<td>
									<a href="<?php echo Route::_($detailViewLink); ?>" target="_blank"><?php echo $this->escape($item->title); ?></a>
								</td>

								<td>
									<?php 
										if ($item->agency_title)
										{
											echo ucwords($this->escape($item->agency_title));
										}
										else
										{
											echo "-";
										}
									?>
								</td>

								<td>
									<?php 
										echo HTMLHelper::_('date', $this->escape($item->due_date), $dateTimeFormat, false);
									?>
								</td>
								<td>
									<?php
									if ($item->done_date != "0000-00-00 00:00:00" && $item->status === 'C')
									{
										echo HTMLHelper::_('date', $this->escape($item->done_date), $dateTimeFormat, false);
									}
									else
									{
										echo "-";
									}
									?>
								</td>
								<?php if (!$tmpl) { ?>
									<td>
										<?php echo $this->escape(Factory::getUser($item->assigned_to)->name).' ('.$this->escape(Factory::getUser($item->assigned_to)->email).')'; ?>
									</td>
								<?php } ?>
								<td>
									<?php echo $this->escape(Factory::getUser($item->assigned_by)->name); ?>
								</td>
								<td>
									<?php 
									if ($item->status === 'S')
									{
										echo Text::_('COM_JLIKE_FORM_TODO_STATUS_STARTED');
									}
									elseif ($item->status === 'I')
									{
										echo Text::_('COM_JLIKE_FORM_TODO_STATUS_INCOMPLETED');
									}
									elseif ($item->status === 'C')
									{
										echo Text::_('COM_JLIKE_FORM_TODO_STATUS_COMPLETED');
									}
									?>
								</td>
								<td>
									<?php 
									if (($item->status != 'C' ) && (($item->client != 'com_tjlms.lesson') && ($item->client != 'com_tjlms.course'))) 
									{ ?>
										<span>
											<a class="complete-mark" href="<?php echo Route::_('index.php?option=com_jlike&' . $tmplComponent . 'task=recommendation.updateTodoStatus&status=C&content_id=' . $this->contentId . '&id=' . $item->id); ?>" title="<?php echo Text::_('COM_JLIKE_COMPLETE_TODO');?>" class="btn btn-mini" type="button">
											<i class="fa fa-circle-o fa-lg"></i>
											<i class="fa fa-check-circle fa-lg"></i>
											</a>
										</span>
									<?php 
									}
									elseif (($item->status === 'C') && (($item->client != 'com_tjlms.lesson') && ($item->client != 'com_tjlms.course' )))
									{?>
										<!--Hide actions for todo complete in dpe and org admin-->
										<span>
											<a class="incomplete-mark" href="<?php echo Route::_('index.php?option=com_jlike&' . $tmplComponent . 'task=recommendation.updateTodoStatus&status=I&content_id=' . $this->contentId . '&id=' . $item->id); ?>" title="<?php echo Text::_('COM_JLIKE_INCOMPLETE_TODO');?>" class="btn btn-mini" type="button">
												<i class="fa fa-check-circle fa-lg"></i>
												<i class="fa fa-times-circle fa-lg"></i>
											</a>
										</span> 
									<?php
									}
									?>
									<?php
									// DPE Hack added to allow item action who have RBACL
									if ($editAllowed || ($item->assigned_by == $this->user->id && $item->assigned_to == $this->user->id))
									{
									?>
										<?php
											$editLink = Route::_('index.php?option=com_jlike&' . $tmplComponent . 'view=recommendationform&layout=edit&id=' . $item->id);
										?>
										<span>
											<a class="btn btn-mini d-inline-block" href="<?php echo $editLink; ?>" title="<?php echo Text::_('JACTION_EDIT'); ?>">
												<i class="fa fa-pencil-square-o fa-lg" aria-hidden="true"></i>
											</a>
										</span>
									<?php
									}
									?>
									<!-- Check delete action and allowed cluster to delete option -->
									<?php if ($deleteAllowed): ?>
									<span>
										<a onclick="if(confirm('<?php echo Text::_('COM_JLIKE_DELETE_MESSAGE')?>')) { Joomla.listItemTask('cb<?php echo $i; ?>','recommendation.delete'); }" class="btn btn-mini delete-button" type="button"><i class="fa fa-trash-o fa-lg" ></i></a>
									</span>
									<?php endif; ?>
								</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	<?php 
	} 
			$multiagencyParams = ComponentHelper::getParams('com_multiagency');
			$orgAdminRoleId    = (int) $multiagencyParams->get('multiagency_school_admin_group', '0', 'INT');
		   $orgAdminRoleId 	 = in_array($orgAdminRoleId, $user->groups);
	?>

	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="boxchecked" value="0"/>

    <?php echo HTMLHelper::_('form.token'); ?>
	<div class="col-xs-12">
		<div class="pager" id="pagination">
			<?php echo $this->pagination->getPagesLinks(); ?>
			<!-- <hr class="hr hr-condensed"/> -->
		</div>
	</div>
</form>

<script type="text/javascript">
	jQuery(document).on('click', '.closepopup', function() {

		if (jQuery(this).data('refresh') == 1) {
			window.parent.document.location.reload(true);
		}

		window.parent.SqueezeBox.close();
	});

    /* It restrict the user for manual input in datepicker field */
    jQuery(document).delegate('.calendar-textfield-class', 'focusin', function(event) {
       event.preventDefault();
       jQuery(this).parent().siblings(':eq(0)').show();
    });

    jQuery(document).delegate('.calendar-textfield-class', 'keydown contextmenu', function() {
			return false;
    });
</script>
<!--js added for tags filter -->
<script>

// Get current user ID from PHP and trigger the AJAX call
var userID = <?php echo (int) $userId; ?>;
fetchTodosAssignedByUser(userID);

	jQuery(document).ready(function(){

		jQuery("#filter_tags").attr("data-placeholder", "<?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>");
		jQuery("#filter_tags").trigger("liszt:updated");
		
		// checked dpe admin
		var isDpeAdmin = "<?php echo $user->authorise('core.manageall', 'com_cluster'); ?>";
		var isorgAdmin = "<?php echo $orgAdminRoleId; ?>";


		if (!isDpeAdmin || !isorgAdmin)
		{
			jQuery('#filter_tags_chosen').hide();
		}

	jQuery('#filter_tags').on('change', function() {
		jQuery("#filter_agency_id").val(jQuery("#filter_agency_id option:first").val());
    });
    jQuery('#filter_agency_id').on('change', function() {	
		jQuery("#filter_tags").val('');
    });

    jQuery('#filter_search').attr('placeholder', '<?php echo Text::_('COM_JLIKE_SEARCH_BY_ASSIGNEDTO')?>');

})
	
</script>
