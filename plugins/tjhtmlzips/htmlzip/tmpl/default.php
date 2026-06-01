<?php

/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.techjoomla.com
*/
defined('_JEXEC') or die('Restricted access');

?>
<script>

	<?php
	if ($vars['mode'] != 'preview')
	{
		if ($vars['assessment'] == 1)
		{
			$status = 'AP';
		}
		else
		{
			$status = 'completed';
		}
		?>

		var lessonStartTime = new Date();

		var plugdataObject = {
			plgtype:'<?php echo $vars['plgtype']; ?>',
			plgname:'<?php echo $vars['plgname']; ?>',
			plgtask:'<?php echo $vars['plgtask']; ?>',
			lesson_id: <?php echo $vars['lesson_id']; ?>,
			attempt: <?php echo $vars['attempt']; ?>,
			mode: ' '
		};

		plugdataObject["current_position"] = 1;
		plugdataObject["total_content"] = 1;
		plugdataObject["lesson_status"] = '<?php echo $status; ?>';
		updateData(plugdataObject);

		tjhtmlzipInterval = setInterval(function () {
			lessonStoptime = new Date();
			var timespentonLesson = lessonStoptime - lessonStartTime;
			var timeinseconds = Math.round(timespentonLesson / 1000);
			plugdataObject.time_spent = timeinseconds;
			plugdataObject.lesson_status = "<?php echo $status; ?>";
			lessonStartTime = new Date();
			updateData(plugdataObject);
		}, 10000);
<?php }
else{ ?>
	var fHeight = jQuery(window).height();
	jQuery('#html_object').css('height', fHeight);
<?php
}
?>
</script>
<iframe id='html_object' onload="hideImage();" type='text/html' data-js-attr='tjlms-lesson-iframe' src="<?php echo $vars['file'];?>"
frameborder="0" width="100%" height="100%"></iframe>
