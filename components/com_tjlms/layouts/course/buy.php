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

$courseData     = $displayData;
$userData       = Factory::getUser();
$tjlmshelperObj = new comtjlmsHelper;
$courseLink     = 'index.php?option=com_tjlms&view=course&id=' . $courseData['id'];
$courseItemId   = $tjlmshelperObj->getitemid($courseLink);

if ($courseData['allowBuy'])
{
	if ($userData->guest == 1)
	{
		$buyLinkGuest = $tjlmshelperObj->tjlmsRoute('index.php?option=com_tjlms&view=buy&course_id=' . $courseData['id'] . '&Itemid=' . $courseItemId);
		$msg = Text::_('COM_TJLMS_LOGIN');
		$url = base64_encode($buyLinkGuest);
		?>

		<div class="center text-center control-group">
			<?php if($courseData['checkPrerequisiteCourseStatus'] == true) {?>
			<a href="<?php echo $tjlmshelperObj->tjlmsRoute('index.php?option=com_users&view=login&return='.$url); ?>">
				<button title="<?php echo Text::_('COM_TJLMS_LOGIN'); ?>" class="btn btn-large btn-block btn-primary tjlms-btn-flat" type="button" id="paid_course_button" ><?php echo Text::_('COM_TJLMS_COURSE_BUY_NOW')	?>
				</button>
			</a>
			<?php }else{?>
					<button class="btn btn-large btn-block tjlms-btn-flat btn-disabled bg-lightgrey" title="<?php echo Text::_('COM_TJLMS_VIEW_COURSE_PREREQUISITE_RESTRICT_MESSAGE'); ?>" type="button"><?php echo Text::_('COM_TJLMS_COURSE_BUY_NOW'); ?></button>
			<?php } ?>
		</div>
	<?php
	}
	else
	{
		$buyLink = $tjlmshelperObj->tjlmsRoute('index.php?option=com_tjlms&view=buy&course_id=' . $courseData['id']);
		?>
			<div class="form-group text-center center">
				<?php if($courseData['checkPrerequisiteCourseStatus'] == true) {?>
				<button  title="<?php echo Text::_('COM_TJLMS_BUYNOW_BTN_TOOLTIP'); ?>" class="btn btn-large btn-block btn-primary tjlms-btn-flat" type="button" id="paid_course_button" onclick="window.location.href='<?php	echo $buyLink; ?>'"><?php echo Text::_('COM_TJLMS_COURSE_BUY_NOW'); ?></button>
				<?php }else{?>
					<button class="btn btn-large btn-block btn-disabled bg-lightgrey tjlms-btn-flat" title="<?php echo Text::_('COM_TJLMS_VIEW_COURSE_PREREQUISITE_RESTRICT_MESSAGE'); ?>" type="button" ><?php echo Text::_('COM_TJLMS_COURSE_BUY_NOW'); ?></button>
				<?php } ?>
			</div>
	<?php
	}
}
