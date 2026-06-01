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
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

use Joomla\CMS\Layout\LayoutHelper;

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$listOrder              = $this->state->get('list.ordering');
$listDirn               = $this->state->get('list.direction');
$app                    = Factory::getApplication();
$menu                   = $app->getMenu();
$menuItem               = $menu->getItems('link', 'index.php?option=com_dpe&view=myassignments', true);
$tjlmsparams            = ComponentHelper::getParams('com_tjlms');
$launchLessonFullScreen = $tjlmsparams->get('launch_full_screen');
$target                 = ($launchLessonFullScreen == 'tab') ? 'target="_blank"' : "";
$jlikeTjlmslessonPlugin = new Registry($this->jlikeTjlmslessonPlugin->params);

JHtml::script(Uri::root().'media/com_tjlms/js/tjlms.js');
?>
<div class="my-documents">
<!--
	<div class="page-header">
		<h2><?php echo Text::_('COM_DPE_MY_ASSIGNMENTS_HEAD_TITLE');?></h2>
	</div>
-->
	<form action="<?php echo Route::_('index.php?option=com_dpe&view=myassignments'); ?>" method="post" name="adminForm" id="adminForm">
		<div class="row">
			<div class="col-xs-12">
				<div class="search-field">
				<?php
					echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
				?>
				</div>
				<!-- Show document counts -->
<!--
				<?php if (!empty($this->items)) { ?>
				<div class="doc-count assigned-doc-count pull-left">
					<?php
						//echo JText::sprintf('COM_DPE_ASSIGNED_DOCUMENT_COUNT', count($this->items));
						echo '<span class="count">' . count($this->items) . '</span>';
						echo '<span class="text">' . Text::_('COM_DPE_ASSIGNED_DOCUMENT_COUNT') . '</span>';
					?>
				</div>
				<?php } ?>
-->
				<!-- Show pending document counts -->
				<div class="doc-count pending-doc-count pull-left">
<!--
					<?php
						echo '<span class="count">' . '10' . '</span>';
						echo '<span class="text">' . Text::_('COM_DPE_ASSIGNED_PENDING_DOCUMENT_COUNT') . '</span>';
					?>
-->
				</div>
			</div>
		</div>
		<div class="clearfix"> </div>
		<div class="table-responsive mt-20">
		<?php if (!empty($this->items)) { ?>
			<table class="table" id="">
				<thead class="thead-light">
					<tr>
						<th>
							<?php echo HTMLHelper::_( 'grid.sort', 'COM_DPE_DOUCMENTS_NAME_HEAD', 'a.title', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo HTMLHelper::_( 'grid.sort', 'COM_DPE_DOCUMENT_START_DATE', 'a.start_date', $listDirn, $listOrder); ?>
						</th>
						<th>
							<?php echo HTMLHelper::_( 'grid.sort', 'COM_DPE_DOCUMENT_END_DATE', 'a.due_date', $listDirn, $listOrder); ?>
						</th>
						<?php
						if ($jlikeTjlmslessonPlugin->get('read_interaction') == '1')
						{
						?>
							<th class="text-center">
								<?php echo Text::_('COM_DPE_INTERACTION_READ_UNDERSTOOD'); ?>
							</th>
						<?php
						}

						if ($jlikeTjlmslessonPlugin->get('practice_interaction') == '1')
						{
						?>
							<th class="text-center">
								<?php echo Text::_('COM_DPE_INTERACTION_USED'); ?>
							</th>
						<?php
						}


						?>
						<th>
							<?php echo Text::_('COM_DPE_DOCUMENT_STATUS'); ?>
						</th>
					<!--
						<th>
							<?php echo Text::_('COM_DPE_DOCUMENT_TOTAL_TIME_SPENT'); ?>
						</th>
					-->
					</tr>
				</thead>
				<tbody>
					<?php foreach ($this->items as $i => $item) :
							$intractions = new Registry($item->params);
							// Get file extension
							$info = new SplFileInfo($item->source);
							$extension = $info->getExtension();
					?>
						<td>
							<div class="break-word doc-title">
								<a class="hasTooltip"
								href="<?php echo Route::_('index.php?option=com_tjlms&view=lesson&lesson_id=' . $item->element_id . '&Itemid=' . $menuItem->id); ?>"
								<?php echo $target;?>
								title="<?php echo Text::_('COM_DPE_DOCUMENT_LAUNCH_HINT'); ?>"	>
									<span class="icon pull-left <?php echo $extension;?>"></span>
									<span class="doc-name pull-left"><?php echo $this->escape($item->title); ?></span>
								</a>
							</div>
							<div class="col-sm-12 fs-12">
								<?php
								$lessonDescCharLimit = 100;

								if (strlen($item->description) > $lessonDescCharLimit)
								{
									echo substr(strip_tags($item->description), 0, $lessonDescCharLimit);?>
									<div class="mid" id="HiddenDiv_<?php echo $i ?>">
										<?php echo substr(strip_tags($item->description), $lessonDescCharLimit, strlen($item->description));?>
									</div>
									<a href="javascript:void(0);" class="manage-lesson-more_<?php echo $i ?>" onclick="tjlms.managelessons.showHide('HiddenDiv_<?php echo $i ?>')">
										<?php echo Text::_('COM_TJLMS_MANAGELESSONS_LESSON_DESCRIPTION_READ_MORE');?>
									</a>
									<a href="javascript:void(0);" class="manage-lesson-less_<?php echo $i ?>" style="display:none"onclick="tjlms.managelessons.showHide('HiddenDiv_<?php echo $i ?>')">
										<?php echo Text::_('COM_TJLMS_MANAGELESSONS_LESSON_DESCRIPTION_READ_LESS');?>
									</a>
								<?php
								}
								else
								{
									echo $this->escape($item->description);
								}
								?>
							</div>
						</td>
						<td>
							<?php if ($item->start_date != $this->db->getNullDate()) : ?>
								<?php echo HTMLHelper::_('date', $item->start_date, Text::_('DPE_DATE_FORMAT')); ?>
							<?php endif; ?>
						</td>
						<td class="due-date <?php echo ($item->status); ?>">
							<?php if ($item->due_date != $this->db->getNullDate()) : ?>
								<?php echo HTMLHelper::_('date', $item->due_date, Text::_('DPE_DATE_FORMAT')); ?>
							<?php endif; ?>
						</td>
						<?php
						if ($jlikeTjlmslessonPlugin->get('read_interaction') == '1')
						{
							if ($intractions['read_interaction'])
							{?>
								<td class="text-center read">
									<?php
									 if ($item->read) : ?>
										<i class="fa fa-check-circle-o" aria-hidden="true"></i>
									<?php else : ?>
										<i class="fa fa-circle-o" aria-hidden="true"></i>
									<?php endif; ?>
								</td>
							<?php
							}
							else
							{?>
								<td class="text-center">
									<?php echo Text::_('COM_DPE_NOT_APLLICABLE')?>
								</td>
							<?php
							}
						}

						if ($jlikeTjlmslessonPlugin->get('practice_interaction') == '1')
						{
							if ($intractions['practice_interaction'])
							{?>
								<td class="text-center used">
									<?php
									if ($item->used) : ?>
										<i class="fa fa-check-circle-o" aria-hidden="true"></i>
									<?php else : ?>
										<i class="fa fa-circle-o" aria-hidden="true"></i>
									<?php endif; ?>
								</td>
							<?php
							}
							else
							{?>
								<td class="text-center">
									<?php echo Text::_('COM_DPE_NOT_APLLICABLE')?>
								</td>
							<?php
							}
						}

						// Calculate status
						$status = 'incomplete';

						if ($intractions['read_interaction'] && $intractions['practice_interaction'])
						{
							if ($item->read && $item->used)
							{
								$status = 'completed';
							}
						}
						elseif ($intractions['read_interaction'] && !$intractions['practice_interaction'])
						{
							if ($item->read)
							{
								$status = 'completed';
							}
						}
						elseif (!$intractions['read_interaction'] && !$intractions['practice_interaction'])
						{
							$status = Text::_('COM_DPE_NOT_APLLICABLE');
						}

						/* Get lesson_status and score by attempts grading
						$statusandscore = $this->tjlmsLessonHelper->getLessonScorebyAttemptsgrading($item->element_id, $item->assigned_to);
						$item->status = (empty($statusandscore) || $statusandscore->lesson_status == 'not_started') ? $item->status : $statusandscore->lesson_status;

						if ($item->status)
						{
							if ($item->status === "completed")
							{
								$status = Text::_('COM_DPE_LESSON_COMPLTED_STATUS');
							}
							elseif($item->status === "incomplete")
							{
								$status = Text::_('COM_DPE_LESSON_INCOMPLTED_STATUS');
							}
						}
						*/
						?>
						<td class="<?php  echo $status; ?>">
							<?php echo $status; ?>
						</td>

					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php } else { ?>
			<div class="clearfix">&nbsp;</div>
			<div class="alert alert-info"><?php echo Text::_("COM_DPE_NO_RECORDS_FOUND");?></div>
		<?php } ?>

		<div class="pager" id="pagination">
			<?php echo $this->pagination->getPagesLinks(); ?>
<!--
			<hr class="hr hr-condensed"/>
-->
		</div><!--row-fluid-->
		<input type="hidden" name="task" id="task" value="" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />

		<?php echo HTMLHelper::_('form.token'); ?>
	</form>
</div>
