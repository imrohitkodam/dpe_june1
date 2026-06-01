<?php
/**
 * @package     Shika
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::_('behavior.modal');
HTMLHelper::_('jquery.framework');
HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.formvalidation');

JLoader::import("/techjoomla/tjfileviewer/fileviewer", JPATH_LIBRARIES);

$params    = ComponentHelper::getParams('com_tjlms');
$document  = Factory::getDocument();
$app       = Factory::getApplication();
$testid    = $app->input->get('id');
$flag      = $app->input->get('flag');
$candid_id = $app->input->get('candid_id');
$document->addStyleSheet(Uri::root() . 'media/com_tjlms/css/tjlms.min.css');
$document->addStyleSheet(Uri::root() . 'media/com_tmt/css/print.css');

$options['relative'] = true;
HTMLHelper::stylesheet('com_tmt/tmt.css', $options);
HTMLHelper::stylesheet('com_tjlms/jlike.css', $options);
HTMLHelper::_('stylesheet', 'components/com_tmt/assets/css/jquery.countdown.min.css');
HTMLHelper::_('script', 'com_tjlms/tjlms.js', $options);
HTMLHelper::_('script', 'com_tjlms/tjService.js', $options);

// Load admin language file
$lang = Factory::getLanguage();
$lang->load('com_tmt', JPATH_SITE);

$fromAssessment   = 0;
$answersheetClass = "col-sm-8 col-sm-offset-2 col-xs-12";

if (!empty($this->fromAssessment) && $this->fromAssessment == 1)
{
	$fromAssessment   = 1;
	$answersheetClass = "";
}

if (!empty($this->showAssessments) && $this->showAssessments == 1 && !empty($this->submissions))
{
	$answersheetClass = "col-sm-8";
}

?>

<script type="text/javascript">
Joomla.submitbutton = function(task) {
	if(task=='answersheet.backToReviewedpapers'  || task=='answersheet.backToCandihistory' || 'answersheet.getStepPageData' ){
		Joomla.submitform(task);
	}
}

function PrintElem(divId) {
	jQuery('.drive-inline-preview').hide();
	var printContents       = document.getElementById(divId).innerHTML;
	var originalContents    = document.body.innerHTML;
	document.body.innerHTML = printContents;
	window.print();
	document.body.innerHTML = originalContents;
}

function setUrl(url) {
	jQuery('#rUrl').attr('value',url);
}
</script>

<div id="tmt_test" name="tmt_test" class="tjBs3">
<div class="container-fluid">
	<?php if (($this->lessonTrack->lesson_status == 'passed' || $this->lessonTrack->lesson_status == "failed") && $fromAssessment == 0) : ?>
		<div class="row">
			<div id="jlikeToolbar" class="test-header">

				<div id="jlikeToolbar_content" class="d-flex justify-content-between align-items-center p-5 inline-print">
						<!-- set componentheading -->
							<div class="toolbar_heading hidden-xs text-center p-mr-30">
								<?php echo Text::_('COM_TMT_ANSWERSHEET_TEST_NAME') . ' - "' . $this->item->title . '"';?>
							</div>
							<?php if ($this->item->gradingtype != 'feedback') : ?>
								<div class="font-bold text-center p-mr-30">
									<span><?php echo Text::_('COM_TMT_ATTEMPTREPORT_SCORE');?>:</span>
									<span><?php echo $this->lessonTrack->score;?> /</span>
									<span><?php echo $this->item->total_marks;?></span>
								</div>
							<?php endif;?>
							<div class="font-bold text-center">
								<span><?php echo Text::_('COM_TMT_TEST_APPEAR_TIME_TAKEN');?>: </span>
								<span><?php echo $this->time_taken_format; ?></span>
							</div>
							<?php
							if ($this->item->show_correct_answer)
							{
								?>
								<div class="hidden-xs hidden-print">
									<span class="hidden-print"><i class="fa fa-check alert-primary"></i>: <?php echo Text::_('COM_TMT_ATTEMPTREPORT_QUESTION_CORRECT_MARK_LBL');?>
									</span>
								</div>
								<?php
							}

						if ($this->item->print_answersheet || $this->isAdmin)
						{
						?>
							<div class="tmt-cross-container hidden-print">
								<button type="button" class="btn btn-primary hidden-print" onclick="PrintElem('tmt_test')">
									<span class="icon-print"></span>&#160;<?php echo Text::_('COM_TMT_PRINT'); ?>
								</button>
							</div>
						</div>
					<?php } ?>
					</div>
				</div>
			</div>
	<?php endif; ?>
		<div id="printest" name="printest">
			<!-- set componentheading -->
			<div class="quiz_content row py-30" id="quiz_content">
			<?php if ($fromAssessment == 1 && $this->item->gradingtype == 'quiz') : ?>

				<div class="col-sm-12 tjBs3">
					<div class="assessment-form_msg">
						<div class="alert hide">
						</div>
					</div>
					<div class="assessment-form_score my-10 text-center">
						<label class="mb-0"><strong><?php echo isset($this->trackReviews->score) ? Text::sprintf('COM_TJLMS_ASSESSMENTS_SCORE', $this->trackReviews->score) : Text::sprintf('COM_TJLMS_ASSESSMENTS_SCORE', 0);?></strong>
						</label>
					</div>
				</div>

			<?php endif; ?>
				<div class="col-sm-12 tjBs3">
					<!-- show form/items if items found -->

					<?php
					if(! empty ($this->item->questions) ){
						?>
						<div id="print_questions_container"  class="<?php echo $answersheetClass;?>">
							<div class="questions_container pt-20 pl-20 pr-20 mb-40">
								<div class="inline-preview">
									<button type="button" class="btn btn-default btn-primary pull-right drive-inline-preview-btn hide"><?php echo Text::_('COM_TMT_QUESTION_FILE_TYPE_INLINE_PREVIEW_TOGGLE'); ?></button>
								</div>
								<div class="clearfix"></div>
								<br>
							<?php
							$question_ids = array();
							$i            = 1;
							$totalScore   = 0;

							$showInlinePreviewBtn = false;

							foreach($this->item->questions as $q){
								$scoreMark      = 0;
								$qcorrect_txt   = '';
								$question_ids[] = $q->question_id;
								$markstype      = "hidden"; $classHide = "hide"; $fieldClass = "";

								if(in_array($q->type, array('text', 'textarea', 'file_upload', 'rating')) && $this->item->gradingtype == 'quiz')
								{
									$markstype  = "text";
									$classHide  ="";
									$fieldClass ="assessment_field";
								}

								if ($q->type == 'file_upload')
								{
									$showInlinePreviewBtn = true;
								}

								?>

								<div class="well well-small <?php echo $fieldClass;?>">
									<div class="row">
										<?php $class = ($this->item->gradingtype == 'quiz' ? 'col-sm-12' : 'col-sm-12'); ?>

										<div class="<?php echo $class;?> col-xs-12">
											 <!-- Text:: is used in multilingual sites -->
											<strong><?php echo $i . '. ' . nl2br(Text::_(trim($q->title))); ?>
											<?php
												if ($q->is_compulsory)
												{
											?>
												<span class="star">*</span>
											<?php
												}
											?>
											</strong>
											<br/>
											<!--  Text:: is used in multilingual sites -->
											<em class="ml-15"> <?php echo nl2br(Text::_(trim($q->description))); ?></em>
										</div>

										<div class="col-xs-12 col-sm-6">
											<?php
											// Use layouts to render media elements
											if (!empty($q->media_id))
											{
												$original_media_type = $q->media_type;

												if (strpos($q->media_type, 'video') !== false)
												{
													$q->media_type = 'video';
												}
												elseif(strpos($q->media_type, 'image') !== false)
												{
													$q->media_type = 'image';
												}
												elseif(strpos($q->media_type, 'audio') !== false)
												{
													$q->media_type = 'audio';
												}
												else
												{
													$q->media_type = 'file';
												}

												$layout = new FileLayout($q->media_type, JPATH_ROOT . '/components/com_tmt/layouts/media');
												$mediaData                      = array();
												$mediaData['media']             = $q->source;
												$mediaData['mediaUploadPath']   = $this->mediaLib->mediaUploadPath;
												$mediaData['originalMediaType'] = $original_media_type;
												$mediaData['originalFilename']  = $q->original_filename;
												$mediaData['media_type']        = $q->media_type;

												echo $layout->render($mediaData);
											}
											?>
										</div>

									<?php if ($this->item->gradingtype == 'quiz'): ?>
										<?php
										if ($fromAssessment == 1 && in_array($q->type, array('file_upload', 'rating', 'text', 'textarea')))
										{
											$scoreMark = $q->userMarks;

											if($this->reviewStatus == 0)
											{
												if ($q->userAnswer == '' || $q->userAnswer == '-')
												{
													$qcorrect_txt = '<span style=" margin-left: 3px; " class="label label-info">' . Text::_('COM_TMT_TEST_APPEAR_QUESTION_NOT_ATTEMPTED') . '</span>';
												}
												else
												{
													$qcorrect_txt = '<span style=" margin-left: 3px; " class="label label-warning">' . Text::_('COM_TMT_TEST_APPEAR_QUESTION_NOT_REVIEWED') . '</span>';
												}
											}

											if ($q->userMarks == 0 && $this->reviewStatus == 1)
											{
												$qcorrect_txt = '<span style=" margin-left: 3px; " class="label label-danger">' . Text::_('COM_TMT_TEST_APPEAR_QUESTION_WRONG') . '</span>';
											}
											elseif ($q->userMarks > 0 && $this->reviewStatus == 1)
											{
												$qcorrect_txt = '<span style=" margin-left: 3px; " class="label label-success">' . Text::_('COM_TMT_TEST_APPEAR_QUESTION_CORRECT') . '</span>';
											}
										}
										else
										{
											if(!empty($q->userAnswer) && !empty($q->correct))
											{
												if($q->type == 'checkbox')
												{
													$qanswer = array();
													$qanswer = $q->userAnswer;

													foreach($qanswer as $uans)
													{
														foreach((array) $q->answers as $qans)
														{
															if($qans->id == $uans && $qans->is_correct == 1)
															{
																$scoreMark = $scoreMark + $qans->marks;
															}
														}
													}

													$totalScore = $totalScore + $scoreMark;
												}
												elseif (in_array($q->type, array('file_upload', 'rating', 'text', 'textarea')))
												{
													$scoreMark = $q->userMarks;
												}
												else
												{
													$scoreMark  = $q->userMarks;
													$totalScore = $totalScore + $scoreMark;
												}

												if ($q->correct && $q->marks == $q->userMarks)
												{
													$qcorrect_txt = '<span style=" margin-left: 3px; " class="label label-success">'.Text::_('COM_TMT_TEST_APPEAR_QUESTION_CORRECT') . '</span>';
												}
												else
												{
													$qcorrect_txt = '<span style=" margin-left: 3px; " class="label label-warning">'.Text::_('COM_TMT_TEST_APPEAR_QUESTION_PARTIAL_CORRECT') . '</span>';
												}
											}
											else
											{
												$scoreMark = 0;
												$qcorrect_txt = '<span style=" margin-left: 3px; " class="label label-danger">' . Text::_('COM_TMT_TEST_APPEAR_QUESTION_WRONG') . '</span>';

												if ($q->userAnswer == '' || $q->userAnswer == '-' || (is_array($q->userAnswer) && empty($q->userAnswer[0]) && ($q->type == 'checkbox')))
												{
													$qcorrect_txt = '<span style=" margin-left: 3px; " class="label label-info">' . Text::_('COM_TMT_TEST_APPEAR_QUESTION_NOT_ATTEMPTED') . '</span>';
												}
												elseif ($this->lessonTrack->lesson_status == "AP" && in_array($q->type, array('file_upload', 'rating', 'text', 'textarea')))
												{
													$qcorrect_txt = '<span style=" margin-left: 3px; " class="label label-warning">' . Text::_('COM_TMT_TEST_APPEAR_QUESTION_NOT_REVIEWED') . '</span>';
												}
											}
										}
										?>

											<div class="col-xs-12 col-sm-6">
												<div class="text-center tj_textpullright">
												<strong style=" ">
													<?php
													if($q->marks > 1)
														echo $scoreMark . " / " . $q->marks . " " . Text::_('COM_TMT_TEST_APPEAR_QUESTION_MARKS_TXT') ;
													else
														echo $scoreMark . " / " . $q->marks . " " . Text::_('COM_TMT_TEST_APPEAR_QUESTION_MARK_TXT') ;
													?>
												</strong>
												<?php echo $qcorrect_txt ?>
											</div>
										</div>

									<?php endif;?>

										</div><!--row-fluid-->

										<div class="row review-answer test-question__answers" data-js-id="test-review-question">
											<div class="col-xs-12 test-question__answers-options-<?php echo $q->type; ?>"><?php
	;
												switch ($q->type)
												{
													case "radio":
														$comments       = '';
														$no_ans_comment = '';
														foreach($q->answers as $a)
														{
															$checked = "";

															if(!empty($q->userAnswer) && in_array($a->id, $q->userAnswer, true))
															{
																$checked  = 'checked';
																$comments = $a->comments;
															}?>
															<div class="col-xs-12 col-md-6">
																<label class="radio input-label mb-20 d-block">
																	<input type="radio" name="questions[mcqs][<?php echo $q->question_id;?>][]" id="questions<?php echo $q->question_id;?>" value="<?php echo $a->id;?>" <?php echo $checked;?> disabled />

																	<!-- Text:: is used in multilingual sites -->
																	<?php echo Text::_(trim($a->answer));?>

																	<?php if($this->item->show_correct_answer && $a->is_correct && $this->item->gradingtype == 'quiz'): ?>

																		<i class="fa fa-check"></i>&#160;

																	<?php endif;?>

																	<?php
																		if(!empty($q->userAnswer) && in_array($a->id, $q->userAnswer, true) && $comments)
																			{ ?>
																				<div class="result-top comments" >
																					<span>
																						<i class="fa fa-info-circle fa_comments"></i>
																						<i>
																							<!-- Text:: is used in multilingual sites -->
																							<?php echo Text::_(trim($comments)); ?>
																						</i>
																					</span>
																				</div>
																			<?php } ?>

																	<span class="radiobtn"></span>
																</label>

																<?php
																// Use layouts to render media elements
																if (!empty($a->media_id))
																{
																	$original_media_type = $a->media_type;

																	if (strpos($a->media_type, 'video') !== false)
																	{
																		$a->media_type = 'video';
																	}
																	elseif(strpos($a->media_type, 'image') !== false)
																	{
																		$a->media_type = 'image';
																	}
																	elseif(strpos($a->media_type, 'audio') !== false)
																	{
																		$a->media_type = 'audio';
																	}
																	else
																	{
																		$a->media_type = 'file';
																	}

																	$layout = new FileLayout($a->media_type, JPATH_ROOT . '/components/com_tmt/layouts/media');
																		$mediaData                      = array();
																		$mediaData['media']             = $a->source;
																		$mediaData['mediaUploadPath']   = $this->mediaLib->mediaUploadPath;
																		$mediaData['originalMediaType'] = $original_media_type;
																		$mediaData['originalFilename']  = $a->original_filename;
																		$mediaData['media_type']        = $a->media_type;

																	echo $layout->render($mediaData);
																}
																?>
															</div>
													<?php

														}
													?>

												<?php
													break;

													case "checkbox":
														$comments = '';

														foreach($q->answers as $a)
														{
															$checked = '';

															if (!empty($q->userAnswer) && in_array($a->id, $q->userAnswer, true))
															{
																$checked = 'checked';
																if ($a->comments)
																$comments = $a->comments;
															}

															?>
															<div class="col-xs-12 col-md-6">
																<label class="checkbox input-label mb-20 d-block">
																	<input type="checkbox" name="questions[mcqs][<?php echo $q->question_id;?>][]" id="questions<?php echo $q->question_id;?>" value="<?php echo $a->id;?>" <?php echo $checked;?> disabled />

																	<!-- Text:: is used in multilingual sites -->
																	<?php echo Text::_(trim($a->answer));?>

																	<?php if($this->item->show_correct_answer && $a->is_correct && $this->item->gradingtype == 'quiz') : ?>

																				<i class="fa fa-check"></i>&#160;

																	<?php endif;?>
																	<?php
																		if(!empty($q->userAnswer) && in_array($a->id, $q->userAnswer, true) && $comments)
																			{ ?>
																				<div class="result-top comments" >
																					<span>
																						<i class="fa fa-info-circle fa_comments"></i>
																						<i>
																							<!-- Text:: is used in multilingual sites -->
																							<?php echo Text::_(trim($comments)); ?>
																						</i>
																					</span>
																				</div>
																	<?php } ?>
																	<span class="checkmark"></span>
																</label>

																<?php
																// Use layouts to render media elements
																if (!empty($a->media_id))
																{
																	$original_media_type = $a->media_type;

																	if (strpos($a->media_type, 'video') !== false)
																	{
																		$a->media_type = 'video';
																	}
																	elseif(strpos($a->media_type, 'image') !== false)
																	{
																		$a->media_type = 'image';
																	}
																	elseif(strpos($a->media_type, 'audio') !== false)
																	{
																		$a->media_type = 'audio';
																	}
																	else
																	{
																		$a->media_type = 'file';
																	}

																	$layout = new FileLayout($a->media_type, JPATH_ROOT . '/components/com_tmt/layouts/media');
																	$mediaData                      = array();
																	$mediaData['media']             = $a->source;
																	$mediaData['mediaUploadPath']   = $this->mediaLib->mediaUploadPath;
																	$mediaData['originalMediaType'] = $original_media_type;
																	$mediaData['originalFilename']  = $a->original_filename;
																	$mediaData['media_type']        = $a->media_type;

																	echo $layout->render($mediaData);
																}
																?>
															</div>
													<?php } ?>

											<?php
														break;

														case "text" :
															echo htmlentities($q->userAnswer);
														break;
														case "objtext" :
															echo htmlentities($q->userAnswer);
														break;
														case "textarea":
															echo htmlentities($q->userAnswer);
														break;

														case "file_upload":

															if(!empty($q->userAnswer)) :

																foreach ($q->userAnswer as $userAnswer)
																{ ?>
																	<div class="col-sm-6" data-js-id="each-file" data-js-itemid="<?php echo $userAnswer->media_id;?>" data-js-answerid="<?php echo $q->userAnswerId ?>">
																		<a href="<?php echo $userAnswer->path;?>" target="_blank">
																			<?php echo $userAnswer->org_filename;?>
																			<i class="fa fa-download" aria-hidden="true"></i>
																		</a>
																	</div>

																	<?php

																	$iframeAttribs = array (
																		'class' => 'drive-inline-preview',
																		'style' => 'width:100%; height:500px;',
																		'frameborder' => '0'
																	);

																	$getMediaViwer = $params->get('media_viewer');

																	echo TJFileViewer::_($userAnswer->mediaTimelyUrl, $getMediaViwer, 'drive-inline-preview', $iframeAttribs);
																}
																?>

														<?php endif;
														break;

														case "rating":

														$a     = $q->answers['0']->answer;
														$limit = $q->answers['1']->answer;

														$qParams = new Registry;
														$qParams->loadString($q->params);

														$ratinglabels = array();
														if ($qParams->get('rating_label'))
														{
															$ratinglabels = explode(',', $qParams->get('rating_label'));
														}

														$checked = '';
														if(!empty($q->userAnswer))
														{
															$checked = 'checked';
														}	?>
														<div class="table-responsive my-20">
															<table class="table">
																<thead>
																	<tr>
																	<?php
																	if (!empty($ratinglabels))
																	{
																		foreach ($ratinglabels as $key => $value)
																		{
																			?>
																			<th class="center">
																				<?php echo JText::_(trim($value)); ?>
																			</th>
																			<?php
																		}
																	}
																	else
																	{
																		for($j = (int)$a; $j<= (int)$limit; $j++)
																		{	?>
																			<th class="center"><?php echo $j; ?></th>
																	<?php
																		}
																	}	?>
																	</tr>
																</thead>
																<tbody>
																	<tr>
																	<?php
																		for($k = (int)$a; $k<= (int)$limit; $k++)
																		{	?>
																		<td class="center">
																			<label class="input-label">
																			<input
																				type="radio"
																				name="questions[rating][<?php echo $q->question_id;?>]"
																				id="questions<?php echo $q->question_id;?>"
																				value="<?php echo $k;?>" <?php if($k == $q->userAnswer){echo $checked;} ?>
																				disabled
																			/>
																			<span class="radiobtn"></span>
																			</label>
																		</td>
																<?php	}	?>
																	</tr>
																</tbody>
															</table>
														</div>

												<?php	break;

														default:
															$q->type=$q->type;
												}//switch ?>
											</div><!--span4-->
								<?php
								if ($fromAssessment == 1 && $this->item->gradingtype == 'quiz') {
								?>
									<div class="<?php echo $classHide;?>">
										<label> <?php echo Text::_('COM_TMT_ENTER_MARKS'); ?> </label>
											<div class="d-inline-block">
												<input  type='<?php echo $markstype;?>' data-js-id="test-review-marks" name="marks[<?php echo $q->question_id;?>]" style="width:75px;" class="textinput validate validate-whole-number" value="<?php echo $q->userMarks;?>" data-maxval="<?php echo $q->marks;?>">
												<input type="hidden" data-js-id="test-review-qmarks"  name="qmarks[<?php echo $q->question_id;?>]" value="<?php echo $q->marks;?>">
										</div>
									</div>
									<div data-js-id="test-review-msg" class="hide msg">
										<div class="alert alert-error">
											<?php echo Text::_('COM_TMT_VALID_MARKS'); ?>
										</div>
									</div>

							<?php } ?>

								</div><!--row-fluid-->
							</div><!--row-fluid-->
						<?php $i++;?>

						<?php

							}//for
							?>
							</div>
						</div>

					<?php if (!empty($this->showAssessments) && $this->showAssessments == 1 && !empty($this->submissions)): ?>
						<div class="col-sm-4 quiz_content">
							<h4><?php echo Text::sprintf("COM_TJLMS_ASSESSEMENT_ASSESSORS", count($this->submissions))?></h4>
							<hr class="tjlms-hr-dark mt-10">
							<div class="panel-group" id="accordion">

							<?php foreach ($this->submissions as $sub): ?>
								<div class="panel panel-default border-0">

									<div class="cursor-pointer panel-heading collapsed border-0" data-jstoggle="collapse" data-target="#collapse_<?php echo $sub->id;?>" aria-expanded="false">
										<h5 class="panel-title accordion-toggle">
											<a class="d-inline-block">
												<i class="fa fa-book" aria-hidden="true"></i>
												<span><?php echo Text::sprintf("COM_TJLMS_ASSESSEMENT_DONE_BY", Factory::getUser($sub->reviewer_id)->name)?></span>
											</a>
										</h5>
									</div>
									<div id="collapse_<?php echo $sub->id;?>" class="panel-collapse collapse">
										<div class="panel-body">
											<div id="assessment-form-container" class="tjlms-lesson__toolbar-content assessment-toggle-main col-xs-12 p-0 border-0">
												<?php
												$layout = new FileLayout('assessment.assessment_form', JPATH_SITE . '/components/com_tjlms/layouts');

												echo $layout->render(array('lessonTrack'=>$this->lessonTrack, 'trackReviews'=>$sub,'trackRatings'=>$sub->assessment_params_ratings, 'lessonAssessment'=>$this->lessonAssessment, 'lesson'=>$this->lesson, 'role' => 'student'));
												?>
											</div><!--assessment form container-->
										</div>
									</div>
								</div>
							<?php endforeach; ?>
							</div>
						</div>
							<script>
							tjlms.assessment.init();
							//apply class to body only for Quiz view
							document.body.classList.add("tmtBody");
							</script>
					<?php endif; ?>
				<?php }//if ?>
				</div><!--span12-->
			</div><!--row-fluid-->
		</div>

<?php if ($fromAssessment != 1) : ?>
		<div class="tmt_test__footer fixed-bottom p-15 hidden-print">
			<div id="jlikeToolbar_bottom" class="row">
				<div class="col-xs-12 col-sm-8 col-sm-offset-2">
					<div id="jlikeToolbar_content p-0">
						<?php
							if( isset($this->item->attempted_count) && isset($this->item->q_count) && ($this->item->q_count > 0) ){
								$question_progress = ( 100 * $this->item->attempted_count ) / $this->item->q_count;
							}?>

						<div class="progress mb-0">
							<div class="progress-bar" role="progressbar"  style="width:<?php echo $question_progress;?>%;" aria-valuenow="<?php echo $question_progress;?>" aria-valuemin="0" aria-valuemax="100">
								<span class="progress_bar_text">
								<?php
								if( isset($this->item->attempted_count) && isset($this->item->q_count) && ($this->item->q_count > 0) )
								{
									echo Text::sprintf('COM_TMT_TEST_APPEAR_ATTEMPTED_OF', $this->item->attempted_count, $this->item->q_count);
								}
								?>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
<?php endif; ?>
	</div><!--row-fluid-->
</div>


<script type="text/javascript">
	jQuery(document).ready(function () {
		<?php
		if ($showInlinePreviewBtn)
		{
		?>
			jQuery('.drive-inline-preview-btn').removeClass('hide');

			jQuery(document).on("click", ".drive-inline-preview-btn", function () {
				jQuery('.drive-inline-preview').toggle();
			});

		<?php
		}
		?>
	});
</script>

<?php if ($fromAssessment != 1) : ?>

<script language="javascript" type="text/javascript">

	window.onload = function()
	{
		<?php
		if ($this->fromAdmin == 0)
		{
		?>
			var btnRelease = document.getElementById('<%= btnRelease.ClientID %>');

			//Find the button set null value to click event and alert will not appear for that specific button

			function setGlobal() {
				window.onbeforeunload = null;
			}
			jQuery(btnRelease).click(setGlobal);

			// Alert will not appear for all links on the page
			jQuery('a').click(function() {
				window.onbeforeunload = null;

			});

			//	Refresh or Back from the TestScorePage, It will redirect to course page.
			window.onbeforeunload = function(){
				window.setTimeout(function () { window.location ="<?php echo $this->courseDetailsUrl;?>";}, 10);
				window.onbeforeunload = null;
			}
		<?php
		}
		?>
		var totalscore = "<?php echo $totalScore;?>";
		jQuery('.total_score').text(totalscore);
	};

</script>

<script language="javascript" type="text/javascript">
	jQuery(document).ready(function()
	{
		<?php
		if ($this->fromAdmin == 0)
		{
		?>
			if (window.history && window.history.pushState)
			{

				jQuery(window).on('popstate', function() {
					var hashLocation = location.hash;
					var hashSplit    = hashLocation.split("#!/");
					var hashName     = hashSplit[1];

					if (hashName !== '')
					{
						var hash = window.location.hash;

						if (hash === '')
						{
							window.location = "<?php echo $this->courseDetailsUrl;?>";
							return false;
						}
					}
				});

				window.history.pushState('answersheet', null, './#answersheet');
			}
		<?php
		}
		?>
	});
</script>
<?php endif; ?>
