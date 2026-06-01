<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.categories');

/**
 * Tjlms Component Route Helper
 *
 * @static
 * @package  Com_Tjlms
 *
 * @since    1.0.0
 */
abstract class TjlmscourseHelperRoute
{
	protected static $lookup = array();

	protected static $lang_lookup = array();

	/**
	 * Function to get the link of the course
	 *
	 * @param   INT  $id        Id
	 * @param   INT  $catid     Cat ID
	 * @param   INT  $language  Lang
	 *
	 * @return  STRING  $links
	 *
	 * @since  1.0.0
	 */
	public static function getCourseRoute($id, $catid = 0, $language = 0)
	{
		$comtjlmsHelper = new comtjlmsHelper;
		$helperFile = JPATH_BASE . '/components/com_tjlms/helpers/main.php';
		JLoader::register('comtjlmsHelper', $helperFile);

		$id = explode(':', $id);

		// Create the link
		$link = 'index.php?option=com_tjlms&view=course&id=' . $id[0];

		$courseUrl = $comtjlmsHelper->tjlmsRoute($link);

		return $courseUrl;
	}
}
