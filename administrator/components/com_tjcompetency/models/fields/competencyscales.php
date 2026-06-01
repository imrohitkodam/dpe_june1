<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

JLoader::import('components.com_tjcompetency.includes.tjcompetency', JPATH_ADMINISTRATOR);

/**
 * Custom field to list Competency Scales
 *
 * @since  1.0.0
 */
class JFormFieldCompetencyScales extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $type = 'CompetencyScales';

	/**
	 * Layout to render
	 *
	 * @var   string
	 * @since 3.5
	 */
	protected $layout = 'fields.scales';

		/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value. This acts as an array container for the field.
	 *                                       For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                       full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.7.0
	 *
	 * @see     JFormField::setup()
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		return $return;
	}

	/**
	 * Method to get the user field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.6
	 */
	protected function getInput()
	{
		if (empty($this->layout))
		{
			throw new \UnexpectedValueException(sprintf('%s has no layout assigned.', $this->name));
		}

		return $this->getRenderer($this->layout)->render($this->getLayoutData());

	}

	/**
	 * Get the data that is going to be passed to the layout
	 *
	 * @return  array
	 *
	 * @since   3.5
	 */
	public function getLayoutData()
	{
		// Get the basic field data
		$data = parent::getLayoutData();

		// Initialize value
		$name = Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTMAP_FORM_SELECT_SCALE');

		// User lookup went wrong, we assign the value instead.
		if ($this->value)
		{
			$name = $this->value;
		}

		if (is_numeric($this->value))
		{
			$name = TjCompetency::Scale($this->value)->title;
		}

		$extraData = array(
			'scaleName'  => $name
		);

		return array_merge($data, $extraData);
	}
}
