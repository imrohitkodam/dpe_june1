<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_Multiagency
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  2017 Techjoomla
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.modal', 'a.tjmodal');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::script( Uri::root().'components/com_tjlms/assets/js/tjlms.js' );

JLoader::import('components.com_tjlms.helpers.main', JPATH_SITE);
$tjlmsHelper = new ComtjlmsHelper;

$user       = Factory::getUser();
$userId     = $user->get('id');
$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
$canCreate  = $user->authorise('core.create', 'com_multiagency') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'multiagencyform.xml');
$canEdit    = $user->authorise('core.edit', 'com_multiagency') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'multiagencyform.xml');
$canCheckin = $user->authorise('core.manage', 'com_multiagency');
$canChange  = $user->authorise('core.edit.state', 'com_multiagency');
$canDelete  = $user->authorise('core.delete', 'com_multiagency');

JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
$licenseModel = BaseDatabaseModel::getInstance('Licences', 'MultiagencyModel', array('ignore_request' => true));

?>
<form action="<?php echo Route::_('index.php?option=com_multiagency&view=courses'); ?>" method="post" name="adminForm" id="adminForm">
	<legend><?php echo Text::_("COM_MULTIAGENCY_TITLE_LIST_VIEW_COURSES");?></legend>
	<div class="clearfix">&nbsp;</div>
	<div class="clearfix">&nbsp;</div>
	<div id="filter-progress-bar" class="btn-toolprogress-bar manage-courses dp-search-filter">
		<div class="filter-search btn-group pull-left">
<!--
			<label for="filter_search" class="element-invisible">
				<?php echo Text::_('JSEARCH_FILTER'); ?>
			</label>
-->
			<input type="text" name="filter_search" id="filter_search"
					placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>"
					value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
					title="<?php echo Text::_('JSEARCH_FILTER'); ?>"/>
		</div>
		<div class="pull-left">
			<button class="btn btn-default" type="submit"
					title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
				<i class="icon-search"></i>
			<button class="btn txt-gray mx-10" id="clear-search-button" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><?php echo Text::_('COM_MULTIAGENCY_SEARCH_FILTER_CLEAR'); ?></button>
		</div>
		<div class="filter-search btn-group pull-left ml-1">
		<?php  echo HTMLHelper::_('select.genericlist', $this->agencies, 'agencies[]', 'class="form-control"  name="agencies" onchange="this.form.submit();"', "value", "text",$this->agenciesId); ?>
		</div>

		<div class="btn-group pull-right hidden-xm hidden-phone">
<!--
			<label for="limit"
				   class="element-invisible">
				<?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?>
			</label>
-->
			<?php //echo $this->pagination->getLimitBox(); ?>
		</div>
	</div>
	<div class="clearfix">&nbsp;</div>
		<?php
		if (!empty($this->items))
		{
		?>
		<table class="table table-striped" id="multiagencyList">
			<thead>
				<tr>
<!--
					<?php //if (isset($this->items[0]->state)): ?>
					<th width="5%">
						<?php //echo HTMLHelper::_( 'grid.sort', 'JPUBLISHED', 'a.state', $listDirn, $listOrder); ?>
					</th>
					<?php //endif; ?>
-->
					<th class=''>
						<?php echo HTMLHelper::_( 'grid.sort', 'COM_MULTIAGENCY_LICENCES_COURSE_ID', 'b.title', $listDirn, $listOrder); ?>
					</th>
					<th class=''>
						<?php echo HTMLHelper::_( 'grid.sort', 'COM_MULTIAGENCY_LICENCES_TOTAL_LICENCES', 'a.total_seats', $listDirn, $listOrder); ?>
					</th>
					<th class=''>
						<?php echo HTMLHelper::_( 'grid.sort', 'COM_MULTIAGENCY_LICENCES_USED_LICENCES', 'a.used_seats', $listDirn, $listOrder); ?>
					</th>
					<th class=''>
						<?php echo JTEXT::_('COM_MULTIAGENCY_LICENCES_ACTIONS'); ?>
					</th>
<!--
					<?php //if ($canEdit || $canDelete): ?>
					<th class="center">
						<?php //echo JText::_( 'COM_MULTIAGENCY_MULTIAGENCES_ACTIONS'); ?>
					</th>
					<?php //endif; ?>
-->
				</tr>
			</thead>
			<tfoot>
				<tr class="pagers">
					<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 5; ?>">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach ($this->items as $i => $item) : ?>
					<td>
						<?php
						if ($item->type == 'all')
						{
							echo JTEXT::_('COM_MULTIAGENCY_LICENCES_ALL');
						}
						else
						{
							echo $item->title;
						}
						 ?> </td>
					<td>
						<?php
						if ($item->total_seats == 0)
						{
							echo JTEXT::_('COM_MULTIAGENCY_LICENCES_UNLIMITED');
						}
						else
						{
							echo $item->total_seats;
						}
						?>
					</td>
					<td>
						<?php echo $item->used_seats; ?>
					</td>
					<td>
						<?php
						if ($licenseModel->isValidLicense($item->id))
						{
							//$assignLink = $tjlmsHelper->tjlmsRoute( 'index.php?option=com_tjlms&view=enrolluser&tmpl=component&selectedcourse[]=' . $item->course_id . ',7&course_al=' . $item->access . '&type=assign', false);
							$assignLink = $tjlmsHelper->tjlmsRoute( 'index.php?com_multiagency&view=enrollment&agency&license=' . $item->id . '&tmpl=component', false); ?>

						<a class="btn btn-primary" onclick="openAssignRecommendPopups('<?php echo $assignLink;?>')" id="assign-modal-link" >
							<?php echo JTEXT::_('COM_MULTIAGENCY_COURSES_ACTIONS_MANAGE_ENROLLMENTS');?>
						</a>
						<?php } else
						{
							echo Text::_('COM_MULTIAGENCY_LICENCES_EXPIRE');
						}?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		}
		else
		{
			?>
			<div class="clearfix">&nbsp;</div>
			<div class="alert alert-info"><?php echo Text::_("COM_MULTIAGENCY_NO_RECORDS_FOUND");?></div>
			<?php
		}
		?>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />

	<?php echo HTMLHelper::_( 'form.token'); ?>
</form>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('.delete-button').click(deleteItem);
		jQuery('#clear-search-button').on('click', function () {
			jQuery('#filter_search').val('');
			jQuery('#adminForm').submit();
		});
	});

	function deleteItem() {

		if (!confirm("<?php echo Text::_('COM_MULTIAGENCY_DELETE_MESSAGE'); ?>")) {
			return false;
		}
	}
</script>
<style>
.tjlms_lesson_screen #sbox-btn-close{
display: none;
}
</style>
