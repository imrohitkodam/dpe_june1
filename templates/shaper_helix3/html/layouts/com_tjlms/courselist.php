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
<!--tjlmslist-->
<div class="tjlmslist1 d-none d-sm-block mb-4 col-sm-6 col-12">
	<!--thumbnail-->
	<div class="thumbnail br-0 p-0 border jjjj">
		<!--row-->
		<div class="row">
			<!--col-sm-3-->
			<div class="col-12">
				<!--course-image-->
				<a href="<?php echo  $data['url']; ?>"
				class="">
					<!--tjlmslist__image-->
					<div class="tjlmslist__image" title="<?php echo $this->escape($courseName); ?>" style="background-image:url('<?php echo $data['image'];?>'); background-position: center center; background-size: contain; background-repeat: no-repeat;background-color:#f2f2f2;height:250px;">
						<!-- <img class='tjlms_pin_image invisible img-responsive' src="<?php //echo $data['image'];?>" alt="<?php //echo  Text::_('TJLMS_IMG_NOT_FOUND') ?>" title="<?php //echo $this->escape($courseName); ?>" /> -->

						<!--
						<span class="tjlmspin__price">
							<small>
							<?php
								// echo $data['formatted_price'];
							?>
							</small>
						</span>
						-->
					</div><!--/tjlmslist__image-->
				</a><!--/course-image-->
			</div><!--/col-sm-3-->

			<!--col-sm-9-->
			<div class="col-12">
				<div class="p-3">
					<h4 class="d-none d-sm-block tjlmspin__caption_title text-truncate m-0 py-10">
						<a title="<?php echo $this->escape($courseName); ?>" href="<?php echo  $data['url']; ?>">
								<?php echo $this->escape($courseName); ?>
						</a>
					</h4>
					<strong class="d-blobk d-sm-none tjlmspin__caption_title mt-0 mb-0">
						<a title="<?php echo $this->escape($courseName); ?>" href="<?php echo  $data['url']; ?>">
								<?php echo $this->escape($courseName); ?>
						</a>
					</strong>
					<small class="tjlmspin__caption_desc font-500">
						<?php

						$short_desc_char = $comparams->get('pin_short_desc_char', 50, 'INT');

						if(strlen($data['short_desc']) >= $short_desc_char)
							echo substr($data['short_desc'], 0, $short_desc_char).'...';
						else
							echo $data['short_desc'];
						?>
					</small>
					<!-- <hr class="my-10"> -->
					<!--d-flex-->
					<div class="d-flex justify-content-end">
						<!--tjlmspin__likes-->
						<!--
								<div class="tjlmspin__likes pr-10">
									<span class="" title="<?php echo Text::_('COM_TJLMS_LIKES'); ?>">
										<i class="fa fa-thumbs-up"></i>
										<span class="count">
											<b><?php //  echo $data['likesforCourse']; ?>
											</b>
										</span>
									</span>
								</div>
							-->
							<!--/tjlmspin__likes-->
												<!--tjlmspin__users-->
							<!--
										<div class="tjlmspin__users pr-10">

											<span class="" title="<?php // echo JText::_('COM_TJLMS_STUDENT'); ?>">
												<i class="fa fa-user"></i>
												<span class="count">
														<b><?php // echo $data['enrolled_users_cnt']; ?></b>
												</span>
											</span>
										</div>
							-->
							<!--tjlmspin__users-->
					</div><!--/d-flex-->
				</div>	
			</div><!--/col-12-->
		</div><!--/row-->
	</div><!--/thumbnail-->
</div><!--/tjlmslist-->


<!--tjlmspin-->
<div class="col-12 col-sm-6 tjlmspin d-block d-sm-none">
	<div class="thumbnail p-0 br-0 tjlmspin__thumbnail border mb-4">
		<!--COURSE IMAGE PART-->
		<a href="<?php echo  $data['url']; ?>"  class="center">
			<div class="bg-contain bg-repn" title="<?php echo $this->escape($courseName); ?>" style="background:url('<?php echo $data['image'];?>'); background-position: center center; background-size: cover; background-repeat: no-repeat;">
				<img class='tjlms_pin_image' style="visibility:hidden" src="<?php echo $data['image'];?>" alt="<?php echo  Text::_('TJLMS_IMG_NOT_FOUND') ?>" title="<?php echo $this->escape($courseName); ?>" />
			</div>
		</a>

		<div class="caption tjlmspin__caption p-2">
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

		<small class="tjlmspin__price px-2">
				<?php
					echo $data['formatted_price'];
				?>
		</small>

		<div class="tjlmspin__likes_users">
			<div class="px-3">
				<div class="row text-center mb-10">
					<hr class="mt-10 mb-10">
					<div class="col-6 col-sm-6 tjlmspin__likes">
						<span class=" " title="<?php echo Text::_('COM_TJLMS_LIKES'); ?>">
							<i class="fa fa-thumbs-up"></i>
							<span class="count">
								<b><?php echo $data['likesforCourse']; ?></b>
							</span>
						</span>
					</div>

					<div class="col-6 col-sm-6 tjlmspin__users">
						<span class="  " title="<?php echo Text::_('COM_TJLMS_STUDENT'); ?>">
							<i class="fa fa-user"></i>
							<span class="count">
								<b><?php echo $data['enrolled_users_cnt']; ?></b>
							</span>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div><!--/tjlmspin-->
