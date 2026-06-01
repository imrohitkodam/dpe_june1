use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
<li id="sectionlist_<?php echo $row->id; ?>" class="mod_outer">
	<div class="row-fluid tjlms_section question_row">
		<div class="content-li span10">
			<i class="icon-menu sectionSortingHandler" title="Sort this Section"></i>
			<i class="icon-book icon-white"></i>
			<span class="tjlms_section_title"><?php echo $row->title; ?></span>
		</div>
		<div class="tjlms-actions btn-group non-accordian span2">
			<div class="section-functionality-icons row-fluid">
				<a class="editsectionlink" title="Edit this Section" onclick="tjform.editSection('<?php echo $data['test_id']?>','<?php echo $row->id; ?>')">
					<span class="icon-edit"></span>
				</a>

				<a class="sectiondelete tjlms_display_none" title="Delete this Section" onclick="tjform.delete('<?php echo $data['test_id']; ?>','<?php echo $row->id; ?>');" >
					<span class="icon-trash"></span>
				</a>
			</div>
		</div>
	</div>
		<div class="section-edit-form" id="add_section_form_<?php echo $row->id;?>" style='padding-top:40px;'>
			<?php
						require_once JPATH_ROOT . '/libraries/techjoomla/common.php';
						$section_html='';
						$test_id = $data['test_id'];
						$section_id = $row->id;
						$section_name = $row->title;
						$section_state	= $data['state'];
						$qztype = $data['qztype'];
						$tjcommon	=	new TechjoomlaCommon();
						$layout = $tjcommon->getViewpath('com_tmt','section','section','ADMIN','ADMIN');
						ob_start();
						include($layout);
						$section_html.= ob_get_contents();
						ob_end_clean();
						echo $section_html;
					?>

	</div>

	<div class="row-fluid action">
	<?php
		if($data['questions_count'])
		{	?>

		<?php $link = Route::_(Uri::base()."index.php?option=com_tmt&view=questions&layout=qpopup&tmpl=component&fromPlugin=1&qztype=". $data['qztype'] . "&unique=".$data['lesson_id']."&test_id=".$data['test_id']);	?>

		<div class="span4">
			<a onclick="opentmtSqueezeBoxForm('<?php echo  $link; ?>','<?php echo $data['lesson_id'];?>','<?php echo $row->id;?>')" class="btn btn-primary btn-block"><?php echo Text::_( 'COM_TMT_FORM_TEST_ADD_QUESTIONS'); ?></a>
		</div>

		<?php
		$link = Route::_(Uri::base()."index.php?option=com_tmt&view=test&layout=addrules&tmpl=component&fromPlugin=1&qztype=". $data['qztype'] . "&unique=".$data['lesson_id']."&test-id=".$data['test_id']);?>

		<div class="span4">
			<a class="btn btn-primary btn-block" href="#" onclick="opentmtSqueezeBoxForm('<?php echo $link; ?>','<?php echo $data['lesson_id']; ?>','<?php echo $row->id; ?>')" ><?php echo Text::_( 'COM_TMT_FORM_TEST_AUTO_GENERATE_QP' )?></a>
		</div>
<?php	}
		$link = Route::_(Uri::base()."index.php?option=com_tmt&view=question&fromPlugin=1&qztype=". $data['qztype'] . "&unique=".$data['lesson_id']."&tmpl=component&addquiz=1&test_id=".$data['test_id']);	?>

		<div class="span4">
			<a onclick="opentmtSqueezeBoxForm('<?php echo $link; ?>','<?php echo $data['lesson_id']; ?>','<?php echo $row->id; ?>')" class="btn btn-primary btn-block"><?php echo Text::_( 'COM_TMT_FORM_TEST_ADD_QUESTION' );?></a>
		</div>
		<input type="hidden" name="section_id" value="<?php echo $row->id; ?>"/>
	</div>

	<div class="question_paper">
		<div class="row-fluid">
			<div id="questions_block" class="row-fluid">
				<div id="questions_container" class="row-fluid">
					<div class="thead row-fluid">
						<div class="tr row-fluid question_head_row ques-alignment">
							<div class="span1 center">
								<?php Text::_("COM_TMT_TEST_FORM_ORDER"); ?>
							</div>
							<div class="span4">
								<?php echo Text::_("COM_TMT_TEST_FORM_QUESTION"); ?>
							</div>
							<div class="span2">
								<?php echo Text::_("COM_TMT_TEST_FORM_CATEGORY"); ?>
							</div>
							<div class="span3">
								<?php echo Text::_("COM_TMT_TEST_FORM_TYPE"); ?>
							</div>

							<?php
							if($data['qztype'] == "quiz" )
							{	?>
							<div class="span1">
								<?php echo Text::_("COM_TMT_TEST_FORM_MARKS"); ?>
							</div>
							<?php	}	?>

							<div class="span1">
								<?php echo Text::_("COM_TMT_TEST_FORM_REMOVE"); ?>
							</div>
						</div>
					</div>
					<div id="question_paper_<?php echo $row->id; ?>" class="questionInSection ui-sortable connectedSortable curriculum-lesson-ul question_paper" style=" ">
					</div>
				</div>
			</div>
		</div>
	</div>
</li>
