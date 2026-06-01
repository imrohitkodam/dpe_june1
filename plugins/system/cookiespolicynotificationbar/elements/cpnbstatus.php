<?php
/* ======================================================
# Cookies Policy Notification Bar for Joomla! - v4.0.0 (Pro version)
# -------------------------------------------------------
# For Joomla! CMS
# Author: Web357 (Yiannis Christodoulou)
# Copyright (©) 2009-2020 Web357. All rights reserved.
# License: GNU/GPLv3, http://www.gnu.org/licenses/gpl-3.0.html
# Website: https:/www.web357.com/
# Demo: https://demo.web357.com/joomla/cookiespolicynotificationbar
# Support: support@web357.com
# Last modified: 26 Nov 2020, 01:41:46
========================================================= */

defined('JPATH_BASE') or die;
		
jimport('joomla.form.formfield');
jimport( 'joomla.form.form' );

class JFormFieldcpnbstatus extends JFormField {
	
	protected $type = 'cpnbstatus';

	protected function getLabel()
	{
		// BEGIN: Check if CPNB plugin exists
		jimport('joomla.plugin.helper');
		if(!JPluginHelper::isEnabled('system', 'cookiespolicynotificationbar')):
			return JText::_('<div style="border:1px solid red; padding:10px; width: 50%"><strong style="color:red;">The plugin is unpublished.</strong><br>The plugin should be enabled to display the input text fields for each of your active languages. Please, enable the plugin first and then try to navigate to this tab again!</div>');
		else:
			return '';	
		endif;
		// END: Check if CPNB plugin exists
	}

	protected function getInput() 
	{
		return '';
	}
	
}