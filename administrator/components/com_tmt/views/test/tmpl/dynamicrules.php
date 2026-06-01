<?php
/**
 * @package     TMT
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('bootstrap.renderModal', 'a.tjmodal');

$options['relative'] = true;
HTMLHelper::_('script', 'com_tjlms/tjService.js', $options);
HTMLHelper::_('script', 'com_tmt/tmt.js', $options);
HTMLHelper::_('script', 'com_tjlms/tjlmsAdmin.js', $options);


$input = Factory::getApplication()->input;

if (!empty($input->get('unique', '', 'STRING')))
{
	$this->unique = $input->get('unique', '', 'STRING');
	$this->unique = (string) preg_replace('/[^0-9_]/i', '', $this->unique);
	$forDynamic   = 0;
}
?>
<div class="tjBs3">
<?php if (!$forDynamic) :?>

	<div class="modal-header pl-0">
		<!-- <button type="button" class="close border-0" onclick="closebackendPopup(1);" data-dismiss="modal" aria-hidden="true">×</button> -->
		<h3 class="font-bold"><?php echo ucfirst(Text::_('COM_TMT_TEST_AUTO_PICK_QUESTIONS'));?></h3>
	</div><!--modal-header-->

<?php endif; ?>
<div class="test-rules pt-20" data-js-attr="test-rules">
	<form action="<?php echo Route::_('index.php?option=com_tmt&view=test&layout=rules&tmpl=component');?>" class="form-validate" method="post" name="adminForm" id="testFormrules">
		<div class="tjmodal-body">
			<div class="rules-header row">
				<div class="question-forquiz-count-header col-lg-1"><?php echo ucfirst(Text::_('COM_TMT_DYNAMIC_TEST_FORM_LBL_QUESTIONS'));?>
				<span class="star">*</span>
				</div>
				<div class="question-forpull-header col-lg-1 <?php echo ($forDynamic == 0?'d-none':'');?>">
				<?php echo ucfirst(Text::_('COM_TMT_DYNAMIC_TEST_FORM_LBL_Q_CNT_FOR_DYNAMICSET'));?>
				<span class="star">*</span>
				</div>
			<?php if($this->gradingtype == 'quiz'):	?>
				<div class="question-marks-header col-lg-1"><?php echo ucfirst(Text::_('COM_TMT_TEST_FORM_LBL_MARKS'));?>
					<span class="star">*</span>
				</div>
			<?php endif;?>
				<div class="question-cat-header col-lg-2"><?php echo ucfirst(Text::_('COM_TMT_TEST_FORM_LBL_FROM'));?></div>
				<div class="question-diff-header col-lg-2"><?php echo ucfirst(Text::_('COM_TMT_TEST_FORM_LBL_HAVING_D_LEVEL'));?></div>
				<div class="question-type-header col-lg-2"><?php echo ucfirst(Text::_('COM_TMT_TEST_FORM_LBL_Q_TYPE'));?></div>
				<div class="question-avail-header col-lg-1" title="<?php echo Text::_('COM_TMT_TEST_FORM_LBL_Q_CNT_IN_QB_DYNAMICSET_DESC');?>"><?php echo ucfirst(Text::_('COM_TMT_TEST_FORM_LBL_Q_CNT_IN_QB_DYNAMICSET'));?></div>
				<!--div class="question-remain-header span1 tmt_display_none"><?php echo Text::_('Remaining');?></div-->
				<div class="question-type-header col-lg-2">&nbsp;</div>

	</div>

	<div class="rules_block rules-container" id="rules_block" data-js-id="test-rules-block">

	<?php
	$rules_checked     = 0;
	$rules = isset($section->rules) ? $section->rules:'' ;
	if($this->item->type != 'plain')
	{

	// Display existing rules if present.
		if($rules)
		{
			$rules_checked     = 1;
			$i = 1;
			foreach($rules as $rule)
			{
			?>
				<div class="row rule-template rule-template--disabled" id="test-rule-<?php echo $i++;?>" data-js-id="test-rule">
					<div class="rule-qcount col-lg-1">
						<input type="hidden" name="rule_id[]"
						class="inputbox input-mini rule_id" value="<?php if(isset($rule->id))echo $rule->id;?>"
						/>
						<input type="text" name="questions_count[]"
						class="inputbox input-mini questions_count" value="<?php if(isset($rule->questions_count))echo $rule->questions_count;?>"
						placeholder="<?php echo Text::_('0');?>"
						onchange="getCntNeededForPull(this,<?php echo (int) $this->multiplicateFactor?>);"
						/>
					</div>

					<div class="rule-qpullcount col-lg-1">
						<input type="text" name="pull_questions_count[]" id="pull_questions_count"
						class="inputbox input-mini pull_questions_count"
						value="<?php echo  $rule->pull_questions_count?>"
						placeholder="<?php echo Text::_('0');?>" />
						<span class="">*</span>
					</div>
				<?php if($this->gradingtype == 'quiz'):	?>

					<div class="rule-marks col-lg-1">
						<input type="text" name="questions_marks[]"
						class="inputbox input-mini questions_marks" value="<?php if(isset($rule->marks))echo $rule->marks;?>"
						placeholder="<?php echo Text::_('0');?>"  />
					</div>

				<?php endif;?>
					<div class="rule-cat col-lg-2">
						<?php
							echo HTMLHelper::_('select.genericlist', $this->categories, "questions_category[]",
							'class="input input-medium small form-control edit_category" name=""', "value", "text", $category = isset($rule->category)?  $rule->category : '');
						?>
					</div>

					<div class="rule-level col-lg-2">
						<?php
							echo HTMLHelper::_('select.genericlist', $this->difficultyLevels, "questions_level[]",
								'class="input input-medium small form-control edit_difficultyLevel" name=""', "value", "text", $difficulty_level = isset($rule->difficulty_level)?  $rule->difficulty_level : '');
						?>
					</div>
					<div class="rule-qtype col-lg-2">
						<?php
							echo HTMLHelper::_('select.genericlist', $this->qTypes, "questions_type[]",
								'class="input input-medium small form-control edit_qTypes" name=""', "value", "text", $question_type = isset($rule->question_type)?  $rule->question_type : '');
						?>
					</div>
					<span class="col-lg-1 rule-question-available" data-js-id="rule-question-available"></span>

					<div class="col-lg-1 actions-div">
						<span class="addButtons hide" data-js-id="rule-add-question">
							<?php $link = Route::_("index.php?option=com_tmt&view=question&layout=edit&tmpl=component&gradingtype=" .$this->gradingtype . "&unique=" . $this->unique . "&target=rule&forDynamic=" . $forDynamic);?>
							<a onclick="tjLmsCommon.loadPopup('<?php echo $link?>')" class="btn btn-primary btn-small">
								<?php echo Text::_( 'COM_TMT_FORM_TEST_ADD_QUESTION' ); ?>
							</a>
						</span>

						<button type="button" class="btn btn-danger btn-small" data-js-id="test-remove-rule" onclick="tmt.test.removeSetRule(this);">
							<i class="icon-trash"></i>
						</button>
					</div>
				</div>

				<?php
			}
		}
	}
	?>
		<div class="row rule-template" id="test-rule-0" data-js-id="test-rule">
			<div class="rule-qcount col-lg-1 <?php echo ($forDynamic == 0?'d-none':'');?>">
				<input type="text" name="questions_count[]" id="questions_count"
				class="inputbox input-mini questions_count" value=""
				placeholder="<?php echo Text::_('0');?>"
				onchange="getCntNeededForPull(this,<?php echo (int) $this->multiplicateFactor?>);"/>
			</div>

			<div class="rule-qpullcount col-lg-1 <?php echo ($forDynamic == 0?'rule-qcount-autopick':'');?>">
				<input type="text" name="pull_questions_count[]" id="pull_questions_count"
				class="inputbox input-mini pull_questions_count" value=""
				placeholder="<?php echo Text::_('0');?>" />
			</div>
		<?php if($this->gradingtype == 'quiz'):	?>
			<div class="rule-marks col-lg-1">

				<input type="text" name="questions_marks[]" id="questions_marks"
				class="inputbox input-mini questions_marks" value=""
				placeholder="<?php echo Text::_('0');?>"/>
			</div>
		<?php endif;?>
			<div class="rule-cat col-lg-2">
				<?php
					echo HTMLHelper::_('select.genericlist', $this->categories, "questions_category[]",
					'class="input input-medium small form-control questions_category" name="questions_category[]"', "value", "text", '');
				?>
			</div>

			<div class="rule-level col-lg-2">
				<?php
					echo HTMLHelper::_('select.genericlist', $this->difficultyLevels, "questions_level[]",
						'class="input input-medium small form-control questions_level" name="questions_level[]"', "value", "text", '');
				?>
			</div>

			<div class="rule-qtype col-lg-2">
				<?php
					echo HTMLHelper::_('select.genericlist', $this->qTypes, "questions_type[]",
						'class="input small form-control questions_type" name="questions_type[]" style="width:155px;"', "value", "text", '');
				?>
			</div>
			<span class="col-lg-1 rule-question-available" data-js-id="rule-question-available"></span>
			<!--span class="span1 extra-info-remain tmt-display-none" id="extra-info-remain"></span-->

			<div class="col-lg-2 rule-actions auto-question-btn">
					<span class="addButtons hide" data-js-id="rule-add-question">
						<?php $link = Route::_("index.php?option=com_tmt&view=question&layout=edit&tmpl=component&gradingtype=" .$this->gradingtype . "&unique=" . $this->unique . "&target=rule&forDynamic=" . $forDynamic);?>
						<a onclick="tjLmsCommon.loadPopup('<?php echo $link?>')" class="btn btn-primary btn-small">
							<?php echo Text::_( 'COM_TMT_FORM_TEST_ADD_QUESTION' ); ?>
						</a>
					</span>

					<button type="button" class="btn btn-danger btn-small hide" data-js-id="test-remove-rule" onclick="tmt.test.removeRule(this);">
						<i class="icon-trash"></i>
					</button>
					<button type="button" class="btn btn-primary btn-small" data-js-id="test-add-rule" onclick="tmt.test.cloneRule(this, '<?php echo $forDynamic; ?>');" id="add_answer" title="<?php echo Text::_('COM_TMT_TEST_FORM_RULES_ADD_NEW'); ?>">
						<i class="icon-plus"></i>
					</button>
				</div>
			</div>
		</div>

		<div class="more-to-rules row my-15">

			<div class="col-lg-7">
			<?php if($forDynamic): ?>
				<button type="button" class="btn btn-small btn-primary" data-js-id="fetch-set-rule-Questions">
					<?php echo Text::_('COM_TMT_TEST_DYNAMIC_RULE_VALIDATE_ADD_QUESTIONS') ?>
				</button>
			<?php else: ?>
				<button type="button" class="btn btn-small btn-primary" onclick="tmt.test.fetchRuleQuestions('<?php echo $this->gradingtype;?>', '<?php echo $this->unique; ?>');" data-js-id="fetch-rule-Questions">
					<?php echo Text::_('COM_TMT_TEST_DYNAMIC_RULE_VALIDATE_ADD_QUESTIONS') ?>
				</button>
			<?php endif;?>
			</div>

			<div class="col-lg-2">&nbsp;</div>

			<input type="hidden" id="invalidrules" value="0"/>
			<input type="hidden" id="readytosaverules" value="0"/>
			<input type="hidden" id="perfectrules" value="0"/>
		</div>
		<div class="test-rules-questions hide" data-js-id="test-rule-questions">
		<?php if ($forDynamic == 0) :?>
			<div data-js-id="questions_container" class="questions_container">

			</div>
		<?php endif;?>
			<!--section-question-->
			<div class="section_question p-10 hide" id="section_question_0" data-js-itemid="" data-js-id="section-question">
				<!--row-fluid-->
				<div class="row">
					<!--span6-->
					<div class="col-lg-4">
						<input onclick="Joomla.isChecked(this.checked)" class="hide" type='checkbox' id='cb0' name='cid[]' value='' checked />
						<span data-js-id="section-question-title"></span>
					</div>
					<div class="col-lg-7">
						<div class="row">
							<?php $class = "col-lg-4"; ?>
						<?php if($this->gradingtype == 'quiz'):	?>
							<?php $class = "col-lg-3"; ?>
							<div class="<?php echo $class;?>">
								<?php echo Text::_('COM_TMT_TEST_FORM_MARKS'). ": "; ?><span data-js-id="section-question-marks"></span>
							</div>
						<?php endif;?>
							<div class="<?php echo $class;?>">
								<?php echo Text::_('COM_TMT_TEST_FORM_CATEGORY'). ": "; ?><span data-js-id="section-question-category"></span>
							</div><!--/span5-->
							<div class="<?php echo $class;?>">
								<?php echo Text::_('COM_TMT_TEST_FORM_TYPE') . ": "; ?><span data-js-id="section-question-type"></span>
							</div>
							<div class="<?php echo $class;?>">
								<?php echo Text::_('COM_TMT_TEST_FORM_LEVEL') . ": "; ?><span data-js-id="section-question-level"></span>
							</div>
						</div>
					</div>
					<!--span1-->
					<div class="col-lg-1">
						<span data-js-id="delete-question" onclick="tmt.test.removeRuleQuestion(this);" class="btn btn-small" title="<?php echo Text::_('COM_TMT_TEST_FORM_DELETE');?>">
							<i class="icon-trash"> </i>
						</span>
					</div><!--/span1-->
				</div><!--/row-fluid-->
			</div><!--/section-question-end-->
		</div><!--test-rules-questions-->
	</div><!--modal-body-->
	<?php if (!$forDynamic) :?>
	<div class="modal-footer fixed-bottom text-right p-20">
		<button type="button" class="btn btn-small btn-success pt-5 pb-5 add-rules disabled" onclick="tmt.test.addRuleQuestionstoSections('<?php echo $this->unique; ?>');">
				<span class="fa fa-check" aria-hidden="true"></span>
				<?php echo Text::_('COM_TMT_TEST_DYNAMIC_RULE_ADD_QUESTIONS') ?>
		</button>
	</div><!--modal-footer-->
<?php endif; ?>
	</form>
</div>
</div>
