<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

// Import CSS
$conf        = Factory::getConfig();
$editorName = $conf->get('editor');
$input       = Factory::getApplication()->input;
$id          = $input->get('id', '', 'INT');
?>

<?php
Factory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task) {
		if (task == "alert.cancel") {
			Joomla.submitform(task, document.getElementById("alert-form"));
		}
		else {
			if (task != "alert.cancel" && document.formvalidator.isValid(document.getElementById("alert-form"))) {
				/*Get array of allowed frequencies*/
				var allowed_frequencies = jQuery("#jform_allowed_freq").val();

				/*Get the default frequency*/
				var default_freq = jQuery("#jform_default_freq").val();

				/*Check default frequency exist in allowed frequency*/
				var valid_default_freq = allowed_frequencies.indexOf(default_freq);

				/*Check default frequency exist in allowed frequency*/
				if (valid_default_freq >= 0) {
					Joomla.submitform(task, document.getElementById("alert-form"));
				}
				else
				{
					alert("Please select only default frquency which is in selected allowed frequencies");

					return false;
				}
			}
			else {
				alert("' . $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED')) . '");
			}
		}
	}
'
);

if (!$id)
{
	Factory::getDocument()->addScriptDeclaration('
		jQuery(document).ready(function() {
			/*Time used to avoid blank editor area when creating new alert*/
			setTimeout(function() {
				jQuery.ajax({
					url: "index.php?option=com_jmailalerts&task=alert.loadTemplate",
					type: "GET",
					dataType: "json",
					success: function(data)
					{
						var editor = "' . $editorName . '";

						if (editor == "tinymce" ||  editor == "jce")
						{
							jQuery("iframe").contents().find("body#tinymce").html(data["template"]);
						}
						else if (editor == "none")
						{
							jQuery(\'textarea[name="jform[template]"]\').val(data["template"]);
						}
						else
						{
							/*@cke_show_borders*/
							jQuery("iframe").contents().find("body").html(data["template"]);
						}

						/*Change text area value*/
						jQuery("#jform_template_css").val(data["css"]);
					}
				});
			}, 2000);
		});
	'
	);
}
?>

<div class="<?php echo JMAILALERTS_WRAPPER_CLASS; ?>" id="jmailalerts-alert">
	<form action="<?php echo Route::_('index.php?option=com_jmailalerts&view=alert&layout=edit&id=' . (int) $this->item->id); ?>"
		method="post" enctype="multipart/form-data"
		name="adminForm" id="alert-form"
		class="form-horizontal form-validate">

		<div class="row-fluid">
			<div class="span7">
				<fieldset class="adminform">
					<?php echo $this->form->renderField('id'); ?>
					<?php echo $this->form->renderField('state'); ?>
					<?php echo $this->form->renderField('title'); ?>
					<?php echo $this->form->renderField('description'); ?>
					<?php echo $this->form->renderField('email_subject'); ?>
				</fieldset>

				<fieldset>
					<legend>
						<?php echo Text::_('COM_JMAILALERTS_FORM_LBL_ALERT_TEMPLATE'); ?>
					</legend>

					<div class="control-group">
						<?php echo $this->form->getInput('template'); ?>
					</div>
				</fieldset>
			</div>

			<div class="span5">
				<fieldset class="adminform">
					<?php echo $this->form->renderField('allow_users_select_plugins'); ?>
					<?php echo $this->form->renderField('respect_last_email_date'); ?>
					<?php echo $this->form->renderField('is_default'); ?>
					<?php echo $this->form->renderField('allowed_freq'); ?>
					<?php echo $this->form->renderField('default_freq'); ?>
					<?php echo $this->form->renderField('enable_batch'); ?>
					<?php echo $this->form->renderField('batch_size'); ?>
				</fieldset>

				<fieldset>
					<legend>
						<?php echo Text::_('COM_JMAILALERTS_FORM_LBL_ALERT_TEMPLATE_CSS'); ?>
					</legend>

					<p class="text text-info small">
						<?php echo Text::_('COM_JMAILALERTS_CSS_EDITOR_MSG'); ?>
					</p>

					<div class="control-group">
						<?php echo $this->form->getInput('template_css'); ?>
					</div>
				</fieldset>

				<fieldset>
					<legend>
						<?php echo Text::_('COM_JMAILALERTS_TAGS_LIST'); ?>
					</legend>

					<p class="text text-info small">
						<?php echo Text::_('COM_JMAILALERTS_TAGS_LIST_INFO'); ?>
					</p>

					<div class="control-group">
						<?php
						if (count($this->item->email_alert_plugin_names))
						{
							echo "<hr class='hr hr-condensed'/>";
							echo "<p class='text text-info'>" . Text::_('COM_JMAILALERTS_JMA_PLUGINS_TAGS') . "</p>";

							// This code echoes the plugin 'tags' on the right side of the config
							// Set index to 0
							$i    = 0;
							$lang = Factory::getLanguage();

							foreach ($this->item->email_alert_plugin_names as $emailAlertPluginName)
							{
								echo "<hr class='hr hr-condensed'/>";

								$lang->load("plg_emailalerts_" . $emailAlertPluginName, JPATH_ADMINISTRATOR);

								echo '[' . $emailAlertPluginName . ']
								<p class="small">' . Text::_($this->item->plugin_description_array[$i++]) . '</p>';
							}

							echo "<hr class='hr'/>";
						}
						?>

						<div class="">[NAME]
							<p class="small">
								<?php echo Text::_('COM_JMAILALERTS_NAME_OF_RECIVER'); ?>
							</p>
						</div>

						<div class="">[SITENAME]
							<p class="small">
								<?php echo Text::_('COM_JMAILALERTS_SITE_NAME'); ?>
							</p>
						</div>

						<div class="">[SITELINK]
							<p class="small">
								<?php echo Text::_('COM_JMAILALERTS_SITE_LINK'); ?>
							</p>
						</div>

						<div class="">[PREFRENCES]
							<p class="small">
								<?php echo Text::_('COM_JMAILALERTS_PREF_LINK'); ?>
							</p>
						</div>

						<div class="">[mailuser]
							<p class="small">
								<?php echo Text::_('COM_JMAILALERTS_EMAIL_SUBS'); ?>
							</p>
						</div>
					</div>
				<fieldset>
			</div>

			<input type="hidden" name="task" value="" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</div>
	</form>
</div>
