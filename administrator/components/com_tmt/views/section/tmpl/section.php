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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

?>

<form action="<?php echo Route::_('index.php?option=com_tmt&view=test&layout=section&lesson_id='. $this->unique); ?>" name="add-section-form" class="tjlms_section_form" id="tjlms_section_form_<?php echo $section_id;?>" method="POST" onsubmit="return false;">
	<div class="tjlms_section_errors alert alert-danger">
		<div class="msg"></div>
	</div>

	<input type="hidden" value="<?php echo $section_id; ?>" name="tjlms_section[id]" id="section_id">
	<input type="hidden" value="<?php echo $test_id; ?>" name="tjlms_section[test_id]" id="test_id">
	<input type="hidden" value="<?php echo $this->unique; ?>" name="tjlms_section[lesson_id]" id="lesson_id">
	<input type="hidden" value="<?php echo $this->questions_count; ?>" name="tjlms_section[questions_count]" id="questions_count">
	<input type="hidden" value="<?php echo $qztype; ?>" name="tjlms_section[qztype]" id="qztype">

	<div class="manage-fields-wrapper add-section-style">
		<div id="form-item-title" class="row-fluid non-labeled">
			<div class="span2 section-title-lable tjlms_text_right">
				<?php echo Text::_("COM_TMT_FORM_LBL_TJSECTION_NAME").' : ';?>
			</div>
			<div style=" " class="span10 tooltip-reference" id="tooltip-reference-title">
				<input type="text" value="<?php echo $section_name; ?>" maxlength="80" data-show-counter="1" data-max-length="80" class="text-input ch-count-field ud-textinput input-block-level section-title" name="tjlms_section[title]" id="title" >
				<span class="ch-count" id="title-counter">64</span>
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span10 submit-row">
			<input type="button" data-loading-text="Save Section" class="btn btn-primary" value="<?php echo Text::_("COM_TMT_SAVE_SECTION")?>" onclick="sectionActions(this,'section.save')">

			<a data-wrapcss="static-content-wrapper" class="cancel-link btn btn-primary" onclick="sectionActions(this,'section.sectionCancel' ,'<?php echo $section_name; ?>')"> <?php echo Text::_("COM_TJLMS_CANCEL_BUTTON")?> </a>

			<span class="ajax-loader-tiny js-bottom-loader hidden"></span>
		</div>
	</div>
	<input type="hidden" value="<?php echo $section_state;?>" name="tjlms_section[state]">
	<input type="hidden" name="option" value="com_tmt" />
	<input type="hidden" name="task" id="task" value="" />

	<?php echo HTMLHelper::_('form.token'); ?>
</form>
