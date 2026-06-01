<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;

$app = Factory::getApplication();

if ($app->isClient('site'))
{
	Session::checkToken('get') or die(Text::_('JINVALID_TOKEN'));
}

require_once JPATH_ROOT . '/components/com_tjlms/helpers/route.php';

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.framework', true);
HTMLHelper::_('formbehavior.chosen', 'select');

$function  = $app->input->getCmd('function', 'jSelectCourse');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$fieldView = $app->input->getInt('fieldView');

// Special case for the search field tooltip.
$searchFilterDesc = $this->filterForm->getFieldAttribute('search', 'description', null, 'filter');
JHtml::_('bootstrap.tooltip', '#filter_search', array('title' => Text::_($searchFilterDesc), 'placement' => 'bottom'));

?>
<form action="<?php echo Route::_('index.php?option=com_tjlms&view=courses&layout=modal&tmpl=component&function=' . $function . '&' . Session::getFormToken() . '=1&fieldView=' . $fieldView);?>"
      method="post" name="adminForm" id="adminForm" class="form-inline">

     <?php 	echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));?>

	<table class="table table-striped">
		<thead>
			<tr>
				<th class="title">
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSES_TITLE', 'a.title', $listDirn, $listOrder); ?>
				</th>
				<th width="15%" class="center nowrap">
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSES_CREATED_BY', 'a.created_by', $listDirn, $listOrder); ?>
				</th>
				<th width="15%" class="center nowrap">
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSES_CAT_ID', 'a.catid', $listDirn, $listOrder); ?>
				</th>
				<th width="5%" class="center nowrap">
					<?php	echo JHTML::tooltip(Text::_('COM_TJLMS_TOTAL_ENROLLED_USERS'), '','', Text::_('COM_TJLMS_TOTAL_ENROLLED_USERS')); ?>
				</th>
				<th width="5%" class="center nowrap">
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSES_START_DATE', 'a.start_date', $listDirn, $listOrder); ?>
				</th>
				<th width="1%" class="center nowrap">
					<?php echo JHTML::tooltip(Text::_('COM_TJLMS_ACCESS_LEVEL'), '','', Text::_('COM_TJLMS_ACCESS_LEVEL')); ?>
				</th>
				<th width="1%" class="center nowrap">
					<?php echo HTMLHelper::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="15">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
		<?php foreach ($this->items as $i => $item) : ?>

			<tr class="row<?php echo $i % 2; ?>">
				<td>
					<a href="javascript:void(0)" <?php if ($fieldView) { ?> class="pointer button-select" data-user-value="<?php echo $item->id; ?>" data-user-name="<?php echo $this->escape($item->title); ?>" <?php } ?> onclick="if (window.parent) window.parent.<?php echo $this->escape($function);?>('<?php echo $item->id; ?>', '<?php echo $this->escape(addslashes($item->title)); ?>', '<?php echo $this->escape(TjlmscourseHelperRoute::getCourseRoute($item->id)); ?>');">
						<?php echo $this->escape($item->title); ?></a>
				</td>
				<td class="center">
					<?php echo $item->created_by; ?>
				</td>
				<td class="center">
					<?php echo $item->cat; ?>
				</td>
				<td class="center">
					<?php echo (!empty($item->enrolled_users)) ? $item->enrolled_users : 0; ?>
				</td>
				<td class="center nowrap">
					<?php echo HTMLHelper::_('date', $item->start_date, Text::_('DATE_FORMAT_LC4')); ?>
				</td>
				<td class="center">
					<?php echo $item->access_level_title; ?>
				</td>
				<td class="center">
					<?php echo (int) $item->id; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
