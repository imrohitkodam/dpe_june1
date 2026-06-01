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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$params = ComponentHelper::getParams('com_multiagency');
$user       = Factory::getUser();
$userId     = $user->id;
$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
$canCreate  = $user->authorise('core.create', 'com_multiagency') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'multiagencyform.xml');
$canEdit    = $user->authorise('core.edit', 'com_multiagency') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'multiagencyform.xml');
$canCheckin = $user->authorise('core.manage', 'com_multiagency');
$canChange  = $user->authorise('core.edit.state', 'com_multiagency');
$canDelete  = $user->authorise('core.delete', 'com_multiagency');

// Get Actionboard Itemid
$app = Factory::getApplication();
$menu = $app->getMenu();
$agencyFormMenuItem = $menu->getItems('link', 'index.php?option=com_multiagency&view=multiagencyform&layout=agencydetails', true);

JText::script('COM_MULTIAGENCY_DELETE_MESSAGE');
JHtml::script( Uri::root().'media/com_multiagency/js/multiagency.js' );
?>
<form action="<?php echo Route::_('index.php?option=com_multiagency&view=multiagences'); ?>" method="post" name="adminForm" id="adminForm">
<div class="row manage-schools-page">
	<div class="col-xs-12">
		<div id="filter-progress-bar" class="row">
			<div class="col-xs-12 col-sm-5">
				<div class="filter-search input-group dp-search-filter">
					<input type="text" name="filter_search" id="filter_search"
						placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>"
						value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
						title="<?php echo Text::_('JSEARCH_FILTER'); ?>"/>
					<button class="btn btn-default" type="submit"
						title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>">
						<i class="icon-search"></i></button>
					<button class="btn btn-basic" id="clear-search-button" type="button"
					title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><?php echo Text::_('COM_MULTIAGENCY_SEARCH_FILTER_CLEAR'); ?>
						</button>
				</div>
			</div>
			<div class="col-xxxs-12 col-xs-12 col-sm-7 marginb10 text-right">
				<div class="btn-group hidden-xs pull-right ml-20">
					<?php echo $this->pagination->getLimitBox(); ?>
				</div>
				<?php if ($canCreate) : ?>
					<a href="<?php echo Route::_('index.php?option=com_multiagency&task=multiagencyform.edit&id=0', false); ?>" class="btn btn-primary"><i class="icon-plus"></i>
						<?php echo Text::sprintf('COM_MULTIAGENCY_ADD_ITEM', Text::_('COM_MULTIAGENCY_ORGANISATION')); ?>
					</a>
				<?php endif; ?>
				<?php //echo  $this->pagination->getLimitBox(); ?>
                	</div>
		</div>
	</div>
	<div class="col-xs-12">
		<?php
		if (!empty($this->items))
		{
		?>
		<table class="table table-striped" id="multiagencyList">
			<thead>
				<tr>
					<?php if (isset($this->items[0]->state)): ?>

					<?php endif; ?>
					<th class=''>
						<?php echo HTMLHelper::_( 'grid.sort', 'COM_MULTIAGENCY_MULTIAGENCES_ID', 'a.id', $listDirn, $listOrder); ?>
					</th>
					<th class=''>
						<?php echo HTMLHelper::_( 'grid.sort', Text::sprintf('COM_MULTIAGENCY_MULTIAGENCES_TITLE', Text::_('COM_MULTIAGENCY_ORGANISATION')), 'a.title', $listDirn, $listOrder); ?>
					</th>
					<th class=''>
							<?php echo Text::_( 'COM_MULTIAGENCY_MULTIAGENCES_MANAGER_COUNT'); ?>
					</th>
					<?php if ($canEdit || $canDelete): ?>
					<th class="center">
						<?php echo Text::_( 'COM_MULTIAGENCY_MULTIAGENCES_ACTIONS'); ?>
					</th>
					<?php endif; ?>
				</tr>
			</thead>
			<tfoot>
				<tr class="pagers">
					<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 5; ?>">
						<div class="pager" id="pagination">
							<?php echo $this->pagination->getPagesLinks(); ?>
							<hr class="hr hr-condensed"/>
						</div>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach ($this->items as $i => $item) : ?>
				<?php $canEdit = $user->authorise('core.edit', 'com_multiagency'); ?>
				<?php if (!$canEdit && $user->authorise('core.edit.own', 'com_multiagency')): ?>
				<?php $canEdit = Factory::getUser()->id == $item->created_by; ?>
				<?php endif; ?>
				<tr class="row<?php echo $i % 2; ?>">
					<?php if (isset($this->items[0]->state)) : ?>
					<?php $class=( $canChange) ? 'active' : 'disabled'; ?>

					<?php endif; ?>
					<td>
						<?php echo $item->id; ?>
					</td>
					<td>
						<?php
						$agencyFormMenuUrl = 'index.php?option=com_multiagency&view=multiagencyform&layout=agencydetails&id='. $item->id;
						?>
						<a target="_blank" href="<?php echo Route::_($agencyFormMenuUrl.'&Itemid=' . $agencyFormMenuItem->id, false); ?>">
							<?php echo $this->escape($item->title);?>
						</a>
					</td>
                                        <td>
						<?php echo $item->manager_count; ?> </td>


					<?php if ($canEdit || $canDelete): ?>
					<td class="center">
						<?php if ($canEdit): ?>
						<a href="<?php echo Route::_('index.php?option=com_multiagency&task=multiagencyform.edit&id=' . $item->id, false); ?>" class="btn btn-mini" type="button"><i class="icon-edit" ></i></a>
						<?php endif; ?>
						<?php if ($canDelete): ?>
						<a href="javascript:void(0);" onclick="deleteItem('<?php echo base64_encode($item->id); ?>')" class="btn btn-mini delete-button" type="button"><i class="icon-trash" ></i></a>
						<?php endif; ?>
					</td>
					<?php endif; ?>

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
	</div>
</div>
</form>
