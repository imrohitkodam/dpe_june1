<?php
/**
 * @version     1.0.0
 * @package     com_tjlms
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */
// @deprecated  1.3.32 Use TJCertificate certificate view instead
// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
HTMLHelper::_('bootstrap.renderModal', 'a.tjmodal');

$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');

?>
<div class="tjBs3 <?php echo COM_TJLMS_WRAPPER_DIV; ?>">
	<form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">
		<!-- If a user is not authorized to view the course-->
		<?php if ($this->params->get('show_page_heading', 1)) : ?>
		<div class="page-header">
			<h2><?php echo $this->escape($this->params->get('page_heading')); ?></h2>
		</div>
		<?php endif; ?>
		<?php
			$searchTool = LayoutHelper::render('joomla.searchtools.default', array('view' => $this,'options'=>array('filterButton'=>false)));
			echo $searchTool;
		?>
		<div class="clearfix"></div>

		<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
		<?php else : ?>
		<div class="table-responsive">
			<table class="table table-striped table-bordered table-hover" id="certificatesList">
				<thead>
					<tr>
						<th class='center'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_CERTIFICATES_COURSE_TITLE', 'cert_id', $listDirn, $listOrder); ?>
						</th>
						<th class='center'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_CERTIFICATES_CERTIFICATE_ID', 'course_title', $listDirn, $listOrder); ?>
						</th>
						<th class='center'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_CERTIFICATES_GRANT_DATE', 'grant_date', $listDirn, $listOrder); ?>
						</th>
						<th class='center'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_CERTIFICATES_EXP_DATE', 'exp_date', $listDirn, $listOrder); ?>
						</th>
						<th class='center'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_CERTIFICATES_TIME_SPENT', 'time_spent', $listDirn, $listOrder); ?>
						</th>
						<th class='center'>
						<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_CERTIFICATES_CERT_TITLE', 'expired', $listDirn, $listOrder); ?>
						</th>
					</tr>
				</thead>
			<tfoot>
				<tr class="center">
					<td colspan="6">
						<div class="pager">
							<?php echo $this->pagination->getPagesLinks(); ?>
						</div>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php foreach ($this->items as $item) :
				$certPopupLink = '
					<a rel="{handler: \'iframe\', size: {x: 800, y: 600}}" style="text-decoration:none;text-align:center" class="tjmodal"
						href="index.php?option=com_tjlms&view=certificate&tmpl=component&course_id='.$item->course_id.'&user_id='.$item->user_id.'">
						<button class="btn btn-small btn-success tjlms-btn-flat">
							'.Text::_('COM_TJLMS_CERTIFICATES_CERT_LINK').'
						</button>
					</a>';
				?>
				<tr>
					<td class="center"><a href="<?php echo $this->comtjlmsHelper->tjlmsRoute('index.php?option=com_tjlms&view=course&id='.$item->course_id); ?>"><?php echo $this->escape($item->course_title); ?></a></td>
					<td class="center"><?php echo $item->cert_id; ?> </td>
					<td class="center"><?php echo $item->disp_grant_date; ?> </td>
					<td class="center"><?php
							if ($item->disp_exp_date == '0000-00-00 00:00:00')
							{
								echo '-';
							}
							else
							{
								echo $item->disp_exp_date;
							} ?>
					</td>
					<td class="center"><?php echo $item->disp_time_spent; ?> </td>
					<td class="center"><?php echo $item->expired ? Text::_('COM_TJLMS_CERTIFICATES_CERT_EXPIRED') : $certPopupLink; ?> </td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<?php endif; ?>
		<input type="hidden" name="option" value="com_tjlms" />
		<input type="hidden" name="view" value="certificates" />
		<input type="hidden" name="layout" value="my" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>

	</form>
</div> <!--techjoomla-bootstrap-->


