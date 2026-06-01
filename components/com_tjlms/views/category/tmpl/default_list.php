<?php
/**
* @version		1.0.0 jgive $
* @package		jgive
* @copyright	Copyright © 2012 - All rights reserved.
* @license		GNU/GPL
* @author		TechJoomla
* @author mail	extensions@techjoomla.com
* @website		http://techjoomla.com
*/
// no direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

if(JVERSION>=3.0)
{
	HTMLHelper::_('bootstrap.tooltip');
	HTMLHelper::_('behavior.multiselect');
	//JHtml::_('formbehavior.chosen', 'select');
}


?>
<div id="container">

		<?php
		foreach($courses as $course)
		{
			?>
			<!--tjlms_pin_item-->
			<div class="tjlms_list_item">

				<?php
					$course_url =	Uri::root().substr(Route::_('index.php?option=com_tjlms&view=course&id='.$course->id.'&Itemid='.$this->allcousresItemid),strlen(Uri::base(true))+1);

					// converting to array
					$course_arr = (array)$course;
					$courseImg	= $this->tjlmsCoursesHelper->getCourseImage($course_arr,'S_');
				?>
				<!--thumbnail-->
				<div class="thumbnail tjlms_list_layout_element " style="padding:0px;">

					<!-- Image-->
					<div class="tjlms_list_image pull-left">
						<a href="<?php echo $course_url; ?>">
							<img alt="200x150"   class="tjlms_thmb_style" src="<?php echo $courseImg;?>">
						</a>
					</div>
					<!-- Image-->

					<div class="tjlms_list_layout_element_details">
					<!--caption -->
						<div class="tjlms_list_caption">
								<a href="<?php echo $course_url; ?>" style="font-weight: bold;color:#000 !important;  font-size: 18px;">
								  <?php
									if(strlen($course->title)>=100)
										echo substr($course->title,0,100).'...';
									else
										echo $course->title;
									?>
								</a>
						</div>
						<!--caption -->

						<!--course description -->
						<div class="tjlms_list_desc">
								  <?php
									if(strlen($course->description)>=400)
										echo substr($course->description,0,400).'...';
									else
										echo $course->description;
									?>
						</div>
						<!--course description -->
						<hr class="condensed">

							

						<!--coures details -->
					</div>
				</div>
				<!--thumbnail-->
			</div>
			<!--tjlms_pin_item-->
		<?php
		}
			?>
</div>
<!--container -->

