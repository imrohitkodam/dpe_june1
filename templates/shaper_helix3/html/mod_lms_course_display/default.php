<?php

/**
 * @package     LMS_Shika
 * @subpackage  mod_lms_course_display
 * @copyright   Copyright (C) 2009-2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.techjoomla.com
 */
// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;


JHTML::_('bootstrap.renderModal');
JHtml::stylesheet('modules/mod_lms_course_display/assets/css/thumbnail-slider.css');
JHtml::script('modules/mod_lms_course_display/assets/js/thumbnail-slider.js');
JHtml::stylesheet('media/com_tjlms/vendors/artificiers/artficier.css');
JHtml::stylesheet('media/com_tjlms/css/tjlms.min.css');

$app = Factory::getApplication();
$pinWidth = (int) $params->get('pin_width', 250);
$displayLimit            = $params->get('displayLimit', 2);
$pinPadding              = $params->get('pin_padding', 3);



// $displayLimit = (int) $params->get('display_limit', 4); // total items per page
$layout = new FileLayout('coursepin', JPATH_SITE . '/components/com_tjlms/layouts/');

// Template override
$overridePath = JPATH_SITE.'/templates/'.$app->getTemplate().'/html/layouts/com_tjlms/';
if (File::exists($overridePath . 'coursepin.php')) {
    $layout = new FileLayout('coursepin', $overridePath);
}
?>

<style>

#tj-contentslider-<?php echo $module->id; ?> .tj-contentslider-center {
  display: flex;
  flex-wrap: nowrap !important; /* prevent wrap */
  gap: 0; /* use padding instead */
}

#tj-contentslider-<?php echo $module->id; ?> .jsslide {
  box-sizing: border-box;
}

.tj-contentslide-left-img,
.tj-contentslide-right-img {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  user-select: none;
  font-size: 24px;
  font-weight: bold;
  color: #000;
  z-index: 10;
}

.tj-contentslide-left-img::before {
  content: "<"; /* left arrow */
}

.tj-contentslide-right-img::before {
  content: ">"; /* right arrow */
  margin-left:70px;
}

<?php
// Compute the total gap and flex‑basis calc values in PHP so CSS can use them
$gapPerSlide = $pinPadding * 2;                 // left+right padding per slide
$totalGap    = ($displayLimit - 1) * $gapPerSlide;
?>

#tj-contentslider-<?php echo $module->id; ?> .tj-contentslider-center {
  display: flex;
  flex-wrap: nowrap;
  gap: <?php echo $gapPerSlide;?>px;
  overflow: hidden;
}

/* Each jsslide takes an equal share of the container width minus gaps */
#tj-contentslider-<?php echo $module->id; ?> .jsslide {
  flex: 0 0 calc((100% - <?php echo $totalGap;?>px) / <?php echo $displayLimit;?>);
  box-sizing: border-box;


  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Make the thumbnail (image area) fill the top part of each slide */
#tj-contentslider-<?php echo $module->id; ?> .tjlmspin__thumbnail {
  flex: 1;               /* fill available height */
  overflow: hidden;
}

/* Caption area sits below, fixed height or auto */
#tj-contentslider-<?php echo $module->id; ?> .tjlmspin__caption {
  padding: 8px;
  background: #fff;
}



</style>
<?php
$padding = (int) $pinPadding;
$totalWidth = ($pinWidth + $padding * 2) * $displayLimit;


?>

<div id="mod_lms_course_display_<?php echo $module->id; ?>" class="mod_lms_course_display" style="width: <?php echo $totalWidth; ?>px;">


<div class="com_tjlms_content tjlms-wrapper coursesModule tjBs3" id="tj-contentslider-<?php echo $module->id;?>">
	<div class="tj-contentslider-left tj-contentslide-left-img" title="<?php echo Text::_('Previous'); ?>">&nbsp;</div>

	<div class="tj-contentslider-center-wrap" style="overflow: hidden;">
		<div id="tj-contentslider-center-<?php echo $module->id;?>" class="tj-contentslider-center">
			<?php
			if (!empty($courses))
			{
				foreach($courses as $index => $data)
				{
					$data = (array)$data;
					$data['pinclass'] = 'col-xs-12';
					$data['index'] = $index;
					$data['pinwidth'] = $pinWidth;
					echo '<div class="jsslide" style="box-sizing: border-box; padding: ' . $padding . 'px; width:' . $pinWidth . 'px;">';
					echo $layout->render($data);
					echo '</div>';
				}
			}
			?>
		</div>
	</div>

	<div class="tj-contentslider-right tj-contentslide-right-img" title="<?php echo Text::_('Next'); ?>">&nbsp;</div>
</div>
</div>


<script>
jQuery(function($) {
	const moduleId = <?php echo (int) $module->id; ?>;
	const slideWrapper = $('#tj-contentslider-center-' + moduleId);
	const slides = slideWrapper.find('.jsslide');
	const perPage = <?php echo $displayLimit; ?>;
	let currentPage = 0;

	function showPage(page) {
		slides.hide();
		slides.slice(page * perPage, (page + 1) * perPage).show();
	}

	// Init
	showPage(currentPage);

	// Events
	$('.tj-contentslide-left-img').on('click', function() {
		currentPage = Math.max(0, currentPage - 1);
		showPage(currentPage);
	});

	$('.tj-contentslide-right-img').on('click', function() {
		const totalPages = Math.ceil(slides.length / perPage);
		currentPage = Math.min(totalPages - 1, currentPage + 1);
		showPage(currentPage);
	});
});
</script>

<style>
#tj-contentslider-<?php echo $module->id;?> .jsslide {
	margin-bottom: 10px;
}
</style>