<?php
/**
 * @version     1.0.0
 * @package     com_tjlms
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - www.techjoomla.com
 */

// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::addIncludePath(JPATH_COMPONENT.'/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');


?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">

	<form action="<?php echo Route::_('index.php?option=com_tjlms&view=activities'); ?>" method="post" name="adminForm" id="adminForm">

		<?php
			ob_start();
			include JPATH_BASE . '/components/com_tjlms/layouts/header.sidebar.php';
			$layoutOutput = ob_get_contents();
			ob_end_clean();
			echo $layoutOutput;

			echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
		?>
		<!-- Display message if no data found-->

		<?php
		if (!$this->userid)
		{
			?>
			<div class="alert alert-warning">
				<?php echo Text::_('COM_TJLMS_LOGIN_MESSAGE'); ?>
			</div>

			<?php

			return false;
		}

		$listOrder = $this->state->get('list.ordering');
		$listDirn = $this->state->get('list.direction');
		?>

		<div class="clearfix"> </div>
		<?php if (empty($this->items)) : ?>
			<div class="alert alert-no-items">
				<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
			</div>
		<?php else : ?>
		<div class="tjlms-tbl">
			<table class="table table-striped" id="activityList">
				<thead>
					<tr>
						<th width="1%" class="nowrap center">
							<?php echo Text::_( 'COM_TJLMS_ACTIVITY_TEXT'); ?>
						</th>
					</tr>
				</thead>
				<tfoot>
					<?php
						if(isset($this->items[0]))
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
					<?php

					foreach ($this->items as $i => $activity){ ?>
						<tr class="row<?php echo $i % 2; ?> tjlms-activity-list">

							<!--Activity string formation-->
							<!--Activity string formation ENDS-->

							<td>
								<?php echo $activity->actionString; ?>
										<small><em>
											<?php
												echo $this->comtjlmstrackingHelper->time_elapsed_string($activity->added_time,true);
											?>
										</em></small>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>

		<input type="hidden" name="task" id="task" value="" />
	</form>
</div>
