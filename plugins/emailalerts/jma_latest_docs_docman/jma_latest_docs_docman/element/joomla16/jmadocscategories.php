<?php
/**
 * @package     JMailAlerts
 * @subpackage  jma_latest_docs_docman
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
 => <param name="catid" type="jmacategoriesk2" default="1" label="LBL_CATEGORY_K2"
 * element="jma_latest_content_k2" multiple="multiple" description="DESC_CATEGORY_K2"
 * key_field='id' value_field='name' />
 * where element is the name of your plugin entry file
 */

JFormHelper::loadFieldClass('list');

/**
 * Supports an HTML select list of categories
 *
 * @since  2.5.1
 */
class JFormFieldJmadocscategories extends JFormFieldList
{
	/**
	 * Element name
	 *
	 * @access       protected
	 * @var          string
	 */
	protected $type = 'Jmadocscategories';

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return  array  An array of JHtml options.
	 *
	 * @since   2.5.1
	 */
	protected function getOptions()
	{
		$docmanPath = JPATH_SITE . '/components/com_docman';

		if (!JFolder::exists($docmanPath))
		{
			return array();
		}

		$user = JFactory::getUser();

		// Get plugin name (plugin entryfile name)
		$db = JFactory::getDbo();

		// Get plugin params from #__extensions table
		$query = "SELECT params FROM `#__extensions`
		 WHERE element='jma_latest_docs_docman'
		 AND folder='emailalerts'";
		$db->setQuery($query);

		$plug_params = $db->loadResult();

		if (!$plug_params)
		{
			return array();
		}

		if (preg_match_all('/\[(.*?)\]/', $plug_params, $match))
		{
			foreach ($match[1] as $mat)
			{
				$match       = str_replace(',', '|', $mat);
				$plug_params = str_replace($mat, $match, $plug_params);
			}
		}

		$newlin = explode(",", $plug_params);

		foreach ($newlin as $v)
		{
			$entry = "";

			if (!empty($v))
			{
				$v = str_replace('{', '', $v);
				$v = str_replace(':', '=', $v);
				$v = str_replace('"', '', $v);
				$v = str_replace('}', '', $v);
				$v = str_replace('[', '', $v);
				$v = str_replace(']', '', $v);
				$v = str_replace('|', ',', $v);
				$v = explode("=", $v);

				$default_plugin_params[$v[0]] = $v[1];
			}
		}

		if (isset($default_plugin_params['category']))
		{
			$cats = $default_plugin_params['category'];

			if ($cats)
			{
				$sql = "SELECT docman_category_id AS id, title
				 FROM #__docman_categories
				 WHERE enabled=1
				 AND docman_category_id IN (" . $cats . ")";
			}
			else
			{
				$sql = "SELECT docman_category_id AS id, title
				 FROM #__docman_categories
				 WHERE enabled=1";
			}
		}
		else
		{
			$sql = "SELECT docman_category_id AS id, title
			 FROM #__docman_categories
			 WHERE enabled=1";
		}

		// Add ACL check
		$groups = implode(',', $user->getAuthorisedViewLevels());
		$checkacc = ' AND access IN (' . $groups . ')';
		$sql  .= $checkacc;

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
			return JText::_('NO_CATS_MSG');
		}
	}
}
