<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::script(Uri::root() . 'media/com_timelog/js/timelog.js');
HTMLHelper::script(Uri::root() . 'media/system/js/messages.min.js');
HTMLHelper::_('jquery.token');
Text::script('COM_TIMELOG_CONFIRM_DELETE_ATTACHMENT');

$app        = Factory::getApplication();
$tmpl       = $app->input->getString('tmpl', '');
$licenseId  = $app->input->getInt('licence_id', 0);
$popupClass = $appendUrl = '';

if (!empty($licenseId))
{
	$appendUrl = '&licence_id=' . $licenseId;
}

// Check template component set or not.
if (!empty($tmpl))
{
	$doc = Factory::getDocument();
	$appendUrl .= '&tmpl=' . $tmpl;
	$doc->addStyleSheet('templates/shaper_helix3/css/bootstrap.min.css');
	$doc->addStyleSheet('templates/shaper_helix3/css/custom.css');
	$popupClass = 'timelog-add-form';
}

?>
<div id="system-message-container"></div>
<div class=" <?php echo $popupClass; ?> activity-edit front-end-edit ml-20 mr-20">
	<?php if (!$this->canCreateTimelog) : ?>
		<h3>
			<?php throw new Exception(Text::_('COM_TIMELOG_ERROR_MESSAGE_NOT_AUTHORISED'), 403); ?>
		</h3>
	<?php else : ?>
	<?php if (!empty($popupClass)) { ?>
			   <button type="button" data-refresh="<?php echo !empty($licenseId)? 1 : 0;?>"class="close closetimelogpopup">&times;</button>
	<?php }
	if (!empty($this->item->id)): ?>
		<h3 class="activity-header fs-20"> <?php echo Text::_('COM_TIMELOG_EDIT_ITEM_TITLE') . ' ' .Text::_('COM_TIMELOG'); ?></h3>
	<?php else: ?>
		<h3 class="activity-header fs-20"><?php echo Text::_('COM_TIMELOG_ADD_ITEM_TITLE') . ' ' .Text::_('COM_TIMELOG'); ?></h3>
	<?php endif; ?>

		<form id="form-activity"
			action="<?php echo Route::_('index.php?option=com_timelog&task=activity.save'); ?>"
			method="post" class="form-validate form-horizontal ticketPopupForm" enctype="multipart/form-data" onsubmit="disableSubmitBtn()">
			<?php
				echo $this->form->renderField('id');
				if(!empty($licenseId))
				{
				?>
					<input type="hidden" name="licence_id" value="<?php echo $licenseId; ?>"/>
					<input type="hidden" name="jform[license_id]" value="<?php echo $licenseId; ?>"/>
			<?php
				}

					echo $this->form->renderField('license_id');
					echo $this->form->renderField('client_id');
					echo $this->form->renderField('activity_type_id');
				?>
			<div class="hide">
				<?php echo $this->form->renderField('client'); ?>
			</div>

			<div class="control-group">
				<div class="control-label"><?php echo Text::_('COM_TIMELOG_FORM_LBL_ACTIVITY_LOG_TIME')?></div>
				<div class="controls add-timelog-format">
					<?php echo $this->form->getInput('hours');?>
					<?php echo $this->form->getInput('min');?>
				</div>
			</div>
			<?php
				echo $this->form->renderField('activity_note');
				echo $this->form->renderField('created_date');
			?>
			<div class="control-group">
				<div class="controls w-100 control-group-fwidth">
					<?php echo $this->form->getInput('attachment');?>
					<ul class="list-unstyled ml-0 mt-0">
					<?php
					if (!empty($this->item->oldAttachment))
					{
						$oldFiles = array();
						$token = Session::getFormToken();

						foreach ($this->item->oldAttachment as $key=>$attachment)
						{
							echo '<input type="hidden" name="oldFiles['. $key . ']" value="'. $attachment['media_id'] . '">';

							$downloadAttachmentLink = Uri::root() . 'index.php?option=com_timelog&task=activity.downloadAttachment&' .
							$token . '=1' . '&mediaId=' . $attachment['media_id'] . '&activityId=' . $this->item->id;

						?>
							<li>
							<a
								class="mr-20"
								href="<?php echo Route::_($downloadAttachmentLink);?>"
								target=""
								title="<?php echo $this->escape(strip_tags((string)$attachment));?>">
								<?php echo $attachment['title'];?>
								<span><i class="icon-download" aria-hidden="true"></i></span>
							</a>

							<i class="icon-trash"
								title="<?php echo Text::_('COM_TIMELOG_ATTACHMENT_DELETE');?>"
								data-mid="<?php echo $attachment['media_id'];?>"
								data-aid="<?php echo $this->item->id;?>"
								onclick="timeLog.deleteAttachment('activity.deleteAttachment', this, '<?php echo $token ?>')"></i>
							<li>
						<?php
						}
					}?>
					</ul>
				</div>
			</div>

			<?php
				echo $this->form->renderField('state');
				echo $this->form->getInput('created_by');
				echo $this->form->getInput('modified_by');
			?>

			<div class="control-group1 pull-right ">
				<div class="controls pull-right">
					<?php if ($this->canSave): ?>
						<button type="submit" class="validate btn btn-primary" id="activity-submit" onclick="if (jQuery('#jform_hours').val() == 0 && jQuery('#jform_min').val() == 0){var msg = '<?php echo Text::_('COM_TIMELOG_ZERO_TIMELOG_ERROR');?>';
					Joomla.renderMessages({'warning' : [msg]});
					return false;}" >
							<?php echo Text::_('JSUBMIT'); ?>
						</button>
					<?php endif; ?>
					<a class="btn closetimelogpopup" href="javascript:void(0)" data-refresh="<?php echo !empty($licenseId)? 1 : 0;?>" title="<?php echo Text::_('JCANCEL'); ?>">
						<?php echo Text::_('JCANCEL'); ?>
					</a>
				</div>
			</div>

			<!-- No need to show field labels on form but it is required to show validation of field -->

			<div class="hide">
				<?php
					echo $this->form->getLabel('hours');
					echo $this->form->getLabel('min');
				?>
			</div>

			<input type="hidden" name="option" value="com_timelog"/>
			<?php
				if(!empty($tmpl))
				{
				?>
					<input type="hidden" name="tmpl" value="component"/>
			<?php
				}
				?>
				<input type="hidden" name="task" value="dpeactivityform.save"/>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php endif; ?>
</div>
<script type="text/javascript">
// <!-- Close popup if data saved successfully -->
jQuery(document).ready (function(){

	function checkTimeValue()
	{
				if (jQuery('#jform_hours').val() == 0 && jQuery('#jform_min').val() == 0)
				{
					var msg = "<?php echo Text::_('COM_TIMELOG_ZERO_TIMELOG_ERROR');?>";
					Joomla.renderMessages({'warning' : [msg]});
					return false;
				}

	}
	jQuery('.timelog-add-form').find('.subform-repeatable').children('.btn-toolbar').remove();

	// DPE  - hack - start To remove add more button for multiple file upload against timelog activity
	jQuery('.timelog-add-form').find('.subform-repeatable-group').children('.btn-toolbar').remove();

	// DPE  - hack - end ( Note: If you want multiple upload remove above line and change activityform.xml file field 'max' params)

	if(jQuery('#system-message div').hasClass('alert alert-success'))
	{
		jQuery("#system-message-container").fadeTo(2000, 500, function(){
			window.parent.document.location.reload(true);
			window.parent.SqueezeBox.close();
		});
	}

	// Joomla form validator to check -ve value in hour and min
	document.formvalidator.setHandler('positivenumber', function (value) {
		regex=/^[+]?[0-9]*$/;

		if (regex.test(value))
		{
			if (jQuery('#jform_hours').val() == 0 && jQuery('#jform_min').val() == 0)
			{
				return false;
			}

			return true;
		}
    });
});

var allowedAttachments    = "<?php echo $this->params->get('upload_extensions', 'image/jpeg,image/jpg,image/png,application/pdf');?>";
var attachmentMaxSize     = "<?php echo $this->params->get('upload_maxsize', 2);?>";

function disableSubmitBtn()
{
	jQuery("#activity-submit").prop('disabled',true);
}

	 jQuery(document).on('click', '.joomla-alert--close', function() {
       jQuery(this).closest('joomla-alert').remove();
    });
      jQuery('a.close').click(function() {
        jQuery(this).parent().remove();
    });


</script>
