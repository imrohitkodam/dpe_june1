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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$q               = $displayData['question'];
$params          = $displayData['params'];
$fileCountLimit  = $accept = '';
$fileSizeAllowed = $params->get('lesson_upload_size', '0', 'INT');
$db              = Factory::getDbo();

if (!empty($q->params))
{
	$queParams = json_decode($q->params);

	$fileCountLimit = Text::sprintf('COM_TMT_UPLOAD_FILE_WITH_COUNT_LIMIT', $this->escape($queParams->file_count));

	if ($queParams->file_format)
	{
		$accept = 'accept="' . $this->escape($queParams->file_format) . '"';

		$fileFormatAllowed = Text::sprintf('COM_TMT_UPLOAD_FILE_WITH_FORMAT', $this->escape($queParams->file_format));
	}

	if ($queParams->file_size)
	{
		$fileSizeAllowed = min($this->escape($queParams->file_size), $params->get('lesson_upload_size', '0', 'INT'));
	}
}
?>

<?php

$filename = Text::sprintf('COM_TMT_UPLOAD_FILE_WITH_EXTENSION', $fileSizeAllowed);
$file_browse_lang = 'COM_TMT_UPLOAD_FILE_BROWSE';

if (!empty($q->userAnswer))
{
	$file_browse_lang = 'COM_TMT_UPLOAD_FILE_CHANGE';
}

?>

<div class="col-sm-12">
	<div class="fileupload fileupload-new form-inline">
		<div class="input-group">
			<div class="uneditable-input d-inline-block mb-0 br-0 p-5">
				<span class="fileupload-preview">
					<?php echo $filename;?>
				</span>
				<?php if (!empty($queParams->file_count)) : ?>
					<span class="fileupload-preview">
						<?php echo $fileCountLimit;?>
					</span>
				<?php endif; ?>
				<?php if (!empty($queParams->file_format)) : ?>
					<span class="fileupload-preview">
						<?php echo $fileFormatAllowed;?>
					</span>
				<?php endif; ?>

			</div>

			<div class="input-group-btn d-inline-block">
				<span class="btn btn-file btn-default">
					<span class="fileupload-new"><?php echo Text::_($file_browse_lang);?></span>
					<input type="file" id="lesson_files_<?php echo $q->question_id;?>"
						name="questions[upload][<?php echo $q->question_id;?>]" <?php echo $accept;?> />
				</span>
			</div>
		</div>
	</div><!--fileupload fileupload-new-->

	<div class="clearfix"></div>
	<div id='msg_<?php echo $q->question_id;?>'></div>

	<div class="container-fluid">
		<?php
		$class = '';
		if (empty($q->userAnswer))
		{
			$class = "d-none";
		}
		?>

		<div class="row font-600 pt-5 pb-5 <?php echo $class;?>" data-js-id="uploaded-file-list-header">
			<?php echo Text::_("COM_TMT_ALREDY_UPLOADED");?>
		</div>

		<div class="row" data-js-id="uploaded-file-list">
			<?php
			if (!empty($q->userAnswer))
			{
				foreach ($q->userAnswer as $userAnswer)
				{
					?>
					<div class="col-sm-6"
						data-js-id="each-file"
						data-js-itemid="<?php echo $userAnswer['media_id'];?>"
						data-js-answerid="<?php echo $q->userAnswerId ?>">
						<a class="mr-5" href="<?php echo $userAnswer['path'];?>" target="_blank">
							<?php echo $filename = $userAnswer['org_filename'];?>
						</a>

						<a href="javascript:void(0)" data-js-id="delete" title="<?php echo Text::_("COM_TMT_DELETE_ITEM");?>">
							<i class="fa fa-trash" aria-hidden="true"></i>
						</a>
					</div>

					<input type="hidden"
						name="questions[upload][<?php echo $q->question_id;?>][]"
						value="<?php echo  $userAnswer['media_id']; ?>"/>
					<?php
				}
			}
			?>
		</div>
	</div>
</div>
