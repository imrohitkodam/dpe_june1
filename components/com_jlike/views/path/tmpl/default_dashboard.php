<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_JLike
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */

// No direct access
defined('_JEXEC') or die;

jimport( 'joomla.application.module.helper' );
$module = JModuleHelper::getModule( 'mod_todos', 'My Todos' );
$attribs['style'] = 'xhtml';
JModuleHelper::renderModule( $module, $attribs );

$ruleSetId  = $this->app->input->get('id');
$model = $this->getModel();
$SubscribeRole   = $model->getSubscribeRole($ruleSetId);

$completeStatus = 0;

foreach ($this->item->info as $info)
{
	if ($info['status'] == 'C')
	{
		$completeStatus ++;
	}
}
$totalStep = count($this->item->info);

// Welcome Case
$case = 'welcome';

if (!empty($ruleSetId))
{
	// Confirm Case
	if ($totalStep == $completeStatus)
	{
		$case = 'inprogress';
	}
}

// Thank you Case
if ($SubscribeRole->status)
{
	$case = 'complete';
}

switch ($case)
{
	case "welcome":
		$message = JText::_('COM_JLIKE_TODO_WELCOME_MESSAGE');
		?>
		<div class="alert alert-success"><?php echo $message;?></div>
		<?php
	break;

	case "inprogress":
		$message = JText::_('COM_JLIKE_TODO_FINAL_SUBMIT_MESSAGE');
		?>
		<div class="alert alert-success"><?php echo $message;?></div>
		<form action="index.php?option=com_jlike&task=ruleset.confirm" id="uploadForm" class="form-inline" name="uploadForm" method="post" enctype="multipart/form-data">
			<div class="">
				<input type="hidden" name="jform[id]" value="<?php echo $SubscribeRole->id; ?>" />
				<input type="hidden" name="jform[rule_set_id]" value="<?php echo $ruleSetId; ?>" />
				<input type="hidden" name="jform[user_id]" value="<?php echo $this->user->id; ?>" />
				<input type="hidden" name="jform[status]" value="1" />
				<button type="submit" id="submit" class="validate btn btn-primary">
					<?php echo JText::_('COM_JLIKE_TODO_CONFIRM_BTN'); ?>
				</button>
			</div>
		</form>
		<?php
	break;

	case "complete":
		$message = JText::_('COM_JLIKE_TODO_THANK_YOU_MESSAGE');
		?>
		<div class="alert alert-success"><?php echo $message;?></div>
		<?php
	break;

	default:
		$message = JText::_('COM_JLIKE_TODO_WELCOME_MESSAGE');
		?>
		<div class="alert alert-success"><?php echo $message;?></div>
		<?php
}
?>
