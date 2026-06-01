<?php 
/**
 * @version     1.1
 * @package     mod_showcasebuilder
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      JoomlaForce Team <support@joomlaforce.com> - http://www.joomlaforce.com
 */

// Explicitly declare the type of content
header("Content-type: text/css; charset=UTF-8");
    
// Grab module id from the request
$suffix = $_GET['suffix']; 
?>

#mod_jquery_<?php echo $suffix; ?> {
	padding-bottom: 10px;
}

	#message_lib_<?php echo $suffix; ?> {
	    margin: 5px 0; 
	    padding: 8px 35px 8px 14px; 
	    border-radius: 4px;
	}
	
	#mod_jquery_<?php echo $suffix; ?> .alert-error {
		border: 1px solid #EED3D7; 
		background-color: #F2DEDE; 
		color: #B94A48;		
	}
	
	#mod_jquery_<?php echo $suffix; ?> .alert-success {
		border: 1px solid #D6E9C6; 
		background-color: #DFF0D8; 
		color: #468847;
	}