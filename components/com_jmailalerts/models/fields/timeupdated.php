<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Date\Date;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

/**
 * Supports an HTML select list of categories
 *
 * @since  1.0.0
 */
class JFormFieldTimeupdated extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'timeupdated';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup
	 *
	 * @since	1.6
	 */
	protected function getInput()
	{
		// Initialize variables.
		$html = array();

		$oldTimeUpdated = $this->value;
		$hidden         = (boolean) $this->element['hidden'];

		if ($hidden == null || !$hidden)
		{
			if (!strtotime($oldTimeUpdated))
			{
				$html[] = '-';
			}
			else
			{
				$jdate      = new Date($oldTimeUpdated);
				$prettyDate = $jdate->format(Text::_('DATE_FORMAT_LC2'));
				$html[]     = "<div>" . $prettyDate . "</div>";
			}
		}

		$timeUpdated = date("Y-m-d H:i:s");
		$html[]      = '<input type="hidden" name="' . $this->name . '" value="' . $timeUpdated . '" />';

		return implode($html);
	}
}
