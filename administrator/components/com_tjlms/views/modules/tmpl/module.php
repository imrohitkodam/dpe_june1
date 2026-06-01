<?php
use Joomla\String\StringHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

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
use Joomla\CMS\Uri\Uri;
$imgClass = 'hide';
?>

<form action="<?php echo Route::_('index.php?option=com_tjlms&view=modules&course_id='. $this->course_id); ?>" name="add-module-form" enctype="multipart/form-data" class="tjlms_module_form" id="tjlms_module_form_<?php echo $modId;?>" method="POST" onsubmit="return false;">

	<input type="hidden" value="<?php	echo	$modId;	?>" name="tjlms_module[id]" id="mod_id">
	<input type="hidden" value="<?php	echo	$courseId;	?>" name="tjlms_module[course_id]" id="course_id">
	<div class="manage-fields-wrapper add-module-style">
		<div id="form-item-title" class="row control-group">
			<div class="col-lg-2 module-title-lable tjlms_text_left" id="module-title<?php echo $modId;?>-lbl">
				<?php echo Text::_("COM_TJLMS_FORM_LBL_TJMODULE_NAME").'  : ';?>
			</div>
			<div style=" " class="col-lg-10 tooltip-reference" id="tooltip-reference-title">
				<input type="text" value="<?php	echo htmlentities($modName);	?>" maxlength="80" data-show-counter="1" data-max-length="80" class="text-input ch-count-field ud-textinput input-block-level module-title required form-control" name="tjlms_module[name]" id="module-title<?php echo $modId;?>">
				<span class="ch-count" id="title-counter">64</span>
			</div>
		</div>
	<div class="tjlms_module_image_desc hide">
		<div class="tjlms_module_desc row control-group">
			<div class="col-lg-2 tjlms_text_left" id="module-description<?php echo $modId;?>-lbl">
				<?php echo Text::_("COM_TJLMS_FORM_LBL_TJMODULE_DESCRIPTION"); ?>
			</div>
			<div class="col-lg-10">
			<textarea class="text-input ch-count-field ud-textinput input-block-level form-control" name="tjlms_module[description]" id="module-description<?php echo $modId;?>" cols="80" rows="3" placeholder="<?php echo Text::_("COM_TJLMS_FORM_DESC_TJMODULE_DESCRIPTION").'  : ';?>" ><?php	echo $modDescription;	?></textarea>
			</div>
		</div>

		<div class="tjlms_module_image row control-group">
			<div class="col-lg-2 tjlms_text_left" id="module-image<?php echo $modId;?>-lbl">
				<?php echo Text::_("COM_TJLMS_FORM_LBL_TJMODULE_IMAGE"); ?>
			</div>
			<div class="col-lg-5">
				<input type="file" name="tjlms_module[image]" class="form-control" id="module-image<?php echo $modId;?>" accept="image/*">
			</div>
			<div class="col-lg-5">
				<br>
					<?php if ($modId > 0 && !empty($modImage)) 
					   {
								$imgClass ='';
					   }
					?>
					<div class="tjlms_module_thumbail thumbnail <?php echo $imgClass;?>">
    				<span>
    					<?php echo Text::_("COM_TJLMS_FORM_LBL_TJMODULE_IMAGE_TITLE"); ?>
    				</span>
				<!-- If edit show IMage of Module-->
					<button class="close" onclick="tjlmsAdmin.modules.deleteMedia('<?php echo $modId;?>')">×</button>
						<?php $moduleImgPath = Uri::root() . StringHelper::ltrim($this->moduleImagePath , '/') . $modImage; ?>
						<img src="<?php echo $moduleImgPath;?>" class="tjlms_module_image_path" />
					</div>
					<input type="hidden" name="tjlms_module[moduleimage]" class="tjlms_module_thumbnail_image" value="<?php echo $modImage;?>" />
					<?php HTMLHelper::_('jquery.token'); ?>
			</div>
		</div>
		</div>
		<div class="row">
		<div class="col-lg-6 tjlms_text_left"></div>
		<div class="toggleModuleButton text_underline" onclick="tjlmsAdmin.modules.toggleModuleAdditionalInfo(<?php echo $modId; ?>)"><a class="text-blue"><?php echo Text::_("COM_TJLMS_ADDITIONAL_DETAILS")?></a></div>
		</div>
	</div>

	<div class="row-fluid">
		<div class="col-lg-2"></div>
		<div class="col-lg-10 submit-row">
			<input type="button" data-loading-text="Save Section" class="btn btn-primary" value="<?php echo Text::_("COM_TJLMS_SAVE_MODULE")?>" onclick="tjlmsAdmin.modules.editModule('<?php echo $modId;?>')">

			<!--<a data-wrapcss="static-content-wrapper" class="cancel-link" onclick="hideeditModule('<?php echo $courseId; ?>','<?php echo $modId; ?>')"> cancel </a>-->
			<a data-wrapcss="static-content-wrapper" class="cancel-link btn btn-primary" onclick="tjlmsAdmin.modules.toggleForm('<?php echo $modId; ?>', 'hide')"> <?php echo Text::_("COM_TJLMS_CANCEL_BUTTON")?> </a>

			<span class="ajax-loader-tiny js-bottom-loader hidden"></span>
		</div>
	</div>
	<input type="hidden" value="<?php	echo $modState;	?>" name="tjlms_module[state]" id="state<?php echo $modId;?>">

	<?php echo HTMLHelper::_('form.token'); ?>
</form>

