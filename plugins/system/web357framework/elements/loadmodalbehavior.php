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

defined('JPATH_PLATFORM') or die;
		
class JFormFieldloadmodalbehavior extends JFormField 
{
	protected $type = 'loadmodalbehavior';

	protected function getLabel()
	{
		return '';
	}

	protected function getInput() 
	{
		if (version_compare(JVERSION, '4.0', 'lt'))
		{
			JHtml::_('behavior.modal');
		}
	}
}