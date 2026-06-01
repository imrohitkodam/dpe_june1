<?php
/**
 * @version    SVN: <svn_id>
 * @package    JLike
 * @copyright  Copyright (C) 2005 - 2015. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<div class="container-fluid">
	<!--filter section-->
	<div class="row hidden-phone mt-10 mb-10">
		<div class=" col-md-6 pl-0">
			<div class="">
				<?php if ($this->type != 'reco'): ?>
					<?php echo $this->filterForm->getInput('subuserfilter','filter');?>
				<?php endif; ?>
			</div>
		</div>
		<div class="hidden-sm  col-md-6 pr-0 pull-right">
			<?php echo $this->filterForm->getInput('limit','list');?>
		</div>
	</div>

	<!--/filter section-->
	<div class="row overflow-y-auto" id="<?php echo ($this->type == 'reco') ? 'recommend-table-container' : 'assign-table-container';?>">
		<table class="table table-striped new mb-0" id="usersList">
			<!-- TABLE HEADER -->
			<thead>
				<tr>
					<th width="1%">
						<?php
							$disableCheck = '';
							if (empty($this->items)):
								$disableCheck = 'disabled'; ?>
							<?php endif;?>
						<input type="checkbox" name="checkall-toggle" value=""
							title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>"
							onclick="Joomla.checkAll(this)" <?php echo $disableCheck;?>/>
					</th>
					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ENROLMENT_USER_NAME', 'uc.name', $listDirn, $listOrder); ?>
						<div class="hidden-phone">
							<?php echo $this->filterForm->getInput('search','filter');?>
						</div>
					</th>
					<th class='left'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ENROLMENT_USER_USERNAME', 'uc.username', $listDirn, $listOrder); ?>
					</th>
					<th class='left'>
						<?php echo Text::_('COM_TJLMS_ENROLMENT_GROUP_TITLE'); ?>
						<div class="hidden-phone">
							<?php echo $this->filterForm->getInput('groupfilter','filter');?>
						</div>
					</th>
					<th class='left'>
					<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ENROLMENT_USERID', 'uc.id',$listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>

			<!-- TABLE BODY -->
			<tbody>
			<!-- NO USER FOUND -->
			<?php if (empty($this->items)): ?>
			<tr>
				<td colspan="5" class="alert alert-warning center"><?php echo Text::_('COM_TJLMS_NO_USERS_FOUND'); ?></td>
			</tr>
			<?php endif;?>
				<?php foreach ($this->items as $i => $item): ?>
					<tr id="rocommenToUser<?php echo $item->id; ?>">
						<td class="center">
							<?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid'); ?>
						</td>
						<td><?php echo $item->name;?></td>
						<td><?php echo $item->username;?></td>
						<td>
						<?php if (substr_count($item->groups, "<br />") > 2) : ?>
							<span class="hasTooltip" title="<?php echo HTMLHelper::_('tooltipText', Text::_('COM_TJLMS_ENROLMENT_GROUP_TITLE'), nl2br($item->groups), 0); ?>"><?php echo Text::_('COM_TJLMS_ENROLMENT_MULTI_GROUP_TITLE'); ?></span>
						<?php else : ?>
							<?php echo nl2br($item->groups); ?>
						<?php endif; ?>
						</td>
						<td><?php echo $item->id; ?></td>
					</tr>
				<?php endforeach;?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="5" class="center text-center">
						<div class="pager my-0">
							<?php echo $this->pagination->getPagesLinks(); ?>
						</div>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
