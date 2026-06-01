<?php
/* ======================================================
# Web357 Framework for Joomla! - v1.8.1 (Free version)
# -------------------------------------------------------
# For Joomla! CMS
# Author: Web357 (Yiannis Christodoulou)
# Copyright (©) 2009-2020 Web357. All rights reserved.
# License: GNU/GPLv3, http://www.gnu.org/licenses/gpl-3.0.html
# Website: https:/www.web357.com/
# Demo: https://demo.web357.com/joomla/web357framework
# Support: support@web357.com
# Last modified: 01 Dec 2020, 16:42:27
========================================================= */

defined('JPATH_BASE') or die;

require_once(JPATH_PLUGINS . DIRECTORY_SEPARATOR . "system" . DIRECTORY_SEPARATOR . "web357framework" . DIRECTORY_SEPARATOR . "elements" . DIRECTORY_SEPARATOR . "elements_helper.php");

jimport('joomla.form.formfield');
jimport( 'joomla.form.form' );

class JFormFieldcheckextension extends JFormField {
	
	protected $type = 'checkextension';

	protected function getLabel()
	{
		$option = (string) $this->element["option"];

		if (!empty($option) && !$this->isActive($option))
		{
            return '<div style="color:red">'.sprintf(JText::_('W357FRM_EXTENSION_IS_NOT_ACTIVE'), $option).'</div>';
		}
		else
		{
            return '<div style="color:darkgreen">'.sprintf(JText::_('W357FRM_EXTENSION_IS_ACTIVE'), $option).'</div>';
		}
	}

	// Check if the component is installed and is enabled
	public function isActive($option) // e.g. $option = com_k2
	{
		if (!empty($option))
		{
			jimport('joomla.component.helper');
			if(!JComponentHelper::isEnabled($option))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		else
		{
			die('The extension name is not detected.');
		}
		
	}

	protected function getInput() 
	{
		return '';
	}
	
}