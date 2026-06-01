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

$olUser       = Factory::getUser();
$olUserId     = $olUser->get('id');

$jinput = Factory::getApplication()->input;
$jinput->set('tmpl', 'component');
?>
<div class="tjlms-wrapper tjBs3">
<form action="" method="post" name="adminForm" id="adminForm" class='form-validate'>

	<div class="container-fluid">
		<div id="tmt_test" name="tmt_test">
			<?php if (empty($this->submissions)) : ?>
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
										<?php echo Text::_('COM_TJLMS_LESSON_ASSESSMENT_REVIEWER'); ?>
									</th>

									<th class="center border-top-blue greyish">
										<?php echo Text::_('COM_TJLMS_LESSON_NAME'); ?>

									</th>
									<th class="center border-top-red greyish">
										<?php echo Text::_('COM_TJLMS_ATTEMPT'); ?>
									</th>

									<th class="center border-top-red greyish">
										<?php echo Text::_('COM_TJLMS_SCORE'); ?>
									</th>
									<th class="center border-top-blue greyish">
										<?php echo Text::_( 'COM_TJLMS_LESSON_STATUS' ); ?>
									</th>
									<th class="center border-top-blue greyish">
										<?php echo Text::_( 'COM_TJLMS_ASSESSMENTS_ACTION' ); ?>
									</th>
								</tr>
							</thead>
							<tbody>
							<?php
							foreach ($this->submissions as $review_detail):
								$reviewer = Factory::getUser($review_detail->reviewer_id);

								/*$assessor_detail = 0;
								if ($review_detail->reviewer_id)
								{
									$assessor_detail = Factory::getUser($review_detail->reviewer_id);
								}*/

								?>
								<tr>
									<td class=center>
									<?php
										echo $reviewer->name;
									?>
									</td>
									<td class=center>
									<?php
										echo $this->lesson->title;
									?>
									<input type='hidden' name='lesson_id' value='<?php echo $review_detail->lessonId;?>'>
									</td>
									<td class=center>
									<?php
										echo $this->lessonTrack->attempt;
									?>
									</td>

									<!--td class=center>
									<?php
										echo $date = HTMLHelper::date($review_detail->created_date , 'F - j - Y g:i a', true);
									?>
									</td-->

									<td class=center>
									<?php

										echo intval($review_detail->score);
									?>
									</td>
									<td class=center>
									<?php
									if ($this->lesson->passing_marks <= $review_detail->score)
										echo Text::_("COM_TJLMS_LESSON_STATUS_PASSED");
									else
										echo Text::_("COM_TJLMS_LESSON_STATUS_FAILED");
									?>
									</td>
									<td class=center>
									<?php
										$slink = 'index.php?option=com_tjlms&view=assesslesson&reviewId='. $review_detail->id .'&tmpl=component';
										$link = $this->tjlmshelperObj->tjlmsRoute($slink, false);
									?>
										<a class="" href="<?php echo $link ?>" >
										<?php

										if ($olUser->authorise('core.assessment.editown', 'com_tjlms.course.' . (int) $review_detail->courseId) || $olUser->authorise('core.assessment.editall', 'com_tjlms.course.' . (int) $review_detail->courseId))
												{
													echo Text::_('COM_TJLMS_ASSESSMENTS_EDIT_SUBMISSION');
												}
												else
												{
													echo
													Text::_('COM_TJLMS_ASSESSMENTS_VIEW_SUBMISSION');

												}
										?>
										</a>
									</td>
								</tr>
							<?php
							endforeach;	?>
							</tbody>
						</table>
					</div>
			</div>
		<?php	endif ;?>
		</div>
	</div>
</form>
</div>
