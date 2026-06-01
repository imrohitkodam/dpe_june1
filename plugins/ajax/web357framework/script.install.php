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
# Last modified: 01 Dec 2020, 16:42:28
========================================================= */

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class PlgAjaxWeb357frameworkInstallerScript extends PlgAjaxWeb357frameworkInstallerScriptHelper
{
	public $name           	= 'Web357 Framework';
	public $alias          	= 'web357framework';
	public $extension_type 	= 'plugin';
	public $plugin_folder   = 'ajax';
}