<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJtextmedia,lessonlink
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;

include_once JPATH_ROOT . '/administrator/components/com_tjlms/js_defines.php';
$input      = Factory::getApplication()->input;
$this->mode = $input->get('mode', '', 'STRING');
$url = Uri::root(true) . '/plugins/tjtextmedia/' . $this->_name . '/' . $this->_name . '/js/Readability.min.js';
Factory::getDocument()->addScript($url);
?>
<link rel="stylesheet" type="text/css"
	href="<?php echo Uri::root(true) . '/plugins/tjtextmedia/' . $this->_name . '/' . $this->_name . '/style/tjlmslessonlink.css';?>"></link>
<script>
	jQuery(document).ready(function () {
		hideImage();
		jQuery(".tjlms-lesson-player").addClass('hide_lesson_scroll');
		var fHeight = jQuery(window).height();
		jQuery('.jt_link_lesson').css('height', fHeight * 0.9);
	});
	<?php
	if ($this->mode != 'preview')
	{
		?>
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

		tjlessolinkInterval = setInterval(function () {
			lessonStoptime = new Date();
			var timespentonLesson = lessonStoptime - lessonStartTime;
			var timeinseconds = Math.round(timespentonLesson / 1000);
			plugdataObject.time_spent = timeinseconds;
			plugdataObject.lesson_status = "completed";
			lessonStartTime = new Date();
			updateData(plugdataObject);
		}, 10000);

		<?php
	}
	?>
</script>

<?php

	if ($mediaParams['readerview'] == 1)
	{
		?>
		<script type="text/javascript">
			jQuery(document).ready(function (){
				var documentClone = document.cloneNode(true);
				var article       = new Readability(documentClone).parse();
				jQuery('#url-title').empty().html(article.title);
				jQuery('#url-content').empty().html(article.content);
			});

		</script>
		<h1 id="url-title"></h1>
		<hr/>
		<div id="url-content">
			<?php echo file_get_contents($config['file']); ?>
		</div>
		<?php
	}
	else
	{
		?>
		<iframe type="text/html" height="100%" width="100%"
		class="jt_link_lesson" src="<?php echo $config['file'];?>">
		</iframe>
		<?php
	}
