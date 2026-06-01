<?php
/**
 * @package     Jlike
 * @subpackage  com_jlike
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('behavior.formvalidation');
HTMLHelper::_('behavior.modal');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('jquery.token');
HTMLHelper::_('formbehavior.chosen', 'select');
Factory::getDocument()->getWebAssetManager()->useAsset('script', 'messages');

$options['relative'] = true;
HTMLHelper::_('script', 'com_jlike/jlikeService.js', $options);
HTMLHelper::_('script', 'com_jlike/jlike.js', $options);
?>
<div id="system-message-container"></div>
<form action="" class="form-validate form-horizontal"
    method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">
	<div class="container-fluid">
		<div class="row">
			<div class="col-sm-6">
					<h3 class="header">
						<?php
							echo (empty($this->item->id)) ? Text::_('COM_JLIKE_ADD_RECOMMENDATION') : Text::_('COM_JLIKE_EDIT_RECOMMENDATION');
						?>
					</h3>
				<div class="form-group" style="display:none;">
					<label class="col-sm-6"><?php echo $this->form->getLabel('id'); ?></label>
					<div class="col-sm-6"><?php echo $this->form->getInput('id'); ?></div>
				</div>
				<?php
					 echo $this->form->renderField('title'); 
					 echo $this->form->renderField('sender_msg');

					// If multiagency component enable and agency support config is true then render clutser field
					if ($this->isAgencyEnabled)
					{
						echo $this->form->renderField('clusters');
					}

					 echo $this->form->renderField('assigned_to');
					 echo $this->form->renderField('cc_users');
					 echo $this->form->renderField('due_date'); 
				?>
				<?php
					if ($this->item->id)
					{
						echo $this->form->renderField('status'); 
					}

					if (!$this->item->id)
					{
						echo $this->form->renderField('reminder'); 
					}
				?>
				<div class="control-group">
					<div class="controls">
						<button onclick="Joomla.submitbutton('recommendationform.save');" type="button" class="btn btn-primary"><?php echo Text::_('JSUBMIT'); ?></button>
						<button type="button" class="btn btn-default"  onclick="Joomla.submitbutton('recommendation.cancel')">
								<span><?php echo Text::_('JCANCEL'); ?></span>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>	
	
	<input type="hidden" id="assigned_user_id" value="<?php echo $this->item->assigned_to; ?>" />
	<input type="hidden" name="jform[id]" id="id" value="<?php echo $this->item->id; ?>" />
	<input type="hidden" name="cc_users" id="cc_users" value="<?php echo $this->item->cc_users; ?>" />
	<input type="hidden" name="option" value="com_jlike"/>
	<input type="hidden" name="task" value="recommendationform.save"/>
	<input type="hidden" name="jform[element]" id="element" value=""/>
	<input type="hidden" name="jform[element_id]" id="element_id" value=""/>
	<input type="hidden" name="jform[url]" id="url" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
<script>
jlike.init();
</script>
