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

$courseData      = $displayData;
$userData        = Factory::getUser();
$tjlmshelperObj  = new comtjlmsHelper;
$courseLink      = 'index.php?option=com_tjlms&view=course&id=' . $courseData['id'];
$itemId          = $tjlmshelperObj->getitemid($courseLink);
$relrUrl         = base64_encode($courseLink . '&Itemid=' . $itemId);

?>
<div class="enrollHtml">
	<?php if ($userData->guest == 1) {
		$enrollLinkGuest = 'index.php?option=com_tjlms&task=course.userEnrollAction&cId=' . $courseData['id'];
		$url             = base64_encode($enrollLinkGuest);
		?>

		<div class=" center text-center control-group">
			<?php if($courseData['checkPrerequisiteCourseStatus'] == true) {?>
			<a href="<?php echo $tjlmshelperObj->tjlmsRoute('index.php?option=com_users&view=login&return=' . $url); ?>">
			<button title="<?php echo Text::_('COM_TJLMS_ENROL_BTN_TOOLTIP');?> " class="btn btn-large btn-block btn-primary tjlms-btn-flat" type="button"><?php echo Text::_('TJLMS_COURSE_ENROL')	?></button>
			</a>
			<?php }else{?>
				<button class="btn btn-large btn-block btn-disabled bg-lightgrey tjlms-btn-flat" title="<?php echo Text::_('COM_TJLMS_VIEW_COURSE_PREREQUISITE_RESTRICT_MESSAGE'); ?>" type="button" ><?php echo Text::_('TJLMS_COURSE_ENROL'); ?></button>
			<?php } ?>
		</div>

	<?php }else{ ?>

		<form method='POST' name='adminFormToc<?php echo $courseData['id'];?>' id='adminFormToc<?php echo $courseData['id'];?>' class="form-validate form-horizontal enrolmentform mb-15" action='' enctype="multipart/form-data">
			<div class="center">
				<?php if($courseData['checkPrerequisiteCourseStatus'] == true) {?>
				<button title="<?php echo Text::_('COM_TJLMS_ENROL_BTN_TOOLTIP');?> " class="btn btn-large btn-block btn-primary tjlms-btn-flat" type="button" id="free_course_button" onclick="enrollUser('<?php echo $courseData['id'];?>');" ><?php	echo Text::_('TJLMS_COURSE_ENROL')	?></button>
				<?php }else{?>
				<button class="btn btn-large btn-block btn-disabled bg-lightgrey tjlms-btn-flat"  title="<?php echo Text::_('COM_TJLMS_VIEW_COURSE_PREREQUISITE_RESTRICT_MESSAGE'); ?>" type="button" ><?php echo Text::_('TJLMS_COURSE_ENROL'); ?></button>
				<?php } ?>

			</div>
			<input type="hidden" name="option" value="com_tjlms" />
			<input type="hidden" id="task" name="task" value="course.userEnrollAction" />
			<input type="hidden" name="view" value="course" />
			<input type="hidden" id="course_id" name="cId" value="<?php echo (int) $courseData['id']; ?>"/>
			<input type="hidden" id="rUrl" name="rUrl" value="<?php echo $relrUrl; ?>" />
			<input type="hidden" name="boxchecked" value="" />
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php } ?>
</div>
