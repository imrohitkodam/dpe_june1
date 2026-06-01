<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

JFormHelper::loadFieldClass('list');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;

JLoader::import('components.com_tjcompetency.includes.tjcompetency', JPATH_ADMINISTRATOR);

/**
 * Custom field to list Competency ContentTypes
 *
 * @since  1.0.0
 */
class JFormFieldCompetencyContentTypes extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $type = 'CompetencyContentTypes';

	protected static $pluginType = 'tjcompetencycontenttype';

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value. This acts as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.7.0
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if (!$element['useinfilter'])
		{
			// Onchange must always be the change context function
			$this->onchange = 'contentTypeChanged(jQuery(this).val());';
		}
		elseif ($element['useinfilter'])
		{
			$this->onchange = 'clearClientIdField(jQuery(this).val());this.form.submit();';
		}

		return $return;
	}

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 *
	 * @since   1.0.1
	 */
	public function getOptions()
	{
		$app         = Factory::getApplication();
		$jinput      = $app->input;
		$option      = $jinput->get('option');
		$view        = $jinput->get('view');
		$viewContext = $option . '.' . $view;

		$options = array();

		if (!$this->multiple)
		{
			$options[] = HTMLHelper::_('select.option', "", Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTTYPE_FIELD_SELECT'));
		}

		$plugins = TjCompetency::SkillContentMap()::getCompetencyContentTypes();

		if (!empty($plugins))
		{
			

			foreach ($plugins as $key => $value)
			{
				PluginHelper::importPlugin(self::$pluginType, $value->name);
				$plgSupportedContext = Factory::getApplication()->triggerEvent($value->name . 'GetSupportedContext', array());

				if (!empty($plgSupportedContext[0]) && (in_array($viewContext, $plgSupportedContext[0])))
				{
					$displayName = $value->name;

					if (!empty($value->params))
					{
						$params = json_decode($value->params, true);

						if (!empty($params['display_name']))
						{
							$displayName = $params['display_name'];
						}
					}

					$options[] = HTMLHelper::_('select.option', $value->name, Text::_($displayName));
				}
			}
		}

		return $options;
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
    	if (!$this->element['useinfilter'])
		{
	    	Factory::getDocument()->addScriptDeclaration(
				"
				function contentTypeChanged(client)
				{
					var regex = new RegExp(\"([?;&])client[^&;]*[;&]?\");
					var url = window.location.href;
					var query = url.replace(regex, \"$1\").replace(/&$/, '');
					window.location.href = (query.length > 2 ? query + \"&\" : \"?\") + (client ? \"client=\" + client : '');
				}
				"
			);
	    }
	    elseif ($this->element['useinfilter'])
	    {
	    	Factory::getDocument()->addScriptDeclaration(
				"
				let originalClientVal = jQuery('#filter_client').val();

				function clearClientIdField(client)
				{
					if (originalClientVal != '')
					{
						jQuery('#filter_client_id').val('');
					}
				}
				"
			);
	    }

        return parent::getInput();
    }

    /**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 *
	 * @since   3.7.0
	 */
	protected function getLabel()
	{
		if (count($this->getOptions()) <= 1)
		{
			return Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTTYPE_FIELD_EMPTY');
		}

		return parent::getLabel();
	}
}
