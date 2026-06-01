<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Factory;

$input = Factory::getApplication()->input;
$view = $input->get('view');
?>

<script>
Joomla = window.Joomla || {};

!(function(document) {
window.jQuery && (function($){
	$(document).ready(function ()
	{
		var view = '<?php echo $view; ?>';

		if (view == 'dashboard')
		{
			versionCheckerOutput();
		}
	});
	})(jQuery);

})(document, Joomla);

	function versionCheckerOutput()
	{
		jQuery.ajax({
			url: "index.php?option=com_tjlms&task=dashboard.versionCheckerOutput",
			dataType: "HTML",
			success: function(data) {
			  jQuery('#version-widget').html(data);
			}
		});
	}

	function openMenu(thismeubutton)
	{
		jQuery(thismeubutton).closest('.tjlms-menu').toggleClass('open');
	}

	function tjlmstogglesidebar()
	{
		jQuery('body.com_tjlms #j-sidebar-container').toggleClass('tjlms-sidebar-hidden');
		jQuery('body.com_tjlms #j-main-container').toggleClass('tjlms-full-screen');
		jQuery('body.com_tjlms #j-sidebar-container #version-widget').toggleClass('tjlms-hide');
	}

</script>

<?php
	if (!empty($this->sidebar))
	{
	?>
		<div id="j-sidebar-container" class="span2" >
			<?php echo $this->sidebar; ?>
			<div id="version-widget">
			</div>
		</div>
		<div id="j-main-container" class="span10">
<?php
	}
	else
	{
?>
		<div id="j-main-container">
	<?php
	}
