<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.renderModal');

$data = $displayData;

$app = Factory::getApplication();
$comparams = $app->getParams();
$courseName = $data['title'];

?>

<div class="<?php echo $data['pinclass'];?> tjlmspin">
	<div class="thumbnail p-0 br-0 tjlmspin__thumbnail">
		<!--COURSE IMAGE PART-->
		<a href="<?php echo  $data['url']; ?>"  class="center">
			<div class="bg-contain bg-repn" title="<?php echo $this->escape($courseName); ?>" style="background:url('<?php echo $data['image'];?>'); background-position: center center; background-size: cover; background-repeat: no-repeat;">
				<img class='tjlms_pin_image' style="visibility:hidden" src="<?php echo $data['image'];?>" alt="<?php echo  Text::_('TJLMS_IMG_NOT_FOUND') ?>" title="<?php echo $this->escape($courseName); ?>" />
			</div>
		</a>

		<div class="caption tjlmspin__caption">
			<h4 class="tjlmspin__caption_title text-truncate">
				<a title="<?php echo $this->escape($courseName); ?>" href="<?php echo  $data['url']; ?>">
					<?php echo $this->escape($courseName); ?>
				</a>
			</h4>

			<small class="tjlmspin__caption_desc">
			<?php

			$short_desc_char = $comparams->get('pin_short_desc_char', 50, 'INT');

			if(strlen($data['short_desc']) >= $short_desc_char)
				echo substr($data['short_desc'], 0, $short_desc_char).'...';
			else
				echo $data['short_desc'];
			?>
			</small>

		</div>
<!--
		<div class="tjlmspin__likes_users">
			<div class="container-fluid">
				<div class="row text-center mb-10">
					<hr class="mt-10 mb-10">
					<div class="col-xs-6 col-sm-6 tjlmspin__likes">
						<span class=" " title="<?php // echo JText::_('COM_TJLMS_LIKES'); ?>">
							<i class="fa fa-thumbs-up"></i>
							<span class="count">
								<b><?php // echo $data['likesforCourse']; ?></b>
							</span>
						</span>
					</div>

					<div class="col-xs-6 col-sm-6 tjlmspin__users">
						<span class="  " title="<?php // echo JText::_('COM_TJLMS_STUDENT'); ?>">
							<i class="fa fa-user"></i>
							<span class="count">
								<b><?php // echo $data['enrolled_users_cnt']; ?></b>
							</span>
						</span>
					</div>
				</div>
			</div>
		</div>
-->
	</div>
</div>
