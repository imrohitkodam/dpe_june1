<?php

/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.techjoomla.com
*/
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

$html = $config['source'];	//$this->lesson_typedata->source;

include_once JPATH_ROOT.DS.'administrator/components/com_tjlms/js_defines.php';
$input = Factory::getApplication()->input;
$this->mode = $input->get('mode', '', 'STRING');
?>
<link rel="stylesheet" type="text/css"  href="<?php echo Uri::root(true). '/plugins/tjtextmedia/' . $this->_name . '/' . $this->_name .'/style/joomlacontent.css';?>"></link>
<script type="text/javascript" language="javascript">
	jQuery(document).ready(function () {
		hideImage();
		jQuery(".tjlms-lesson-player").addClass('hide_lesson_scroll');
		jQuery(".jt_content").on('load', function(){
			var frameContent = jQuery('.jt_content').contents();
			frameContent.find(".article-info").remove();
			frameContent.find(".pagenav").remove();
			frameContent.find(".icons").remove();
			frameContent.find(".item-page").css('padding','20px');
		});

		var fHeight = jQuery(window).height();
		jQuery('.jt_content').css('height', fHeight * 0.90);
	});
	<?php if ($this->mode != 'preview')
	{?>
		lessonStartTime = new Date();

		var plugdataObject = {
			plgtype:'<?php echo $config['plgtype']; ?>',
			plgname:'<?php echo $config['plgname']; ?>',
			plgtask:'<?php echo $config['plgtask']; ?>',
			lesson_id: <?php echo $config['lesson_id']; ?>,
			attempt: <?php echo $config['attempt']; ?>,
			mode: ' '
		};
		plugdataObject["current_position"] = 1;
		plugdataObject["total_content"] = 1;
		plugdataObject["lesson_status"] = "completed";
		updateData(plugdataObject);

		tjjoomlacontentInterval = setInterval(function () {
			lessonStoptime = new Date();
			var timespentonLesson = lessonStoptime - lessonStartTime;
			var timeinseconds = Math.round(timespentonLesson / 1000);
			plugdataObject.time_spent = timeinseconds;
			plugdataObject.lesson_status = "completed";
			lessonStartTime = new Date();
			updateData(plugdataObject);
		}, 10000);
	<?php
	} ?>
</script>
<iframe class="jt_content"type="text/html" width="100%"
		src="<?php echo Uri::root() . $config['file'] . '&tmpl=component';?>">
</iframe>
