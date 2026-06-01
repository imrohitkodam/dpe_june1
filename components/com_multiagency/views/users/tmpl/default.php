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

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('behavior.modal');
HTMLHelper::_('formbehavior.chosen', 'select');
jimport( 'joomla.html.html.select' );

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

// Get user groups as per name
$leadConsultantGroup = Table::getInstance('Usergroup', 'JTable');
$leadConsultantGroup->load(array('title' => 'External Lead Consultant'));

if (property_exists($leadConsultantGroup, 'id'))
{
	$leadConsultantGroupId = $leadConsultantGroup->id;
}

?>
<script type="text/javascript">
		var nonvalid_extension = "<?php echo Text::_('COM_USER_UPLOAD_EXTENSION_ERROR');?>"
 </script>


<div class="tj-page">
<div class ="row">
<!--
	<div class="col-xs-12">
		<legend><?php echo Text::_('COM_MULTIAGENCY_USERS'); ?></legend>
	</div>
-->
	<div class="col-xs-12 pt-10 pb-30">
		<form action="<?php echo Route::_('index.php?option=com_multiagency&view=users'); ?>" method="post" name="adminForm" id="adminForm">

		<div id="filter-bar1" class="row">
			<div class="col-sm-12 col-md-6">
				<div class="filter-search input-group manage-staff dp-search-filter">
					<?php
						echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this, 'options' => array('filterButton' => true, 'filtersHidden' => false)));
					?>
				</div>
			</div>

			<div class="col-sm-12 col-md-1  pull-right">
				<div class="btn-group hidden-xs pull-right ml-10">
					<?php echo $this->pagination->getLimitBox(); ?>
				</div>
			</div>

			<?php if ($allowRemoveUser):?>
			<div class="col-sm-12 col-md-1 pull-right">
					<div class="ml-5">
						<button onclick="if (document.adminForm.boxchecked.value == 0) { alert('<?php echo Text::_('COM_MULTIAGENCY_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');?>'); } else { if (confirm('<?php echo Text::_('COM_MULTIAGENCY_DELETE_USERS_MESSAGE');?>')) { Joomla.submitbutton('users.blockUser'); } }" class="btn btn-danger">
						<span class="icon-delete" aria-hidden="true"></span>
						<?php echo Text::_('COM_MULTIAGENCY_DELETE_BUTTON')?></button>
					</div>
			</div>
			<?php endif; ?>

			<div class="col-sm-12 col-md-1 pull-right">
				<?php if ($this->canCreate || $this->canEditUser || $this->addUser) : ?>
					<a href="<?php echo Route::_('index.php?option=com_multiagency&task=userform.edit&id=0', false, 0); ?>" class="btn btn-primary">
						<?php echo Text::_('COM_MULTIAGENCY_ADD_USER'); ?>
					</a>
				<?php endif; ?>
			</div>

			<div class="col-sm-12 col-md-3  staff-import text-right pull-right">
				<?php if ($this->canCreate) : ?>
				<a  rel="{handler:'iframe'}"   href="<?php echo Route::_('index.php?option=com_multiagency&view=users&tmpl=component&layout=import', false); ?>" class="modal">
					<button type="button" class="btn btn-primary size-bt">
						<span class="fa fa-upload"></span> &nbsp;
						<?php echo Text::_('COM_USER_ENROL_IMPORT_CSV'); ?>
					</button>
				</a>
				<?php endif; ?>
			</div>
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
							<?php echo HTMLHelper::_('grid.sort',  'COM_MULTIAGENCY_USERS_USER_NAME', 'a.username', $listDirn, $listOrder); ?>
						</th>

						<th class=''>
							<?php echo HTMLHelper::_('grid.sort',  'COM_MULTIAGENCY_USERS_EMAIL', 'a.email', $listDirn, $listOrder); ?>
						</th>

						<th class=''>
							<?php echo HTMLHelper::_( 'grid.sort', Text::sprintf('COM_MULTIAGENCY_MULTIAGENCY_TITLE_HEAD', Text::_('COM_MULTIAGENCY_ORGANISATION')), 'c.title', $listDirn, $listOrder); ?>
						</th>
						<th class=''>
						<?php echo Text::_( 'COM_MULTIAGENCY_USERS_ROLE'); ?>
						</th>

						<?php if ($allowRemoveUser):?>
							<th class=''>
							<?php echo Text::_( 'COM_MULTIAGENCY_ACTION'); ?>
							</th>
						<?php endif; ?>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
							<div class="pager" id="pagination">
								<?php echo $this->pagination->getPagesLinks(); ?>
								<hr class="hr hr-condensed"/>
							</div>
						</td>
					</tr>
				</tfoot>

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
					?>
							<tr class="row<?php echo $i % 2; ?>">
								<?php if ($allowRemoveUser && ($this->user->id != $item->id) && !($isLeadConsultant)) { ?>
								<td class="center">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<?php } elseif ($allowRemoveUser || $isLeadConsultant) { ?>
								<td>-</td>
								<?php } ?>

								<td>
								<?php if ($this->editOwnUser || $this->editUser) : ?>
								<a href="<?php echo Route::_('index.php?option=com_multiagency&task=userform.edit&id=' . (int) $item->id); ?>" title="<?php echo Text::sprintf('COM_MULTIAGENCY_EDIT_ITEM', $this->escape($item->name)); ?>">
									<?php echo $this->escape($item->name); ?>
								</a>
								<?php else : ?>
									<?php echo $this->escape($item->name); ?>
								<?php endif; ?>
								</td>

								<td>
									<?php echo $item->username; ?>
								</td>

								<td>
									<?php echo $item->email; ?>
								</td>

								<td>
									<?php echo (isset($item->title) ? $item->title : ' - '); ?>
								</td>

								<td>
									<?php echo (isset($item->role_title) ? $item->role_title : ' - '); ?>
								</td>
								<?php if ($allowRemoveUser && ($this->user->id != $item->id) && !($isLeadConsultant)) { ?>
								<td>
									<a onclick="if(confirm('<?php echo Text::_('COM_MULTIAGENCY_DELETE_USER_MESSAGE')?>')) { Joomla.listItemTask('cb<?php echo $i; ?>','users.blockUser'); }" class="btn btn-mini delete-button" type="button"><i class="fa fa-trash-o fa-lg" ></i></a>
								</td>
								<?php } elseif ($allowRemoveUser || $isLeadConsultant) { ?>
								<td>-</td>
								<?php } ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		} else { ?>
			<div class="alert alert-danger margint20"><?php echo Text::_('COM_MULTIAGENCY_NO_USER_FOUND');?></div>
		<?php }  ?>
		</div>
		<input type="hidden" name="task" value=""/>
		<input type="hidden" name="boxchecked" value="0"/>
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
