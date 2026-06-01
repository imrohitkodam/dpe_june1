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

$app                = Factory::getApplication();
$input              = $app->input;
$frameworkId        = $input->getInt('framework_id');
$frameworkUrlAppend = '';

if (!empty($frameworkId))
{
	$frameworkUrlAppend = '&framework_id=' . $frameworkId;
}

?>
<form action="<?php echo Route::_('index.php?option=com_tjcompetency&view=skill&layout=edit&id=' . (int) $this->item->id . $frameworkUrlAppend, false);
?>" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="form-validate">

	<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>
	<div class="form-horizontal">
		<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>
		<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('COM_TJCOMPETENCY_VIEW_COMPETENCY_SKILL_TAB')); ?>
		<div class="row-fluid">
			<div class="span9">
				<?php echo $this->form->renderField('framework_id'); ?>
				<?php echo $this->form->renderField('description'); ?>
				<?php echo $this->form->getInput('created_by'); ?>
				<?php echo $this->form->getInput('modified_on'); ?>
				<?php echo $this->form->getInput('modified_by'); ?>
				<?php echo $this->form->getInput('ordering'); ?>
				<?php echo $this->form->getInput('checked_out'); ?>
				<?php echo $this->form->getInput('checked_out_time'); ?>
			</div>
			<div class="span3">
				<div class="form-vertical">
					<div class="row-fluid">
				<?php echo $this->form->renderField('parent_id'); ?>
				<?php echo $this->form->renderField('unique_code'); ?>
				<?php echo $this->form->renderField('state'); ?>
					</div>
				</div>
			</div>
		</div>
		<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
		<?php echo LayoutHelper::render('joomla.edit.params', $this); ?>
		<input type="hidden" name="task" value="" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</div>
</form>