<?php
/**
 * @package     TMT
 * @subpackage  com_tmt
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Language\Text;

$q        = $displayData['question'];
$item     = $displayData['item'];
$params   = $displayData['params'];
$mediaLib = $displayData['mediaLib'];
$i        = $displayData['qNo'];
?>

<div class="test-question pb-10" data-js-id="test-question"
	data-js-type="<?php echo $q->type;?>"
	data-js-itemid="<?php echo $q->question_id;?>" data-js-compulsory="<?php echo ($q->is_compulsory) ? 1 : 0;?>" id="question-<?php echo $q->question_id;?>">

	<?php // Add anchor ?>
	<div>
		<a name="question-<?php echo $q->question_id;?>"></a>
	</div>

	<div class="row">
		<div class="test-question__header mb-10 col-xs-12">
			<div class="<?php echo ($item->gradingtype == 'quiz') ? 'col-xs-12 col-sm-11 ' : '';?>">
				<div class="test-question__header_title">
					<div class="tmt-qno-container col-xs-12 col-sm-1">
						<div class="tmt-qno center text-center font-700 mr-5 pull-left">
							<span class="ques-no">
								<?php echo $i; ?>
							</span>
						</div>
					</div>

					<?php
					// Use layouts to render media elements
					if (!empty($q->media_id))
					{
						?>
						<div class='tmt-qtext valign-middle pl-5 col-xs-12 col-sm-3'>
							<?php
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
							$mediaData['mediaUploadPath']   = $mediaLib->mediaUploadPath;
							$mediaData['originalMediaType'] = $original_media_type;
							$mediaData['originalFilename']  = $q->original_filename;
							$mediaData['media_type']        = $q->media_type;

							echo $layout->render($mediaData);
							?>
						</div>
						<?php
					}
					?>

					<div class="col-xs-12 col-sm-8">
						<h4 class="font-600">
							<?php echo nl2br(htmlentities(Text::_(trim($q->title))));
							if ($q->is_compulsory)
							{
							?>
								<span class="star">*</span>
							<?php
							}
							?>
						</h4>
						<div class="test-question__header_desc pl-45">
							<em><?php echo nl2br(htmlentities(Text::_(trim($q->description)))); ?></em>
						</div>
					</div>
				</div>
			</div>

			<?php
			if ($item->gradingtype == 'quiz')
			{
				?>
				<div class="col-sm-1 col-xs-12 text-center mb-20">
					<div class="tmt-que-marks p-5 center pull-right">
						<?php
						if ($q->marks > 1)
						{
							echo $q->marks . " " . Text::_('COM_TMT_TEST_APPEAR_QUESTION_MARKS_TXT');
						}
						else
						{
							echo $q->marks . " " . Text::_('COM_TMT_TEST_APPEAR_QUESTION_MARK_TXT');
						}
						?>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div><!--row-fluid-->

	<div class="row test-question__answers">
		<div class="col-xs-12 col-sm-11 col-sm-offset-1 test-question__answers-options">
			<?php
			// Use layouts to generate question type wise HTML
			$layout = new FileLayout($q->type, $basePath = JPATH_ROOT . '/components/com_tmt/layouts/question');
			$data   = array();
			$data['question'] = $q;
			$data['item']     = $item;
			$data['params']   = $params;
			$data['mediaLib'] = $mediaLib;
			echo $layout->render($data);
			?>
		</div>
	</div><!--row-fluid-->

	<div class="clearfix row">
		<div class="col-xs-12 col-sm-11 col-sm-offset-1">
			<div class="clearfix">&nbsp;</div>
			<?php if($q->type != 'file_upload'): ?>

				<button type="button" class="btn btn-sm" onclick="tjlms.test.resetAnswerOptions('<?php echo $q->type; ?>', <?php echo $q->question_id; ?>);"><?php echo Text::_('COM_TMT_QUESTION_SKIP'); ?></button>
				&nbsp;

			<?php endif; ?>

			<?php if($item->show_questions_overview): ?>
			<button type="button" class="btn btn-sm" onclick="tjlms.test.flagQuestion(this, <?php echo $q->question_id; ?>);"><?php echo ($q->flagged == 0) ? Text::_('COM_TMT_QUESTION_FLAG') : Text::_('COM_TMT_QUESTION_UNFLAG'); ?></button>
		<?php endif; ?>
		</div>
	</div><!--row-->

	<div class="row d-none" data-js-id="test-question-msg">
		<div class="col-sm-9 col-sm-offset-1">
			<div class="alert">
			</div>
		</div>
	</div>
</div>
