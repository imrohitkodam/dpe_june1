<?php
/**
* @version    CVS: 1.0.0
* @package    Com_Multiagency
* @author     Techjoomla <contact@techjoomla.com>
* @copyright  2017 Techjoomla
* @license    GNU General Public License version 2 or later; see LICENSE.txt
*/

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::script('media/com_multiagency/js/licence.js' );

// Load admin language file
$lang = Factory::getLanguage();
$lang->load('com_multiagency', JPATH_SITE);
$UriRoot = Uri::root();
$user    = Factory::getUser();

$canEdit    = $user->authorise('core.edit', 'com_multiagency');

$tjlmsExtention = ComponentHelper::getComponent('com_tjlms', true);
?>

<script type="text/javascript">

function validateLicences()
{
	var promise = validLicence();

	var a = document.formvalidator.isValid(document.id('adminForm'));

	promise.fail(function(response)
	{

	}).done(function(response)
	{
		if (response.data.id == null)
		{
			Joomla.submitform('licenceform.save');
		}
		else
		{
			if (response.data.type === 'all')
			{
				alert("<?php echo Text::_('COM_MULTIAGENCY_ALL_ALREADY_PRESENT_EDIT_ERROR'); ?>");

			}

			if(response.data.type === 'per')
			{
				alert("<?php echo Text::sprintf('COM_MULTIAGENCY_ALREADY_PRESENT_EDIT_ERROR', Text::_('COM_MULTIAGENCY_ORGANISATION')); ?>");
				return false;
			}

			return false;
		}
	});
}


function validLicence()
{
	var urlpath = Joomla.getOptions('system.paths').root + '/index.php?option=com_multiagency&task=licenceform.checkcourse';
	var userId = jQuery("#jform_multiagency_id").val();
	var licenceType = jQuery("#jform_licence_type").val();

	if (licenceType === 'all')
	{
		var courseId = 0;
	}
	else
	{
		var courseId = jQuery("#jform_course_id").val();
	}

	return jQuery.ajax({
		url: urlpath,
		type: 'post',
		data : {'userId' : userId,'courseId': courseId},
		dataType: 'json',
	});
}

jQuery( document ).ready(function() {
	var licenceType =  jQuery('#jform_licence_type').val();

	if (licenceType === 'all')
	{
	      jQuery('.courseList').addClass('hide');
	      jQuery('#jform_course_id').removeAttr('required');
	      jQuery('#jform_course_id').removeClass('required');
	}

	jQuery('#jform_multiagency_id').on('change', function() {
		jQuery('#jform_multiagency_id').prop("selected", false).trigger("liszt:updated");
		var urlpath = Joomla.getOptions('system.paths').root + '/index.php?option=com_multiagency&task=licenceform.getNotAssignCourse';
		var multiagencyId = jQuery("#jform_multiagency_id").val();
		return jQuery.ajax({
			url: urlpath,
			type: 'post',
			data : {'multiagencyId' : multiagencyId},
			dataType: 'json',
			success: function(data)
			{
				jQuery("#jform_course_id").find('option').remove().end().append(data);
				jQuery("#jform_course_id").trigger("liszt:updated");
			}
	});
	});
});
</script>

<div class="licence-edit front-end-edit">
		<?php if (!$canEdit) : ?>
			<h3><?php throw new Exception(Text::_('COM_MULTIAGENCY_ERROR_MESSAGE_NOT_AUTHORISED'), 403); ?></h3>
		<?php else : ?>
		<?php if (!empty($this->item->id)): ?>
			<div class="page-header"><h2 itemprop="name"><?php echo Text::_('COM_MULTIAGENCY_EDIT_ITEM_TITLE_LICENCES'); ?></h2></div>
		<?php else: ?>
			<div class="page-header"><h2 itemprop="name"><?php echo Text::_('COM_MULTIAGENCY_ADD_ITEM_LICENCE'); ?></h2></div>
		<?php endif; ?>
	<div class="row">
		<div class="col-xs-12 col-sm-7 col-md-5">
			<div class="form-cover padding25">
				<div class="clearfix"></div>
					<form id="adminForm" action="" method="post" class="form-validate form-horizontal" enctype="multipart/form-data">
						<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
						<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
						<input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
						<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
						<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />
						<?php echo $this->form->getInput('created_by'); ?>
						<?php echo $this->form->getInput('modified_by');
						if (empty($this->item->id))
						{
						?>
						<?php echo $this->form->renderField('multiagency_id'); ?>
                                                <?php echo $this->form->renderField('licence_type'); ?>
						<div class="courseList hide">
        					<?php echo $this->form->renderField('course_id'); ?>
						</div>
						<?php
						}
						else
						{
							JLoader::register('MultiagencyFrontendHelpers', JPATH_SITE . '/components/com_multiagency/helpers/multiagency.php');
							$multiagencyHelper = new MultiagencyFrontendHelpers;

							JLoader::register('TjlmsCoursesHelper', JPATH_SITE . '/components/com_tjlms/helpers/courses.php');
							$tjlmsCoursesHelper = new TjlmsCoursesHelper;
							$courseInfo   = $tjlmsCoursesHelper->getCourseColumn($this->item->course_id, array('title'));
							?>
							<div class="control-group">
								<div class="control-label">
									<?php echo $this->form->getLabel('multiagency_id'); ?>
								</div>
								<div class="controls">
									<input type="text" class="form-control" disabled value="<?php echo $multiagencyHelper->getmultiagency($this->item->multiagency_id);?>">
								</div>
							</div>
							<div class="control-group">
								<div class="control-label">
									<?php echo $this->form->getLabel('licence_type'); ?>
								</div>
								<div class="controls">
									<input type="text" class="form-control" disabled value="<?php echo $this->item->type;?>">
								</div>
							</div>
                        <div class="control-group <?php echo ($this->item->type == strtolower(Text::_('COM_MULTIAGENCY_LICENCE_TYPE_ALL'))) ? 'hide':''; ?>">
								<div class="control-label">
									<?php echo $this->form->getLabel('course_id'); ?>
								</div>
								<div class="controls">
									<input type="text" class="form-control" disabled value="<?php echo $courseInfo->title;?>">
								</div>
							</div>

							<input type="hidden" value="<?php echo $this->item->type;?>" name="jform[licence_type]"/>
							<input type="hidden" value="<?php echo $this->item->multiagency_id;?>" name="jform[multiagency_id]"/>
							<input type="hidden" value="<?php echo $this->item->course_id;?>" name="jform[course_id]"/>
							<?php
						}

						echo $this->form->renderField('total_seats'); ?>
						<?php if (!empty($this->item->id)) {  ?>
							<div class="control-group">
								<div class="control-label">
									<?php echo $this->form->getLabel('used_seats'); ?>
								</div>
								<div class="controls">
									<input type="text" class="form-control" disabled value="<?php echo $this->item->used_seats;?>">
								</div>
							</div>
						<?php } ?>

						<?php echo $this->form->renderField('start_date'); ?>
						<?php echo $this->form->renderField('end_date'); ?>
						<?php echo $this->form->renderField('comment'); ?>
						<!--  -->

						<?php if (!Factory::getUser()->authorise('core.admin','multiagency')): ?>
							<script type="text/javascript">
								jQuery.noConflict();
								jQuery('.tab-pane select').each(function(){
									var option_selected = jQuery(this).find(':selected');
									var input = document.createElement("input");
									input.setAttribute("type", "hidden");
									input.setAttribute("name", jQuery(this).attr('name'));
									input.setAttribute("value", option_selected.val());
									document.getElementById("form-licence").appendChild(input);
								});
							</script>
						<?php endif; ?>

						<div class="control-group">
							<div class="controls">
								<?php if ($this->canSave): ?>
									<button  type="submit" class="licenceform validate btn btn-primary" onclick="return validateLicences()"><?php echo Text::_('JSUBMIT'); ?></button>
								<?php endif; ?>

								<a class="btn btn-default" href="<?php echo Route::_('index.php?option=com_multiagency&view=licences'); ?>" title="<?php echo Text::_('JCANCEL'); ?>"><?php echo Text::_('JCANCEL'); ?></a>
							</div>
						</div>

						<input type="hidden" name="option" value="com_multiagency"/>
						<input type="hidden" name="task" value="licenceform.save"/>

						<?php echo HTMLHelper::_('form.token'); ?>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
