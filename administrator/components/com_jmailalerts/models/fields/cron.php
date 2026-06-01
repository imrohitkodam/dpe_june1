<?php
/**
 * @package     JMailAlerts
 * @subpackage  com_jmailalerts
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Custom cron field for component params.
 *
 * @since  2.5.0
 */
class JFormFieldCron extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var   string
	 * @since  1.6
	 */
	protected $type = 'Cron';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.7.0
	 */
	protected function getInput()
	{
		HTMLHelper::_('stylesheet', 'administrator/components/com_jmailalerts/assets/css/jmailalerts.css');

		if ($this->name == 'jform[private_key_cronjob]')
		{
			return $this->getCronKey($this->name, $this->value);
		}
		elseif ($this->name == 'jform[cron_url]')
		{
			$cronjoburl = $this->getCronUrl();

			return '<input type="text" name="cron_url" disabled="disabled" value="' . $cronjoburl . '" class="input input-xxlarge form-control">';
		}
	}

	/**
	 * Get Cron Key
	 *
	 * @param   string  $name   Name
	 * @param   string  $value  Value
	 *
	 * @return  string
	 */
	public function getCronKey($name, $value)
	{
		// Generate randome string
		if (empty($value))
		{
			$length       = 10;
			$characters   = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$randomString = '';

			for ($i = 0; $i < $length; $i++)
			{
				$randomString .= $characters[rand(0, strlen($characters) - 1)];
			}

			return "<input type='text' name='" . $name . "' value='" . $randomString . "'>";
		}

		return "<input type='text' name='$name' value=" . $value . " class='input input-xxlarge form-control'></label>";
	}

	/**
	 * Get Cron URL
	 *
	 * @return  string
	 */
	public function getCronUrl()
	{
		$params = ComponentHelper::getParams('com_jmailalerts');

		$url = Route::_(
			Uri::root() .
			'index.php?option=com_jmailalerts&view=emails&tmpl=component&task=processMailAlerts&pkey=' .
			$params->get('private_key_cronjob')
		);

		return $url;
	}
}
