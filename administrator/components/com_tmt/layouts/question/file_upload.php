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

$q                = $displayData['item'];
$params           = $q->params;
$componentParams  = $displayData['componentParams'];
$lessonUploadSize = $componentParams->get("lesson_upload_size");
$db               = Factory::getDbo();
?>
<div class="alert alert-info">
	<?php echo Text::_("COM_TMT_QUESTION_FILE_UPLOAD_MSG"); ?>
</div>
<div class="question-params-file_upload form-inline clearfix">
	<div class="control-group">
		<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_FILE_FORMAT_LABEL'); ?>">
			<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_FILE_FORMAT_LABEL');?>
		</div>
		<div class="controls">
		<input type="text" name="jform[params][file_format]" class="inputbox question_params" size="20"
		value="<?php echo !empty($params['file_format']) ? $db->escape($params['file_format']) : ''; ?>" placeholder="e.g. pdf,doc,png"/>
		</div>
	</div>

	<div class="control-group">
		<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_FILE_COUNT_LABEL'); ?>">
			<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_FILE_COUNT_LABEL');?>
		</div>
		<div class="controls">
			<input type="number" name="jform[params][file_count]" id="question_params"
			class="inputbox question_params" size="20" value="<?php echo !empty($params['file_count']) ? $db->escape($params['file_count']) : ''; ?>"/>
		</div>
	</div>

	<div class="control-group">
		<div class="control-label" title="<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_FILE_SIZE_LABEL');?>">
			<?php echo Text::_('COM_TMT_Q_FORM_PARAMS_FILE_SIZE_LABEL');?>
		</div>
		<div class="controls">
			<input type="number" name="jform[params][file_size]" class="inputbox question_params" size="20" value="<?php echo !empty($params['file_size']) ? $db->escape($params['file_size']) : ''; ?>" <?php echo ($lessonUploadSize) ? "max = " . $lessonUploadSize : ''?> />
			<?php echo ($lessonUploadSize) ?  '<p class="help-block">' . Text::sprintf("COM_TMT_Q_FORM_PARAMS_FILE_SIZE_MSG", $lessonUploadSize) . '</p>' : '';?>
		</div>
	</div>
</div>

<?php
