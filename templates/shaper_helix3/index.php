<?php
/**
* @package Helix3 Framework
* Template Name - Shaper Helix3
* @author JoomShaper https://www.joomshaper.com
* @copyright (c) 2010 - 2021 JoomShaper
* @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
*/
//no direct accees
defined('_JEXEC') or die('restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;

$doc = Factory::getDocument();
$app = Factory::getApplication();
$menu = $app->getMenu()->getActive();
// DPE hack
$doc->addStyleSheet(Uri::root() . 'media/system/css/modal.css');
$doc->addScript(Uri::root() . 'media/system/js/messages.min.js');
$doc->addScript(Uri::root() . 'media/com_dpe/js/squeezebox-compat.js');
$doc->addScript(Uri::root() . 'media/com_dpe/js/sppagebuildertab.js');

JLoader::import('ComtjlmsHelper', JPATH_SITE . '/components/com_tjlms/helpers');
$app              = Factory::getApplication();
$comtjlmsHelper   = new ComtjlmsHelper;
$ucmitemId           = $comtjlmsHelper->getitemid('index.php?option=com_dpe&view=ucmbulkdownload');

// Hack end
//Load Helix
$helix3_path = JPATH_PLUGINS . '/system/helix3/core/helix3.php';
$user = Factory::getUser();

if (file_exists($helix3_path))
{
	require_once($helix3_path);
	$helix3 = helix3::getInstance();
}
else
{
	die('Please install and activate helix plugin');
}

//Coming Soon
if ($helix3->getParam('comingsoon_mode'))
{
	header("Location: " . Route::_(Uri::root(true) . "/index.php?tmpl=comingsoon", false));
	exit();
}

//Class Classes
$body_classes = '';
if ($helix3->getParam('sticky_header'))
{
	$body_classes .= ' sticky-header';
}

$body_classes .= ($helix3->getParam('boxed_layout', 0)) ? ' layout-boxed' : ' layout-fluid';

if (isset($menu) && $menu)
{
	if ($menu->getParams()->get('pageclass_sfx'))
	{
		$body_classes .= ' ' . $menu->getParams()->get('pageclass_sfx');
	}
}

$body_classes .= ' off-canvas-menu-init';

//Body Background Image
if ($bg_image = $helix3->getParam('body_bg_image'))
{
	$body_style = 'background-image: url(' . Uri::base(true) . '/' . $bg_image . ');';
	$body_style .= 'background-repeat: ' . $helix3->getParam('body_bg_repeat') . ';';
	$body_style .= 'background-size: ' . $helix3->getParam('body_bg_size') . ';';
	$body_style .= 'background-attachment: ' . $helix3->getParam('body_bg_attachment') . ';';
	$body_style .= 'background-position: ' . $helix3->getParam('body_bg_position') . ';';
	$body_style = 'body.site {' . $body_style . '}';
	
	$doc->addStyledeclaration($body_style);
}

//Custom CSS
if ($custom_css = $helix3->getParam('custom_css'))
{
	$doc->addStyledeclaration($custom_css);
}

//Custom JS
if ($custom_js = $helix3->getParam('custom_js'))
{
	$doc->addScriptdeclaration($custom_js);
}

//preloader & goto top
$doc->addScriptdeclaration("\nvar sp_preloader = '" . $this->params->get('preloader') . "';\n");
$doc->addScriptdeclaration("\nvar sp_gotop = '" . $this->params->get('goto_top') . "';\n");
$doc->addScriptdeclaration("\nvar sp_offanimation = '" . $this->params->get('offcanvas_animation') . "';\n");
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php $helix3->loadHead(); ?>
	<?php
	$megabgcolor = ($helix3->PresetParam('_megabg')) ? $helix3->PresetParam('_megabg') : '#ffffff';
	$megabgtx = ($helix3->PresetParam('_megatx')) ? $helix3->PresetParam('_megatx') : '#333333';

	$preloader_bg = ($helix3->getParam('preloader_bg')) ? $helix3->getParam('preloader_bg') : '#f5f5f5';
	$preloader_tx = ($helix3->getParam('preloader_tx')) ? $helix3->getParam('preloader_tx') : '#f5f5f5';

			// load css, less and js
			$helix3->addCSS('bootstrap.min.css'); // CSS Files
			
			$version = new Version();
			$JoomlaVersion = $version->getShortVersion();

			if (version_compare($JoomlaVersion, '5.0.0', '<')) {
				$helix3->addCSS('joomla-fontawesome.min.css, font-awesome-v4-shims.min.css');
			} else {
				$helix3->addCSS('fontawesome.min.css, font-awesome-v4-shims.min.css');
			}

			$helix3->addJS('bootstrap.min.js, jquery.sticky.js, main.js') // JS Files
			->lessInit()->setLessVariables(array(
				'preset' => $helix3->Preset(),
				'bg_color' => $helix3->PresetParam('_bg'),
				'text_color' => $helix3->PresetParam('_text'),
				'major_color' => $helix3->PresetParam('_major'),
				'megabg_color' => $megabgcolor,
				'megatx_color' => $megabgtx,
				'preloader_bg' => $preloader_bg,
				'preloader_tx' => $preloader_tx,
			))
			->addLess('master', 'template');

			//RTL
			if ($this->direction == 'rtl')
			{
				$helix3->addCSS('bootstrap-rtl.min.css')
				->addLess('rtl', 'rtl');
			}
			
			$helix3->addLess('presets', 'presets/' . $helix3->Preset(), array('class' => 'preset'));
			
			//Before Head
			if ($before_head = $helix3->getParam('before_head')) {
				echo $before_head . "\n";
			}
			?>
		</head>

		<body class="<?php echo $helix3->bodyClass($body_classes); ?>">

			<input type='hidden' id="ucmBulkItemId" value="<?php echo $ucmitemId;?>">
			<div class="body-wrapper">
				<div class="body-innerwrapper">
					<?php $helix3->generatelayout(); ?>
				</div>
			</div>

			<!-- Off Canvas Menu -->
			<div class="offcanvas-menu">
				<a href="#" class="close-offcanvas" aria-label="Close"><i class="fa fa-remove" aria-hidden="true" title="<?php echo Text::_('HELIX_CLOSE_MENU'); ?>"></i></a>
				<div class="offcanvas-inner">
					<?php if ($helix3->countModules('offcanvas')) : ?>
						<jdoc:include type="modules" name="offcanvas" style="sp_xhtml"/>
					<?php else : ?>
						<p class="alert alert-warning">
							<?php echo Text::_('HELIX_NO_MODULE_OFFCANVAS'); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>

			<?php
			if ($this->params->get('compress_css'))
			{
				$helix3->compressCSS();
			}
			
			$tempOption    = $app->input->get('option');
			
			if ($this->params->get('compress_js') && $tempOption != 'com_config')
			{
				$helix3->compressJS($this->params->get('exclude_js'));
			}
			
			//before body
			if ($before_body = $helix3->getParam('before_body'))
			{
				echo $before_body . "\n";
			}
			?>

			<jdoc:include type="modules" name="debug" />
			<jdoc:include type="modules" name="helixpreloader" />

			<!-- Go to top -->
			<?php if ($this->params->get('goto_top')) : ?>
				<a href="javascript:void(0)" class="scrollup" aria-label="<?php echo Text::_('HELIX_GOTO_TOP'); ?>">&nbsp;</a>
			<?php endif; ?>
		</body>

		<!-- //DPE hack -->
		<script>
			setTimeout(function(){

				jQuery('.close-icon').click(function() {
					var target = jQuery(this).closest('span').data('bs-target');
					if (target){

						var ulElementId = target.replace('#', '');
					}
					else
					{
						var target = jQuery(this).closest('span').data('target');
						if(target){
							var ulElementId = target.replace('#', '');
						}
						
					}
					
					jQuery('#'+ulElementId).removeAttr('style');
					
					setTimeout(function(){
						jQuery('#'+ulElementId).removeClass('show');
					},1200)
					jQuery('[data-bs-target="'+target+'"]').addClass('collapsed');
					jQuery('[data-target="'+target+'"]').addClass('collapsed');


				});

				jQuery('.open-icon').click(function() {
					var target = jQuery(this).closest('span').data('target');
					if (target){

						var ulElementId = target.replace('#', '');
					}
					else
					{
						var target = jQuery(this).closest('span').data('target');
						if(target){var ulElementId = target.replace('#', '');}
						
					}  
					jQuery('#'+ulElementId).removeAttr('style');
					
					setTimeout(function(){
						jQuery('#'+ulElementId).addClass('show');
					});
					jQuery('[data-target="'+target+'"]').removeClass('collapsed');

				});
			})
			

// DPE Hack
			jQuery(document).ready(function () {
				setTimeout(function(){ 

					var userId = '<?php echo $user->id;?>';
					if (userId !=0)
					{
						setOrgIdInMenu();
					}
					
				},3000);
			})
			jQuery(document).on("change", ".cluster-filter", function () {

				setTimeout(function(){ 
					var userId = '<?php echo $user->id;?>';
				
					if (userId != 0)
					{
						setOrgIdInMenu();
					    storeClusterIdViaAjax();
					}
					
				},1000);
			})
			// Function to set cluster_id = "All" via AJAX
			jQuery(document).on("change", "#tags", function () {

			setTimeout(function(){ 
				var clisterId='All';
				storeClusterIdViaAjax(clisterId);
			},1000);	
			})
			function setOrgIdInMenu()
			{
	   // Loop through each anchor tag in the .sp-megamenu-parent element
				jQuery('.sp-megamenu-parent a').each(function () {
        var href = jQuery(this).attr('href'); // Get the href attribute value

        // Ensure the href exists and is not a placeholder
        if (href && href !== 'javascript:void(0);') {

            // Remove any existing ?cluster= and everything that follows it
        	href = href.split('?cluster=')[0];

            // Fetch the cluster value from the PHP session
        	var selectedCluster = sessionStorage.getItem("selectedCluster");
			var clusterValue = selectedCluster ? selectedCluster.replace(/[^0-9]/g, "") : '';	

			var selectedValue = jQuery("#cluster_id_chosen .chosen-single span").text().trim();
    
			if (selectedValue == "All") {
				return false;
			}

        	let updatedHref;

            // Check specific URL cases and append the appropriate query parameters
        	if (href.includes('tools')) {
        		updatedHref = href.includes('?') 
        		? `${href}&filter[agencies]=${clusterValue}` 
        		: `${href}?filter[agencies]=${clusterValue}`;
        	} else if (href.includes('certificates')) {
        		// updatedHref = href.includes('?') 
        		// ? `${href}&filter[agency_id]=${clusterValue}` 
        		// : `${href}?filter[agency_id]=${clusterValue}`;
        	}else if (href.includes('checklist') || href.includes('dashboardchecklist') || href.includes('generated-documents')) {
        		updatedHref = href.includes('?') 
        		? `${href}&filter[cluster_id]=${clusterValue}` 
        		: `${href}?filter[cluster_id]=${clusterValue}`;
        	} else if (href.includes('staff-dashboard') || href.includes('news-top')) {
        		updatedHref = href;
        	}else {
        		updatedHref = href.includes('?') 
        		? `${href}&cluster=${clusterValue}` 
        		: `${href}?cluster=${clusterValue}`;
        	}

            // Update the href attribute with the modified URL
        	jQuery(this).attr('href', updatedHref);
        }
    });

			}
			document.addEventListener("DOMContentLoaded", function() {
			jQuery('.sp-megamenu-parent li').each(function () {
				var anchorTag = jQuery(this).find('> a'); // Find direct <a> tag inside li

				if (anchorTag.length === 0) {
					jQuery(this).remove(); // Remove <li> if it has no <a> tag
				}
			});
		});

		//	Sends an AJAX request to store the selected cluster ID in the session.
		function storeClusterIdViaAjax(clusterId=null) {
			var selectedCluster = sessionStorage.getItem("selectedCluster");
			var clusterValue = selectedCluster ? selectedCluster.replace(/[^0-9]/g, "") : '';

			jQuery.ajax({
				url: "index.php?option=com_dpe&task=tjucm.storeClusterIdInSession", // PHP file to handle the request  
				type: "POST",
				data: { cluster_id: clusterId ? clusterId : selectedCluster },  
				success: function (response) {
				},
				error: function (xhr, status, error) {
					console.error("AJAX Error:", error);
				}
			});
		}
		</script>
	<?php
	// DPE Hack
	// This will be used to display the message on the frontend 
	$userId = Factory::getUser()->id;
	$tempPath = JPATH_SITE . '/tmp/copy_message_' . $userId . '.txt';

	if (file_exists($tempPath)) :
		$message = file_get_contents($tempPath);
		unlink($tempPath); // Delete after reading
	?>
		<div id="copySuccessMessage"
			class="position-fixed bottom-0 end-0 mb-4 p-3 rounded shadow"
			style="background-color: #28a745; color: #fff; z-index: 1050; max-width: 400px;
		height: 80px;
		margin-right: 80px;">
		
		<!-- Close button in top-right corner -->
		<button type="button" class="btn-close btn-close-white"
			style="position: absolute; top: 8px; right: 8px; filter: brightness(0) invert(1);"
			aria-label="Close" onclick="this.parentElement.remove();"></button>

			<div class="d-flex justify-content-between align-items-start h-100">
			<div class="flex-grow-1 pe-2 mt-3 d-flex align-items-center">
				<?php echo htmlspecialchars($message); ?>
			</div>
		</div>
		</div>

		<script>
			// Automatically fade out the message after 8 seconds
			setTimeout(function () {
				const msgBox = document.getElementById('copySuccessMessage');
				if (msgBox) {
					msgBox.style.transition = 'opacity 1s';
					msgBox.style.opacity = '0';
					setTimeout(() => msgBox.remove(), 1000);
				}
			}, 8000);
		</script>
	<?php endif; ?>
	</html>






