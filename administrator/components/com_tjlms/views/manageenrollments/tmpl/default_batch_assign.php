<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
?>


<div class="form-inline bau">

	<div class="form-group">
		<label><?php echo Text::_('COM_TJLMS_ENROLMENT_START_DATE_TITLE'); ?></label>
		<div class="form-control">
			<?php
				echo Jhtml::calendar("",'batch_start_date', "batch_start_date", '%Y-%m-%d',array('','size'=>'8','maxlength'=>'10'));
			?>
		</div>
	</div>
	<div class="form-group" >
		<label><?php echo Text::_('COM_TJLMS_ENROLMENT_DUE_DATE_TITLE'); ?></label>
		<div class="form-control">
				<?php
			echo Jhtml::calendar("",'batch_due_date', "batch_due_date", '%Y-%m-%d',array('size'=>'8','','maxlength'=>'10'));
		?>
		</div>
	</div>

	<div class="form-group">
		<div class="control-label">
			<input id="notify_user_batch" type="checkbox" name="notify_user_batch" value="1" checked>
						<?php echo Text::_('COM_TJLMS_NOTIFY_ASSIGN_USER'); ?>
		</div>
	</div>


</div>


<!-- <div class="row-fluid form-horizontal " >
	<div class="control-group">
		<div class="control-label">
			<input id="notify_user_batch" type="checkbox" name="notify_user_batch" value="1" checked>
						<?php echo Text::_('COM_TJLMS_NOTIFY_ASSIGN_USER'); ?>
		</div>
	</div>
	<div class="control-group">
		<div class="control-label"><?php echo Text::_('COM_TJLMS_ENROLMENT_START_DATE_TITLE'); ?></div>
		<div class="controls">
			<?php
				echo Jhtml::calendar("",'batch_start_date', "batch_start_date", '%Y-%m-%d',array('','size'=>'8','maxlength'=>'10'));
			?>
		</div>
	</div>
	<div class="control-group" >
		<div class="control-label"><?php echo Text::_('COM_TJLMS_ENROLMENT_DUE_DATE_TITLE'); ?></div>
		<div class="controls">
				<?php
			echo Jhtml::calendar("",'batch_due_date', "batch_due_date", '%Y-%m-%d',array('size'=>'8','','maxlength'=>'10'));
		?>
		</div>
	</div>
	<div>
	</div>
</div>
-->


