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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

if (!empty($this->item->format_details))
{
	$lang_con_for_upload_formt_file	= "COM_TJLMS_UPLOAD_NEW_FORMAT";
}
else
{
	$lang_con_for_upload_formt_file	= "COM_TJLMS_UPLOAD_FORMAT";
}

$lessonSubformat = '';

if (!empty($this->item->format) && !empty($this->item->sub_format))
{
	$temp            = explode('.', $this->item->sub_format);
	$lessonSubformat = $temp[0];
}

?>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
<form action="<?php echo Route::_('index.php?option=com_tjlms&view=lessonform&id='. $this->lessonId); ?>" method="post" name="adminForm" id="lesson-format-form_<?php echo $this->formId;?>" class="form-validate form-horizontal lesson-format-form" >
	<div class="clearfix mb-10"> </div>
	<input type="hidden" class="extra_validations" data-js-validation-functions="tjlmsAdmin.lessonFormatForm.validate">
	<div class="container-fluid">

		<!--div for show selected formats options -->
		<div class="row">
			<?php
			if(empty($this->format)){
			?>
				<div id="lesson_format_msg" class="alert alert-info" >
					<?php echo Text::_("COM_TJLMS_FORMAT_CHOOSE_MSG");?>
				</div>
			<?php
			}

			$subformat = array();

			foreach ($this->subformatOptions as $sf)
			{
				$subformat[] = HTMLHelper::_('select.option', $sf['id'], $sf['name']);
			}
?>
		<div id="lesson_format">
			<!-- Form elements to show lesson format -->
			<div class="lesson_format" id="<?php echo $this->format ?>">
				<div class="control-group">
					<div class="control-label">
						<label title="<?php echo Text::_("COM_TJLMS_".strtoupper($this->format)."_SUBFORMAT_OPTIONS");?>" ><?php echo Text::_("COM_TJLMS_".strtoupper($this->format)."_SUBFORMAT_OPTIONS");?></label>
					</div>
					<div class="controls" id="<?php echo $lesson_format ?>_subformat_options">

				<?php


				 echo HTMLHelper::_('select.genericlist', $subformat, "lesson_format[subformat]",
							'class="class_' . $this->format . '_subformat" data-js-id="subformat"', "value", "text", $lessonSubformat);
				?>
					</div>
				</div>
				<?php

				if (!$lessonSubformat)
				{
					$lessonSubformat = $this->subformatOptions[0]['id'];
				}

				foreach ($this->subformatOptions as $ind => $plugin)
				{
					$format = 'tj' . $this->format;
					$comp_params = ComponentHelper::getParams('com_tjlms');

					PluginHelper::importPlugin($this->format, $plugin['id']);

					// Call the plugin and get the result
					$results = Factory::getApplication()->triggerEvent('onGetSubFormat_' . $plugin['id'] . 'ContentHTML', array($this->moduleId, $this->item->id, $this->item, $this->params, $this->formId));
				?>
				<?php
					$class="hide";

					if (($lessonSubformat && $lessonSubformat == $plugin['id']))
					{
						$class= "";
					}
				?>

				<div data-subformat="<?php echo strtolower($plugin['id']);?>" class="<?php echo $this->format ?>_subformat form-horizontal subformat <?php echo $class;?>">
						<?php echo $results[0];?>

				</div>
			<?php } ?>
			</div>
		</div>
		<!--END-->
			<input type="hidden" name="option" value="com_tjlms" />
			<input type="hidden" name="task" value="lessonform.updateformat" />
			<input type="hidden" name="lesson_format[format]" data-js-id="format" value="<?php echo (!empty($this->format)) ? $this->format : '';?>">
			<input type="hidden" name="lesson_format[media_id]" data-js-id="media_id" id="lesson_format_id" value="<?php echo (!empty($this->format)) ? $this->item->media_id: 0;?>">
			<input type="hidden" name="lesson_format[id]" data-js-id="id" value="<?php echo (!empty($this->item->id)) ? $this->item->id : 0;?>">
			<input type="hidden" name="lesson_format[form_id]" id="form_id" value="<?php echo $this->formId; ?>" />
			<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</form>
</div>
