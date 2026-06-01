<?php
/**
 * @package     LMS_Shika
 * @subpackage  mod_lms_course_display
 * @copyright   Copyright (C) 2009-2020 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link        http://www.techjoomla.com
 */
// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;

HTMLHelper::_('bootstrap.renderModal');
HTMLHelper::stylesheet('modules/mod_lms_course_display/assets/css/thumbnail-slider.css');
HTMLHelper::script('modules/mod_lms_course_display/assets/js/thumbnail-slider.js');
HTMLHelper::stylesheet('media/com_tjlms/vendors/artificiers/artficier.css');
HTMLHelper::stylesheet('media/com_tjlms/css/tjlms.min.css');
HTMLHelper::script('media/com_tjlms/js/tjlms.min.js');

$app           = Factory::getApplication();
$count         = count($courses);
$courseCount   = $count != 0 ? "(" . sprintf("%02d", $count) . ")" : "";
$module->title = $module->title . $courseCount;
$userid        = Factory::getUser()->id;

if (empty($courses))
{
	return false;
}
?>

<div class="com_tjlms_content tjlms-wrapper coursesModule tjBs3" id="tj-contentslider-<?php echo $module->id;?>">
<!--toolbar-->
	<div class="tj-contentslider-left tj-contentslide-left-img" title="<?php echo Text::_('Previous'); ?>">&nbsp;
	</div>

	<div class="tj-contentslider-center-wrap " style="text-decoration: none;">
		<div  id="tj-contentslider-center-<?php  echo $module->id;?>" class="tj-contentslider-center" style="height:auto; !important" >
	<?php
		$template = $app->getTemplate(true)->template;
		$override = JPATH_SITE . '/templates/' . $template . '/html/layouts/com_tjlms/';
		$setLayout = str_replace(':', '', strstr($params->get('layout'), ':'));
		$basePath = JPATH_SITE . '/components/com_tjlms/layouts/';

		if ($setLayout == 'default')
		{
			$setLayout = 'course';
		}

		// Check course pin layout override exits in template.
		if (File::exists($override . $setLayout . 'pin.php'))
		{
			$layout = new FileLayout($setLayout . 'pin', $override);
		}
		else
		{
			$layout = new FileLayout($setLayout . 'pin', $basePath);
		}

		if (!empty($courses))
		{
			foreach ($courses as $data)
			{
				$checkPrerequisiteCourseStatus = true;

				if (PluginHelper::isEnabled('tjlms', 'courseprerequisite'))
				{
					PluginHelper::importPlugin('tjlms');
					$checkPrerequisiteCourseStatus = Factory::getApplication()->triggerEvent('onCheckPrerequisiteCourseStatus', array($data->id, $userid));
					$checkPrerequisiteCourseStatus = $checkPrerequisiteCourseStatus[0];
				}

				$data = (array) $data;
				$data['pinclass'] = 'col-xs-12';
				$data['checkPrerequisiteCourseStatus'] = $checkPrerequisiteCourseStatus;
				echo $layout->render($data);
			}
		}
	?>
		</div>
	</div>
	<div class="tj-contentslider-right tj-contentslide-right-img" title="<?php echo Text::_('Next'); ?>">&nbsp;
	</div>
</div>

<style>
	div.jsslide{padding:<?php echo $pinPadding; ?>px;}
</style>
<script type="text/javascript">
 	var xheight = '';

	jQuery(window).load(function() {
		xheight = 180;
		var container = $('tj-contentslider-<?php echo $module->id;?>');
		contentSliderInit_<?php echo $module->id; ?>(container);
		setFixedHeight_<?php echo $module->id;?>(container);
		contentSliderInit_<?php echo $module->id; ?>(container);
	});

	var contentSliderInit_<?php echo $module->id;?> = function(container){
		container.getElements('.jsslide').each(function(el){
			el.dispose();
		});

		var elems = $('tj-contentslider-center-<?php echo $module->id;?>').getElements('.tjlmspin');
		var total = elems.length;
		var options={
			w: <?php echo $xwidth; ?>,
			h: xheight,
			num_elem:  <?php echo $displayLimit; ?>,
			mode: 'horizontal', //horizontal or vertical
			direction: 'left', //horizontal: left or right; vertical: up or down
			total: total,
			url: '<?php echo JURI::base(); ?>modules/mod_jacontentslider/mod_jacontentslider.php',
			wrapper:  container.getElement("div.tj-contentslider-center"),
			duration: <?php echo $animationtime; ?>,
			interval: <?php echo $delaytime; ?>,
			modid: <?php echo $module->id;?>,
			running: false,
			auto:0
		};
		var jscontentslider = new Tjlms_ContentSlider( options );

		for(i=0;i<elems.length;i++){
			jscontentslider.update (elems[i].innerHTML, i);
		}
		jscontentslider.setPos(null);
		if(jscontentslider.options.auto){
			jscontentslider.nextRun();
		}

		container.getElement(".tj-contentslide-left-img").onclick = function(){setDirection2<?php echo $module->id;?>('right', jscontentslider);};
		container.getElement(".tj-contentslide-right-img").onclick = function(){setDirection2<?php echo $module->id;?>('left', jscontentslider);};
	};

	var setDirection2<?php echo $module->id;?> = function(direction, jscontentslider) {
		var oldDirection = jscontentslider.options.direction;
		jscontentslider.options.direction = direction;
		jscontentslider.options.interval = 100;
		jscontentslider.options.auto = 1;
		jscontentslider.nextRun();
		jscontentslider.options.auto = <?php echo $auto; ?>;
		jscontentslider.options.interval = <?php echo $delaytime; ?>;

		setTimeout(function(){
			jscontentslider.options.direction = oldDirection;
		}, 510);
	};

	var setFixedHeight_<?php echo $module->id;?> = function(container){
		var imgMaxHeight = -1;
		jQuery('.tjlms_pin_image', container).each(function(){
			imgMaxHeight = imgMaxHeight > jQuery(this).height() ? imgMaxHeight : jQuery(this).height();
		});

		// set max height to lesson image
		jQuery('.tjlms_pin_image', container).css('height', imgMaxHeight);

		var titlemaxHeight = -1;
		jQuery('.jsslide .tjlmspin__caption', container).each(function(){
			titlemaxHeight = titlemaxHeight > jQuery(this).outerHeight(true) ? titlemaxHeight : jQuery(this).outerHeight(true);
		});

		// set max height to lesson title
		jQuery('.tjlmspin__caption', container).css('height', titlemaxHeight);

		var descmaxHeight = -1;
		jQuery('.tjlmspin__likes_users', container).each(function(){
			descmaxHeight = descmaxHeight > jQuery(this).outerHeight(true) ? descmaxHeight : jQuery(this).outerHeight(true);
		});

		// set max height to lesson description
		jQuery('.tjlmspin__likes_users', container).css('height', descmaxHeight);

		var bottomHeight = jQuery('.jsslide .tjlmspin__likes_users', container).outerHeight(true);
		xheight = jQuery('.jsslide .thumbnail', container).outerHeight(true);
	};
</script>
