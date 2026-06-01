<?php
/**
 * @version     1.0.0
 * @package     com_advsearch
 * @copyright   Copyright (C) 2012. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      amol <amol_p@tekdi.net> - http://tekdi.net
 */

// no direct access
defined('_JEXEC') or die;

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
// Import CSS
$document = JFactory::getDocument();
$document->addStyleSheet('components/com_advsearch/assets/css/advsearch.css');
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'createsearchindexer.cancel' || document.formvalidator.isValid(document.id('createsearchindexer-form'))) {
			Joomla.submitform(task, document.getElementById('createsearchindexer-form'));
		}
		else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_advsearch&layout=edit&id='.(int) $this->item->id); ?>" method="post" name="adminForm" id="createsearchindexer-form" class="form-validate">
	<div class="width-60 fltlft">
		<fieldset class="adminform">
			<legend><?php echo JText::_('COM_ADVSEARCH_LEGEND_CREATESEARCHINDEXER'); ?></legend>
			<ul class="adminformlist">
                
				<li><?php echo $this->form->getLabel('id'); ?>
				<?php echo $this->form->getInput('id'); ?></li>
				<li><?php echo $this->form->getLabel('client'); ?>
				<?php echo $this->form->getInput('client'); ?></li>
				<li><?php echo $this->form->getLabel('content_type'); ?>
				<?php echo $this->form->getInput('content_type'); ?></li>
				<li><?php echo $this->form->getLabel('field_code'); ?>
				<?php echo $this->form->getInput('field_code'); ?></li>
				<li><?php echo $this->form->getLabel('field_type'); ?>
				<?php echo $this->form->getInput('field_type'); ?></li>
				<li><?php echo $this->form->getLabel('map_table'); ?>
				<?php echo $this->form->getInput('map_table'); ?></li>
				<li><?php echo $this->form->getLabel('mapping_field'); ?>
				<?php echo $this->form->getInput('mapping_field'); ?></li>
				<li><?php echo $this->form->getLabel('mapping_label'); ?>
				<?php echo $this->form->getInput('mapping_label'); ?></li>
				<li><?php echo $this->form->getLabel('options'); ?>
				<?php echo $this->form->getInput('options'); ?></li>
				<li><?php echo $this->form->getLabel('category'); ?>
				<?php echo $this->form->getInput('category'); ?></li>
				<li><?php echo $this->form->getLabel('published'); ?>
				<?php echo $this->form->getInput('published'); ?></li>


            </ul>
		</fieldset>
	</div>


	<input type="hidden" name="task" value="" />
	<?php echo JHtml::_('form.token'); ?>
	<div class="clr"></div>

    <style type="text/css">
        /* Temporary fix for drifting editor fields */
        .adminformlist li {
            clear: both;
        }
    </style>
</form>