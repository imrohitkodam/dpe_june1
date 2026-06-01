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

if ($this->item->allowBuy)
{
	$buy_link = $this->tjlmshelperObj->tjlmsRoute('index.php?option=com_tjlms&view=buy&course_id='.$this->course_id);

	if ($this->oluser->guest == 1)
	{
		$buy_link_guest = 'index.php?option=com_tjlms&view=buy&course_id='.$this->course_id.'&Itemid=' . $this->courseItemId;
		$msg = Text::_('COM_TJLMS_LOGIN');
		$uri = $buy_link_guest;
		$url = base64_encode($uri);
		?>

		<div class=" center text-center control-group">
			<a href="<?php echo $this->tjlmshelperObj->tjlmsRoute('index.php?option=com_users&view=login&return='.$url); ?>">

				<button  title="<?php echo Text::_('COM_TJLMS_LOGIN'); ?>" class="btn btn-large btn-primary tjlms-btn-flat" type="button" id="paid_course_button" ><?php	echo Text::_('COM_TJLMS_COURSE_BUY_NOW')	?></button>
			</a>
		</div>
	<?php
	}
	else
	{
		$buyButtonText = ($renew != 1) ? Text::_('COM_TJLMS_COURSE_BUY_NOW') :  Text::_('COM_TJLMS_COURSE_RENEW_NOW');

		?>
			<div class="form-group text-center center">
				<button  title="<?php echo Text::_('COM_TJLMS_BUYNOW_BTN_TOOLTIP'); ?>" class="btn btn-large btn-primary tjlms-btn-flat" type="button" id="paid_course_button" onclick="window.location.href='<?php	echo $buy_link; ?>'"><?php	echo $buyButtonText;	?></button>
			</div>
	<?php
	}
}
