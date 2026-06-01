<?php
/**
 * @package     Joomla.Site
 * @subpackage  Com_Advsearch
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2017 TechJoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>.
 * @link        http://techjoomla.com.
 */
defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');

/**
 * Class Supports an HTML select list of categories
 *
 * @since  3.3
 */
class JFormFieldTimecreated extends JFormField
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

		$time_created = $this->value;

		if (!$time_created)
		{
			$time_created = date("Y-m-d H:i:s");
			$html[] = '<input type="hidden" name="' . $this->name . '" value="' . $time_created . '" />';
		}

		$jdate = new JDate($time_created);
		$pretty_date = $jdate->format(JText::_('DATE_FORMAT_LC2'));
		$html[] = "<div>" . $pretty_date . "</div>";

		return implode($html);
	}
}
