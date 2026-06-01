<?php
	$title = $required = $description = '';
	if ($section->id)
	{
		$title = $section->title;
		$description = $section->description;
		$required = "required";
	}

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
?>

<form class="form-validate form-horizontal" method="post" name="adminForm" data-js-id="testFormsection" id="testFormsection<?php echo $section->id;?>" data-js-itemid="<?php echo $section->id;?>">
	<!--section-create-->
	<div class="section-create p-20">
		<!--mr-30-->
		<div class="row mr-30">
			<!--row-fluid-->
			<div class="control-group">	
				<!--span2-->
				<div class="col-md-2">
					<label for="section_title<?php echo $section->id?>"><?php echo Text::_("COM_TMT_SECTION_TITLE");?></label>
				</div><!--/span1-->
				<!--span10-->
				<div class="col-md-7 col-sm-10">
					<span data-js-attr="section-title">
						<input type="text" id="section_title<?php echo $section->id?>" placeholder="<?php echo Text::_("COM_TMT_SECTION_TITLE")?>" maxlength="80" data-show-counter="1" data-max-length="80" class="form-control text-input input-block-level section-title pt-5 pb-5 <?php echo $required;?>" name="section[title]" id="title" value="<?php echo htmlentities($title);?>">
					</span>
				</div><!--/span10-->
			</div><!--/row-fluid-end-->
			
			<div class="control-group">	
				<!--span2-->
				<div class="col-md-2">
					<label for="section_description<?php echo $section->id?>"><?php echo Text::_("COM_TMT_FORM_LBL_TJSECTION");?></label>
				</div><!--/span1-->
				<!--span10-->
				<div class="col-md-7 col-sm-10 ">
					<span data-js-attr="section-description">
						<textarea id="section_description<?php echo $section->id?>"  data-show-counter="1" class="form-control input-block-level" name="section[description]" id="description"><?php echo $description;?></textarea>
					</span>
				</div><!--/span10-->
			</div>
			<div class="row-fluid">
				<!--d-flex-->
				<div class="d-flex mt-1">
					<span class="tjlms-cancel-btn">
						<button class="btn  btn-primary pt-5 pb-5" type="button" data-js-id="action-save-section">
							<i class="fa fa-check"></i>
							<?php echo Text::_("COM_TMT_BUTTON_SAVE");?>
						</button>
						<button class="btn btn-default  pt-5 pb-5 ml-5" type="button" data-js-id="action-cancelsave-section">
							<i class="fa fa-times"></i>
							<?php echo Text::_("COM_TMT_BUTTON_CANCEL");?>
						</button>
					</span>
				</div>
				<!--/d-flex-->
			</div>
		</div><!--/mr-30-->
	</div><!--/section-create-->
	<input type="hidden" name="section[id]" value="<?php echo $section->id?>"/>
	<input type="hidden" name="section[test_id]" data-js-id="id" value="<?php $this->item->id?>"/>
	<input type="hidden" value="<?php echo 'quiz' ?>" name="section[qztype]" id="qztype">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
