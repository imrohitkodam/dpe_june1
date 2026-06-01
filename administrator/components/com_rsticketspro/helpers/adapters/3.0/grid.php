<?php
/**
 * @package    RSTickets! Pro
 *
 * @copyright  (c) 2010 - 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die('Restricted access');

abstract class RsticketsproAdapterGrid
{
	public static function row()
	{
		return 'row-fluid';
	}

	public static function column($size)
	{
		return 'span' . (int) $size;
	}

	public static function sidebar()
	{
		return '<div id="j-sidebar-container" class="' . static::column(2) . '">' .
			JHtmlSidebar::render() .
			'</div>' .
			'<div id="j-main-container" class="' . static::column(10) . '">';
	}

	public static function inputAppend($input, $append)
	{
		return
			'<div class="input-append">' .
			$input .
			'<span class="add-on">' . $append . '</span>' .
			'</div>';
	}
	
	public static function hide($device, $type = '')
	{
		if ($type !== 'table-cell')
		{
			if (in_array($device, array('phone', 'table', 'desktop')))
			{
				return 'hidden-' . $device;
			}
		}

		return '';
	}
	
	public static function show($device, $type = '')
	{
		if ($type !== 'table-cell')
		{
			if (in_array($device, array('phone', 'table', 'desktop')))
			{
				return 'visible-' . $device;
			}
		}

		return '';
	}
}