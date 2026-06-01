<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_tjlms
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\ArrayHelper;

JLoader::register('TjlmsHelper', JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php');

/**
 * Content HTML helper
 *
 * @since  3.0
 */
abstract class HTMLHelperTjlmsAdministrator
{
	/**
	 * Show the feature/unfeature links
	 *
	 * @param   int      $i          Row number
	 * @param   boolean  $canChange  Is user allowed to change?
	 * @param   int      $value      The state value
	 *
	 * @return  string       HTML code
	 */
	public static function featured($i, $canChange = true, $value = 0)
	{
		HTMLHelper::_('bootstrap.tooltip');

		// Array of image, task, title, action
		$states	= array(
			0	=> array('unfeatured',	'courses.featured',	'COM_COURSES_UNFEATURED',	'JGLOBAL_TOGGLE_FEATURED'),
			1	=> array('featured',	'courses.unfeatured',	'COM_COURSES_FEATURED',		'JGLOBAL_TOGGLE_FEATURED'),
		);
		$state	= ArrayHelper::getValue($states, (int) $value, $states[1]);
		$icon	= $state[0];

		if ($canChange)
		{
			$html	= '<a href="#" onclick="return listItemTask(\'cb' . $i . '\',\'' . $state[1] .
						'\')" class="btn btn-micro hasTooltip' . ($value == 1 ? ' active' : '') .
						'" title="' . HTMLHelper::tooltipText($state[3]) . '"><i class="icon-'
					. $icon . '"></i></a>';
		}
		else
		{
			$html	= '<a class="btn btn-micro hasTooltip disabled' . ($value == 1 ? ' active' : '') .
						'" title="' . HTMLHelper::tooltipText($state[2]) . '"><i class="icon-'
					. $icon . '"></i></a>';
		}

		return $html;
	}
}
