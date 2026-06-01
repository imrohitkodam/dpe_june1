<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access

defined('_JEXEC') or die;
use Joomla\CMS\Utility\Utility;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('bootstrap.renderModal', 'a.modal');

$k    = 0;
$cMax = (int) $this->params->get('lesson_upload_size', '0', 'INT');

if (method_exists('Utility', 'getMaxUploadSize'))
{
	$cMax = Utility::getMaxUploadSize($cMax . 'MB');
}

$allowedFileSize = HTMLHelper::_('number.bytes', $cMax, 'MB');

?>
<div class="container-fluid">
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
	<form action="<?php echo Route::_('index.php?option=com_tjlms&view=lessonform&id='. $this->lessonId); ?>" method="post" enctype="multipart/form-data" name="adminForm" data-js-unique="<?php echo $this->formId;?>" id="lesson-associatefile-form_<?php echo $this->formId;?>" class="form-validate lesson-associatefile-form" >
		<div class="clearfix mb-10"> </div>

		<div class="row-fluid">
			<div class="span12 oldassocfiles">

		<?php
			$tableclass = '';
			$style      = '';

			if (empty($this->item->oldAssociateFiles))
			{
				$tableclass = "tjlms_display_none";
				$style = 'style="display: none;"';
			}
			?>

			<table id="list_selected_files" class="table table-bordered table-striped table-responsive list_selected_files <?php echo $tableclass;?>" <?php echo $style; ?>>
			 <caption class="text-left"><strong><?php echo Text::_('COM_TJLMS_YOUR_ASSOC_FILES'); ?></strong></caption>
			<thead>
				<tr class="tableheading">
					<th class="tjlmscenter"><?php echo Text::_('COM_TJLMS_FILENAME'); ?></th>
					<th class="tjlmscenter"><?php echo Text::_('COM_TJLMS_REMOVE_FILE'); ?></th>
				</tr>
			</thead>
				<tbody>
				<?php if (!empty($this->item->oldAssociateFiles)){ ?>

					<?php foreach ($this->item->oldAssociateFiles as $assocfiles): ?>

						<tr id="assocfiletr_<?php echo $assocfiles->media_id; ?>" data-js-id="associated-file">
							<td class="tjlmscenter"><span><?php echo $assocfiles->filename; ?></span></td>
							<td class="tjlmscenter"><i data-js-id="associated-file-remove" data-js-val="<?php echo $assocfiles->media_id;?>" title="<?php echo Text::_('COM_TJLMS_REMOVE_FILE_TITLE'); ?>" class="remove btn">×</i></td>
						</tr>

					<?php endforeach; ?>

				<?php } ?>
				</tbody>
			</table>

			<?php
			$alertclass = '';
			if (!empty($this->item->oldAssociateFiles)){
				$alertclass = "tjlms_display_none";
			 }?>

			<div class="alert alert-info no_selected_files  <?php echo $alertclass;?>">
				<?php echo Text::_('COM_TJLMS_NO_ASSOC_FILES'); ?>
			</div>

			</div>
		</div>

		<div class="row-fluid">
			<div class="span6">
				<div class="row-fluid">
					<div class="span3 help-block">
					<?php echo Text::_('COM_TJLMS_SELECT_ASSOC_FILES'); ?>
					</div>

					<div class="span3 selectfilebtn">
						<?php
							$link = Uri::root() . "index.php?option=com_tjlms&view=lessonform&layout=selectassociatefiles&tmpl=component&id=" . $this->item->id . "&form_id=" . $this->formId;
						?>

						<a id="selectFileLink" class="btn btn-primary btn-block" data-js-role='tjmodal' data-js-link="<?php echo $link; ?>"><?php echo Text::_('COM_TJLMS_SELECT'); ?></a>
					</div>
				</div>
			</div>
			<div class="span6">
				<div class="row-fluid">
				<div class="span4 help-block"><?php echo Text::_('COM_TJLMS_NEW_ASSOC_FILES'); ?></div>

				<div id="file_container<?php echo $k; ?>" class="file_container form-inline span8">
					<div class="controls file_browse_area">
						<div id="associate" class="fileupload fileupload-new" data-provides="fileupload">
							<div class="input-append">
								<div class="uneditable-input span3">
									<span class="fileupload-preview">
									<?php echo Text::sprintf('COM_TJLMS_UPLOAD_FILE_WITH_EXTENSION', 'any', $allowedFileSize);?>

									</span>
								</div>
								<span class="btn btn-primary btn-file" data-js-type="associate_file_upload">
									<span class="fileupload-new"><?php echo Text::_("COM_TJLMS_BROWSE");?></span>
									<input type="file" data-allowedsize="<?php echo trim(str_replace('MB','',$allowedFileSize));?>" id="lesson_files<?php echo $k; ?>" name="lesson_files[<?php echo $k; ?>][file]">

									<input type="hidden" id="assocFileMedia<?php echo $k; ?>" class="assocFileMedia" name="lesson_files[][media_id]" value=""/>

								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			</div>
		</div>
		<!--div class="span1">
			<button class="btn btn-small btn-success" type="button" id='add' onclick="addClone('file_container','file_container','<?php echo $form_id; ?>');" 	title='<?php echo Text::_('COM_TJLMS_ADD_BUTTON');?>'>
			<i class="icon-plus icon-white"></i>
			</button>
		</div-->

		<div style="clear:both"></div>

		<!--END-->
		<input type="hidden" name="option" value="com_tjlms" />
		<input type="hidden" name="task" value="lessonform.updateassocfiles" />
		<input type="hidden" name="lesson_format[format]" id="jform_format" value="associate">
		<input type="hidden" name="lesson_format[format_id]" id="lesson_format_id" value="0">
		<input type="hidden" name="lesson_format[id]" id="lesson_id" data-js-id="id" value="<?php echo ($this->item) ? $this->item->id : 0;?>">
		<?php echo HTMLHelper::_('form.token'); ?>

		<!--div class="form-actions">
			<img class="loading" src="<?php echo Uri::root() . 'components/com_tjlms/assets/images/loading_squares.gif';?>">
			<button type="button" class="btn btn-primary" onclick="lessonBackButton('<?php echo  $form_id ?>')">
				<i class="fa fa-arrow-circle-o-left"></i><?php echo Text::_('COM_TJLMS_PREV'); ?>
			</button>
			<button type="button" class="btn btn-primary" onclick="associateaction('<?php echo  $form_id ?>', 1)"><?php echo Text::_('COM_TJLMS_SAVE_CLOSE'); ?></button>
		</div-->
	</form>
</div>
</div>
