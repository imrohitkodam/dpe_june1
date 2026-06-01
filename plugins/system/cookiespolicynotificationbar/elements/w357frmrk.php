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

class JFormFieldw357frmrk extends JFormField {
	
	protected $type = 'w357frmrk';

	protected function getLabel()
	{
		return '';
	}

	protected function getInput() 
	{
		// BEGIN: Check if Web357 Framework plugin exists
		jimport('joomla.plugin.helper');
		if(!JPluginHelper::isEnabled('system', 'web357framework'))
		{
			$web357framework_required_msg = JText::_('<p>The <strong>"Web357 Framework"</strong> is required for this extension and must be active. Please, download and install it from <a href="http://downloads.web357.com/?item=web357framework&type=free">here</a>. It\'s FREE!</p>');
			JFactory::getApplication()->enqueueMessage($web357framework_required_msg, 'error');

			throw new \Exception('The Web357 Framework - System Plugin is required for this extension and should be enabled.');
		}
		else
		{
			// Call the Web357 Framework Helper Class
			$web357framework_class_file = JPATH_PLUGINS.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'web357framework'.DIRECTORY_SEPARATOR.'web357framework.class.php';
			if (JFile::exists($web357framework_class_file)) 
			{
				require_once($web357framework_class_file);
				$w357frmwrk = new Web357FrameworkHelperClass;

				// API Key Checker
				$w357frmwrk->apikeyChecker();

				return '';	
			}
			else
			{
				throw new \Exception('The Web357 Framework is required for this extension. The file "'.$web357framework_class_file.'" does not exists. Please, download and install the plugin from web357.com.');
			}
		}
		// END: Check if Web357 Framework plugin exists
	}
}