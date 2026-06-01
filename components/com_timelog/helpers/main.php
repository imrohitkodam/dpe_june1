<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;

/**
 * Controller
 *
 * @package     Timelog
 * @subpackage  com_timelog
 * @since       __DEPLOY_VERSION__
 */

class TimelogMainHelper
{
	/**
	 * Declare language constants to use in .js file
	 *
	 * @params  void
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getLanguageConstant()
	{
		Text::script('COM_TIMELOG_CONFIRM_DELETE_ATTACHMENT');
		Text::script('COM_TIMELOG_FILE_SIZE_ERROR');
		Text::script('COM_TIMELOG_FILE_TYPE_ERROR');
	}
}
