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
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
?>
<fieldset id="users-profile-core">
	<legend>
		<?php echo Text::_('COM_JLIKE_RECOMMENDATION_DETAIL_VIEW_HEAD'); ?>
	</legend>
	<dl class="dl-horizontal">
		<dt>
			<?php echo Text::_('COM_JLIKE_FORM_LBL_RECOMMENDATION_TITLE'); ?>
		</dt>
		<dd>
			<?php echo $this->escape($this->item->title); ?>
		</dd>
		<dt>
			<?php echo Text::_('COM_JLIKE_FORM_LBL_TODO_DESCRIPTION'); ?>
		</dt>
		<dd>
			<?php echo $this->escape($this->item->sender_msg); ?>
		</dd>
		<dt>
			<?php echo Text::_('COM_JLIKE_FORM_LBL_RECOMMENDATION_DUE_DATE'); ?>
		</dt>
		<dd>
			<?php echo $this->escape($this->item->due_date); ?>
		</dd>
		<dt>
			<?php echo Text::_('COM_JLIKE_FORM_LBL_TODO_STATUS'); ?>
		</dt>
		<dd>
			<?php 
			if ($this->item->status === 'I')
			{
				echo Text::_('COM_JLIKE_FORM_TODO_STATUS_INCOMPLETED');
			}
			elseif ($this->item->status === 'C')
			{
				echo Text::_('COM_JLIKE_FORM_TODO_STATUS_COMPLETED');
			}
			?>
		</dd>
	</dl>
</fieldset>
