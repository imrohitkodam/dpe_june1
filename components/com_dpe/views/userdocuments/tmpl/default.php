<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_dpe
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Layout\LayoutHelper;

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
?>

<div class="my-documents">
	<form action="<?php echo Route::_('index.php?option=com_dpe&view=userdocuments&document_id=' . $this->doucmentId);?>" method="post" name="adminForm" id="adminForm" class="">
		<div class="row">
			<div class="col-md-2 upload-doc">
					<a href="<?php echo Route::_('index.php?option=com_multiagency&task=userform.edit&id=0', false, 0); ?>" class="btn btn-blue btn-small btn-upload">
						<?php echo Text::_('COM_DPE_ADD_USER'); ?>
					</a>
			</div>
			<div class="col-xs-12">
				<div class="search-field">
					<?php
						echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
					?>
				</div>
			</div>
		</div>
		<div class="clearfix"> </div>
		<div class="table-responsive table-userdoc mt-20 ">
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
			<table class="table" id="">
				<thead class="thead-light">
					<tr>
						<th>
							<?php echo HTMLHelper::_( 'grid.sort', 'COM_DPE_ASSIGNED_USERNAME', 'b.name', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo Text::sprintf('COM_DPE_ASSIGNED_SCHOOLS', Text::_('COM_MULTIAGENCY_ORGANISATIONS')); ?>
						</th>
						<th>
							<?php echo HTMLHelper::_('grid.sort', 'COM_DPE_ASSIGNED', 'assigncount', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($this->items as $i => $item) :
					?>
					<tr>
						<td>
							<?php
								echo $item->name;
							?>
						</td>
						<td>
							<?php
								echo $item->schools;
							?>
						</td>
						<td>
							<?php
								echo $item->assigncount;
							?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="col-xs-12">
			<div class="pull-right">
				<?php echo $this->pagination->getPagesLinks(); ?>
			</div>
		</div>
		<?php endif; ?>
			<input type="hidden" name="task" value=""/>
			<input type="hidden" name="doucmentId" value="<?php echo $this->doucmentId?>"/>
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
			<?php echo HTMLHelper::_('form.token');?>
	</form>
</div>
