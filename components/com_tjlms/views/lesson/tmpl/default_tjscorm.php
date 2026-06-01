<?php
/**
 * @package InviteX
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.techjoomla.com
 */
	defined('_JEXEC') or die('Restricted access');
	jimport('joomla.html.pane');


	$course_entry_file = JURI::base().'media/com_tjlms/lessons/'.$this->lesson_data->id.'/scorm/'.'index.html';
?>
<style>
	iframe, object, embed {
       width: 100%;
       height:620px;
}
</style>
<script>
var stagefile=<?php echo "'". $course_entry_file. "'" ?>;

 function set_entry()
 {
			document.getElementById('scorm_object').setAttribute('src',stagefile);
 }
 window.onload=set_entry;
</script>
<?php


	include(JPATH_COMPONENT_SITE.'/libraries/scorm/titanium.php');
?>

<div class="techjoomla-bootstrap">

<form method='POST' name='adminForm' id='adminForm' action=''>

        <div style="padding-bottom: 9px;">
          <div id="tab1" class="tab-pane active" style="width:100%;">

							<!--Start Player Code -->
							<div id='scormapi-parent'>

							</div>
							<div id="scorm_content">
							<iframe id="scorm_object" type="text/html"></iframe>
							</div>
							<!-- End Player Code-->
          </div>

        </div><!-- tab content-->

		</form>
	</div>



