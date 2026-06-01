<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$jinput = JFactory::getApplication()->input;
$jinput->set('mode', 'preview');
$jinput->set('tmpl', 'component');

// Get lesson data
$lesson_data = $this->lesson;

// To show assessMent params div
$mainDivClass = '';

if ($lesson_data->format != 'quiz')
{
	$mainDivClass = "col-sm-8";
}

// To get User name(who submit the assessment)

if (!empty($this->lessonTrack->user_id))
{
	$userInfo  = JFactory::getUser($this->lessonTrack->user_id);
}

$lesson_url = $this->tjlmshelperObj->tjlmsRoute("index.php?option=com_tjlms&view=lesson&lesson_id=" . $lesson_data->id . "&tmpl=component&lessonscreen=1",false);
$options['relative'] = true;
JHtml::stylesheet('com_tmt/tmt.css', $options);
JHtml::stylesheet('com_tjlms/jlike.css', $options);
JHtml::_('stylesheet', 'components/com_tmt/assets/css/jquery.countdown.min.css');

JHtml::_('script', 'com_tjlms/tjlms.js', $options);
JHtml::_('script', 'com_tjlms/tjService.js', $options);
?>
<!-- Container div-->
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?> com_tjlms_content assesslesson tjBs3">
	<div class="tjlms-lesson container-fluid mt-45" data-js-attr='tjlms-lesson'>
		<div class="fixed-top">
			<div id="jlikeToolbar">
				<div class="row" data-js-attr="tjlms-lesson__toolbar-container">
					<div class="col-sm-12">
						<span class="ml-10 pull-left font-bold jlike-container">
							<h3 class="mt-10"><?php echo JText::sprintf('COM_TJLMS_HEADING_FOR_ASSESSMENTS', htmlentities($this->lessonAssessment->lesson_title));
							if ($this->lessonAssessment->assessment_student_name)
							{
							?>
								<small>
								<?php
								echo JText::sprintf('COM_TJLMS_ASSESSMENT_SUBMITTED_USER_NAME_', htmlentities($userInfo->name));
								?>
								</small>
								<?php
							}
							?>
							</h3>
						</span>

						<div class="text-right jlikeToolbar__buttons" id="jlike_toolbar_buttons">
							<div class="d-inline-block">
								<span data-ref="jliketoolbar-menu" class="hidden toolbar_buttons">
									<i class="fa fa-bars"></i>
								</span>

								<span data-js-attr="jlikeToolbar-close" class="toolbar_buttons closeBtn" title="Close">
									<i class="fa fa-close"></i>
								</span>
							</div>
						</div>
					</div>
				
				</div>
			</div>
		</div>
		<div class="row">
			<div class="tjlms_lesson__player tjlms-lesson-player col-xs-12 p-0 <?php echo $mainDivClass; ?>">
				<div id="tjlms-lesson-content" class="tjlms-lesson-content container-bottom">
					<!-- main container changes -->
					<div id="lesson-main-container" class="lesson-toggle-main lesson-toggle-transition expanded <?php echo !empty($this->lesson_assess_data) && $lesson_data->format != 'quiz' ? 'span9' : '' ?>">
						<div class="main-lesson tjlms-lesson-player">
							<!-- Lesson format-->
							<?php
							if($lesson_data->format !='exercise' && $lesson_data->format != 'quiz' )
							{
								$link = $this->tjlmshelperObj->tjlmsRoute(JURI::root() . "index.php?option=com_tjlms&view=lesson&tmpl=component&lesson_id=" . $lesson_data->id . "&mode=preview");
								?>
								<iframe data-js-attr='tjlms-lesson-iframe' src= "<?php echo $link; ?>" height="100%;" width="100%" frameborder="0">
								</iframe>
						<?php
							}
							else
							{
								if($lesson_data->format == 'quiz') : ?>
									<form class="<?php echo isset($this->trackReviews->review_status) ? $this->trackReviews->review_status : ''; ?>" id="assessment-form" method="post">
								<?php endif;

									echo $this->loadTemplate("answersheet");

								if($lesson_data->format == 'quiz') : ?>
										
										<input type="hidden" name="gradingtype" value="<?php echo $this->item->gradingtype;?>" />
										<input type="hidden" id="invite_id" name="ltId" value="<?php echo $this->ltId;?>" />
										<input name="reviewId" type="hidden" value="<?php if(isset($this->trackReviews->id)){ echo $this->trackReviews->id;} ?>" />
										<input name="reviewerId" type="hidden" value="<?php if(isset($this->trackReviews->reviewer_id)) {echo $this->trackReviews->reviewer_id; }?>" />

										<?php echo JHtml::_( 'form.token' ); ?>
								
									</form>
								
								<?php endif;
							}
						?>


					</div>
				</div><!--Container div ENDS-->
			</div>
			</div>
		<?php if($lesson_data->format != 'quiz') : ?>

			<div id="assessment-form-container" data-js-id="tjlms-sidebar" class="tjlms-lesson__toolbar-content assessment-toggle-main col-sm-4 col-xs-12 p-0 border-0">
				<?php
				$layout  = new JLayoutFile('assessment.assessment_form');
				echo $layout->render(array('lessonTrack'=>$this->lessonTrack, 'trackRatings'=>$this->trackRatings,'trackReviews'=>$this->trackReviews, 'lessonAssessment'=>$this->lessonAssessment, 'lesson'=>$this->lesson, 'canAssess' => $this->canAssess, 'canView' => $this->canView, 'canEdit' => $this->canEdit));
				?>
				
			</div><!--assessment form container-->

		<?php endif;?>
				<?php
				//print_r($this->trackReviews);die;
					if(!empty($this->trackReviews->review_status) && ($this->trackReviews->review_status == 'save'))
					{
						if(($courseAssessmentOwnAcl || $globalAssessmentOwnAcl || $globalAssessmentAllAcl || $courseAssessmentAllAcl))
						{	?>
							<div class="assessment_field form-group">
								<div class="form-inline">
									<button class="btn btn-default submitbtn btn-success"><?php echo JText::_('COM_TJLMS_ASSESSMENTS_SAVE');?></button>
								</div>
								<div class="form-inline">
									<button class="btn btn-default submitbtn btn-success"><?php echo JText::_('COM_TJLMS_ASSESSMENTS_SAVE_N_CLOSE');?></button>
								</div>
							</div>
			<?php 		}
					}
					else
					{	?>
						<div class="assessor-bottom-footer m-0 p-10 text-right">

							<?php if (empty($this->trackReviews->id) ||  $this->trackReviews->review_status == 0):?> 
							<div class="d-inline-block">
								<button type="button" class="btn btn-default" onclick="tjlms.assessment.submit(0);"><?php echo JText::_('COM_TJLMS_ASSESSMENTS_DRAFT');?></button>
							</div>
							<?php endif;?>
							
							<?php if ($this->canEdit):?> 
								<div class="d-inline-block">
									<button type="button"  class="btn btn-default btn-primary" onclick="tjlms.assessment.submit(1);"><?php echo JText::_('COM_TJLMS_ASSESSMENTS_SAVE');?></button>
								</div>
							<?php endif;?>

							<div class="d-inline-block">
								<button type="button" class="btn btn-default btn-danger closeBtn"><?php echo JText::_('COM_TJLMS_CLOSE');?></button>
							</div>
						</div>
				<?php	}	?>
		</div>
	</div>
</div>
<script>
var redirectUrl = "<?php echo $this->tjlmshelperObj->tjlmsRoute("index.php?option=com_tjlms&view=assessments");?>";
var onSuceessredirectUrl = "<?php echo $this->tjlmshelperObj->tjlmsRoute("index.php?option=com_tjlms&view=assessments&assess=1");?>";
tjlms.assessment.init(redirectUrl);

//apply class to body only for Quiz view
document.body.classList.add("tmtBody");
</script>

