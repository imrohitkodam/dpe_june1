<?php

/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.techjoomla.com
*/
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;

$html = '';

if (isset($vars['source']))
{
	$html = $vars['source'];	//$this->lesson_typedata->source;
}

include_once JPATH_ROOT.DS.'administrator/components/com_tjlms/js_defines.php';
$input = Factory::getApplication()->input;
$this->mode = $input->get('mode', '', 'STRING');

?>

<script>

	<?php 	if ($this->mode != 'preview')
	{	?>

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
		plugdataObject["lesson_status"] = "completed";
		updateData(plugdataObject);
	<?php } ?>

</script>

<style>
.tjlms-wrapper
{
	background:#fff;
}
</style>


<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
	<div id="contentarea" class="container">
		<?php echo $html; ?>
	</div>
</div>
<input type="hidden" id="htmlTimeSpent" value=''>
