<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

jimport('techjoomla.common');

HTMLHelper::_('bootstrap.renderModal', 'a.modal');

$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
?>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">

<form action="<?php echo Route::_('index.php?option=com_tjlms&view=filedownloadstats'); ?>" method="post" name="adminForm" id="adminForm">
	<?php
		ob_start();
		include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
		$layoutOutput = ob_get_contents();
		ob_end_clean();

		echo $layoutOutput;

		echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
	?>
	<div class="clearfix"> </div>
	<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
		<div>
		<table class="table table-striped tjlms-filedownloadstats-table" id="fileDownloadStatsList">
			<thead>
				<tr>
    				<th width="1%" class="hidden-phone">
    					<input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
    				</th>
    				<th class='left'>
    					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_FILE_DOWNLAOD_STATS_FILE_TITLE', 'b.org_filename', $listDirn, $listOrder); ?>
    				</th>
    				<th class='left'>
    					<?php echo Text::_('COM_TJLMS_FILE_DOWNLAOD_STATS_FILE_COUNT'); ?>
    				</th>
				</tr>
			</thead>
			<tfoot>
				<?php
				if (isset($this->items[0]))
				{
					$colspan = count(get_object_vars($this->items[0]));
				}
				else
				{
					$colspan = 10;
				}
				?>
				<tr>
    				<td colspan="<?php echo $colspan ?>">
    					<?php echo $this->pagination->getListFooter(); ?>
    				</td>
				</tr>
			</tfoot>
			<tbody>
			<?php foreach ($this->items as $i => $item) :
				?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="center hidden-phone">
						<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
					</td>

    				<td>
    					<?php echo $this->escape($item->title); ?>
    				</td>
					<td>
						<a title="<?php echo Text::_( 'COM_TJLMS_FILE_DOWNLAOD_STATS_INFO' ); ?> " onclick="opentjlmsSqueezeBox('<?php echo JUri::root();?>', 'addModal', <?php echo $item->file_id; ?>); jQuery('#addModal' + <?php echo $item->file_id; ?>).removeClass('hide')">
							<?php echo $item->download_count; ?></a>
						<?php
							$link = 'index.php?option=com_tjlms&view=filedownloadstats&layout=modal&tmpl=component&fileId=' . $item->file_id;

							echo HTMLHelper::_(
								'bootstrap.renderModal',
								'addModal' . $item->file_id,
								array(
									'url'        => $link,
									'width'      => '800px',
									'height'     => '300px',
									'modalWidth' => '80',
									'bodyHeight' => '70'
								)
							)
							?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<?php endif;?>
		<input type="hidden" id='filedownloadstats_id' name="filedownloadstats_id" value="" />
		<input type="hidden" id='processor' name="processor" value="" />
		<input type="hidden" id='controller' name="controller" value="filedownloadstats" />
		<input type="hidden" id='task' name="task" value="" />
		<input type="hidden" id='option' name="option" value="com_tjlms" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
