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

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of allocated GoPhishSendingProfiles
 *
 * @since  1.0.0
 */

class JFormFieldGoPhishSendingProfiles extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'Gophishsendingprofiles';

	/**
	 * Fiedd to decide if options are being loaded externally and from xml
	 *
	 * @var		integer
	 * @since	1.0.0
	 */
	protected $loadExternally = 0;

	/**
	 * The form field value.
	 *
	 * @var    mixed
	 * @since  1.0.0
	 */
	protected $value = '';

	/**
	 * Method to get a list of options for GoPhishSendingProfiles field.
	 *
	 * @return array An array of JHtml options.
	 *
	 * @since   1.0.0
	 */
	protected function getOptions()
	{
		$params = ComponentHelper::getParams('com_tjgophish');
		$goPhishApiEnd = $params->get('api_base_url');
		$goPhishApiKey = $params->get('api_key');

		$Http = new Http;
		$response = $Http->get($params->get('api_base_url') . 'api/smtp/' . '?api_key=' . $goPhishApiKey);
		$goPhishSendingProfiles = json_decode($response->body);

		$options = array();
		$options[] = HTMLHelper::_('select.option', '', Text::_("COM_TJGOPHISH_SELECT_SENDING_PROFILE"));

		// Create option for each sending profile
		foreach ($goPhishSendingProfiles as $goPhishSendingProfile)
		{
			$options[] = HTMLHelper::_('select.option', $goPhishSendingProfile->name, trim($goPhishSendingProfile->name));
		}

		return $options;
	}

	/**
	 * Method to get a list of options for a list input externally and not from xml.
	 *
	 * @return	array	An array of JHtml options.
	 *
	 * @since   1.0.0
	 */
	public function getOptionsExternally()
	{
		$this->loadExternally = 1;

		return $this->getOptions();
	}
}
