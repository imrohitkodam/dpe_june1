<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

$input         = Factory::getApplication()->input;
$form_id       = $input->get('form_id', '', 'STRING');
$lesson_id     = $input->get('lesson_id', '', 'STRING');
$allAssocFiles = $this->allAssociatedFiles;

$options['relative'] = true;
HTMLHelper::_('script', 'com_tjlms/tjService.js', $options);
HTMLHelper::_('script', 'com_tjlms/tjlmsAdmin.js', $options);
?>
<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">

	<form action="<?php echo Route::_('index.php?option=com_tjlms&view=lessonform&layout=selectassociatefiles&lesson_id=' . $lesson_id . '&tmpl=component&form_id=' . $form_id); ?>" method="POST" name='adminForm' id='adminForm' class="form-horizontal form-validate" enctype="multipart/form-data">
		<div class="modal-header">
			<!-- <button type="button" class="close" onclick="closePopup(1);"; data-dismiss="modal" aria-hidden="true">×</button> -->
			<h3><?php echo Text::_('COM_TJLMS_ASSOCIATE_FILE_HEADER');?></h3>
		</div>

		<div id="filter-bar" class="btn-toolbar">
			<div class="filter-search btn-group pull-left">

				<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo Text::_('COM_TJLMS_ASSOCIATE_FILE_SEARCH_TEXT'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo Text::_('JSEARCH_FILTER'); ?>" />
			</div>
			<div class="btn-group pull-left">
				<button class="btn hasTooltip" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
				<button class="btn hasTooltip" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.getElementById('filter_search').value='';this.form.submit();"><i class="icon-remove"></i></button>

			</div>
			<?php if (!empty($allAssocFiles))
			{
				?>
			 <button type="button" id="upload_files" onclick="tjlmsAdmin.associateFileForm.batchSelect('<?php echo $form_id;?>','lessonform');" class="btn btn-primary" ><?php echo Text::_('COM_TJLMS_UPLOAD_FILES'); ?></button>
				<?php
			} ?>
			<div style="clear:both"></div>

			<?php if(JVERSION >= '3.0'): ?>
			<div class="btn-group pull-right hidden-phone">
				<label for="limit" class="element-invisible"><?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
				<?php //echo $this->pagination->getLimitBox(); ?>
			</div>
			<?php endif;?>

		</div>
		<div class="clearfix"> </div>
		<div id="selectFileDiv">
			<?php if (empty($allAssocFiles)): ?>
				<div class="alert alert-warning">
					<span><?php echo Text::_('COM_TJLMS_NO_ASSOCIATE_FILE');  ?></span>
				</div>

			<?php else: ?>

				<div class="alert alert-info" role="alert"><?php echo Text::_('COM_TJLMS_UPLOAD_FILE_MSG'); ?></div>

				<table class="table table-striped">
					<thead>
							<th width="10%">
								<?php echo HTMLHelper::_('grid.checkall'); ?>
							</th>
							<th width="90%"><?php
									echo HTMLHelper::tooltip(Text::_('COM_TJLMS_FILENAME'), '','', Text::_('COM_TJLMS_FILENAME'));
							?></th>
					</thead>

					<?php	foreach ($allAssocFiles as $i => $s_files):	?>
						<tr class="associtefile">
							<td>
								<?php echo HTMLHelper::_('grid.id', $i, $s_files->id ); ?>
							</td>
							<td>
								<span class="associtefile__name"><?php	echo $s_files->filename; ?></span>
							</td>
						</tr>
					<?php endforeach;	?>
				</table>
			<?php endif; ?>

		</div>
		<input type="hidden" name="boxchecked" value="" />
	</form>
</div>
