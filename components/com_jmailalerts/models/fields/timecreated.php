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
class JFormFieldTimecreated extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 * @since	1.6
	 */
	protected $type = 'timecreated';

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 *
	 * @since	1.6
	 */
	protected function getInput()
	{
		// Initialize variables.
		$html = array();

		$timeCreated = $this->value;

		if (!strtotime($timeCreated))
		{
			$timeCreated = date("Y-m-d H:i:s");
			$html[]      = '<input type="hidden" name="' . $this->name . '" value="' . $timeCreated . '" />';
		}

		$hidden = (boolean) $this->element['hidden'];

		if ($hidden == null || !$hidden)
		{
			$jdate      = new Date($timeCreated);
			$prettyDate = $jdate->format(Text::_('DATE_FORMAT_LC2'));
			$html[]     = "<div>" . $prettyDate . "</div>";
		}

		return implode($html);
	}
}
