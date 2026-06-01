<?php
/**
 * Search SEO Glossary plugin installer script
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and change.
 * Otherwise, please feel free to contact us at contact@joomunited.com
 *
 * @package 	SEO Glossary
 * @copyright 	Copyright (C) 2012 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @license 	GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access to this file
defined( '_JEXEC' ) or die( 'Restricted access' );

class plgSearchSeoglossaryInstallerScript
{
	function install( $parent )
	{
		// I activate the plugin
		$db = JFactory::getDbo( );
		$tableExtensions = $db->quoteName( "#__extensions" );
		$columnElement = $db->quoteName( "element" );
		$columnType = $db->quoteName( "type" );
		$columnEnabled = $db->quoteName( "enabled" );
		$folder = $db->quoteName( "folder" );

		// Enable plugin
		$db->setQuery( "UPDATE $tableExtensions SET $columnEnabled=1 WHERE $columnElement='seoglossary' AND $columnType='plugin' AND $folder='search'" );
		$db->execute();
	}

}
?>
