<?php
/**
 * @package Tjlms
 * @copyright Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link     http://www.com
 */
defined('_JEXEC') or die('Restricted access');
?>
<?php
switch ($launch_details->launchin)
{
	case 'iframe' :
	default:
	$target = 'launchFrame';
	break;

	case 'same_view' :
	$target = '';
	break;

	case 'new_tab' :
	$target = '_blank';
	break;
}
?>
<form id="ltiLaunchForm" name="ltiLaunchForm" method="POST" enctype="application/x-www-form-urlencoded" action="<?php echo $config['file'];?>" target="<?php echo $target;?>" style="display:none;">

		<?php
			foreach ($launch_params as $key => $value)
			{
		?>
				<input type="hidden" name="<?php echo htmlspecialchars($key, ENT_QUOTES, $encoding = 'UTF-8');?>"
				value="<?php echo htmlspecialchars($value, ENT_QUOTES, $encoding = 'UTF-8');?>">
		<?php
			}
		?>
		<input type="hidden" name="oauth_signature" value="<?php echo $signature;?>">

</form>
<script>
	var loading_window = "<?php echo $launch_details->launchin; ?>";
	jQuery(document).ready(function()
	{
		jQuery("#ltiLaunchForm").submit();
	});
	jQuery(window).load(function()
	{
		var height = jQuery(this).height();
		jQuery("#id_launchFrame").css("height",height-75);
		jQuery("#id_launchFrame").css("width",'100%');
		hideImage();
	});
</script>
<?php
if ($launch_details->launchin == 'iframe' )
{
?>
	<iframe name="launchFrame" id="id_launchFrame" src="about:blank"  width="100%"  scrolling="auto"> </iframe>
<?php
}


