<?php
/**
 * @version     1.5
 * @package     mod_websitetourbuilder
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      JoomlaForce Team <support@joomlaforce.com> - http://www.joomlaforce.com
 */
// no direct access
defined('_JEXEC') or die('Restricted access'); ?>
use Joomla\CMS\Language\Text;
<?php /*?>
<div id="mod_jquery_<?php echo $class_suffix; ?>">	
	<p id="message_lib_<?php echo $class_suffix; ?>" class="alert-error">
		<?php echo Text::_('MOD_WEBSITETOUR_JQUERY_INFO_ERROR'); ?>
	</p>
</div><?php */?>

<!-- START PAGES TOUR -->

<?php
$displayas = $params->get('displayas');
$lightboxtitle = $params->get('lightboxtitle');
$lightboxdesc = $params->get('lightboxdesc');
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));
$autoStartJs = '';

switch ($displayas) {
    case 0:

        //link
		$startlink ='<div class="tour-menu '.$moduleclass_sfx.'"><ul style="list-style:none;"><li><a id="open-walkthrough" href="javascript:;">'.Text::_('MOD_WEBSITETOUR_START').'</a></li></ul></div>';
        break;
    case 1:
		//button
        $startlink ='<div class="tour-menu '.$moduleclass_sfx.'"><ul style="list-style:none;"><li><a id="open-walkthrough" class="addslidebutton" href="javascript:;">'.Text::_('MOD_WEBSITETOUR_START').'</a></li></ul></div>';
        break;
	case 2:
		//autostart
        echo "";
        break;
	case 3:
		//lightbox
		//<a class="popup-link" href="#" data-popup-target="#example-popup">Clicca per vedere il popup in azione</a>
        
        $lightboxTheme = $params->get('lightboxTheme','default');
        $lightboxThemePath = JPATH_SITE.'/modules/mod_websitetourbuilder/theme/lightbox/'.$lightboxTheme.'.php';
        if(is_file($lightboxThemePath))
        {
            ob_start();
            include($lightboxThemePath);
            $startlink = ob_get_contents();
            ob_end_clean();
        }
        else
        {
            $startlink ='
    			<div class="popup-overlay"></div>
    			<div id="example-popup" class="popup">
    				<div class="popup-body">    
    					<span class="popup-exit"></span>
    					<div class="popup-content">
    							<h2 class="popup-title">'.$lightboxtitle.'</h2>
    						<p>'.$lightboxdesc.'<div class="tour-menu">
    						<a id="open-walkthrough" class="addslidebutton" >'.Text::_('MOD_WEBSITETOUR_START').'</a>
    						</div></p>
    					</div>
    				</div>
    			</div>';
        }
        
        break;
	case 4:
		//nodisplay
        echo "";
        break;
		
	default: 
		echo"";
		break;
}



echo $startlink;

$popupTheme = $params->get('popupTheme','default');
$steps = '<div id="walkthrough">';
$totalSteps = count($items);
$popupThemeLayoutPath = JPATH_SITE.'/modules/mod_websitetourbuilder/theme/popup/'.$popupTheme.'.php';
if(is_file($popupThemeLayoutPath))
{
    ob_start();
    include($popupThemeLayoutPath);
    $steps .= ob_get_contents();
    ob_end_clean();
}
else
{
    for ($i = 0; $i < $totalSteps; $i++) {
        $item = $items[$i];
        //echo $item->wrapper.$i;
        //echo $item->stepcontent.$i;
        //echo $i;
        
        $steps .='<div id="'.$item->stepcontent.'" style="display: none;">';
            $steps .='<p class="tooltipTitle">'.$item->steptitle.'</p>';
            $steps .='<p>'.$item->steptext.'</p>';	
            
            if (($i > 0)&& ($totalSteps-1))
            $steps .= '<a href="javascript:;" class="prev-step" style="float:left;">'.Text::_('MOD_WEBSITETOUR_PREV').'</a>';
            
            if ($i < $totalSteps-1)
            $steps .= ' <a href="javascript:;" class="next-step" style="float:right;">'.Text::_('MOD_WEBSITETOUR_NEXT').'</a>';
        $steps .='</div>';
    }    
}
 

$steps .= "";
$steps .='</div>';//end of walkthrough div

echo $steps;
?>
<!-- END PAGES TOUR -->
