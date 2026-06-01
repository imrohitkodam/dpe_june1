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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;

HTMLHelper::_('bootstrap.renderModal', 'a.modal');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('jquery.framework');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.formvalidator');

$app       = Factory::getApplication();
$document  = Factory::getDocument();
$course_id = $app->input->get('course_id', '0', 'int');

$options['relative'] = true;
HTMLHelper::stylesheet('com_tjlms/jlike.css', $options);
?>

<div id="tmt_test" class="test-thankyou tjBs3 tmt_test tmt_test--thankyou">
	<div id="jlikeToolbar" class="container-fluid fixed-top">
		<div class="row">
			<div id="jlikeToolbar_content">
				<div class="tmt-cross-container valign-top pull-right">
					<span data-js-attr="jlikeToolbar-close" class="toolbar_buttons closeBtn border-0"
					 data-js-id='test-premise-close' title="<?php echo Text::_('COM_TJLMS_CLOSE');?>">
						<i class="fa fa-close"></i>
					</span>
				</div>
			</div>
		</div>
	</div>
	<div class="container-fluid thankyou__content" data-js-id="thankyou-container">
		<div class="row">
			<div class="col-sm-6 col-sm-offset-3 col-xs-12">
				<div class="panel panel-default br-0 mt-30">
					<div class="panel-heading text-center">
							<h3 class="thankyou__heading m-0">
								<?php
								if ($this->item->gradingtype == 'quiz')
								{
									echo Text::sprintf('COM_TMT_TEST_APPEAR_MSG_THANK_YOU_QUIZ', $this->item->title);
								}
								elseif ($this->item->gradingtype == 'exercise' && $this->item->attempt['lesson_status'] == 'AP')
								{
									echo Text::sprintf('COM_TMT_TEST_APPEAR_MSG_THANK_YOU_EXERCISE', $this->item->title);
								}
								elseif ($this->item->gradingtype == 'feedback' && $this->item->attempt['lesson_status'] == 'completed')
								{
									echo Text::_('COM_TMT_TEST_APPEAR_MSG_THANK_YOU_FEEDBACK');
								}
								?>
							</h3>

							<?php
							if ($this->item->attempt['lesson_status'] == 'AP')
							{
							?>
								<h4 class="m-5"> <?php echo Text::_('COM_TMT_TEST_SUBMIT_FOR_ASSESSEMENT'); ?> </h4>
							<?php
							}
							elseif (($this->item->attempt['lesson_status'] == 'incomplete' ||
								$this->item->attempt['lesson_status'] == 'started') && $this->item->resume == 1)
							{
							?>
								<h4 class="m-5"> <?php echo Text::_('COM_TMT_TEST_DRAFT_MSG'); ?> </h4>

							<?php
							}
							?>
					</div>

					<div class="panel-body text-center">
						<?php
						if (($this->item->attempt['lesson_status'] == 'passed' || $this->item->attempt['lesson_status'] == 'failed' )
							&& $this->item->gradingtype == 'quiz' && $this->item->isObjective == 1)
						{
							?>
							<div class="row">
									<h4>
									<?php
									if ($this->item->attempt['lesson_status'] == 'passed')
									{
										?>
										<div class="col-xs-12">
											<?php echo Text::sprintf('COM_TMT_TEST_APPEAR_RESULT_PASS_USER_MSG', ucfirst(Factory::getUser($this->item->attempt['user_id'])->name));?>
										</div>
										<br><br><br>
										<?php
									}
									else
									{
										?>
										<div class="col-xs-12">
											<?php echo Text::sprintf('COM_TMT_TEST_APPEAR_RESULT_FAIL_USER_MSG', ucfirst(Factory::getUser($this->item->attempt['user_id'])->name));?>
										</div>
										<br><br><br>
										<?php
									}
									?>
									</h4>
								<div class="col-xs-12">
									<div class="tmt-passing-score">
										<h4>
										<?php echo Text::sprintf('COM_TMT_TEST_APPEAR_TEST_PASSING_SCORE',
										'<span class="brown-text font-bold"> ' . $this->item->passing_marks . '</span>',
										'<span class="brown-text font-bold"> ' . $this->item->total_marks . '</span>'
										); ?>
										</h4>
									</div>
									<div class="tmt-yourscore">
										<h4>
										<?php echo Text::sprintf(
											'COM_TMT_TEST_APPEAR_SCORE_TAKEN',
											'<span class="brown-text font-bold"> ' . $this->item->attempt['score'] . '</span>'
											); ?>
										</h4>
									</div>
								</div>
							</div>
						<?php
						}
						PluginHelper::importPlugin('tjlmsthankyoupage');
						$pageContent = Factory::getApplication()->triggerEvent('getThankYouPageContent', array($this->course, $this->lesson, $this->item, $this->mediaLib, $this->testState, $this->state, $this->time_taken_format, $this->tjlmsparams));

						if (!empty($pageContent))
						{
							foreach ($pageContent as $content)
							{
								?>
								<hr class="my-10">
								<?php
								echo $content;
							}
						}
						?>
						<div class="row">
							<div class="col-xs-12 result-right-block">
							<?php
							if (isset($this->item->attempted_count) && isset($this->item->q_count) && ($this->item->q_count > 0))
							{
								$question_progress = ( 100 * $this->item->attempted_count ) / $this->item->q_count;
							}
							?>
							<h4>
								<div   aria-valuenow="<?php echo $question_progress;?>">
									<span class="progress_bar_text">
										<?php
											if (isset($this->item->attempted_count) && isset($this->item->q_count) && ($this->item->q_count > 0))
											{
												echo Text::sprintf('COM_TMT_TEST_APPEAR_ATTEMPTED_OF',
												'<span class="brown-text font-bold">' . $this->item->attempted_count . '</span>',
												'<span class="brown-text font-bold">' . $this->item->q_count . '</span>'
											);
											}
										?>
									</span>
								</div>
							</h4>
							<h4>
								<?php
								if ($this->time_taken_format)
								{
								?>
									<span><?php echo Text::_('COM_TMT_TEST_APPEAR_TIME_TAKEN');?>&nbsp;:&nbsp;</span>
									<span class="brown-text font-bold">
										<?php echo $this->time_taken_format; ?>
									</span>
								<?php
								}
								?>
							</h4>
							</div>
						</div><!--row-->
					</div><!--panel-body-->

				<?php
				if ($this->item->isObjective == 1 && $this->item->gradingtype == 'quiz' && $this->item->answer_sheet == 1 && ($this->item->attempt['lesson_status'] == 'passed' || $this->item->attempt['lesson_status'] == 'failed'))
				{
				?>
					<div class="panel-footer">
						<div class="row">
							<div class="col-xs-12 text-center">
								<p class="font-bold">
								<?php echo Text::sprintf('COM_TMT_TEST_APPEAR_VIEW_ANSWERPAPER_MSG'); ?>
								</p>
							</div>
							<div class="col-xs-12 text-center">
								<?php $link = Uri::root() . 'index.php?option=com_tmt&view=answersheet&tmpl=component&id=' . $this->item->id . '&ltId=' . $this->item->attempt['invite_id'] . '&candid_id=' . $this->item->attempt['user_id'] . '&course_id=' . $course_id; ?>
								<a data-bs-toggle="modal" data-bs-target="#reviewContent" class="btn btn-primary com_tmt_button br-0" onclick="openReviewButton('reviewContent');jQuery('#reviewContent').removeClass('fade');">
										<?php echo Text::_('COM_TMT_TEST_APPEAR_VIEW_ANSWERPAPER'); ?>
								</a>
								<?php
									echo HTMLHelper::_(
										'bootstrap.renderModal',
										'reviewContent',
										array(
											'url'        => $link,
											'width'      => '800px',
											'height'     => '300px',
											'modalWidth' => '80',
											'bodyHeight' => '70'
										)
									); ?>
							</div>
							<?php
							if (!empty($this->lesson->no_of_attempts) && isset($this->lessonTrackDetails->attempt))
							{
								if ($this->lessonTrackDetails->attempt >= $this->lesson->no_of_attempts && $this->item->attempt['lesson_status'] == 'failed')
								{
									?>
									<div class="col-xs-12 text-center">
										<p class="font-bold">
										<?php echo Text::_('COM_TMT_TEST_ATTEMPTS_EXHAUSTED'); ?>
										</p>
									</div>
									<?php
								}
								elseif ($this->lessonTrackDetails->attempt < $this->lesson->no_of_attempts)
								{
									?>
									<div class="col-xs-12 text-center">
										<p class="font-bold">
										<?php echo Text::sprintf('COM_TMT_TEST_ATTEMPTS_LEFT', ($this->lesson->no_of_attempts - $this->lessonTrackDetails->attempt)); ?>
										</p>
									</div>
									<?php
								}
							}
							?>
						</div>
					</div><!--panel-footer-->
				<?php
				}
				?>
				</div><!--panel-info-->
			</div><!--col-sm-6-->
		</div><!--row-->

	</div><!--container-fluid-->
</div><!--main-->
<script>
jQuery(document).ready(function(){

	jQuery(window).on('resize',function(){
		setContentPadding();
	})

	function setContentPadding(){
		jQuery('[data-js-id="thankyou-container"]').css('padding-top', jQuery('div#jlikeToolbar').height() + 10);
		jQuery('[data-js-id="thankyou-container"]').css('padding-bottom', jQuery('div#jlikeToolbar_bottom').height() + 5);
	};

	setContentPadding();

	if (window.history && window.history.pushState)
	{
		jQuery(window).on('popstate', function()
		{
			var hashLocation = location.hash;
			var hashSplit    = hashLocation.split("#!/");
			var hashName     = hashSplit[1];

			if (hashName !== '')
			{
				var hash = window.location.hash;

				if (hash === '')
				{
					window.location="<?php echo $this->courseDetailsUrl;?>";
					return false;
				}
			}
		});

		window.history.pushState('thankyou', null, './#thankyou');
	}
});

var oldWindow;

function openReviewButton(modalId = 'addModal', id = null)
{
	jQuery("#" + modalId).modal('show');
}

function closeOldWindow(){
	if(typeof oldWindow != 'undefined'){
		oldWindow.close();
	}
}
window.onunload = closeOldWindow;
</script>

<script type="text/javascript">
	<?php
	$document->addScriptDeclaration(
		"
		let TestObj = new TMT.UI.TestUI('" . $this->item->id . "', '" . $this->item->attempt['invite_id'] . "');
		TestObj.setLessonLaunchType('" . $this->launch_lesson_full_screen . "');
		TestObj.setCourseUrl('" . $this->courseDetailsUrl . "');
		TestObj.pageCloseActions();
		"
	);
	?>

	window.onload = function()
	{
		var btnRelease = document.getElementById('<%= btnRelease.ClientID %>');

		// Find the button set null value to click event and alert will not appear for that specific button
		function setGlobal()
		{
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
	};
</script>
