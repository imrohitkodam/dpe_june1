<?php
/**    
 * SEO Glossary Catglossaries view Default layout
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */


defined('_JEXEC') or die;



$options = array(
	JHtml::_('select.option', 'c', JText::_('JLIB_HTML_BATCH_COPY')),
	JHtml::_('select.option', 'm', JText::_('JLIB_HTML_BATCH_MOVE'))
);
$published	= $this->state->get('filter.published');
$extension	= $this->escape($this->state->get('filter.extension'));
?>
<div class="modal hide fade" id="collapseModal">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">x</button>
		<h3><?php echo JText::_('COM_SEOGLOSSARY_BATCH_OPTIONS');?></h3>
	</div>
	<div class="modal-body">
		<p><?php echo JText::_('COM_SEOGLOSSARY_BATCH_TIP'); ?></p>
		<div class="control-group">
			<div class="controls">
				<?php echo JHtml::_('batch.access');?>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<?php echo JHtml::_('batch.language'); ?>
			</div>
		</div>
		<?php if ($published >= 0) : ?>
			<div class="control-group">
				<label id="batch-choose-action-lbl" for="batch-category-id" class="control-label">
					<?php echo JText::_('COM_SEOGLOSSARY_BATCH_CATEGORY_LABEL'); ?>
				</label>
				<div id="batch-choose-action" class="combo controls">
					<select name="batch[category_id]" class="inputbox" id="batch-category-id">
						<option value=""><?php echo JText::_('JSELECT') ?></option>
						<?php echo JHtml::_('select.options', JHtml::_('catglossary.catglossaries', $extension, array('filter.published' => $published)));?>
					</select>
				</div>
			</div>
			<div class="control-group radio">
				<?php echo JHtml::_('select.radiolist', $options, 'batch[move_copy]', '', 'value', 'text', 'm'); ?>
			</div>
		<?php endif; ?>
	</div>
	<div class="modal-footer">
		<button class="btn" type="button" onclick="document.getElementById('batch-category-id').value='';document.getElementById('batch-access').value='';document.getElementById('batch-language-id').value=''" data-dismiss="modal">
			<?php echo JText::_('JCANCEL'); ?>
		</button>
		<button class="btn btn-primary" type="submit" onclick="Joomla.submitbutton('category.batch');">
			<?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
		</button>
	</div>
</div>
