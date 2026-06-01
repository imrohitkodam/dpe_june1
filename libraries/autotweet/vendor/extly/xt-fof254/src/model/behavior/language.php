<?php
/**
 * @package     XT Transitional Package from FrameworkOnFramework
 * @subpackage  model
 *  @copyright   Copyright (c)2012-2020 Extly, CB. All rights reserved. / Based on FrameworkOnFramework of Akeeba
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('XTF0F_INCLUDED') or die;

/**
 * FrameworkOnFramework model behavior class to filter front-end access to items
 * based on the language.
 *
 * @package  FrameworkOnFramework
 * @since    2.1
 */
class XTF0FModelBehaviorLanguage extends XTF0FModelBehavior
{
	/**
	 * This event runs before we have built the query used to fetch a record
	 * list in a model. It is used to blacklist the language filter
	 *
	 * @param   XTF0FModel        &$model  The model which calls this event
	 * @param   XTF0FDatabaseQuery  &$query  The model which calls this event
	 *
	 * @return  void
	 */
	public function onBeforeBuildQuery(&$model, &$query)
	{
		if (XTF0FPlatform::getInstance()->isFrontend())
		{
			$model->blacklistFilters('language');
		}
	}

	/**
	 * This event runs after we have built the query used to fetch a record
	 * list in a model. It is used to apply automatic query filters.
	 *
	 * @param   XTF0FModel        &$model  The model which calls this event
	 * @param   XTF0FDatabaseQuery  &$query  The model which calls this event
	 *
	 * @return  void
	 */
	public function onAfterBuildQuery(&$model, &$query)
	{
		// This behavior only applies to the front-end.
		if (!XTF0FPlatform::getInstance()->isFrontend())
		{
			return;
		}

		// Get the name of the language field
		$table = $model->getTable();
		$languageField = $table->getColumnAlias('language');

		// Make sure the access field actually exists
		if (!in_array($languageField, $table->getKnownFields()))
		{
			return;
		}

		// Make sure it is a multilingual site and get a list of languages
		$app = JFactory::getApplication();
		$hasLanguageFilter = method_exists($app, 'getLanguageFilter');

		if ($hasLanguageFilter)
		{
			$hasLanguageFilter = $app->getLanguageFilter();
		}

		if (!$hasLanguageFilter)
		{
			return;
		}

		$lang_filter_plugin = JPluginHelper::getPlugin('system', 'languagefilter');
		$lang_filter_params = new JRegistry($lang_filter_plugin->params);

		$languages = array('*');

		if ($lang_filter_params->get('remove_default_prefix'))
		{
			// Get default site language
			$lg = XTF0FPlatform::getInstance()->getLanguage();
			$languages[] = $lg->getTag();
		}
		else
		{
			$languages[] = JFactory::getApplication()->input->getCmd('language', '*');
		}

		// Filter out double languages
		$languages = array_unique($languages);

		// And filter the query output by these languages
		$db = XTF0FPlatform::getInstance()->getDbo();

		// Alias
		$alias = $model->getTableAlias();
		$alias = $alias ? $db->qn($alias) . '.' : '';

		$languages = array_map(array($db, 'quote'), $languages);
		$query->where($alias . $db->qn($languageField) . ' IN (' . implode(',', $languages) . ')');
	}

	/**
	 * The event runs after XTF0FModel has called XTF0FTable and retrieved a single
	 * item from the database. It is used to apply automatic filters.
	 *
	 * @param   XTF0FModel  &$model   The model which was called
	 * @param   XTF0FTable  &$record  The record loaded from the databae
	 *
	 * @return  void
	 */
	public function onAfterGetItem(&$model, &$record)
	{
		if ($record instanceof XTF0FTable)
		{
			$fieldName = $record->getColumnAlias('language');

			// Make sure the field actually exists
			if (!in_array($fieldName, $record->getKnownFields()))
			{
				return;
			}

			// Make sure it is a multilingual site and get a list of languages
			$app = JFactory::getApplication();
			$hasLanguageFilter = method_exists($app, 'getLanguageFilter');

			if ($hasLanguageFilter)
			{
				$hasLanguageFilter = $app->getLanguageFilter();
			}

			if (!$hasLanguageFilter)
			{
				return;
			}

			$lang_filter_plugin = JPluginHelper::getPlugin('system', 'languagefilter');
			$lang_filter_params = new JRegistry($lang_filter_plugin->params);

			$languages = array('*');

			if ($lang_filter_params->get('remove_default_prefix'))
			{
				// Get default site language
				$lg = XTF0FPlatform::getInstance()->getLanguage();
				$languages[] = $lg->getTag();
			}
			else
			{
				$languages[] = JFactory::getApplication()->input->getCmd('language', '*');
			}

			// Filter out double languages
			$languages = array_unique($languages);

			if (!in_array($record->$fieldName, $languages))
			{
				$record = null;
			}
		}
	}
}
