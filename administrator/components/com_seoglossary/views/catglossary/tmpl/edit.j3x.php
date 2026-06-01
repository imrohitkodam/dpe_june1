<?php
/**    
 * SEO Glossary Catglossary view Edit layout
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// no direct access
defined('_JEXEC') or die;

$document = JFactory::getDocument();
$document->addStyleSheet(JURI::root() . '/components/com_seoglossary/assets/css/materialize.css');
JHtml::_('behavior.formvalidator');

?>
<script type="text/javascript">
	<?php if (!version_compare(JVERSION, '4.0', 'ge')){ ?>
	Joomla.submitbutton = function(task)
	{
		if (task == 'catglossary.cancel' || document.formvalidator.isValid(document.getElementById('catglossary-form'))) {
			Joomla.submitform(task, document.getElementById('catglossary-form'));
		}
		else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
	<?php }?>
</script>
<style type="text/css">
	.seoglossary label
	{
		max-width:150px;
	}
</style>
<div id="ju-form" class="main-card">
<form action="<?php echo JRoute::_('index.php?option=com_seoglossary&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="catglossary-form" class="form-validate">
		<div class="form-horizontal">	<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_SEOGLOSSARY_MENU_GLOSSARIES')); ?>
		
           
				<div class="row-fluid form-horizontal-desktop form-horizontal row">
					<div class="span9 col-lg-9">
										<fieldset class="adminform">
		
			<?php /*echo $this->form->getLabel('id'); ?>
			<?php echo $this->form->renderField('id');*/ ?>
			<?php echo  $this->form->renderField('name'); ?>
			<?php echo $this->form->renderField('alias'); ?>
			<?php echo $this->form->renderField('icon'); ?>
			<?php echo $this->form->renderField('description'); ?>
			<?php echo $this->form->renderField('language'); ?>
			<?php echo $this->form->renderField('alphabet'); ?>
			<?php echo $this->form->renderField('state'); ?>
			<?php /* Hidden fields */ ?>
			<?php echo $this->form->renderField('checked_out'); ?>
            <?php echo $this->form->renderField('checked_out_time'); ?>
										</fieldset>
			</div>
		   </div>
		   <?php echo JHtml::_('bootstrap.endTab'); ?>

		<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'otherparams', JText::_('COM_SEOGLOSSARY_COMPONENT_RESTRICTION')); ?>
		
				<div class="row-fluid form-horizontal-desktop form-horizontal">
					<div class="span9">
		<?php echo  $this->form->renderField('disable_menu'); ?>
		<?php echo  $this->form->renderField('disable_component'); ?>
		<?php echo  $this->form->renderField('disable_context'); ?>
		<?php echo  $this->form->renderField('disable_category'); ?>
					</div></div>
		   <?php echo JHtml::_('bootstrap.endTab'); ?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>

	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
	<div class="clr"></div>
		</div>
</form></div>