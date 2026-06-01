<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_latestnews_js
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

// Do not allow direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Renders a multiple item select element using SQL result and explicitly specified params
 * HOW TO USE IN XML ?? example is given below
 => <param name="catid"
	type="JMACategoriesUsers"
	default="1"
	label="LBL_CATEGORY_LN_JS"
	element="jma_latestnews_js"
	multiple="multiple"
	description="DESC_CATEGORY_LN_JS"
	key_field='id'
	value_field='title' />
 * where element is the name of your plugin entry file
 */

JFormHelper::loadFieldClass('list');

/**
 * User selected categories Field class for JMailAlerts. Supports a list of options.
 *
 * @since  2.5
 */
class JFormFieldJmacategoriesusers extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $type = 'Jmacategoriesusers';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   2.5
	 */
	protected function getOptions()
	{
		$db     = JFactory::getDbo();
		$plugin = JPluginHelper::getPlugin('emailalerts', 'jma_latestnews_js');

		// If plugin is enabled
		if ($plugin)
		{
			// Get plugin params, the new way
			$pluginParams = new JRegistry($plugin->params);
			$allowedCategoriesMethod = $pluginParams->get('allowed_categories_method', 'all_selected_categories');

			if ($allowedCategoriesMethod == 'all_selected_categories')
			{
				/*// Get plugin params e.g.: plugintitle=K2-Latest Items category=1|3|4|5|2 no_of_items=5 catid=1|3|4|5|2
				$plug_params = $plugin->params;

				if (preg_match_all('/\[(.*?)\]/', $plug_params, $match))
				{
					foreach ($match[1] as $mat)
					{
						$match       = str_replace(',', '|', $mat);
						$plug_params = str_replace($mat, $match, $plug_params);
					}
				}

				$newlin = explode(",", $plug_params);
				$new1   = explode(":", $newlin[1]);
				$new1   = str_replace('[', '', $new1);
				$new1   = str_replace(']', '', $new1);

				$cats   = str_replace('|', ',', $new1[1]);*/

				$cats = $pluginParams->get('category');

				if (is_array($cats))
				{
					$cats = implode(',', $cats);
				}

				// If categories allowed are selected
				if ($cats)
				{
					$sql = "SELECT id, title
					 FROM #__categories
					 WHERE published=1
					 AND extension='com_content'
					 AND id IN (" . $cats . ")";
				}
				// If no category is yet selected, select all
				else
				{
					$sql = "SELECT id, title
					 FROM #__categories WHERE published=1
					 AND extension='com_content'";
				}
			}
			else
			{
				$sql = "SELECT id, title
				 FROM #__categories
				 WHERE published=1
				 AND extension='com_content'";

				// AND id IN (" . $cats . ")";

				$user   = JFactory::getUser();
				$groups = implode(',', $user->getAuthorisedViewLevels());
				$sql    .= ' AND access IN (' . $groups . ')';
			}

			// Run query, get categories
			$db->setQuery($sql);
			$options = $db->loadObjectList();

			// Generate select list
			if ($options)
			{
				foreach ($options as $i => $option)
				{
					$options[$i]->text  = JText::_($option->title);
					$options[$i]->value = JText::_($option->id);
				}

				// Merge any additional options in the XML definition.
				$options = array_merge(parent::getOptions(), $options);

				return $options;
			}
			else
			{
				return JText::_('JMA_LATESTNEWS_NO_CATS');
			}
		}
		else
		{
			return JText::_('JMA_LATESTNEWS_ENABLE_PLUGIN');
		}
	}
}
