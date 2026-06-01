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

$config = JFactory::getConfig();
$timezone = $config->get('offset');

$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');

?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?> tjBs3">
<div class="container-fluid">
	<form action="" method="post" name="adminForm" id="adminForm">
		<div>
			<span>
				<h2><?php echo Jtext::_('COM_TJLMS_ACTIVITY_LEGEND'); ?></h2>
			</span>
			<span>
				<?php if (JVERSION >= '3.0') : ?>
					<div class="btn-group pull-right">
						<label for="limit" class="element-invisible">
							<?php echo JText::_('COM_TJLMS_SEARCH_SEARCHLIMIT_DESC'); ?>
						</label>
						<?php echo $this->pagination->getLimitBox(); ?>
					</div>
				<?php endif; ?>
			</span>
		</div>
		<div class="clearfix"></div>
		<?php if (empty($this->items)): ?>
			<div class="alert alert-warning">
				 <?php echo JText::_('COM_TJLMS_ACTIVITY_NO_ACTIVITYIES_YET'); ?>
			</div>
		<?php else: ?>
			<table class="table table-bordered table-striped table-condensed">
				<thead>
					<tr>
						<th  class="border-top-blue greyish"> <?php echo JText::_( 'COM_TJLMS_ACTIVITY_TEXT'); ?></th>
					</tr>
				</thead>

			<?php foreach ($this->items as $item)
			{
				 ?>
					<tr>
						<td>
							<div>
								<?php echo $item->activity;?>
							</div>
						</td>
					</tr>
				<?php
				}
				?>
				</table>
			<?php endif; ?>
			<div class="clearfix"></div>
			<div class="pager">
				<?php echo $this->pagination->getPagesLinks(); ?>
			</div>
			<input type="hidden" name="option" value="com_tjlms" />
			<input type="hidden" name="view" value="activities" />
			<input type="hidden" name="task" value="" />
			<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
			<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
			<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
</div>
