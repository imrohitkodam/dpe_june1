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
use Joomla\CMS\HTML\HTMLHelper;
include_once JPATH_ROOT.DS.'administrator/components/com_tjlms/js_defines.php';

HTMLHelper::_('stylesheet', '/components/com_tmt/assets/css/tmt.css');
?>
<script>
jQuery(window).load(function () {
	jQuery("#tjlms-lesson-content").addClass("no-margin");
	SetHeight(0);
	hideImage();
});

</script>
<style>
.tjlms-wrapper
{
	background:#fff;
}
</style>
<?php

$ol_user=Factory::getUser();

//$tjlms_settings			= 	$this->tjlmshelperObj->getSettings();


//$courseEnrolledUsers	=	$this->courseEnrolledUsers;
//$courseEnrolledUsers	=	$this->courseEnrolledUsers;
/*
$canaccess=$flag=0;
$enrol_approval=0;
$canaccess=1;
$flag=1;
$enrol_approval=1;
$attemp_flag=0;
*/
/*if(!JRequest::get('post',''))
	$attemp_flag=1;

*/

?>

<div class="<?php echo COM_TJLMS_WRAPPER_DIV; ?>">
	<div id="contentarea">
<?php
		$tjlmshelperObj	=	new comtjlmsHelper();
		$fileFormat = $tjlmshelperObj->getViewpath('com_tmt','testpremise','default','SITE','SITE');
		//$fileFormat = JPATH_SITE.'/components/com_tmt/views/testpremise/tmpl/default.php';
		$attempt = $this->attempt;
		ob_start();
		include($fileFormat);
		$html = ob_get_contents();
		ob_end_clean();
		echo $html;
?>
	</div>
</div>



