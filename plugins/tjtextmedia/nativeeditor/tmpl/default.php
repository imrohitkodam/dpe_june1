<?php

/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.techjoomla.com
*/
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Plugin\CMSPlugin;

$html = $vars['source'];	//$this->lesson_typedata->source;

include_once JPATH_ROOT.DS.'administrator/components/com_tjlms/js_defines.php';
$input = Factory::getApplication()->input;
$this->mode = $input->get('mode', '', 'STRING');
?>

<script>
	jQuery(window).on('load',function () {
		hideImage();
	});
	<?php 	if ($this->mode != 'preview')
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
		lessonStartTime = new Date();

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

		tjnativeeditorInterval = setInterval(function () {
			lessonStoptime = new Date();
			var timespentonLesson = lessonStoptime - lessonStartTime;
			var timeinseconds = Math.round(timespentonLesson / 1000);
			plugdataObject.time_spent = timeinseconds;
			plugdataObject.lesson_status = "<?php echo $status; ?>";
			lessonStartTime = new Date();
			updateData(plugdataObject);
		}, 10000);
	<?php
	} ?>

</script>

<style>
.tjlms-wrapper
{
	background:#fff;
}
</style>

<?php
$offset = 0;
$item = new stdclass;
$item->text = $html;
$item->id = $vars['lesson_id'];
PluginHelper::importPlugin('content');
Factory::getApplication()->triggerEvent('onContentPrepare', array ('com_tjlms.lesson', &$item, &$item, $offset));

$results = Factory::getApplication()->triggerEvent('onContentBeforeDisplay', array('com_tjlms.lesson', &$item, &$item, $offset));
echo trim(implode("\n", $results));

?>

<div class="p-20">
	<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
			<?php echo $item->text; ?>
	</div>
</div>
<input type="hidden" id="htmlTimeSpent" value=''>

<?php

$results = Factory::getApplication()->triggerEvent('onContentAfterDisplay', array('com_tjlms.lesson', &$item, &$item, $offset));
echo trim(implode("\n", $results));

?>
