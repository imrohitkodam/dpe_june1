<?php
/**
 * @copyright	Copyright (C) 2014 JoomlaForceTeam. All rights reserved.
 * @license		GNU General Public License version 3 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

class JForceLibraries {
	
	static $jqLoaded = false;
	static $jqncLoaded = false;
		
	/**
	 * Load the jQuery library if needed
	 */
	static function loadJQuery($local, $version)
	{
		$doc = JFactory::getDocument();
	
		if (self::$jqLoaded) {
			return;
		}
			
		if ($local) {
			$doc->addScript(JURI::root(true).'/media/jforce/js/jquery/jquery-'.$version.'.min.js');
		} else {
			$doc->addScript('//ajax.googleapis.com/ajax/libs/jquery/'.$version.'/jquery.min.js');
		}
			
		self::$jqLoaded = true;
	}
	
	/**
	 * Load the jQuery library if needed
	 */
	static function loadJQueryNoConflict()
	{
		$doc = JFactory::getDocument();
	
		if (self::$jqncLoaded) {
			return;
		}
			
		$doc->addScript(JURI::root(true).'/media/jforce/js/jquery/jforce.noconflict.js');
			
		self::$jqncLoaded = true;
	}
	
}
?>
