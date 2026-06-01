<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

HTMLHelper::_('jquery.token');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('formbehavior.chosen', 'select');

$app               = JFactory::getApplication();
$scalesetId        = $app->input->getInt('scale_set_id');
$scaleSetUrlAppend = '';

if (!empty($scalesetId))
{
	$scaleSetUrlAppend = '&scale_set_id=' . $scalesetId;
}

?>
<div class="tj-page">
	<div class="row-fluid">
		<form action="<?php echo Route::_('index.php?option=com_tjcompetency&view=scale&layout=edit&id=' . (int) $this->item->id . $scaleSetUrlAppend, false);
		?>" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="form-validate">
			<?php if (!empty( $this->sidebar))
			{
			?>
				<div id="j-sidebar-container" class="span2">
					<?php echo $this->sidebar; ?>
				</div>
				<div id="j-main-container" class="span10">
			<?php
			}
			else
			{
				?>
				<div id="j-main-container">
			<?php
			}
			?>
			<div class="form-horizontal">
				<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>
				<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('COM_TJCOMPETENCY_VIEW_COMPETENCY_SCALE_TAB')); ?>
				<div class="row-fluid">
					<?php echo $this->form->renderField('title'); ?>
					<?php echo $this->form->renderField('scale_set_id'); ?>
					<?php echo $this->form->renderField('sequence_number'); ?>
					<?php echo $this->form->renderField('description'); ?>
					<?php echo $this->form->renderField('state'); ?>
					<?php echo $this->form->getInput('created_by'); ?>
					<?php echo $this->form->getInput('modified_on'); ?>
					<?php echo $this->form->getInput('modified_by'); ?>
					<?php echo $this->form->getInput('ordering'); ?>
					<?php echo $this->form->getInput('checked_out'); ?>
					<?php echo $this->form->getInput('checked_out_time'); ?>
				</div>
				<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
				<?php echo LayoutHelper::render('joomla.edit.params', $this); ?>
				<input type="hidden" name="task" value="" />
				<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</form>
	</div>
</div>
