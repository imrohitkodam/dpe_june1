<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$enrollLinkGuest = 'index.php?option=com_tjlms&task=course.userEnrollAction&cId='.$this->course_id;
$msg = Text::_('COM_TJLMS_LOGIN');

$url = base64_encode($enrollLinkGuest);
?>
<div class="enrollHtml">
	<?php if ($this->oluser->guest == 1) { ?>

		<div class=" center text-center control-group">
			<a href="<?php echo $this->tjlmshelperObj->tjlmsRoute('index.php?option=com_users&view=login&return='.$url); ?>">
			<button title="<?php echo Text::_('COM_TJLMS_ENROL_BTN_TOOLTIP');?> " class="btn btn-large btn-block btn-primary tjlms-btn-flat" type="button"><?php	echo Text::_('TJLMS_COURSE_ENROL')	?></button>
			</a>
		</div>

	<?php }else{ ?>

		<form method='POST' name='adminForm' id='adminForm' class="form-validate form-horizontal enrolmentform mb-15" action='' enctype="multipart/form-data">
			<div class="center">
				<button title="<?php echo Text::_('COM_TJLMS_ENROL_BTN_TOOLTIP');?> " class="btn btn-large btn-block btn-primary tjlms-btn-flat" type="button" id="free_course_button" onclick="enrollUser();" ><?php	echo Text::_('TJLMS_COURSE_ENROL')	?></button>
			</div>
			<input type="hidden" name="option" value="com_tjlms" />
			<input type="hidden" id="task" name="task" value="course.userEnrollAction" />
			<input type="hidden" name="view" value="course" />
			<input type="hidden" id="course_id" name="cId" value="<?php echo (int) $this->item->id; ?>"/>
			<input type="hidden" id="rUrl" name="rUrl" value=<?php echo $this->relrUrl; ?> />
			<input type="hidden" name="boxchecked" value="" />
		</form>

		<?php
			if (!$this->oluser->guest) {
				$result = $this->dispatcher->trigger('onShowSetGoalBtn',array('com_tjlms.course',$this->item->id, $this->item->title));

				if(!empty($result))
				{
					echo $result[0];
				}
			}
			?>
	<?php } ?>
</div>
