<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Uri\Uri;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;

HTMLHelper::_('bootstrap.tooltip');
$course   = $this->item;

$descShown = 0;

PluginHelper::importPlugin('content');

$jlikeresult = Factory::getApplication()->triggerEvent('OnAftercourseTitle', array('com_tjlms.course', $this->course_id, $course->title));

?>
<div class="tjlms-course-header">
	<div class="container-fluid mb-20">
		<div class="row">

			<!--Course message and desc -->

			<?php
				echo $this->loadTemplate('message');
			?>
			<!--Course message and desc ends -->

			<div class="tjlms-course-header__img d-table-cell col-lg-4 col-sm-5 col-12">
				<img itemprop="image" alt="<?php echo $course->title;?>" src="<?php echo $course->image;?>" id="<?php echo 'img'.$course->id;?>" />
			</div>
			<div class="tjlms-course-header__info d-table-cell valign-top col-lg-8 col-sm-7 col-12	">
				<h1 itemprop="name" class="mt-0">
					<?php echo $this->escape($course->title);	?>
				</h1>
				<div class="small hidden-xs"><?php  echo Text::_('TJLMS_COURSE_NAME'). implode(" > ", $this->course_categories); ?></div>

		<?php if (!empty($jlikeresult[0])): ?>

				<div class="tjcourse-likes" id="jlike-container">
						<?php	echo $jlikeresult[0]; ?>
				</div>

		<?php  endif;	?>

		<!-- <?php if (!empty($this->courseTrack['status'])) { ?>
			<li class="">
				<div class="label label-success">
				<?php
				// Display course status, if course status updated through backend the display status as 'Manually Completed'
				if (($this->courseTrack['totalLessons'] != $this->courseTrack['completedLessons']) && $this->courseTrack['status'] == 'C') {
					echo Text::_('COM_TJLMS_VIEW_COURSE_TRACK_MANUALLY_STATUS');
				}

				echo Text::_('COM_TJLMS_VIEW_COURSE_TRACK_' . $this->courseTrack['status']);
				?>
				</div>
			</li>
		<?php } ?> -->

		<?php
			if ((isset($this->item->userOrder->status) && $this->item->userOrder->status == "C" || $this->item->userEnrollment->id) && !empty($this->item->toc))
			{
				echo $this->loadTemplate('resume');

			}
		?>

		<?php if($this->tjlmsparams->get('social_sharing')) :?>

				<div class="container-fluid hidden-xs">
					<div class="row">
					<?php
					if($this->tjlmsparams->get('social_shring_type')=='addthis')
					{
						$pid = $this->tjlmsparams->get('addthis_publishid');

						if(!empty($pid))
						{
							$add_this_js='http://s7.addthis.com/js/300/addthis_widget.js';
							HTMLHelper::_('script', $add_this_js);

							$add_this_share='
							<!-- AddThis Button BEGIN -->
							<div class="addthis_toolbox addthis_default_style">
							<a class="addthis_button_facebook_like" fb:like:layout="button_count" class="addthis_button" addthis:url="'.$this->courseDetailsUrl.'"></a>
							<a class="addthis_button_google_plusone" g:plusone:size="medium" class="addthis_button" addthis:url="'.$this->courseDetailsUrl.'"></a>
							<a class="addthis_button_tweet" class="addthis_button" addthis:url="'.$this->courseDetailsUrl.'"></a>
							<a class="addthis_button_pinterest_pinit" class="addthis_button" addthis:url="'.$this->courseDetailsUrl.'"></a>
							<a class="addthis_counter addthis_pill_style" class="addthis_button" addthis:url="'.$this->courseDetailsUrl.'"></a>
							</div>
							<script type="text/javascript">
								var addthis_config ={ pubid: "'.$pid.'"};
							</script>
							<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid="'.$pid.'"></script>
							<!-- AddThis Button END -->' ;

							//$integrationsHelper=new integrationsHelper();
							//$integrationsHelper->loadScriptOnce($add_this_js);
							//output all social sharing buttons
							echo' <div id="rr" style="">
								<div class="social_share_container">
								<div class="social_share_container_inner">'.
									$add_this_share.
								'</div>
							</div>
							</div>
							';
						}
					}
					else
					{
						$native_share = Uri::root(true) . '/components/com_tjlms/assets/js/native_share.js';
						echo "<script type='text/javascript' src='".$native_share."'></script>";
						echo '<div id="fb-root"></div>';
						echo '<div class="tjlms_horizontal_social_buttons pl-0">';

						echo '<div class="pull-left"><div class="fb-share-button"
								data-href="'.$this->courseDetailsUrl.'"
								data-layout="button_count">
							  </div></div>';

						echo '<div class="pull-left">
								&nbsp; <a href="https://twitter.com/share" class="twitter-share-button" data-url="'.$this->courseDetailsUrl.'" data-counturl="'.$this->courseDetailsUrl.'"  data-lang="en">Tweet</a>
							</div>';
						echo '</div>
							<div class="clearfix"></div>';
					}
					?>
					</div>
				</div>

		<?php endif; ?>
			</div>

		</div>
	</div>

	<?php if ($this->tjlmsparams->get('enable_tags') == 1 && !empty($course->course_tags->itemTags)) : ?>
	<div class="row">
		<div class="col-xs-12">
			<div class="mt-15">
				<?php
					$course->tagLayout = new FileLayout('course.tags', JPATH_SITE . '/components/com_tjlms/layouts');
					echo $course->tagLayout->render($course->course_tags->itemTags);
				?>
			</div>
		</div>
	</div>

	<?php endif; ?>

	<div class="container-fluid">
		<div itemprop="description" class="row tjlms-course-header__desc">

			<div class="long_desc text-break">
				<?php
				if ($course->description)
				{
					if(strlen(strip_tags($course->description)) > 150 )
						echo $this->tjlmshelperObj->html_substr($course->description, 0, 150 ).'<a href="javascript:" class="r-more">' . Text::_("COM_TJLMS_TOC_COURSE_DESC_MORE") . '</a>';
					else
						echo $this->tjlmshelperObj->html_substr($course->description, 0);
				}
				else
				{
					if(strlen($course->short_desc) > 150 )
						echo $this->tjlmshelperObj->html_substr($this->escape($course->short_desc), 0, 150 ).'<a href="javascript:" class="r-more">' . Text::_("COM_TJLMS_TOC_COURSE_DESC_MORE") . '</a>';
					else
						echo $this->tjlmshelperObj->html_substr($this->escape($course->short_desc), 0);
				}
				?>
			</div>
			<div class="long_desc_extend no-margin" style="display:none;">
					<?php
					if (!empty($course->description))
					{
						echo $course->description.'<a href="javascript:" class="r-less">' . Text::_("COM_TJLMS_TOC_COURSE_DESC_LESS") . '</a>';
					}
					else
					{
						echo $this->escape($course->short_desc).'<a href="javascript:" class="r-less">' . Text::_("COM_TJLMS_TOC_COURSE_DESC_LESS") . '</a>';
					}
					?>
				</div>

		</div>
	</div>
</div>
