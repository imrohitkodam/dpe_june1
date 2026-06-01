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

jimport('joomla.form.formfield');

class JFormFieldw357note extends JFormField {
	
	protected $type = 'w357note';

	function getInput()
	{
		if (version_compare(JVERSION, '4.0', '>='))
		{
			return $this->getInput_J4();
		}
		else
		{
			return $this->getInput_J3();
		}
	}

	function getLabel()
	{
		if (version_compare(JVERSION, '4.0', '>='))
		{
			return $this->getLabel_J4();
		}
		else
		{
			return $this->getLabel_J3();
		}
	}

	protected function getLabel_J3()
	{	
		return \JText::_($this->element['description']);
	}
	
	protected function getInput_J3()
	{
		return ' ';
	}

	protected function getLabel_J4()
	{
		return ' ';
	}

	protected function getInput_J4()
	{
		return ' ';
	}
}