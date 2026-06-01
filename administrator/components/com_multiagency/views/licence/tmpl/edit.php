<?php
/**
 * @version    CVS: 1.0.0
 * @package    Com_multiagency
 * @author     Techjoomla <contact@techjoomla.com>
 * @copyright  2017 Techjoomla
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::_('behavior.keepalive');

// Import CSS
$document = Factory::getDocument();
$document->addStyleSheet(Uri::root() . 'media/com_multiagency/css/form.css');
?>
<script type="text/javascript">
	js = jQuery.noConflict();
	js(document).ready(function () {

	js('input:hidden.course_id').each(function(){
		var name = js(this).attr('name');
		if(name.indexOf('course_idhidden')){
			js('#jform_course_id option[value="'+js(this).val()+'"]').attr('selected',true);
		}
	});
	js("#jform_course_id").trigger("liszt:updated");
	});

	Joomla.submitbutton = function (task) {
		if (task == 'licence.cancel') {
			Joomla.submitform(task, document.getElementById('licence-form'));
		}
		else {

			if (task != 'licence.cancel' && document.formvalidator.isValid(document.id('licence-form'))) {

				Joomla.submitform(task, document.getElementById('licence-form'));
			}
			else {
				alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
			}
		}
	}
</script>

<form
	action="<?php echo Route::_('index.php?option=com_multiagency&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="licence-form" class="form-validate">

	<div class="form-horizontal">
		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('COM_MULTIAGENCY_TITLE_SUBSCRIPTION', true)); ?>
		<div class="row-fluid">
			<div class="span10 form-horizontal">
				<fieldset class="adminform">

									<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
				<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
				<input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
				<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
				<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />

				<?php echo $this->form->renderField('created_by'); ?>
				<?php echo $this->form->renderField('modified_by'); ?>				<?php echo $this->form->renderField('course_id'); ?>

			<?php
				foreach((array)$this->item->course_id as $value):
					if(!is_array($value)):
						echo '<input type="hidden" class="course_id" name="jform[course_idhidden]['.$value.']" value="'.$value.'" />';
					endif;
				endforeach;
			?>				<?php echo $this->form->renderField('total_seats'); ?>
				<?php echo $this->form->renderField('used_seats'); ?>
				<?php echo $this->form->renderField('start_date'); ?>
				<?php echo $this->form->renderField('end_date'); ?>
				<?php echo $this->form->renderField('comment'); ?>


					<?php if ($this->state->params->get('save_history', 1)) : ?>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('version_note'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('version_note'); ?></div>
					</div>
					<?php endif; ?>
				</fieldset>
			</div>
		</div>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

		<?php if (Factory::getUser()->authorise('core.admin','multiagency')) : ?>
	<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
		<?php echo $this->form->getInput('rules'); ?>
	<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
<?php endif; ?>

		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>

		<input type="hidden" name="task" value=""/>
		<?php echo HTMLHelper::_('form.token'); ?>

	</div>
</form>
