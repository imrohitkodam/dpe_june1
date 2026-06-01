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

<div class="popup-overlay-dark"></div>
<div id="example-popup" class="popup">
	<div class="popup-body" style="background-color:#000 !important;">    
		<span class="popup-exit"></span>
		<div class="popup-content">
		    <h2 class="popup-title" style="color:#fff;"><?php echo $lightboxtitle; ?></h2>
			<p style="color: #fff;"><?php echo $lightboxdesc; ?>
                <div class="tour-menu">
                    <a id="open-walkthrough" class="addslidebutton"><?php echo Text::_('MOD_WEBSITETOUR_START'); ?></a>
    			</div>
            </p>
		</div>
	</div>
</div>