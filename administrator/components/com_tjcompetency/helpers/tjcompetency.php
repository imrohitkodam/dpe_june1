<?php
/**
 * @package     TjCompetency
 * @subpackage  com_tjcompetency
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;

/**
 * Competency helper.
 *
 * @since  1.0.0
 */
class TjCompetencyHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  view name string
	 *
	 * @return void
	 */
	public static function addSubmenu($vName = '')
	{
		$extension = Factory::getApplication()->input->get('extension', '', 'STRING');

		$parts = explode('.', $extension);

		// Eg com_tjcompetency
		$component = $parts[0];
		$eName     = str_replace('com_', '', $component);
		$file      = JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component . '/helpers/' . $eName . '.php');

		if (empty($extension) || (!empty($extension) && !file_exists($file)))
		{
			JHtmlSidebar::addEntry(
				Text::_('COM_TJCOMPETENCY_VIEW_COMPETENCY_FRAMEWORKS'),
				'index.php?option=com_tjcompetency&view=frameworks',
				$vName == 'frameworks'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_TJCOMPETENCY_VIEW_COMPETENCY_SKILLS'),
				'index.php?option=com_tjcompetency&view=skills',
				$vName == 'skills'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_TJCOMPETENCY_VIEW_COMPETENCY_SCALESETS'),
				'index.php?option=com_tjcompetency&view=scalesets',
				$vName == 'scalesets'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_TJCOMPETENCY_VIEW_COMPETENCY_SCALES'),
				'index.php?option=com_tjcompetency&view=scales',
				$vName == 'scales'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_TJCOMPETENCY_VIEW_COMPETENCY_SKILLCONTENTMAPS'),
				'index.php?option=com_tjcompetency&view=skillcontentmaps',
				$vName == 'skillcontentmaps'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_TJCOMPETENCY_VIEW_COMPETENCY_SKILLCONTENTUSERMAPS'),
				'index.php?option=com_tjcompetency&view=skillcontentusermaps',
				$vName == 'skillcontentusermaps'
			);

			JHtmlSidebar::addEntry(
				Text::_('COM_TJCOMPETENCY_VIEW_COMPETENCY_SKILLUSERMAPS'),
				'index.php?option=com_tjcompetency&view=skillusermaps',
				$vName == 'skillusermaps'
			);
		}
		else
		{
			if (file_exists($file))
			{
				require_once $file;

				$prefix = ucfirst(str_replace('com_', '', $component));
				$cName = $prefix . 'Helper';

				if (class_exists($cName))
				{
					if (is_callable(array($cName, 'addSubmenu')))
					{
						$lang = JFactory::getLanguage();

						// Loading language file from the administrator/language directory then
						// Loading language file from the administrator/components/*extension*/language directory
						$lang->load($component, JPATH_BASE, null, false, false)
						|| $lang->load($component, JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component), null, false, false)
						|| $lang->load($component, JPATH_BASE, $lang->getDefault(), false, false)
						|| $lang->load($component, JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component), $lang->getDefault(), false, false);

						// Call_user_func(array($cName, 'addSubmenu'), 'categories' . (isset($section) ? '.' . $section : ''));
						call_user_func(array($cName, 'addSubmenu'), $vName);
					}
				}
			}
		}
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return	JObject
	 *
	 * @since	1.0.0
	 */
	public static function getActions()
	{
		$user      = Factory::getUser();
		$result    = new JObject;
		$assetName = 'com_tjcompetency';
		$actions   = array(
						'core.admin',
						'core.manage',
						'core.create',
						'core.edit',
						'core.edit.own',
						'core.edit.state',
						'core.delete'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * Method to get report filter options
	 *
	 * @param   Boolean  $all        Option to see all reports
	 * @param   INT      &$selected  First selected option
	 *
	 * @return  mixed  An array of options
	 *
	 * @since   1.0.0
	 */
	public static function getReportFilterOptions($all = true, &$selected = null)
	{
		$options = array();

		$canDo = self::getActions();

		if ($canDo->get('core.create'))
		{
			$selected = 1;
		}

		if ($all)
		{
			$selected = 0;
		}

		return $options;
	}

	/**
	 * Method to get report filter values
	 *
	 * @param   object   $model        Model class object
	 * @param   INT      &$selected    First selected option
	 * @param   INT      &$created_by  Course creator id
	 * @param   Boolean  &$myTeam      Sets whether user is a manager or not
	 *
	 * @return  mixed  An array of options
	 *
	 * @since   1.0.0
	 */
	public static function getReportFilterValues($model, &$selected, &$created_by, &$myTeam)
	{
		$reportId       = $model->getState('reportId', 0);
		$user           = Factory::getUser();
		$userId         = $user->id;
		$viewAll        = $user->authorise('core.viewall', 'com_tjreports.tjreport.' . $reportId);
		$reportOptions  = self::getReportFilterOptions($viewAll, $selected);

		$filters = $model->getState('filters');

		if (empty($filters['report_filter']))
		{
			$filters['report_filter'] = $selected;
			$model->setState('filters', $filters);
		}

		$created_by			= (int) $filters['report_filter'] === 1 ? $userId : 0;
		$myTeam				= (int) $filters['report_filter'] === -1 ? true : false;

		return $reportOptions;
	}

	/**
	 * Method to get report filter.
	 *
	 * @return  array|void  filters.
	 *
	 * @since   1.0.0
	 */
	public static function getReportFilters($filterName)
	{
		if (!empty($filterName))
		{
			$formPath = JPATH_ADMINISTRATOR . '/components/com_tjcompetency/models/forms/filter_skillcontentusermaps.xml';
			$form     = Form::getInstance('', $formPath);
			$field    = $form->getField($filterName, 'filter');

			return $field->getOptions($filterName);
		}
	}

	/**
	 * Method to get user filter.
	 *
	 * @return  array|void  user filter.
	 *
	 * @since   1.0.0
	 */
	public static function getUserFilter()
	{
		return self::getReportFilters('user_id');
	}

	/**
	 * Method to get content type filter.
	 *
	 * @return  array|void  content type filter.
	 *
	 * @since   1.0.0
	 */
	public static function getContentTypeFilter()
	{
		return self::getReportFilters('client');
	}

	/**
	 * Method to get content filter.
	 *
	 * @return  array|void  content filter.
	 *
	 * @since   1.0.0
	 */
	public static function getContentFilter()
	{
		return self::getReportFilters('client_id');
	}

	/**
	 * Method to get framework filter.
	 *
	 * @return  array|void  framework filter.
	 *
	 * @since   1.0.0
	 */
	public static function getFrameworkFilter()
	{
		return self::getReportFilters('framework_id');
	}

	/**
	 * Method to get skill filter.
	 *
	 * @return  array|void  skill filter.
	 *
	 * @since   1.0.0
	 */
	public static function getSkillFilter()
	{
		return self::getReportFilters('skill_id');
	}

	/**
	 * Method to get scale filter.
	 *
	 * @return  array|void  scale filter.
	 *
	 * @since   1.0.0
	 */
	public static function getScaleFilter()
	{
		return self::getReportFilters('scale_id');
	}
}
