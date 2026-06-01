<?php
/**
 * @version     1.0.0
 * @package     com_tmt
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Techjoomla <contact@techjoomla.com> - http://techjoomla.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.modal', 'a.tjmodal');

$olUser       = Factory::getUser();
$olUserId     = $olUser->get('id');

$listOrder	= $this->state->get('list.ordering');
$listDirn	= $this->state->get('list.direction');
?>
<div class="tjlms-wrapper tjBs3">
<div class="row">
		<h2><?php echo Text::_("COM_TJLMS_ASSESSMENTS") ; ?></h2>
	</div>
<form action="" method="post" name="adminForm" id="adminForm" class='form-validate'>
	<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));?>
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />

	<div class="container-fluid">
		<div id="tmt_test" name="tmt_test">
			<?php if (empty($this->items)) : ?>
				<div class="row">
					<div class="alert alert-no-items">
						<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS');?>
					</div>
				</div>
			<?php else: ?>
			<div class="row">
					<div class="tjlms-tbl">
						<table class="table table-bordered tjlms-table tbl-align" width="100%">
							<thead>
								<tr>
									<th class="center border-top-blue greyish">
										<?php echo Text::_('COM_TJLMS_STUDENT_NAME'); ?>
									</th>
									<th class="center border-top-blue greyish">
										<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_COURSE_NAME_LABEL', 'c.title', $listDirn, $listOrder); ?>
									</th>
									<th class="center border-top-blue greyish">
										<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_LESSON_NAME', 'l.title', $listDirn, $listOrder); ?>
									</th>
									<th class="center border-top-red greyish">
										<?php echo Text::_('COM_TJLMS_ATTEMPT'); ?>
									</th>
									<th class="center border-top-red greyish">
									 	<?php echo Text::_('COM_TJLMS_ASSESSMENTS_NEEDED'); ?>
									</th>
									<th class="center border-top-red greyish">
										<?php echo Text::_('COM_TJLMS_ASSESSMENTS_DONE'); ?>
									</th>
									<th class="center border-top-red greyish">
										<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_SCORE', 'lt.score', $listDirn, $listOrder); ?>
									</th>
									<th class="center border-top-red greyish">
										<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ASSESSMENT_START_DATE', 'lt.timestart', $listDirn, $listOrder); ?>
									</th>
									<th class="center border-top-red greyish">
										<?php echo HTMLHelper::_('grid.sort',  'COM_TJLMS_ASSESSMENT_END_DATE', 'lt.timeend', $listDirn, $listOrder); ?>
									</th>
									<th class="center border-top-blue greyish">
										<?php echo Text::_( 'COM_TJLMS_LESSON_STATUS' ); ?>
									</th>
									<th class="center border-top-blue greyish">
										<?php echo Text::_( 'COM_TJLMS_ASSESSMENTS_ACTION' ); ?>
									</th>
									<!--th class="center border-top-blue greyish">
										<?php echo Text::_('COM_TJLMS_ASSESSED_BY'); ?>
									</th-->
								</tr>
							</thead>
							<tbody>
							<?php
							foreach ($this->items as $review_detail):

								/*$assessor_detail = 0;
								if ($review_detail->reviewer_id)
								{
									$assessor_detail = JFactory::getUser($review_detail->reviewer_id);
								}*/

								?>
								<tr>
									<td class=center>
									<?php
										echo $review_detail->user_name;
									?>
									</td>
									<td class=center>
									<?php
										echo $review_detail->courseTitle;
									?>
									<input type='hidden' name='course_id' value='<?php echo $review_detail->courseId;?>'>
									</td>
									<td class=center>
									<?php
										echo $review_detail->title;
									?>
									<input type='hidden' name='lesson_id' value='<?php echo $review_detail->lessonId;?>'>
									</td>
									<td class=center>
									<?php
										echo $review_detail->attempt;
									?>
									</td>
									<td class=center>
									<?php
										echo $review_detail->lessonAssessment->assessment_attempts;
									?>
									</td>
									<td class=center>
									<?php
									if ($olUser->authorise('core.assessment.editall', 'com_tjlms.course.'
											. (int) $review_detail->courseId) && $review_detail->livetrackReviews) : ?>

										<?php
										$slink = 'index.php?option=com_tjlms&view=assesslesson&layout=submissions&ltId='. $review_detail->lessonTrackId . "&tmpl=component";
											$link = $this->comtjlmsHelper->tjlmsRoute($slink, false);
										?>
										<a class="tjmodal" href="<?php echo $link ?>" >
											<?php echo $review_detail->livetrackReviews;?>
										</a>

									<?php else : ?>

										<?php echo $review_detail->livetrackReviews;?>

									<?php endif;?>

									</td>
									
									<td class=center>
									<?php
										echo $review_detail->score;
									?>
									</td>
									<td class=center>
									<?php
										echo HTMLHelper::date($review_detail->timestart, Text::_('DATE_FORMAT_LC2'));
									?>
									</td>
									<td class=center>
									<?php
										echo HTMLHelper::date($review_detail->timeend, Text::_('DATE_FORMAT_LC2'));
									?>
									</td>
									<td class=center>
									<?php
										echo $review_detail->lesson_status;
									?>
									</td>
									<td class=center>
										<?php
											$review_status = 0;

											if($review_detail->modified_by != 0){
												$review_status = 1;
											}


											$slink = 'index.php?option=com_tjlms&view=assesslesson&ltId='. $review_detail->lessonTrackId .'&tmpl=component';
											$link = $this->comtjlmsHelper->tjlmsRoute($slink, false);

										?>
										<a class="" href="<?php echo $link ?>" >
											<?php
											if (isset($review_detail->trackReview->id))
											{
												$myReview = $review_detail->trackReview;

												if ($olUser->authorise('core.assessment.editown', 'com_tjlms.course.'
												. (int) $review_detail->courseId)
												|| $myReview->review_status
												== 0 || $olUser->authorise('core.assessment.editall', 'com_tjlms.course.'
												. (int) $review_detail->courseId))
												{
													echo Text::_('COM_TJLMS_ASSESSMENTS_EDIT_SUBMISSION');
												}
												else
												{
													echo
													Text::_('COM_TJLMS_ASSESSMENTS_VIEW_SUBMISSION');

												}
											}
											else if($review_detail->livetrackReviews < $review_detail->lessonAssessment->assessment_attempts)
											{
												echo Text::_('COM_TJLMS_ASSESSMENTS_SUBMIT_ASSESSMENT');
											}
											/*
												if (($review_detail->review_status == 'save')  || ($review_status == 1))
												{
													echo Text::_('COM_TJLMS_ASSESSMENTS_ASSESSED');
												}
												else
												{
													echo Text::_('COM_TJLMS_ASSESSMENTS_NOT_ASSESSED');
												}
											*/?>
										</a>
									</td>
								</tr>
							<?php
							$assessor_detail = NULL;
							endforeach;	?>
							</tbody>
						</table>
					</div>
			</div>
		<?php	endif ;?>
			<div class="pager">
				<?php echo $this->pagination->getPagesLinks(); ?>
				<hr class="hr hr-condensed"/>
			</div><!--row-fluid-->
		</div>
	</div>
</form>
</div>
