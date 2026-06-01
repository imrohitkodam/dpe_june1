<?php
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\HTML\HTMLHelper;

$trackRatings = $displayData['trackRatings'];
$trackReviews = $displayData['trackReviews'];
$lessonAssessment = $displayData['lessonAssessment'];
$lesson = $displayData['lesson'];
$lessonTrack = $displayData['lessonTrack'];
$role = '';
$answersheetOptions = new stdClass;

if (isset($displayData['role']) && $displayData['role'] == 'student')
{
	$role = 'student';
	$canAssess = $canEdit = 0;
	$canView = 1;
	$answersheetOptions = json_decode($lessonAssessment->answersheet_options);
}
else
{
	$canAssess = $displayData['canAssess'];
	$canView = $displayData['canView'];
	$canEdit = $displayData['canEdit'];
}

$assessmentParams = $lessonAssessment->assessmentParams;

$user = Factory::getUser();
$app  = Factory::getApplication();

$score = 0;
$noEditAccess = 'noEditAccess';

if (!$canAssess && !$canView  && !$canEdit)
{
	$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
	$app->setHeader('status', 403, true);

	return false;
}
elseif ($canEdit)
{
	$noEditAccess = '';
}

$score = $i =0 ;
if($trackRatings)
{
	foreach($trackRatings as $ratings)
	{
		$score +=  ($ratings->rating_value * $assessmentParams[$i]->weightage);
		$i++;
	}
}
else
{
	if($trackReviews)
	{
		$score = $trackReviews->score;
	}
}
?>

<form class="<?php echo isset($trackReviews->review_status) ? $trackReviews->review_status : ''; ?> <?php echo $noEditAccess;?>" id="assessment-form" method="post">
	<div class="pt-10 pb-20">
	<div class="quiz_content px-15">
	<div class="assessment-form_msg">
		<div class="alert hide">
		</div>
	</div>
	<div class="assessment-form_score my-10 text-center">
		<label class="mb-0"><strong><?php echo isset($trackReviews->score) ? Text::sprintf('COM_TJLMS_ASSESSMENTS_SCORE', $score) : Text::sprintf('COM_TJLMS_ASSESSMENTS_SCORE', 0);?></strong>
		</label>
	</div>
	<div class="clearfix"></div>
	<?php
		if(!empty($assessmentParams))
		{
			$i = 1;
			foreach ($assessmentParams as $param)
			{	?>
			<div class="assessment_field form-group">
				<div class="has-success">
				<?php
					$layout  = new FileLayout('assessment.fields.' . $param->type, JPATH_SITE . '/components/com_tjlms/layouts');

					if(isset($trackRatings[$param->id]))
					{
						$reviewerRating = $trackRatings[$param->id];
					}
					else
					{
						$reviewerRating = new stdClass;
						$reviewerRating->id = $reviewerRating->rating_comment = $reviewerRating->rating_value = '';
					}
					?>

					<?php if($param->type != 'checkbox') : ?>
					<?php if (!$role || ($role == 'student' && ($answersheetOptions->param_comments == 1 || $answersheetOptions->param_marks == 1))) :?>
					<div class="container-fluid">
						<div class="row">
							<div class="no-gutters">
								<div class="col-sm-10 col-xs-8">
									<div class="d-flex align-items-start">
										<div class="font-bold text-center">
											<div class="assessment-param__number img-circle">
												<span><?php echo $i++;?></span>
											</div>
										</div>
										<div class="assessment-param__title pl-10">
											<span><?php echo htmlentities($param->title);?></span>
										</div>
									</div>
									<?php if(trim($param->description)):?>
										<div class="ml-30 mb-10 pl-10 pt-5">
											<em><?php echo htmlentities($param->description);?></em>
										</div>
									<?php endif;?>
								</div>
								<div class="col-sm-2 col-xs-4">
									<span class="assessment-param__marks p-5 text-center pull-right">
										<?php echo $param->value;
										echo Text::_('COM_TJLMS_ASSESSMENTS_PARAMS_MARKS');?>
									</span>
								</div>
							</div>
						</div>
					</div>
					<?php endif;?>
				<?php else :?>
						<?php $i++;?>
					<?php endif;?>

				<?php if (!$role || ($role == 'student' && $answersheetOptions->param_marks == 1)) :?>
					<?php echo $layout->render(array('reviewerRating'=>$reviewerRating,'param'=>$param, 'index' => $i));?>
				<?php endif;?>

					<?php if($param->allow_comment && !$role || ($role == 'student' && $answersheetOptions->param_comments == 1 && !empty($reviewerRating->rating_comment))) : ?>
						<div class=" form-block">
							<textarea class="assessment-param__comment form-control" rows="3" name="assessmentParams[<?php echo $param->id?>][rating_comment]" placeholder="<?php echo Text::_('COM_TJLMS_ASSESSMENTS_COMMENTS');?>"><?php echo $reviewerRating->rating_comment?></textarea>
						</div>
					<?php endif;?>
					

					<input name="assessmentParams[<?php echo $param->id?>][rating_id]" type="hidden" value="<?php echo $param->id?>" />
					<input name="assessmentParams[<?php echo $param->id?>][review_rating_id]" type="hidden" value="<?php echo $reviewerRating->id?>" />
				</div>
			</div>
			<hr />
	<?php	}
		}
		else
		{
			if($displayData['lesson_data']->format == 'exercise'):?>
				<label><?php echo Text::_('Enter Score');?></label>
				<input name="score" type="text" value="<?php echo isset($trackReviews->score) ? $trackReviews->score : 0; ?>" class='score' data-maxscore="<?php echo $lesson_data->total_marks;?>"/>
				<div class='msg'><?php echo Text::sprintf('COM_TJLMS_ASSESSMENTS_MAX_MARKS',$lesson->total_marks);?></div>
<?php		endif;
		}
		?>

		<input name="ltId" type="hidden" value="<?php echo $lessonTrack->id; ?>" />
		<input name="gradingtype" type="hidden" value="<?php echo $lesson->format; ?>" />
		<input name="reviewId" type="hidden" value="<?php if(isset($trackReviews->id)){ echo $trackReviews->id;} ?>" />
		<input name="reviewerId" type="hidden" value="<?php if(isset($trackReviews->reviewer_id)) {echo $trackReviews->reviewer_id; }?>" />

	<?php if(!$role || ($role == 'student' && $answersheetOptions->feedback == 1)) : ?>
		<div class="assessment_field form-group">
			<label class="d-block text-muted"><?php echo Text::_('COM_TJLMS_ASSESSMENTS_FEEDBACK');?></label>
			<textarea rows="3" class="assessment_feedback form-control" name="feedback"><?php echo isset($trackReviews->feedback) ? $trackReviews->feedback : ''; ?></textarea>
		</div>
	<?php endif; ?>

	<?php if($lessonAssessment->allow_attachments):	?>
		<input type="file" name="jform[attachments]" />
	<?php endif; ?>
	<?php echo HTMLHelper::_( 'form.token' ); ?>
	</div>
	</div>
</form>

