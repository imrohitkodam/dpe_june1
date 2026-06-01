<?php
/**
 * @version     1.5
 * @package     mod_websitetourbuilder
 * @copyright   Copyright (C) 2013. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      JoomlaForce Team <support@joomlaforce.com> - http://www.joomlaforce.com
 */

// No direct access to this file
defined('_JEXEC') or die;
use Joomla\CMS\Version;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filesystem\Folder;

/**
 * Script file of the LatestNewsEnhanced module
 */
class mod_websitetourbuilderInstallerScript
{	
	static $minimum_needed_library_version = '1.0.0';
	static $download_link = 'http://www.joomlaforce.com'; // full URL link to the library
	
	/**
	 * Called before an install/update/uninstall method
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function preflight($type, $parent) {
		
		$version = new Version();
		$jversion = explode('.', $version->getShortVersion());
		
		if (intval($jversion[0]) > 2) {
			echo '<p style="text-align: center">';
		} else {
			echo '<p>';
		}
		
		echo Text::_('MOD_WEBSITETOUR_JQUERY_VERSION');
		echo '<br /><br />JoomlaForce Team <a href="http://www.joomlaforce.com" target="_blank">www.joomlaForce.com</a>';
		echo '</p>';
	}
	
	/**
	 * Called after an install/update/uninstall method
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function postflight($type, $parent) 
	{			
		// check if jforce library is present		
		
		$style = 'margin: 5px 0; padding: 8px 35px 8px 14px; border-radius: 4px; border: 1px solid #FBEED5; background-color: #FCF8E3; color: #C09853;';
		
		if (!Folder::exists(JPATH_ROOT.'/libraries/jforce')) {			
			echo '<div style="'.$style.'">';
			echo '    <span>'.Text::_('JFORCE_MISSING_LIBRARY').'</span><br />';
			echo '    <a href="'.self::$download_link.'" target="_blank">'.Text::_('JFORCE_DOWNLOAD_LIBRARY').'</a>';
			echo '</div>';
		} else {
			jimport('jforce.version');			
			if (JForceVersion::isCompatible(self::$minimum_needed_library_version)) {	
				$style = 'margin: 5px 0; padding: 8px 35px 8px 14px; border-radius: 4px; border: 1px solid #D6E9C6; background-color: #DFF0D8; color: #468847;';
			
				echo '<div style="'.$style.'">';
				echo '    <span>'.Text::_('JFORCE_COMPATIBLE_LIBRARY').'</span>';
				echo '</div>';
			} else {
				echo '<div style="'.$style.'">';
				echo '    <span>'.Text::_('JFORCE_NONCOMPATIBLE_LIBRARY').'</span><br />';
				echo '    <span>'.Text::_('JFORCE_UPDATE_LIBRARY').Text::_('JFORCE_OR').'</span>';
				echo '    <a href="'.self::$download_link.'" target="_blank">'.strtolower(Text::_('JFORCE_DOWNLOAD_LIBRARY')).'</a>';
				echo '</div>';
			}
		}	
		
		return true;
	}	
	
	/**
	 * Called on installation
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function install($parent) {
		
	}
	
	/**
	 * Called on update
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function update($parent) {
		
	}
	
	/**
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 */
	public function uninstall($parent) {
		
	}
}
?>