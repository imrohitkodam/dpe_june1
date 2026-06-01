<?php
/**
 * @package     Multiagency
 * @subpackage  com_multiagency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('jquery.framework');

$user = Factory::getUser();
$doc = Factory::getDocument();
$doc->addScript(Uri::root(true) . '/media/com_dpe/js/dpe_ucm_tab.js');



// User srach by organisation
$mainframe                = Factory::getApplication();
$search_organisationuser = $mainframe->getUserStateFromRequest('.filter_user_search', 'filter_user_search');

$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');

$allowBlock = (($this->removeOwnUser || $this->removeUser) ? true : false);

$allowRemoveUser = false;

if (!$this->user->authorise('core.manageall', 'com_cluster'))
{
	if (in_array($this->agenciesId, $this->agencyListArray))
	{
		$allowRemoveUser = RBACL::check($this->user->id, 'com_multiagency', 'core.own.removeuser', 'com_multiagency', $this->agenciesId);
	}
}
else
{
	$allowRemoveUser = true;
}

// Hide the Tags filter for non-Super Admin users
if (!$this->user->authorise('core.admin'))
{
	if ($this->filterForm->getField('tags', 'filter')) {
		$this->filterForm->removeField('tags', 'filter');
	}
}

// Get user groups as per name
$leadConsultantGroup = Table::getInstance('Usergroup', 'JTable');
$leadConsultantGroup->load(array('title' => 'External Lead Consultant'));

if (property_exists($leadConsultantGroup, 'id'))
{
	$leadConsultantGroupId = $leadConsultantGroup->id;
}

// Get create staff Item ID
$menu = $mainframe->getMenu();
$createStaffMenuItem = $menu->getItems('link', 'index.php?option=com_multiagency&view=userform&layout=edit', true );
$inputs         = Factory::getApplication()->input;

$filters = $inputs->get('filter', [], 'ARRAY');
$filterAgencies = isset($filters['agencies']) ? $filters['agencies'] : '';

// Tags filter fetch from URL
$filterTags = isset($filters['tags']) ? $filters['tags'] : [];
$url = 'index.php?option=com_tjucm&view=items&filter[agencies]=' . urlencode($filterAgencies);


if (!empty($filterTags) && is_array($filterTags)) {
    foreach ($filterTags as $index => $tag) {
        $url .= '&filter[tags][' . $index . ']=' . urlencode($tag);
    }
}
else{
	$url .= '&filter[tags][]=';
}

// Final Route URL
$finalUrl = Route::_($url . '&Itemid=1262');
$selected      = $this->getState('filter.dpelead');
$check         = ($selected == 'dpelead') ? 'checked': '';

// Get Joomla session and input
$session = Factory::getSession();
if($filterAgencies !=''){
	$session->set('selectedCluster', $filterAgencies);
}

?>
<script type="text/javascript">


var nonvalid_extension = "<?php echo Text::_('COM_USER_UPLOAD_EXTENSION_ERROR');?>"

function updateFilterUrl() {
	// Base URL
	let baseUrl = "<?php echo Uri::base(); ?>index.php?option=com_tjucm&view=items";

    // Get selected agencies filter
    let agenciesFilter = document.querySelector('[name="filter[agencies]"]');
    let agenciesValue = agenciesFilter ? agenciesFilter.value : "all";

    // Get selected tags filter (for checkboxes or multi-select dropdown)
    let selectedTags = document.querySelectorAll('[name="filter[tags][]"] option:checked');
    let tagsParams = "";
    
    selectedTags.forEach((tag, index) => {
        if (tag.value) {
            tagsParams += `&filter[tags][${index}]=${encodeURIComponent(tag.value)}`;
        }
    });

    // Final updated URL
    let updatedUrl = `${baseUrl}&filter[agencies]=${encodeURIComponent(agenciesValue)}${tagsParams}&Itemid=1262`;

    // Update the button link
    document.getElementById("addStaffButton").href = updatedUrl;
}

// Event Listeners for Filters
document.addEventListener("DOMContentLoaded", function () {
    let agenciesFilter = document.querySelector('[name="filter[agencies]"]');
    if (agenciesFilter) {
        agenciesFilter.addEventListener("change", updateFilterUrl);
    }
    
    let tagsCheckboxes = document.querySelectorAll('[name="filter[tags][]"] option');
    tagsCheckboxes.forEach((tag) => {
        tag.addEventListener("change", updateFilterUrl);
    });

    // Initial Call to Set URL on Page Load
    updateFilterUrl();
});


jQuery(document).ready(function ($) {
    var $limit   = $('#limit');
    var $tags    = $('#filter_tags');
    var $cluster = $('#filter_agencies');
    var $serach = $('#filter_search');

    // Handle tags, cluster, and limit change events
   $limit.on('change', function (e) {
        e.preventDefault(); // prevent default immediate submit
        var limitVal = $limit.val();

        // Special check if "All" is selected
        if (limitVal === "0") {
            var selectedTags = $tags.val() || [];
            var selectedCluster = $cluster.val() || '';

            if (selectedTags.length === 0 && (!selectedCluster || selectedCluster === 'all') && !$serach.val()) {
                alert("<?php echo Text::_('COM_DPE_SELECT_FILTER_ALERT'); ?>");
                return false;
            }
        }
    });
});
</script>

<div class="tj-page">
<div class ="row">
<!--
	<div class="col-xs-12">
		<legend><?php echo Text::_('COM_MULTIAGENCY_USERS'); ?></legend>
	</div>
-->
	<div class="col-xs-12 pt-10 pb-30">
		<form action="<?php echo Route::_('index.php?option=com_multiagency&view=users'); ?>" method="post" name="adminForm" id="adminForm" class="manage-staff-view">

		<div id="filter-bar1" class="row">
			<div class="col-sm-12 col-md-6">
				<div class="filter-search input-group manage-staff dp-search-filter">
					<?php
						echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this, 'options' => array('filterButton' => true, 'filtersHidden' => false)));
					?>
				</div>
			</div>
			<div class="col-sm-12 col-md-6">
			<div class="d-inline-block pull-right">
				<div class="btn-group hidden-xs ml-10">
					<?php echo $this->pagination->getLimitBox(); ?>
				</div>
			</div>

			
			<?php if ($allowRemoveUser):?>
			<div class="delete-staff pull-right">
					<div class="ml-5">
						<button onclick="if (document.adminForm.boxchecked.value == 0) { alert('<?php echo Text::_('COM_MULTIAGENCY_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');?>'); } else { if (confirm('<?php echo Text::_('COM_MULTIAGENCY_DELETE_USERS_MESSAGE');?>')) { Joomla.submitbutton('users.blockUser'); } }" class="btn btn-danger">
						<!-- <span class="icon-delete" aria-hidden="true"></span> -->
						<span class="fa fa-close"></span> &nbsp;
						<?php echo Text::_('COM_MULTIAGENCY_DELETE_BUTTON')?></button>
					</div>
			</div>
			<?php endif; ?>

			<div class="add-staff pull-right">
				<?php if ($this->isSuperAdmin || $this->canCreate || $this->canEditUser || $this->addUser) : ?>
					<?php if ($this->user->authorise('core.manageall', 'com_cluster')) { ?>
						<a href="<?php echo Route::_('index.php?option=com_multiagency&task=userform.edit&id=0&Itemid='.$createStaffMenuItem->id, false, 0); ?>" class="btn btn-primary">
							<?php echo Text::_('COM_MULTIAGENCY_ADD_USER'); ?>
						</a>
					<?php } else { ?>
						<a href="<?php echo Route::_('index.php?option=com_multiagency&view=userform&layout=default_userform&id=0&Itemid='.$createStaffMenuItem->id, false, 0); ?>" class="btn btn-primary">
						<?php echo Text::_('COM_MULTIAGENCY_ADD_USER'); ?>
						</a>
					<?php } ?>
				<?php endif; ?>
			</div>

			<div class="staff-import pull-right me-2">
				<?php if ($this->isSuperAdmin || $this->canCreate) : ?>
					<?php $srclink = Route::_('index.php?option=com_multiagency&view=users&tmpl=component&layout=import', false); ?>
				<a onclick="openspotlight('<?php echo $srclink; ?>')">
					<button type="button" class="btn btn-primary size-bt">
						<span class="fa fa-upload"></span> &nbsp;
						<?php echo Text::_('COM_USER_ENROL_IMPORT_CSV'); ?>
					</button>
				<?php endif; ?>
			</div>

			<div class="staff-export pull-right me-2">
				<?php if ($this->isSuperAdmin || $this->canCreate) : ?>

				<?php $srcExportlink = Route::_('index.php?option=com_multiagency&view=users&tmpl=component&layout=export', false); ?>

                 <a href="javascript:void(0);" onclick="validateAndExport('<?php echo $srcExportlink; ?>','<?php echo Text::_('COM_MULTIAGENCY_PLEASE_SET_FILTERS_BEFORE_EXPORTING'); ?>');">
                 <button type="button" class="btn btn-primary size-bt ml-5" id="btnExportUsers">
                    <span class="fa fa-download"></span> &nbsp;
                    <?php echo Text::_('COM_MULTIAGENCY_EXPORT_USERS'); ?>
                </button>
                </a>
				<?php endif; ?>
			</div>
			<div class="job-tittle" style="margin-left: 12px;">
				<a class="btn btn-primary" id="addStaffButton" href="<?php echo $finalUrl;?>"><?php echo Text::_('COM_USERS_SCHOOL_JOBTITLE_LABEL');?></a>
			</div>
		</div>
		<div class="d-inline-block mr-20">
							<input class="" type="checkbox"style='margin-left: 5px;' name="filter[dpelead]" id="filter_dpelead" <?php echo $check;?> onchange="this.form.submit();">
							<label for="dpelead" class="fw-bold"><?php echo Text::_('COM_MULTIAGENCY_DPE_LEAD_FIELD_CHECKBOX');?></label>
						</div>
	</div>
	<div class="col-xs-12">
		<?php // Check if record is present
		if ($this->items)
		{ ?>
		<div class="table-responsive">
			<table class="table table-striped" id="userList">
				<thead>
					<tr>
						<?php if ($allowRemoveUser):?>
							<th width="1%" class="nowrap center">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</th>
						<?php endif; ?>
						<th class=''>
							<?php echo HTMLHelper::_('grid.sort',  'COM_MULTIAGENCY_USERS_NAME', 'a.name', $listDirn, $listOrder); ?>
						</th>
						<th class=''>
							<?php echo HTMLHelper::_('grid.sort',  'COM_MULTIAGENCY_USERS_EMAIL', 'a.email', $listDirn, $listOrder); ?>
						</th>

						<th class=''>
							<?php echo HTMLHelper::_( 'grid.sort', Text::sprintf('COM_MULTIAGENCY_MULTIAGENCY_TITLE_HEAD', Text::_('COM_MULTIAGENCY_ORGANISATION')), 'c.title', $listDirn, $listOrder); ?>
						</th>
						
						<th class=''>
						<?php echo Text::_( 'COM_MULTIAGENCY_USERS_JOBTITLE'); ?>
						</th>						<th class=''>
						<?php echo Text::_( 'COM_MULTIAGENCY_USERS_ROLE'); ?>
						</th>
						<th class=''>
						<?php echo Text::_( 'COM_MULTIAGENCY_FORM_DPELEAD_LIST'); ?>
						</th>

						<?php if ($allowRemoveUser):?>
							<th class=''>
							<?php echo Text::_( 'COM_MULTIAGENCY_ACTION'); ?>
							</th>
						<?php endif; ?>
					</tr>
				</thead>

				<!-- <tfoot>
					<tr>
						<td colspan="<?php //echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">

						</td>
					</tr>
				</tfoot> -->

				<tbody>
					<?php foreach ($this->items as $i => $item) :
					?>
					<?php
						$isLeadConsultant = false;
						$isViewOnly       = false;
						$userGroups       = Factory::getUser($item->id)->groups;

						if (in_array($leadConsultantGroupId, $userGroups))
						{
							$isLeadConsultant = true;
						}

						if ($item->client_id)
						{
							// Get allowed Roles
							$userFormModel = BaseDatabaseModel::getInstance('UserForm', 'MultiagencyModel', array('ignore_request' => true));
							$roles      = $userFormModel->getUserAgencyRole((int) $item->client_id);
							$allowRoles = array_column($roles, 'role_id');
						}
					?>
							<tr class="row<?php echo $i % 2; ?>">
								<?php if ($allowRemoveUser && ($this->user->id != $item->id) && !($isLeadConsultant)) { ?>
								<td class="center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<?php } elseif ($allowRemoveUser && ($isLeadConsultant || $this->user->id == $item->id)) { ?>
								<td>-</td>
								<?php } ?>

								<td>
								<!-- Allow user edit if having user edit permissions and role or for the users who don't have any role -->
								<?php if (($this->isSuperAdmin || $this->editOwnUser || $this->editUser) && (in_array($item->roleId, (array) $allowRoles) || !isset($item->roleId))) : ?>
									<?php if ($this->user->authorise('core.manageall', 'com_cluster')) { ?>
										<a href="<?php echo Route::_('index.php?option=com_multiagency&task=userform.edit&id=' . (int) $item->id.'&Itemid=' . $createStaffMenuItem->id); ?>" title="<?php echo Text::sprintf('COM_MULTIAGENCY_EDIT_ITEM', $this->escape($item->name)); ?>">
											<?php echo $this->escape($item->name); ?>
										</a>
									<?php }else{ ?>
										<a href="<?php echo Route::_('index.php?option=com_multiagency&view=userform&layout=default_userform&id=' . (int) $item->id.'&Itemid=' . $createStaffMenuItem->id); ?>" title="<?php echo Text::sprintf('COM_MULTIAGENCY_EDIT_ITEM', $this->escape($item->name)); ?>">
											<?php echo $this->escape($item->name); ?>
										</a>
									<?php } ?>
								<?php else : ?>
									<?php echo $this->escape($item->name); ?>
								<?php endif; ?>
								</td>
								<td>
									<?php echo $item->email; ?>
								</td>

								<td>
									<?php echo (isset($item->title) ? $item->title : ' - '); ?>
								</td>
								<td>
									<?php echo  $item->jobtitle;; ?>
								</td>
								<td>
									<?php echo (isset($item->role_title) ? $item->role_title : ' - '); ?>
								</td>
								<?php 
								$params = ComponentHelper::getParams('com_multiagency');
								JLoader::import("/components/com_subusers/includes/rbacl", JPATH_ADMINISTRATOR);
								$orgAdminRoleId = $params->get('school_admin_role_id', '0', 'INT');
								?>
								<td><?php echo ($item->dpelead == '1' && ($orgAdminRoleId == $item->roleId))?'<i class="fa fa-check fa-lg dpelead"></i>' :'' ?></td>
								<?php if ($allowRemoveUser && ($this->user->id != $item->id) && !($isLeadConsultant)) { ?>
								<td>
									<a href="javascript:void(0)" onclick="if(confirm('<?php echo Text::_('COM_MULTIAGENCY_DELETE_USER_MESSAGE')?>')) { Joomla.listItemTask('cb<?php echo $i; ?>','users.blockUser'); }" type="button"><i class="fa fa-trash-o fa-lg" ></i></a>
								</td>
								<?php } elseif ($allowRemoveUser && ($isLeadConsultant || $this->user->id == $item->id)) { ?>
								<td>-</td>
								<?php } ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="pager" id="pagination">
								<?php echo $this->pagination->getPagesLinks(); ?>
								<!-- <hr class="hr hr-condensed"/> -->
							</div>
		<?php
		} else { ?>
			<div class="alert alert-danger margint20"><?php echo Text::_('COM_MULTIAGENCY_NO_USER_FOUND');?></div>
		<?php } 
		$multiagencyParams = ComponentHelper::getParams('com_multiagency');
			$orgAdminRoleId    = (int) $multiagencyParams->get('multiagency_school_admin_group', '0', 'INT');
		   $orgAdminRoleId 	 = in_array($orgAdminRoleId, $user->groups);

		  JLoader::import("/components/com_dpe/includes/dpe", JPATH_SITE);
		  $dpeModel = DPE::model('school', array('ignore_request' => true));
		  $tags = json_encode($dpeModel->getAgencyTags($orgAdminRoleId)); 

		  $optionHtml = '';
			foreach ($tags as $option) {
			    $optionHtml .= '<option value="' . $option['value'] . '">' . $option['text'] . '</option>';
			}

		 ?>
		</div>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>

		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>

<!-- js for tag filter -->
<script>
	function openspotlight(srclink)
		{			
			SqueezeBox.open( srclink,{handler: "iframe", size: {x:1000, y:550}});	

		}
	jQuery(document).ready(function(){

		
		
		jQuery("#filter_tags").attr("data-placeholder", "<?php echo Text::_('COM_DPE_FORM_LBL_TAG'); ?>");
		jQuery("#filter_tags").trigger("chosen:updated");
		
		// checked for dpe admin or not
		var isDpeAdmin = "<?php echo $user->authorise('core.manageall', 'com_cluster'); ?>";
		var isorgAdmin = "<?php echo $orgAdminRoleId; ?>";


		if (!isDpeAdmin && !isorgAdmin)
		{
			jQuery('#filter_tags_chosen').hide();
		}

		if (isorgAdmin)
		{

			var options = <?php echo $tags; ?>;
			jQuery("#filter_tags").empty().append('<option></option>');
			jQuery.each(options, function(index, option) {
			    jQuery("#filter_tags").append('<option value="' + option.value + '">' + option.text + '</option>');
			});

			jQuery("#filter_tags").trigger("chosen:updated");
		}

		jQuery('#filter_tags').on('change', function() {
			jQuery("#filter_agencies").val(jQuery("#filter_agencies option:first").val());
	    });
	    jQuery('#filter_agencies').on('change', function() {	
			jQuery("#filter_tags").val('');
	    });
})

		jQuery("#filter_dpelead").click(function() {

			var ischecked= jQuery('#filter_dpelead').is(':checked');
			if(!ischecked)
			{
				jQuery('#filter_dpelead').val('no');
			}
			else
			{
				jQuery('#filter_dpelead').val('dpelead');
			}

    });

	jQuery(document).ready(function($) {
	// Backup original Joomla tableOrdering function if not already saved
	if (typeof Joomla.originalTableOrdering === 'undefined') {
		Joomla.originalTableOrdering = Joomla.tableOrdering;
	}

	Joomla.tableOrdering = function(order, dir, task) {
		// Persist order + direction in Joomla PHP session using AJAX
		$.ajax({
			url: 'index.php?option=com_dpe&task=users.setOrderFilter',
			type: 'POST',
			dataType: 'json',
			data: {
				filter_order: order,
				filter_order_Dir: dir
			},
			success: function(response) {
				console.log('Ordering stored in session:', response);

				// Continue with original ordering action
				Joomla.originalTableOrdering(order, dir, task);
			},
			error: function(xhr, status, error) {
				console.warn('Failed to store ordering in session:', error);

				// Fallback to original ordering even if AJAX fails
				Joomla.originalTableOrdering(order, dir, task);
			}
		});
	};
});
</script>
<!-- js for tag filter end -->
