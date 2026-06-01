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
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('behavior.formvalidation');
HTMLHelper::_('behavior.modal', 'a.tjmodal');
HTMLHelper::_('jquery.framework');
HTMLHelper::stylesheet('media/techjoomla_strapper/bs3/css/bootstrap.css');
HTMLHelper::_('bootstrap.framework');

$params = ComponentHelper::getParams('com_tjlms');

$document = Factory::getDocument();
$document->addScript(Uri::root() . 'media/com_tmt/vendors/jquery.countdown.js');
$document->addScript(Uri::root() . 'components/com_tmt/assets/js/tmt.js');
$document->addScript(Uri::root() . 'media/com_tmt/vendors/bootpag.js');

$options['relative'] = true;
HTMLHelper::stylesheet('com_tmt/tmt.css', $options);
HTMLHelper::stylesheet('com_tjlms/jlike.css', $options);
HTMLHelper::stylesheet('com_tjlms/tjlms.min.css', $options);
HTMLHelper::_('stylesheet', 'components/com_tmt/assets/css/jquery.countdown.min.css');

$document->addScriptDeclaration("var rootUrl = '" . Uri::root() . "'");
HTMLHelper::_('script', 'com_tjlms/tjService.js', $options);

$jinput = Factory::getApplication()->input;
$jinput->set('tmpl', 'component');

$showAllQuestions      = $this->item->show_all_questions;
$showQuizMarks         = $this->item->show_quiz_marks;
$showQuestionsOverview = $this->item->show_questions_overview;

// Overview shown only when all questsions are shown
$showQuestionsOverview = ($showAllQuestions) ? $showQuestionsOverview : 0;

if (!$showAllQuestions)
{
	$currentPage = $this->item->currentPage;
	$section     = $this->testState['sectionsPerPage'][$currentPage];
	$totalPages  = count($this->testState['questionsPerPage']);
}
else
{
	$currentPage = $this->item->currentPage;
	$section     = $this->testState['sectionsPerPage'][$currentPage];

	// @TODO - @VK - to confirm
	// $totalPages  = count($this->testState['questionsPerPage']);
	$totalPages = 1;
}

$currentPage    = $this->item->currentPage;
$totalPages     = count($this->testState['questionsPerPage']);
$totalQuestions = $this->testState['totalQuestions'];

?>

<div id="tmt_test" class="tjlms-wrapper tjBs3 tmt_test">
	<div id="jlikeToolbar" class="container-fluid fixed-top">
		<div class="row">
			<div class="no-gutters">
				<div id="jlikeToolbar_content" class="d-table">
					<div class="d-table-row">
						<div class="d-table-cell valign-middle">
							<!-- set componentheading -->
							<div class="toolbar_heading pl-10">
								<?php
								// @if ($showAllQuestions)
								{
									?>
									<div class="hidden-md-down d-inline-block">
										<?php echo htmlentities($this->item->title); ?>
									</div>
									<?php
								}
								/*else
								{
									?>
									<div class="hidden-md-down d-inline-block">
										<?php echo htmlentities($this->item->title), " - ";?>
									</div>
									<span class="label label-success p-5"><?php echo $section->title?></span>
									<?php
								}*/
								?>
							</div>
							<div class="visible-xs  pl-10">
								<div class="test__timer">
									<div id="countdown_timer-xs"></div>
									<div id='countdown_timer_msg' ></div>
								</div>
							</div>
						</div>
						<div class="d-table-cell hidden-xs valign-middle text-right">
						<?php
							$countDown_timer = $this->item->show_time? '':'hide';
						?>
						<div class="test__timer <?php echo $countDown_timer; ?>">
								<h3 class="m-0 font-bold"><i class="fa fa-clock-o"></i>
								<span id="countdown_timer" class="ml-10"></span></h3>
								<div id="countdown_timer_msg"></div>
							</div>
						</div>

						<div class="d-table-cell">
							<div class="tmt-cross-container valign-top pull-right">
								<span data-js-id="test-close"
									data-js-attr="jlikeToolbar-close"
									class="toolbar_buttons closeBtn"
									title="<?php echo Text::_('COM_TJLMS_CLOSE'); ?>">
									<i class="fa fa-close"></i>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<form method="post" name="adminForm" id="adminForm">
		<div class="container-fluid">
			<div class="row">
				<div class="quiz_content" id="quiz_content">
					<!-- show form/items if items found -->
					<?php
					$questionsContainerDivClass = ($showQuestionsOverview) ? 'col-md-9' : 'col-sm-12';
					?>

					<div class="<?php echo $questionsContainerDivClass; ?>">
						<div class="questions_container p-20" id="renderTest">
						</div>
						<!-- questions-conainer -->
					</div>

					<?php
					if ($showQuestionsOverview)
					{
					?>
						<span class="visible-xs visible-sm btn-slide-panel">Click</span>
						<div class="col-md-3 attempted_qlist_container--wrapper slide-panel py-10">
							<div id="attempted_qlist_container">

								<div class="center">
									<?php echo Text::_('COM_TMT_QUESTION_JUMP'); ?>
								</div>

								<div class="flex-center-row">
									<div class="tmt-circle tmt-circle--attempted tmt-circle--margin tmt-circle--big"><?php echo Text::_('COM_TMT_QUESTION_ATTEMPTED'); ?></div>
									<div class="tmt-circle tmt-circle--margin tmt-circle--big"><?php echo Text::_('COM_TMT_QUESTION_SKIPPED'); ?></div>
									<div class="tmt-circle tmt-circle--attempted tmt-circle--margin tmt-circle--big">
										<span class="fa fa-flag  tmt-circle--flag"></span><?php echo Text::_('COM_TMT_QUESTION_FLAGGED'); ?>
									</div>
								</div>

								<div class="clearfix">
									<hr/>
								</div>
								<div id="question_palette">
								</div>
							</div>
						</div>
					<?php
					}
					?>
				</div>
			</div>

			<div class="tmt_test__footer fixed-bottom pl-10 pr-10">
				<div id="jlikeToolbar_bottom">
					<div id="jlikeToolbar_content">
						<div class="row">
							<div class="tmt_test__footer_element hidden-xs col-sm-4 text-left" data-js-id="test-controls">
								<?php
								/*
								* [l] => 5
								* [ls] => 15
								* [q_count] => 13
								* [p_count] => 3
								* [current_p] => 4
								*/

								if (isset($this->item->attemptedCount))
								{
									$question_progress = (100 * $this->item->attemptedCount ) / $totalQuestions;
								}
								?>

								<div class="progress mb-0 mt-10">
									<div class="progress-bar" role="progressbar"
										style="width:<?php echo $question_progress;?>%;"
										aria-valuenow="<?php echo $question_progress;?>"
										aria-valuemin="0" aria-valuemax="100">
										<span class="progress_bar_text">
											<?php
											if (isset($this->item->attemptedCount) && $totalQuestions && ($totalQuestions))
											{
												echo Text::sprintf('COM_TMT_TEST_APPEAR_ATTEMPTED_OF', $this->item->attemptedCount, $totalQuestions);
											}
											?>
										</span>
									</div>
								</div>
							</div>

							<div class="tmt_test__footer_element pull-right center mr-10 d-none" data-js-id="toolbar">
								<span data-js-id="toolbar-prev" class="toolbar__span">
									<button type="button"
										data-js-id="test-prev"
										class="tmt_test__footer__navbutton btn btn-default">
										<i class="fa fa-chevron-left" aria-hidden="true"></i>
										<?php echo Text::_('COM_TMT_PREV'); ?>
									</button>
								</span>
								<span data-js-id="toolbar-next" class="toolbar__span">
									<button type="button"
										data-js-id="test-next"
										class="tmt_test__footer__navbutton btn btn-primary">
										<?php echo Text::_('COM_TMT_NEXT'); ?>
										<i class="fa fa-chevron-right" aria-hidden="true"></i>
									</button>
								</span>
								<span data-js-id="toolbar-draft" class="toolbar__span">
									<button type="button"
										class="tmt_test__footer__navbutton btn btn-success"
										data-js-id="drafttest">
											<i class="fa fa-edit" aria-hidden="true"></i>
											<?php echo Text::_('COM_TMT_TEST_FINAL_DRAFT'); ?>
										</button>
								</span>
								<span data-js-id="toolbar-final" class="toolbar__span">
									<span class="automatic-submit-msg">
										<?php echo Text::sprintf('COM_TMT_TEST_AUTOMATIC_SUBMIT_MESSAGE', '<span class="count"></span>'); ?>
									</span>

									<button type="button"
										class="tmt_test__footer__navbutton btn btn-primary"
										data-js-id="submittest">
										<i class="fa fa-check" aria-hidden="true"></i>
										<?php echo Text::_('COM_TMT_TEST_FINAL_SAVE'); ?>
									</button>
								</span>
							</div>

					<?php
						if ($totalPages != 1)
						{
						?>
							<div id="tmt-page-selection" class="pull-right mt-10">
							</div>
					<?php
						}
					?>
						</div>
					</div>
				</div>
			</div>

			<input type="hidden" name="option" value="com_tmt" />
			<input type="hidden" name="view" value="test" />
			<input type="hidden" name="test[course_id]" value="<?php echo $this->course->id; ?>" />
			<input type="hidden" name="test[lesson_id]" value="<?php echo $this->lesson->id; ?>" />
			<input type="hidden" id="id" name="test[id]" value="<?php echo $this->item->id;?>" />
			<input type="hidden" id="invite_id" name="invite_id" value="<?php echo $this->item->invite_id;?>" />
			<input type="hidden" id="currentpage" name="test[current]" value="<?php echo $currentPage;?>" />
			<input type="hidden" id="totalpages" name="test[totalpages]" value="<?php echo $totalPages;?>" />
			<input type="hidden" id="ltCp" name="test[ltCp]" value="<?php echo $currentPage;?>" />
			<!--input type="hidden" id="prev" name="test[prev]" value="<?php echo $prevPage;?>" />
			<input type="hidden" id="next" name="test[next]" value="<?php echo $nextPage;?>" /-->
			<input type="hidden" id="unAttemptedCompulsoryCnt"value=""/>
			<input type="hidden" id="action" name="test[action]" value=""/>

			<?php
			if ($this->item->show_thankyou_page)
			{
				$redirectAfterTestSubmission = 'index.php?option=com_tmt&view=test&tmpl=component';
				$redirectAfterTestSubmission = Route::_(
					$redirectAfterTestSubmission . '&id=' . $this->item->id .
					'&invite_id=' . $this->item->invite_id .
					'&layout=thankyou&course_id=' . $this->course->id,
					false
				);
			}
			else
			{
				$redirectAfterTestSubmission = $this->tjlmshelperObj->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $this->course->id, false);
			}

			$question_ids = (isset($question_ids) && is_array($question_ids)) ? $question_ids : array();
			?>

			<input type="hidden" id="thankYouLink" value="<?php echo $redirectAfterTestSubmission; ?>"/>

			<input type="hidden" name="question_ids" value="<?php echo base64_encode(implode(',', $question_ids)); ?>" />
			<input type="hidden" name="<?php echo Session::getFormToken();?>" value="1" data-js-id="form-token"/>
		</div>
	</form>
</div><!--row-fluid-->

<?php
$timeSpent = $timeToShowFinishAlert = 0;

if ($this->item->timeSpent)
{
	list($hours, $minutes, $seconds) = explode(":", $this->item->timeSpent);
	$timeSpent = $hours * 3600 + $minutes * 60 + $seconds;
	$timeSpent = $timeSpent * 1000;
}

if ($this->item->show_time_finished)
{
	$timeToShowFinishAlert = $this->item->time_finished_duration * 60;
}
?>
<script>
	/*Apply class to body only for Quiz view*/
	document.body.classList.add("tmtBody");

	jQuery(document).ready(function() {
		/*For mobile slider- Quiz view*/
		flag=true;
		jQuery(".btn-slide-panel").click(function(){
			if(flag) {
					jQuery(".attempted_qlist_container--wrapper").css("right", "-60%");
					jQuery(".btn-slide-panel").css({"position":"fixed", "right":"10px"});
					flag = false;
			}
			else {
					jQuery(".attempted_qlist_container--wrapper").css({"position":"absolute", "right":"-10px"});
					jQuery(".btn-slide-panel").css({"position":"fixed", "right":"60%"});
					flag = true;
				}
			});
	});
	<?php
	$document->addScriptDeclaration(
		"
		let questionsPerPage = " . json_encode($this->testState['questionsPerPage']) . ";
		let testTimeDuration = '" . $this->item->time_duration . "';
		let testTimeremaning =  testTimeDuration * 60 * 1000;
		let timeSpent        = " . $timeSpent . ";
		let showMarks        = " . $showQuizMarks . ";

		if (timeSpent)
		{
			testTimeremaning = testTimeremaning - timeSpent;
		}

		let TestObj = new TMT.UI.TestUI('" . $this->item->id . "', '" . $this->item->invite_id . "');
		TestObj.setTotalPages('" . $totalPages . "');
		TestObj.setTotalQuestions('" . $totalQuestions . "');
		TestObj.setRemainingTime(testTimeremaning);
		TestObj.setTestDuration('" . $this->item->time_duration . "');
		TestObj.setTimeToShowFinishAlert('" . $timeToShowFinishAlert . "');
		TestObj.setLessonLaunchType('" . $this->launch_lesson_full_screen . "');
		TestObj.setCourseUrl('" . $this->courseDetailsUrl . "');
		TestObj.setResumeAllowed('" . $this->item->resume . "');
		TestObj.setGradingType('" . $this->item->gradingtype . "');
		TestObj.initTest();
		"
	);
	?>
</script>
