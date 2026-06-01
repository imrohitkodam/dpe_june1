<?php
/**
 * @package     TjCompetency.Plugin
 * @subpackage  TjCompetency,TJReport,userskillreport
 *
 * @copyright   Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\Data\DataObject;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');
JLoader::import('components.com_tjcompetency.includes.tjcompetency', JPATH_ADMINISTRATOR);

/**
 * Attempt report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelUserSkillReport extends TjreportsModelReports
{
	protected $default_order = 'a.id';

	protected $default_order_dir = 'DESC';

	public $allowToCreateResultSets = true;

	public $addMorefilter = 0;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     BaseDatabaseModel
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		// Joomla fields integration
		// Define custom fields table, alias, and table.column to join on
		// $this->customFieldsTable       = '#__tjreports_com_users_user';
		// $this->customFieldsTableAlias  = 'tjrcuu';
		// $this->customFieldsQueryJoinOn = 'a.user_id';

		if (method_exists($this, 'tableExists'))
		{
			$this->customFieldsTableExists = $this->tableExists();
		}

		JLoader::import('administrator.components.com_tjcompetency.helpers.tjcompetency', JPATH_SITE);

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjcompetency', $base_dir);

		$this->columns = array(
			'id'              => array('disable_sorting' => true, 'table_column' => 'a.id', 'title' => 'PLG_TJREPORTS_SKILLUSER_ID'),
			'state'              => array('disable_sorting' => true, 'table_column' => 'a.state', 'title' => 'PLG_TJREPORTS_SKILLUSER_STATUS'),
			'user_id'         => array('disable_sorting' => true, 'table_column' => 'a.user_id', 'title' => 'PLG_TJREPORTS_SKILLUSER_USER_ID', 'isPiiColumn' => true),
			'user_name'       => array('disable_sorting' => true, 'table_column' => 'u.name', 'title' => 'PLG_TJREPORTS_SKILLUSER_USER', 'isPiiColumn' => true),
			'client'          => array('disable_sorting' => true, 'table_column' => 'a.client', 'title' => 'PLG_TJREPORTS_SKILLUSER_CONTENT_TYPE'),
			'client_id'       => array('disable_sorting' => true, 'table_column' => 'a.client_id', 'title' => 'PLG_TJREPORTS_SKILLUSER_CONTENT_ID', 'not_show_hide' => false),
			'content_name'       => array('disable_sorting' => true, 'table_column' => 'a.client_id', 'title' => 'PLG_TJREPORTS_SKILLUSER_CONTENT_NAME'),
			'framework_id' => array('disable_sorting' => true, 'table_column' => 'b.framework_id', 'title' => 'PLG_TJREPORTS_SKILLUSER_FRAMEWORK_ID', 'not_show_hide' => false ),
			'framework_title' => array('disable_sorting' => true, 'table_column' => 'b.framework_id', 'title' => 'PLG_TJREPORTS_SKILLUSER_FRAMEWORK'),
			'skill_id'     => array('disable_sorting' => true, 'table_column' => 'a.skill_id', 'title' => 'PLG_TJREPORTS_SKILLUSER_SKILL_ID', 'not_show_hide' => false ),
			'skill_title'     => array('disable_sorting' => true, 'table_column' => 'a.skill_id', 'title' => 'PLG_TJREPORTS_SKILLUSER_SKILL'),
			'path'     => array('disable_sorting' => true, 'table_column' => 'b.path', 'title' => 'PLG_TJREPORTS_SKILLUSER_SKILL_PATH'),
			'scale_id'     => array('disable_sorting' => true, 'table_column' => 'a.scale_id', 'title' => 'PLG_TJREPORTS_SKILLUSER_SCALE_ID', 'not_show_hide' => false ),
			'scale_title'     => array('disable_sorting' => true, 'table_column' => 'a.scale_id', 'title' => 'PLG_TJREPORTS_SKILLUSER_SCALE'),
			'created_on'      => array('disable_sorting' => true, 'table_column' => 'a.created_on', 'title' => 'PLG_TJREPORTS_SKILLUSER_ALLOTMENT_DATE'),
			'created_by_name'      => array('disable_sorting' => true, 'table_column' => 'users.name', 'title' => 'PLG_TJREPORTS_SKILLUSER_CREATED_BY'),
			'reviewer_by_name'      => array('disable_sorting' => true, 'table_column' => 'r.name', 'title' => 'PLG_TJREPORTS_SKILLUSER_REVIEWER_NAME'),
			'note'      => array('disable_sorting' => true, 'table_column' => 'a.note', 'title' => 'PLG_TJREPORTS_SKILLUSER_NOTE'),
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return STRING Client
	 *
	 * @since   2.0
	 * */
	public function getPluginDetail()
	{
		$detail = array('client' => 'com_tjcompetency', 'title' => Text::_('PLG_TJREPORTS_SKILLUSER_TITLE'));

		return $detail;
	}

	/**
	 * Get style for left sidebar menu
	 *
	 * @return ARRAY Keys of data
	 *
	 * @since   2.0
	 * */
	public function getStyles()
	{
		return array();
	}

	/**
	 * Create an array of filters
	 *
	 * @return    void
	 *
	 * @since    1.0
	 */
	public function displayFilters()
	{
		$reportOptions = TjCompetencyHelper::getReportFilterValues($this, $selected, $created_by, $myTeam);

		$userFilter      = TjCompetencyHelper::getUserFilter();
		$userFilterArray = array();

		HTMLHelper::_('formbehavior.chosen', '.multipleUsers', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_FILTER_USER_SELECT')));

		if (!empty($userFilter))
		{
			foreach ($userFilter as $key => $value)
			{
				$userFilterArray[] = HTMLHelper::_('select.option', $value->value, $value->text);
			}
		}

		$contentTypeFilter      = TjCompetencyHelper::getContentTypeFilter();
		$contentTypeFilterArray = array();

		if (!empty($contentTypeFilter))
		{
			foreach ($contentTypeFilter as $key => $value)
			{
				$contentTypeFilterArray[] = HTMLHelper::_('select.option', $value->value, $value->text);
			}
		}

		$contentFilter      = TjCompetencyHelper::getContentFilter();
		$contentFilterArray = array();

		HTMLHelper::_('formbehavior.chosen', '.multipleContents', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_CONTENT_FIELD_SELECT')));

		if (!empty($contentFilter))
		{
			foreach ($contentFilter as $key => $value)
			{
				$contentFilterArray[] = HTMLHelper::_('select.option', $value->value, $value->text);
			}
		}

		$frameworkFilter      = TjCompetencyHelper::getFrameworkFilter();
		$frameworkFilterArray = array();

		HTMLHelper::_('formbehavior.chosen', '.multipleFrameworks', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_FRAMEWORK_FIELD_SELECT')));

		if (!empty($frameworkFilter))
		{
			foreach ($frameworkFilter as $key => $value)
			{
				$frameworkFilterArray[] = HTMLHelper::_('select.option', $value->value, $value->text);
			}
		}

		$skillFilter      = TjCompetencyHelper::getSkillFilter();
		$skillFilterArray = array();

		HTMLHelper::_('formbehavior.chosen', '.multipleSkills', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_SKILL_FIELD_SELECT')));

		if (!empty($skillFilter))
		{
			foreach ($skillFilter as $key => $value)
			{
				$skillFilterArray[] = HTMLHelper::_('select.option', $value->value, $value->text);
			}
		}

		$scaleFilter      = TjCompetencyHelper::getScaleFilter();
		$scaleFilterArray = array();

		HTMLHelper::_('formbehavior.chosen', '.multipleScales', null, array('placeholder_text_multiple' => Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_SCALE_FIELD_SELECT')));
		HTMLHelper::_('formbehavior.chosen', 'select');

		if (!empty($scaleFilter))
		{
			foreach ($scaleFilter as $key => $value)
			{
				$scaleFilterArray[] = HTMLHelper::_('select.option', $value->value, $value->text);
			}
		}

		$stateFilterArray = array();
		$stateFilterArray[] = HTMLHelper::_('select.option', '', Text::_('JOPTION_SELECT_PUBLISHED'));
		$stateFilterArray[] = HTMLHelper::_('select.option', '3', Text::_('PLG_TJREPORTS_SKILLUSER_STATUS_OPTION_PENDING_REVIEW'));
		$stateFilterArray[] = HTMLHelper::_('select.option', '1', Text::_('JPUBLISHED'));
		$stateFilterArray[] = HTMLHelper::_('select.option', '0', Text::_('JUNPUBLISHED'));
		$stateFilterArray[] = HTMLHelper::_('select.option', '-2', Text::_('JTRASHED'));
		$stateFilterArray[] = HTMLHelper::_('select.option', '2', Text::_('JARCHIVED'));

		$dispFilters = array(
			array(
				// 'id' => array(
				// 	'search_type' => 'text', 'type' => 'equal', 'searchin' => 'a.id'
				// ),
				// 'user_name' => array(
				// 	'search_type' => 'text', 'type' => 'equal', 'searchin' => 'u.name'
				// ),
			),
			array(
				'user_id' => array(
					'search_type' => 'select',
					'select_options' => $userFilterArray,
					'type' => 'equal',
					'searchin' => 'a.user_id',
					'multiple' => true,
					'class' => 'multipleUsers'
				),
				'framework_id' => array(
					'search_type' => 'select',
					'select_options' => $frameworkFilterArray,
					'type' => 'equal',
					'searchin' => 'b.framework_id',
					'multiple' => true,
					'class' => 'multipleFrameworks'
				),
				'skill_id' => array(
					'search_type' => 'select',
					'select_options' => $skillFilterArray,
					'type' => 'equal',
					'searchin' => 'a.skill_id',
					'multiple' => true,
					'class' => 'multipleSkills'
				),
				'scale_id' => array(
					'search_type' => 'select',
					'select_options' => $scaleFilterArray,
					'type' => 'equal',
					'searchin' => 'a.scale_id',
					'multiple' => true,
					'class' => 'multipleScales'
				),
				'client' => array(
					'search_type' => 'select',
					'select_options' => $contentTypeFilterArray,
					'type' => 'equal',
					'searchin' => 'a.client',
					'class' => 'multipleContentTypes'
				),
				'client_id' => array(
					'search_type' => 'select',
					'select_options' => $contentFilterArray,
					'type' => 'equal',
					'searchin' => 'a.client_id',
					'multiple' => true,
					'class' => 'multipleContents'
				),
				'state' => array(
					'search_type' => 'select',
					'select_options' => $stateFilterArray,
					'type' => 'equal',
					'searchin' => 'a.state'
				),
			)
		);

		if (count($reportOptions) > 1)
		{
			$dispFilters[1]['report_filter'] = array(
					'search_type' => 'select', 'select_options' => $reportOptions
				);
		}

		// Joomla fields integration
		// Call parent function to set filters for custom fields
		if (method_exists(get_parent_class($this), 'setCustomFieldsDisplayFilters'))
		{
			parent::setCustomFieldsDisplayFilters($dispFilters);
		}

		if ($this->allowToCreateResultSets)
		{
			Factory::getDocument()->addScriptDeclaration(
				"
				var placeholders = {
					user_id : '" . Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_FILTER_USER_SELECT') . "',
					framework_id : '" . Text::_('COM_COMPETENCY_COMPETENCY_FRAMEWORK_FIELD_SELECT') . "',
					skill_id : '" . Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_SKILL_FIELD_SELECT') . "',
					scale_id : '" . Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_SCALE_FIELD_SELECT') . "',
					client_id : '" . Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_CONTENT_FIELD_SELECT') . "'
				};

				tjrContentUI.utility.initiateChznPlaceholders(placeholders);

				function resetContentDropdown()
				{
					jQuery('.multipleContentTypes').each(function (){
						jQuery(this).removeAttr('onchange');

						if (this.value == 'course')
						{
							let val = '(Event)';
							jQuery('#' + this.id + '_id option:contains(\'' + val + '\')').remove();
							jQuery('#' + this.id + '_id').trigger('liszt:updated');
						}
						else if (this.value == 'event')
						{
							let val = '(Course)';
							jQuery('#' + this.id + '_id option:contains(\'' + val + '\')').remove();
							jQuery('#' + this.id + '_id').trigger('liszt:updated');
						}
						else if (this.value == 'manual' || this.value == '')
						{
							jQuery('#' + this.id + '_id').empty().trigger('liszt:updated');
						}
					});
				}

				jQuery(document).ajaxComplete(function() {
					resetContentDropdown();
					tjrContentUI.utility.initiateChznPlaceholders(placeholders);
				});

				jQuery(document).ready(function () {

					resetContentDropdown();

					// This is needed if user clicks on the dropdown and doesn't select any option
					jQuery(document).on('blur', 'li.search-field input.default', function () {
						setTimeout(function(){ tjrContentUI.utility.initiateChznPlaceholders(placeholders); }, 100);
					});

					jQuery(document).on('change', '.multipleContentTypes', function () {
						jQuery('#' + this.id + '_id').val('');
						tjrContentUI.report.submitTJRData();
					});
				});

				"
			);
		}

		return $dispFilters;
	}

	/**
	 * Method to get a DataObjectbaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DataObjectbaseQuery  A DataObjectbaseQuery object to retrieve the data set.
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		$input     = Factory::getApplication()->input;
		$db        = $this->_db;
		$query     = parent::getListQuery();
		$colToshow = (array) $this->getState('colToshow');
		$filters   = $this->getState('filters');
		$user      = Factory::getUser();
		$userId    = $user->id;
		$limit     = $this->getState('list.limit');
		$start     = $this->getState('list.start');

		$addMorefilter = $input->get('addMorefilter', 0, 'INT');

		if (empty($addMorefilter))
		{
			$addMorefilter = $this->addMorefilter;
		}

		$removeFilter = $input->get('removeFilter', '', 'STRING');

		$displayFilters = (array) $this->displayFilters();

		if ($this->allowToCreateResultSets && $addMorefilter)
		{
			$removeFilterNum = '';

			if (isset($removeFilter) && $removeFilter != '' && $removeFilter != 0)
			{
				$removeFilterNum = (int) $removeFilter;

				unset($filters['options-' . $removeFilterNum]);
				$filterInc = 0;
				$tempFilter = array();

				foreach ($filters as $f_key => $f_value)
				{
					$tempFilter['options-' . $filterInc] = $f_value;
					$filterInc++;
				}

				$filters = $tempFilter;
			}

			$queries = array();

			for ((int) $i = 0; $i < $addMorefilter; $i++)
			{
				$queries[$i] = parent::getListQuery();

				$queries[$i]->select(array('a.*', 'a.client_id as content_name', 'IF(users.name IS NULL,"' . Text::_('COM_TJCOMPETENCY_BLOCKED_USER') . '",users.name) AS uname', 'IF(u.name IS NULL,"' . Text::_('COM_TJCOMPETENCY_BLOCKED_USER') . '",u.name) AS user_name', 'b.title as skill_title', 'b.path', 'c.title as scale_title'));
				$queries[$i]->select(array('d.title as framework_title'));
				$queries[$i]->select(array('IF(r.name IS NULL,"' . Text::_('COM_TJCOMPETENCY_BLOCKED_USER') . '",users.name) AS rname'));
				$queries[$i]->from($db->quoteName('#__tjcompetency_skill_user_content_map', 'a'));
				$queries[$i]->join('LEFT', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('a.created_by') . ' = ' . $db->quoteName('users.id') . ')');
				$queries[$i]->join('LEFT', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('a.user_id') . ' = ' . $db->quoteName('u.id') . ')');
				$queries[$i]->join('LEFT', $db->quoteName('#__users', 'r') . ' ON (' . $db->quoteName('a.reviewer_id') . ' = ' . $db->quoteName('r.id') . ')');
				$queries[$i]->join('LEFT', $db->quoteName('#__tjcompetency_skills', 'b') . ' ON (' . $db->quoteName('a.skill_id') . ' = ' . $db->quoteName('b.id') . ')');
				$queries[$i]->join('LEFT', $db->quoteName('#__tjcompetency_scales', 'c') . ' ON (' . $db->quoteName('a.scale_id') . ' = ' . $db->quoteName('c.id') . ')');
				$queries[$i]->join('LEFT', $db->quoteName('#__tjcompetency_frameworks', 'd') . ' ON (' . $db->quoteName('b.framework_id') . ' = ' . $db->quoteName('d.id') . ')');

				$reportId = $this->getDefaultReport($this->name);
				$viewAll = $this->checkpermissions($reportId);

				if ((int) $filters['report_filter'] === 1)
				{
					$queries[$i]->where('a.created_by = ' . (int) $userId);
				}
				elseif(!$viewAll)
				{
					$queries[$i]->where('a.created_by=0');
				}

				// Loop through different levels of filters
				foreach ($displayFilters as $displayFilter)
				{
					foreach ($displayFilter as $key => $dispFilter)
					{
						// Check if any of the filter is set
						if (((isset($filters['options-' . $i][$key]) && $filters['options-' . $i][$key] != '') || substr($dispFilter['search_type'], -6) === '.range') /*&& in_array($key, $colToshow)*/)
						{
							if (!isset($dispFilter['searchin']))
							{
								continue;
							}

							$columnName = $dispFilter['searchin'];

							if (substr($dispFilter['search_type'], -6) === '.range')
							{
								$fromCol = $columnName . '_from';
								$toCol   = $columnName . '_to';

								if (!empty($filters['options-' . $i][$fromCol]))
								{
									$fromTime = $filters['options-' . $i][$fromCol] . ' 00:00:00';
									$fromTime = new Date($fromTime, 'UTC');
									$queries[$i]->where($dispFilter['searchin'] . ' >= ' . $db->quote($fromTime));
								}

								if (!empty($filters['options-' . $i][$toCol]))
								{
									$toTime = $filters['options-' . $i][$toCol] . ' 23:59:59';
									$toTime = new Date($toTime, 'UTC');
									$queries[$i]->where($dispFilter['searchin'] . ' <= ' . $db->quote($toTime));
								}
							}
							elseif (isset($dispFilter['type']))
							{
								if ($dispFilter['type'] == 'custom')
								{
									if ($dispFilter['search_type'] == 'select' && $dispFilter['multiple'] && is_array($filters['options-' . $i][$key]))
									{
										$filters['options-' . $i][$key] = array_map(array($db, 'quote'), $filters['options-' . $i][$key]);

										// Create safe string of array.
										$filters['options-' . $i][$key] = implode(',', $filters['options-' . $i][$key]);
										$queries[$i]->where(sprintf($dispFilter['searchin'], $filters['options-' . $i][$key]));
									}
									else
									{
										$queries[$i]->where(sprintf($dispFilter['searchin'], $db->quote($filters['options-' . $i][$key])));
									}
								}
								else
								{
									if ($dispFilter['search_type'] == 'select' && $dispFilter['multiple'] && is_array($filters['options-' . $i][$key]))
									{
										$filters['options-' . $i][$key] = array_map(array($db, 'quote'), $filters['options-' . $i][$key]);

										// Create safe string of array.
										$filters['options-' . $i][$key] = implode(',', $filters['options-' . $i][$key]);

										$queries[$i]->where($db->quoteName($columnName) . ' IN (' . $filters['options-' . $i][$key] . ')');
									}
									else
									{
										$queries[$i]->where($db->quoteName($columnName) . '=' . $db->quote($filters['options-' . $i][$key]));
									}
								}
							}
							else
							{
								$search = $db->Quote('%' . $db->escape($filters['options-' . $i][$key], true) . '%');
								$queries[$i]->where($db->quoteName($columnName) . ' LIKE (' . $search . ')');
							}
						}
					}
				}
			}

			$queryUnion = $queries[0];

			unset($queries[0]);

			if (!empty($queries))
			{
				for ($j = 1; $j <= count($queries); $j++)
				{
					$queryUnion->union($queries[$j]);
				}
			}

			if ($limit != 0)
			{
				$queryUnion->setLimit($limit, $start);
			}

			return $queryUnion;
		}
		else
		{
			$query->select(array('a.*', 'a.client_id as content_name', 'IF(users.name IS NULL,"' . Text::_('COM_TJCOMPETENCY_BLOCKED_USER') . '",users.name) AS uname', 'IF(u.name IS NULL,"' . Text::_('COM_TJCOMPETENCY_BLOCKED_USER') . '",u.name) AS user_name', 'b.title as skill_title', 'c.title as scale_title'));
			$query->select(array('d.title as framework_title'));
			$query->select(array('IF(r.name IS NULL,"' . Text::_('COM_TJCOMPETENCY_BLOCKED_USER') . '",users.name) AS rname'));
			$query->from($db->quoteName('#__tjcompetency_skill_user_content_map', 'a'));
			$query->join('LEFT', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('a.created_by') . ' = ' . $db->quoteName('users.id') . ')');
			$query->join('LEFT', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('a.user_id') . ' = ' . $db->quoteName('u.id') . ')');
			$query->join('LEFT', $db->quoteName('#__users', 'r') . ' ON (' . $db->quoteName('a.reviewer_id') . ' = ' . $db->quoteName('r.id') . ')');
			$query->join('LEFT', $db->quoteName('#__tjcompetency_skills', 'b') . ' ON (' . $db->quoteName('a.skill_id') . ' = ' . $db->quoteName('b.id') . ')');
			$query->join('LEFT', $db->quoteName('#__tjcompetency_scales', 'c') . ' ON (' . $db->quoteName('a.scale_id') . ' = ' . $db->quoteName('c.id') . ')');
			$query->join('LEFT', $db->quoteName('#__tjcompetency_frameworks', 'd') . ' ON (' . $db->quoteName('b.framework_id') . ' = ' . $db->quoteName('d.id') . ')');

			$reportId = $this->getDefaultReport($this->name);
			$viewAll = $this->checkpermissions($reportId);

			if ((int) $filters['report_filter'] === 1)
			{
				$query->where('a.created_by = ' . (int) $userId);
			}
			elseif(!$viewAll)
			{
				$query->where('a.created_by=0');
			}

			if ($limit != 0)
			{
				$query->setLimit($limit, $start);
			}
		}

		return $query;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.6
	 */
	public function getItems()
	{
		$items     = parent::getItems();
		$colToshow = $this->getState('colToshow');

		if (empty($items))
		{
			return;
		}

		foreach ($items as &$item)
		{

			$item['content_name'] = TjCompetency::SkillContentMap()::getContentName($item['client'], $item['client_id']);
			$item['created_on']   = HTMLHelper::_('date', $item['created_on'], Text::_('DATE_FORMAT_LC1'), false);

			switch ($item['state'])
			{
				case '1':
					$item['state'] = Text::_('JPUBLISHED');
					break;

				case '0':
					$item['state'] = Text::_('JUNPUBLISHED');
					break;

				case '2':
					$item['state'] = Text::_('JARCHIVED');
					break;

				case '-2':
					$item['state'] = Text::_('JTRASHED');
					break;

				case '3':
					$item['state'] = Text::_('PLG_TJREPORTS_SKILLUSER_STATUS_OPTION_PENDING_REVIEW');
					break;

				default:
					break;
			}
		}

		// $items = $this->sortCustomColumns($items);

		return $items;
	}

	/**
	 * Create an array of fields in the form of Google data studio requires
	 * Array(
	 *   array(
	 *		'name' => internal name of the field
	 * 		'label' => Name to be displayed on the report
	 *      'dataType' => 'NUMBER' OR 'STRING' OR 'BOOLEAN'
	 * 		'semantics' => array('conceptType' => 'DIMENSION' OR 'METRIC')
	 * 	  ),
	 * )
	 *
	 * More information about fields https://developers.google.com/datastudio/connector/reference#data_types
	 *
	 * @return  ARRAY
	 *
	 * @since   1.3.30
	 */
	/*public function getGDSFields()
	{
		return array(
			array('name' => 'attempt', 'label' => Text::_('COM_TJLMS_TITLE_ATTEMPTS'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'name', 'label' => Text::_('COM_TJLMS_ATTEMPTREPORT_NAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'lessonFormat', 'label' => Text::_('COM_TJLMS_LESSONS_FORMAT'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'lesson_id', 'label' => Text::_('COM_TJLMS_ATTEMPTREPORT_LESSONID'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'username', 'label' => Text::_('COM_TJLMS_REPORT_USERUSERNAME'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'user_id', 'label' => Text::_('COM_TJLMS_REPORT_USERUSERID'),
				'dataType' => 'NUMBER', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'usergroup', 'label' => Text::_('COM_TJLMS_REPORT_USERGROUP'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'time_spent', 'label' => Text::_('COM_TJLMS_REPORT_LESSON_TIMESPENT'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'lesson_status', 'label' => Text::_('COM_TJLMS_REPORT_LESSON_STATUS'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'score', 'label' => Text::_('COM_TJLMS_REPORT_LESSON_SCORE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION')),
			array('name' => 'timestart', 'label' => Text::_('COM_TJLMS_LESSONREPORT_STARTDATE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'timeend', 'label' => Text::_('COM_TJLMS_LESSONREPORT_ENDDATE'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
			array('name' => 'last_accessed_on', 'label' => Text::_('COM_TJLMS_ATTEMPTREPORT_LASTACCESS'),
				'dataType' => 'STRING', 'semantics' => array('conceptType' => 'DIMENSION', 'semanticType' => 'YEAR_MONTH_DAY')),
		);
	}*/
}
