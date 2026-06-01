<?php
/**
 * @package     XT Transitional Package from FrameworkOnFramework
 * @subpackage  layout
 *  @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved. / Based on FrameworkOnFramework of Akeeba
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('XTF0F_INCLUDED') or die;

/**
 * Helper to render a XTF0FLayout object, storing a base path
 *
 * @package  FrameworkOnFramework
 * @since    x.y
 */
class XTF0FLayoutHelper extends JLayoutHelper
{
	/**
	 * Method to render the layout.
	 *
	 * @param   string  $layoutFile   Dot separated path to the layout file, relative to base path
	 * @param   object  $displayData  Object which properties are used inside the layout file to build displayed output
	 * @param   string  $basePath     Base path to use when loading layout files
	 *
	 * @return  string
	 */
	public static function render($layoutFile, $displayData = null, $basePath = '')
	{
		$basePath = empty($basePath) ? self::$defaultBasePath : $basePath;

		// Make sure we send null to XTF0FLayoutFile if no path set
		$basePath = empty($basePath) ? null : $basePath;
		$layout = new XTF0FLayoutFile($layoutFile, $basePath);
		$renderedLayout = $layout->render($displayData);

		return $renderedLayout;
	}
}
