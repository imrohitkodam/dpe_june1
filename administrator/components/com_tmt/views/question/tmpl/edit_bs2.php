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

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('jquery.ui', array('core', 'sortable'));
HTMLHelper::_('jquery.token');

// Import helper for declaring language constant
JLoader::import('TmtHelper', Uri::root() . 'administrator/components/com_tmt/helpers/tmt.php');
TmtHelper::getLanguageConstant();

$options['relative'] = true;
HTMLHelper::_('script', 'com_tjlms/tjService.js', $options);
HTMLHelper::_('script', 'com_tmt/tmt.js', $options);
?>

<script type="text/javascript">

	/* Added for answer options sorting. */
	jQuery(function()
	{
		jQuery( "#sortable" ).sortable();
	});

</script>

<div id="tmt_question_form" class="row-fluid tjBs3">

		<fieldset>
			<?php if($this->ifintmpl) :?>
				<div class="modal-header mb-20">

						<!-- set componentheading -->
						<button type="button" class="close border-0" onclick="closebackendPopup(1);" data-dismiss="modal" aria-hidden="true">×</button>
						<?php if (!empty($this->item->id)): ?>
							<h3 class="componentheading"><?php echo Text::_('COM_TMT_Q_FORM_HEADING_Q_EDIT') . htmlentities($this->item->title); ?></h3>
						<?php else: ?>
							<h3 class="componentheading"><?php echo Text::_('COM_TMT_Q_FORM_HEADING_Q_CREATE');?></h3>
						<?php endif; ?>

				</div><!--modal-header-->
			<?php endif; ?>

		<?php if ($this->isQuestionAttempted === true && $this->item->state == 1) {?>

			<label class="alert alert-info text-info"><?php echo Text::_('COM_TMT_QUESTION_NOT_FOR_EDIT'); ?></label>

		<?php }?>

			<form action="" method="post" name="adminForm" id="questionForm" class="form-validate form-horizontal" enctype="multipart/form-data">
				<div class="<?php echo ($this->ifintmpl) ? 'tjmodal-body' : ''?>">
				<div class="row-fluid">
					<div class="span12">

						<?php echo HTMLHelper::_('bootstrap.startTabSet', 'questionform', array('active' => 'questiondetails')); ?>
							<?php echo HTMLHelper::_('bootstrap.addTab', 'questionform', 'questiondetails', Text::_('COM_TMT_Q_FORM_QUESTION', true)); ?>
								<div class="span6">
									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('type'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('type'); ?></div>
									</div>
									<?php if (!$this->gradingtype) :?>
									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('gradingtype'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('gradingtype'); ?></div>
									</div>
									<?php else:?>
										<input type="hidden" name="jform[gradingtype]" id="jform_gradingtype" value="<?php echo $this->gradingtype;?>" />
									<?php endif; ?>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('title'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('title'); ?></div>
									</div>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('alias'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('alias'); ?></div>
									</div>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('description'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('description'); ?></div>
									</div>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('ideal_time'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('ideal_time'); ?></div>
									</div>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('category_id'); ?></div>

										<div class="controls"><?php echo $this->form->getInput('category_id'); ?>&nbsp;&nbsp;&nbsp;<br><em><?php echo Text::_('COM_TMT_Q_FORM_CATEGORY_NOT_FOUND');?></em></div>
									</div>

									<div class="control-group">
										<div class="control-label"><?php echo $this->form->getLabel('level'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('level'); ?></div>
									</div>

									<?php if(empty($this->item->created_by)){ ?>
										<input type="hidden" name="jform[created_by]" value="<?php echo Factory::getUser()->id; ?>" />

									<?php }
									else{ ?>
										<input type="hidden" name="jform[created_by]" value="<?php echo $this->item->created_by; ?>" />

									<?php } ?>
								</div>
								<div class="span6">
									<?php
										// Render Media Type
										echo $this->form->renderField('media_type');

										// Render Media Type: File
										echo $this->form->renderField('media_file');
										echo $this->form->renderField('fileNote');

										// Render Media Type: Image
										echo $this->form->renderField('media_image');
										echo $this->form->renderField('imageNote');

										// Render Media Type: Video URL
										echo $this->form->renderField('media_url');
										echo $this->form->renderField('videoNote');

										// Render Media Type: Audio file
										echo $this->form->renderField('media_audio');
										echo $this->form->renderField('audioNote');
										?>

										<div class="clearfix"></div>

										<?php
										// Render Question Media
										if (!empty($this->item->media_type))
										{
											?>
											<div class="controls">
												<div class="span6">
												<?php if ($this->isQuestionAttempted === false) { ?>
													<a class="close" onclick="tmt.tjMedia.deleteMedia('<?php echo $this->item->id; ?>', '<?php echo $this->questionMediaClient; ?>', '<?php echo $this->item->media_id; ?>');">×</a>
												<?php } ?>
												<?php
												// Use layouts to render media elements
												$layout = new FileLayout($this->item->media_type, JPATH_ROOT . '/components/com_tmt/layouts/media');
												$mediaData   = array();
												$mediaData['media'] = $this->item->media_source;
												$mediaData['mediaUploadPath'] = $this->mediaLib->mediaUploadPath;
												$mediaData['originalMediaType'] = $this->item->original_media_type;
												$mediaData['originalFilename'] = $this->item->original_filename;
												$mediaData['media_type'] = $this->item->media_type;
												echo $layout->render($mediaData);
												?>
												</div>
											</div>

										<?php
										}
									?>
								</div>

								<input type="hidden" data-js-id="item-id" name="jform[id]" value="<?php echo $this->item->id; ?>" />
								<input type="hidden" name="jform[unique]" value="<?php echo $this->unique; ?>" />
								<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
								<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
								<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />
								<input type="hidden" name="jform[created_on]" value="<?php echo $this->item->created_on; ?>" />

							<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

							<?php echo HTMLHelper::_('bootstrap.addTab', 'questionform', 'questionanswers', Text::_('COM_TMT_Q_FORM_ANSWERS', true)); ?>

								<input type="hidden" class="extra_validations" data-js-validation-functions="tmt.question.validateQuestion">

									<div class="control-group" data-js-type="quiz">
										<div class="control-label"><?php echo $this->form->getLabel('marks'); ?></div>
										<div class="controls"><?php echo $this->form->getInput('marks'); ?></div>
									</div>

								<div data-js-id="for-mcqs">
									<div id="answers-heading">
										<strong><?php echo Text::_('COM_TMT_Q_FORM_ANSWERS');?></strong>
									</div>
									<hr class="hr hr-condensed"/>

									<div class="row-fluid">
										<div class="span3 font-600">
											<span class="hasTooltip" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_TEXT_DESC'); ?>"><?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_TEXT'). ' *'; ?></span>
										</div>

										<div class="span2 font-600" data-js-id="answer-media">
											<span class="hasTooltip" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_MEDIA_DESC'); ?>">
										<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_MEDIA');?></span>
										</div>

										<div class="span1 font-600" data-js-id="mcq-correct">
											<span class="hasTooltip" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_IS_CORRECT_DESC'); ?>">
										<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_IS_CORRECT');?></span>
										</div>

										<div class="span1 font-600" data-js-type="quiz">
											<span class="hasTooltip" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_MARKS_DESC'); ?>">
										<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_MARKS');?></span>
										</div>

										<div class="span3 font-600">
											<span class="hasTooltip" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_COMMENTS_DESC'); ?>">
										<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_COMMENTS');?></span>
										</div>
										<div class="span1 font-600">
											<span class="hasTooltip" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_REMOVE_DESC'); ?>">
										<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_REMOVE');?></span>
										</div>
										<div class="span1 font-600">
											<span class="hasTooltip span1" title="<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_REORDER_DESC'); ?>">
										<?php echo Text::_('COM_TMT_Q_FORM_ANSWER_OPTION_REORDER');?></span>
										</div>
									</div>
								</div>
									<div style="clear:both"></div>
									<div class="answers-container p-15" id="sortable" >
											<?php
											// Load previous answers as per answer-template
											$layout = new FileLayout($this->item->type, $basePath = JPATH_ROOT . '/administrator/components/com_tmt/layouts/question');
											$data   = array();
											$data['item']     = $this->item;
											$data['isQuestionAttempted']  = $this->isQuestionAttempted;
											$data['componentParams']  = $this->tjLmsParams;
											echo $layout->render($data);
											?>
										</div>


										<div class="clearfix" data-js-id="for-mcqs">
											<button type="button" class="btn btn-primary btn-small mt-15 pt-5 pb-5" onclick="addAnswerClone('<?php echo $this->item->type;?>','answers-container');" id="add_answer">
												<i class="icon-plus"></i> <?php echo Text::_('COM_TMT_Q_FORM_BUTTON_ADD_NEW_ANSWER');?>
											</button>
										</div>

										<div class="clearfix"> </div>

								<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
							<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
							</div><!--span12-->
						</div><!--row-fluid-->
						</div><!--modal-body-->

					<?php if ($this->ifintmpl == 'component'): ?>
					<div class="modal-footer fixed-bottom">
							<!-- show action buttons/toolbar -->
							<div class="btn-toolbar text-right clearfix" data-js-attr="form-actions">

								<div id="toolbar-prev" class="btn-wrapper">
									<button type="button" data-js-attr="form-actions-prev" class="btn  com_tmt_button">
										<span class="icon-arrow-left valign-middle"></span>
										<?php echo Text::_('COM_TMT_BUTTON_PREV') ?>
									</button>
								</div>

								<div id="toolbar-next" class="btn-wrapper">
									<button type="button" data-js-attr="form-actions-next" class="btn btn-success com_tmt_button">
										<?php echo Text::_('COM_TMT_BUTTON_NEXT') ?>
										<span class="icon-arrow-right valign-middle"></span>
									</button>
								</div>

								<div id="toolbar-apply" class="btn-wrapper">
									<button type="button" id="button_save" class="btn btn-primary com_tmt_button" onclick="Joomla.submitbutton('question.apply');">
											<?php echo Text::_('COM_TMT_BUTTON_SAVE') ?>
									</button>
								</div>

								<div id="toolbar-save" class="btn-wrapper">
									<button type="button" id="button_save_and_close" class="btn btn-success mr-10 com_tmt_button" onclick="Joomla.submitbutton('question.save')">
										<span class="fa fa-check mr-0"></span>
										<?php echo (!$this->unique) ? Text::_('COM_TMT_BUTTON_SAVE_AND_CLOSE') : Text::_('COM_TMT_BUTTON_SAVE_AND_ADD_TOQUIZ');?>
									</button>
								</div>
								<div id="toolbar-cancel" class="btn-wrapper">
									<button type="button" class="btn com_tmt_button" onclick="Joomla.submitbutton('question.cancel')">
									<span class="icon-delete valign-middle"></span>
										<?php echo Text::_('COM_TMT_BUTTON_CANCEL') ?>
									</button>
								</div>
							</div>
					</div><!--modal-footer-->
					<?php endif; ?>

					<?php if (!$this->ifintmpl) :?>
						<input type="hidden" name="option" value="com_tmt" />
						<input type="hidden" name="task" value="" />
					<?php endif;?>

					<?php echo HTMLHelper::_( 'form.token' ); ?>
				</form>

	</fieldset>

	<div style="display:none;">
		<?php
			// Load all default answer-templates
			$path = JPATH_ADMINISTRATOR . '/components/com_tmt/views/question/tmpl/answertemplates.php';
			ob_start();
			include($path);
			$html = ob_get_contents();
			ob_end_clean();
			echo $html;
		?>
	</div>
</div><!--row-fluid-->
<script>
var lessonUploadSize = "<?php echo $this->tjLmsParams->get('lesson_upload_size');?>";
tmt.question.init("<?php echo $this->ifintmpl;?>", "<?php echo $this->unique;?>", "<?php echo $this->gradingtype;?>", "<?php echo $this->target;?>", "<?php echo $this->forDynamic;?>", "<?php echo $this->isQuestionAttempted; ?>");

</script>
