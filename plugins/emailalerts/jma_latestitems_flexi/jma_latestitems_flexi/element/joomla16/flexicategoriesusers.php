<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_latestitems_flexi
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
 => <param name="catid" type="JMACategoriesUsers" default="1"
 * label="LBL_CATEGORY_LN_FLEXI" element="jma_latestitems_flexi"
 * multiple="multiple" description="DESC_CATEGORY_LN_FLEXI" key_field='id' value_field='title' />
 * where element is the name of your plugin entry file
 */

JFormHelper::loadFieldClass('list');

/**
 * Latest Items for flexi plugin for JMailAlerts Component.
 *
 * @package     JMailAlerts
 * @subpackage  jma_latestitems_flexi
 * @since       2.5.1
 */
class JFormFieldFlexicategoriesusers extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access       protected
	 * @var          string
	 */
	public $type = 'Flexicategoriesusers';

	/**
	 * function to get categories list
	 *
	 * @return  list of categories
	 *
	 * @since   2.5
	 */
	protected function getOptions()
	{
		$db     = JFactory::getDbo();
		$plugin = JPluginHelper::getPlugin('emailalerts', 'jma_latestitems_flexi');

		if ($plugin)
		{
			// Example: plugintitle=K2-Latest Items category=1|3|4|5|2 no_of_items=5 catid=1|3|4|5|2
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

			$new1 = explode(":", $newlin[1]);
			$new1 = str_replace('[', '', $new1);
			$new1 = str_replace(']', '', $new1);

			$cats = str_replace('|', ',', $new1[1]);

			if ($cats)
			{
				$sql = "SELECT id ,title FROM #__categories WHERE published = 1 AND extension !='com_docman' AND id IN (" . $cats . ")";
			}
			// If no category is yet selected
			else
			{
				$sql = "SELECT id ,title FROM #__categories WHERE published = 1 AND extension !='com_docman'";
			}

			$db->setQuery($sql);
			$options = $db->loadObjectList();

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
				return JText::_('NO_GROUP');
			}
		}
		else
		{
			return JText::_('Please enable plugin first');
		}
	}
}
