<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Timelog helper.
 *
 * @since  __DEPLOY_VERSION__
 */
class TimelogHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  string
	 *
	 * @return void
	 */
	public static function addSubmenu($vName = '')
	{
		/*
		\JHtmlSidebar::addEntry(
			Text::_('COM_TIMELOG_TITLE_ACTIVITIES'),
			'index.php?option=com_timelog&view=activities',
			$vName == 'activities'
		);
		*/
		\JHtmlSidebar::addEntry(
			Text::_('COM_TIMELOG_TITLE_ACTIVITYTYPES'),
			'index.php?option=com_timelog&view=activitytypes',
			$vName == 'activitytypes'
		);
	}
}
