<?php
/**
 * @version     1.0.0
 * @package     com_tjlms
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Aniket <aniket_c@tekdi.net> - http://www.techjoomla.com
 */
// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>

<!--test_section-->
<div class="test_section <?php echo ($section->state) ? 'published' : 'unpublished';?> <?php echo (!$section->id) ? 'forclone d-none' :'';?>" data-js-unique="<?php echo $this->item->id . "_" .$section->id;?>" id="test_section_<?php echo $section->id;?>" data-js-id="test-section" data-js-itemid="<?php echo $section->id;?>">
	<div class="accordion br-0">
		<div class="accordion-item br-0">
			<!--accordian-heading-->
			<div class="accordion-header bg-faded test_section__header" data-js-id="section-header" aria-expanded="false">
				<!--accordian-toggle-->
				<button class="accordion-button" data-bs-target="#collapse<?php echo $section->id;?>" data-bs-toggle="collapse" >
					<!--row-->
					<div class="container-fluid">
						<div class="row">
						<!--col-md-6-->
							<div class="col-md-7">
								<i class="icon-menu sectionSortingHandler valign-middle non-accordian" title="Sort this Section"></i>
								<span class="font-bold" data-js-id="section-title"><?php echo $section->title;?></span>
								<span data-js-attr="action-edit-title">
									<i class="fa fa-pencil fa-2" aria-hidden="true"></i>
								</span>
							</div><!--/col-md-6-->
							<!--col-md-3-->
							<div class="col-md-3 text-end">
								<!--STATE MODULE BUTTON-->
								<span class="test-section__header_questions d-inline-flex">
									<label class="font-bold"><?php echo Text::_("COM_TMT_SECTION_LABEL_QUESTIONS");?></label>
									<span class="font-bold" data-js-id="questions"><?php echo $section->qcnt;?></span>
								</span>
								<?php if ($this->gradingtype == 'quiz') : ?>
									<span class="seperator"> | </span>
									<span  class="test-section__header_marks d-inline-flex">
										<label class="font-bold"><?php echo Text::_("COM_TMT_SECTION_LABEL_MARKS");?></label>
										<span class="font-bold" data-js-id="marks"><?php echo $section->marks;?></span>
									</span>
								<?php endif; ?>
							</div><!--/col-md-3-->
							<!--col-md-3-->
							<div class="col-md-2 text-end">
								<!--STATE MODULE BUTTON-->
								<a class="test-section__header-edit-action hide"  title="<?php echo ($section->state == 0) ? Text::_('COM_TMT_TEST_PUBLISH_SECTION') : Text::_('COM_TMT_TEST_UNPUBLISH_SECTION'); ?>" data-js-id="change-section-state">
									<i class="non-accordian <?php echo ($section->state == 1) ? 'icon-publish' : 'icon-unpublish';?>" data-js-id="section-state-icon"></i>
									<input type="hidden" data-js-id="section-state" value="<?php echo $section->state;?>">
								</a>
								<!--DELETE MODULE BUTTON-->
								<a class="test-section__header-edit-action hide non-accordian" title="<?php echo Text::_('COM_TJLMS_SECTION_DELETE'); ?>" data-js-id="delete-section">
									<span class="non-accordian icon-trash"></span>
								</a>
								<i class="fa" data-jstoggle="collapse" aria-hidden="true"></i>
							</div><!--/col-md-3-->
						</div>
					</div><!--row-->

				</button><!--/accordian-toggle-->
			</div><!--accordian-heading-->

			<div class="row d-none" data-js-id="section-edit-form">
				<?php
					require_once JPATH_ROOT . '/libraries/techjoomla/common.php';
					$tjcommon = new TechjoomlaCommon();
					$sectionEditlayout = $tjcommon->getViewpath('com_tmt','test','sectioncreate','ADMIN','ADMIN');

					ob_start();
					include($sectionEditlayout);
					$sectionEditHtml = ob_get_contents();
					ob_end_clean();
					echo $sectionEditHtml;
				?>
			</div>

			<div id="collapse<?php echo $section->id;?>" class="lessons-module accordion-collapse collapse show" data-js-id="panel-collapse">
				<div class="accordion-body">
						<?php
							$this->unique = $this->item->id . "_" .$section->id;
						?>
					<?php if ($this->gradingtype == 'quiz'): ?>
					<div data-js-attr="set-options">
					<?php
						$forDynamic = 1;

						// Load all default answer-templates
						$path = JPATH_ADMINISTRATOR. '/components/com_tmt/views/test/tmpl/dynamicrules.php';
						ob_start();
						include($path);
						$html = ob_get_contents();
						ob_end_clean();
						echo $html;
					?>
					</div>
				<?php endif; ?>

					<div data-js-id="questions_container" class="questions_container">
						<?php

							foreach ($section->questions as $que)
							{
								$displayData                = (array) $que;
								$displayData['type']        = $this->item->type;

								// $displayData['showQueSort'] = ($this->item->type == "set") ? "1" : "0";
								$displayData['gradingtype'] = $this->item->gradingtype;

								if ($this->item->type == 'set')
								{
									$displayData['canDeleteQ'] = 1;
								}
								else
								{
									$displayData['canDeleteQ'] = ($maxattempt > 0) ? 0 : 1;
								}

								// Load all default answer-templates
								$path = JPATH_ADMINISTRATOR . '/components/com_tmt/layouts/questionrowhtml.php';
								ob_start();
								include $path;
								$html = ob_get_contents();
								ob_end_clean();
								echo $html;

							}
						?>
					</div>

					<div class="row my-10 questionbtn-group" id="toolbar" data-js-attr="plain-options">
						<div class="text-end justify-content-end" id="questions_btns">

						<?php
						$linkAppend =  "&tmpl=component&gradingtype=" . $this->gradingtype;
						?>
						<?php if( $this->questions_count){ ?>
							<?php
								$link = Route::_("index.php?option=com_tmt&view=questions&layout=modal". $linkAppend);
							?>
								<a class="btn mr-5" onclick="tmt.section.openQuestionPopups(this, 'addPickQuestionModal'); jQuery('#addPickQuestionModal').removeClass('hide')">
									<span class="icon-filter bg-primary"></span>
									<?php echo Text::_( 'COM_TMT_FORM_TEST_ADD_QUESTIONS' ); ?>
								</a>
							<?php $link = Route::_("index.php?option=com_tmt&view=test&layout=dynamicrules" .$linkAppend);?>
								<a class="btn mr-5" onclick="tmt.section.openQuestionPopups(this, 'addDynamicRulesModal'); jQuery('#addDynamicRulesModal').removeClass('hide')">
									<span class="icon-question bg-primary"></span>
									<?php echo Text::_( 'COM_TMT_FORM_TEST_AUTO_GENERATE_QP' ); ?>
								</a>
						<?php } ?>
							<?php $link = Route::_("index.php?option=com_tmt&view=question&layout=edit".( isset($this->addquiz) ? "&addquiz=1" : "" ) . $linkAppend  . "&target=section"); ?>
							
							<a class="btn mr-5" onclick="tmt.section.openQuestionPopups(this, 'addQuestionModal'); jQuery('#addQuestionModal').removeClass('hide')">
									<span class="icon-plus-circle bg-primary"></span>
									<?php echo Text::_( 'COM_TMT_FORM_TEST_ADD_QUESTION' ); ?>
							</a>
							
						</div><!--questions_btns-->
					</div><!--row-->
				</div>
			</div>					
		</div>
	</div>
</div><!--/test_section-->

<?php 
	$link = Route::_("index.php?option=com_tmt&view=question&layout=edit".( isset($this->addquiz) ? "&addquiz=1" : "" ) . $linkAppend  . "&target=section");
	echo HTMLHelper::_(
		'bootstrap.renderModal',
		'addQuestionModal',
		array(
			'url'        => $link,
			'width'      => '800px',
			'height'     => '300px',
			'modalWidth' => '80',
			'bodyHeight' => '70'
		)
	);

	$link = Route::_("index.php?option=com_tmt&view=test&layout=dynamicrules" . $linkAppend  . "&target=section");
	echo HTMLHelper::_(
		'bootstrap.renderModal',
		'addDynamicRulesModal',
		array(
			'url'        => $link,
			'width'      => '800px',
			'height'     => '300px',
			'modalWidth' => '80',
			'bodyHeight' => '70'
		)
	);

	$link = Route::_("index.php?option=com_tmt&view=questions&layout=modal". $linkAppend . "&target=section");
	echo HTMLHelper::_(
		'bootstrap.renderModal',
		'addPickQuestionModal',
		array(
			'url'        => $link,
			'width'      => '800px',
			'height'     => '300px',
			'modalWidth' => '80',
			'bodyHeight' => '70'
		)
	);
?>	

