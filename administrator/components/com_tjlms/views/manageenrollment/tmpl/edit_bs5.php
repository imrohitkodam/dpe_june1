<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla extensions@techjoomla.com
 * @copyright   Copyright (C) 2020 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

?>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?>">
	<form action="<?php echo Route::_('index.php?option=com_tjlms&view=manageenrollment&layout=edit&id=' . (int) $this->item->id); ?>" method="post" enctype="multipart/form-data" name="adminForm" id="adminForm" class="form-validate">
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="user_id" value="<?php echo $this->item->user_id; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
		<div class="form-horizontal">
			<div class="row">
				<div class="col-md-12">
					<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'general')); ?>

					<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'general', Text::_('COM_TJLMS_TITLE_MANAGE_ENROLLMENT', true)); ?>
				<!-- General tabs starts here  -->

				<div class="control-group" style="display:none">
					<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
				</div>

				<?php echo $this->form->renderField('course_id'); ?>

				<?php echo $this->form->renderField('user_id'); ?>

				<?php echo $this->form->renderField('state'); ?>

				<?php echo $this->form->renderField('end_time'); ?>

				<!--GENERAL TAB ENDS-->
				<?php echo JHtml::_('uitab.endTab'); ?>
				</div>

				<?php echo LayoutHelper::render('joomla.edit.params', $this); ?>
			</div>
		</div>
	</form>
</div> <!--techjoomla-bootstrap-->