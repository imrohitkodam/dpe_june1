<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Multiagency
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2017 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Multiagency Listhelper.
 *
 * @since  1.6
 */
abstract class HTMLHelperListhelper
{
	/**
	 * Method to toggle list.
	 *
	 * @param   STRING  $value  value.
	 * @param   STRING  $view   view.
	 * @param   STRING  $field  field.
	 * @param   INT     $i      i.
	 *
	 * @return html
	 */
	public static function toggle($value, $view, $field, $i)
	{
		$states = array(
			0 => array('icon-remove', Text::_('Toggle'), 'inactive btn-danger'),
			1 => array('icon-checkmark', Text::_('Toggle'), 'active btn-success')
		);

		$state  = \Joomla\Utilities\ArrayHelper::getValue($states, (int) $value, $states[0]);
		$text   = '<span aria-hidden="true" class="' . $state[0] . '"></span>';
		$html   = '<a href="#" class="btn btn-micro ' . $state[2] . '"';
		$html  .= 'onclick="return toggleField(\'cb' . $i . '\',\'' . $view . '.toggle\',\'' . $field . '\')" title="'
			. Text::_($state[1]) . '">' . $text . '</a>';

		return $html;
	}
}
