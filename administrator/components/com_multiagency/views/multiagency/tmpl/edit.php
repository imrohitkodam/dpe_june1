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
$document->addScript(Uri::root(true) . '/media/com_multiagency/js/multiagency.js');
?>
<script type="text/javascript">
	js = jQuery.noConflict();
	js(document).ready(function () {

	js('input:hidden.country_id').each(function(){
		var name = js(this).attr('name');
		if(name.indexOf('country_idhidden')){
			js('#jform_country_id option[value="'+js(this).val()+'"]').attr('selected',true);
		}
	});
	js("#jform_country_id").trigger("liszt:updated");
	js('input:hidden.state_id').each(function(){
		var name = js(this).attr('name');
		if(name.indexOf('state_idhidden')){
			js('#jform_state_id option[value="'+js(this).val()+'"]').attr('selected',true);
		}
	});
	js("#jform_state_id").trigger("liszt:updated");
	});

	Joomla.submitbutton = function (task) {
		if (task == 'multiagency.cancel') {
			Joomla.submitform(task, document.getElementById('multiagency-form'));
		}
		else {

			if (task != 'multiagency.cancel' && document.formvalidator.isValid(document.id('multiagency-form'))) {

				Joomla.submitform(task, document.getElementById('multiagency-form'));
			}
			else {
				alert('<?php echo $this->escape(Text::_('JGLOBAL_VALIDATION_FORM_FAILED')); ?>');
			}
		}
	}
</script>
<form action="<?php echo Route::_('index.php?option=com_multiagency&layout=edit&id=' . (int) $this->item->id); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="multiagency-form" class="form-validate">
	<div class="form-horizontal">
		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>
		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('COM_MULTIAGENCY_TITLE_MULTIAGENCY', true)); ?>
		<div class="row-fluid">
			<div class="span10 form-horizontal">
				<fieldset class="adminform">
					<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
					<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
					<input type="hidden" name="jform[state]" value="<?php echo $this->item->state; ?>" />
					<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
					<input type="hidden" name="jform[checked_out_time]" value="<?php echo $this->item->checked_out_time; ?>" />
					<?php echo $this->form->renderField('created_by'); ?>
					<?php echo $this->form->renderField('modified_by'); ?>
					<?php echo $this->form->renderField('title'); ?>
					<div class="control-group">
						<div class="control-label">
							<?php echo $this->form->getLabel('manager_id'); ?>
						</div>
						<div class="controls">
							<?php echo HTMLHelper::_( 'select.genericlist', $this->managerList, 'jform[manager_id]', 'class="chzn-done" size="1"', 'id', 'username', $this->item->manager_id, 'jform_manager_id'); ?>
						</div>
					</div>
					<div class="control-group">
						<div class="control-label">
							<?php echo $this->form->getLabel('country_id'); ?>
						</div>
						<div class="controls">
							<?php echo HTMLHelper::_( 'select.genericlist', $this->countryList, 'jform[country_id]', 'class="chzn-done" size="1" onchange="comMultiagency.multiagency.generateStoreState(1)"', 'id', 'country', $this->item->country_id, 'jform_country_id'); ?>
						</div>
					</div>
					<div class="control-group">
						<div class="control-label">
							<?php echo $this->form->getLabel('state_id'); ?>
						</div>
						<div class="controls">
							<?php echo HTMLHelper::_( 'select.genericlist', $this->stateList, 'jform[state_id]', 'class="chzn-done" size="1"', 'id', 'region', $this->item->state_id, 'jform_state_id'); ?>
						</div>
					</div>
				</fieldset>
			</div>
		</div>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
		<?php if (Factory::getUser()->authorise('core.admin','multiagency')) : ?>
			<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
			<?php echo $this->form->getInput('rules'); ?>
			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
		<?php endif;?>
		<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
		<input type="hidden" name="task" value=""/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
