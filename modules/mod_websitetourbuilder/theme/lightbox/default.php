<?php
/**
 * @version     1.5
 * @package     mod_websitetourbuilder
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      JoomlaForce Team <support@joomlaforce.com> - http://www.joomlaforce.com
 */
// no direct access
defined('_JEXEC') or die('Restricted access');
 use Joomla\CMS\Language\Text;
?>


<div class="popup-overlay"></div>
<div id="example-popup" class="popup">
	<div class="popup-body" style="background-color:#fff !important;">    
		<span class="popup-exit"></span>
		<div class="popup-content">
		    <h2 class="popup-title"><?php echo $lightboxtitle; ?></h2>
			<p><?php echo $lightboxdesc; ?>
                <div class="tour-menu">
                    <a id="open-walkthrough" class="addslidebutton"><?php echo Text::_('MOD_WEBSITETOUR_START'); ?></a>
    			</div>
            </p>
		</div>
	</div>
</div>