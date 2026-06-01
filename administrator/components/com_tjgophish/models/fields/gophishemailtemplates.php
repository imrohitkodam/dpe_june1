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
 * Supports an HTML select list of allocated GoPhishEmailTemplates
 *
 * @since  1.0.0
 */

class JFormFieldGoPhishEmailTemplates extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $type = 'Gophishemailtemplates';

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
	 * Method to get a list of options for GoPhishEmailTemplates field.
	 *
	 * @return array An array of JHtml options.
	 *
	 * @since   1.0.0
	 */
	protected function getOptions()
	{
		$doc = Factory::getDocument();
		$doc->addScript(JUri::root() . 'media/com_tjgophish/js/ui/templatefield.min.js');

		$params = ComponentHelper::getParams('com_tjgophish');
		$goPhishApiEnd = $params->get('api_base_url');
		$goPhishApiKey = $params->get('api_key');

		$Http = new Http;
		$response = $Http->get($params->get('api_base_url') . 'api/templates/' . '?api_key=' . $goPhishApiKey);
		$goPhishTemplates = json_decode($response->body);

		$options = array();
		$options[] = HTMLHelper::_('select.option', '', Text::_("COM_TJGOPHISH_SELECT_EMAIL_TEMPLATE"));

		// Create option for each template
		foreach ($goPhishTemplates as $goPhishTemplate)
		{
			$options[] = HTMLHelper::_('select.option', $goPhishTemplate->name, trim($goPhishTemplate->name));
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

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.0.0
	 */
	protected function getInput()
	{
		// Add script to initialise groupslist field
		$document = Factory::getDocument();

		// Add script to update groupslist field onchange of cluster field
		$document->addScriptDeclaration('jQuery(document).ready(function() {
			template.getTemplate("' . $this->id . '");

			jQuery("#' . $this->id . '").change(function(){
				template.getTemplate("' . $this->id . '");
			});
		});');

		return parent::getInput();
	}
}
