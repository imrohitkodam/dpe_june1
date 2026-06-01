<?php
/**
 * @package     TjGoPhish
 * @subpackage  com_tjgophish
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;

/**
 * TjGoPhish component helper.
 *
 * @param   string  $submenu  The name of the active view.
 *
 * @return  void
 *
 * @since  1.0.0
 */
abstract class TjGoPhishHelper extends ContentHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $submenu  submenu
	 *
	 * @return Bool
	 */
	public static function addSubmenu($submenu)
	{
	}
}
