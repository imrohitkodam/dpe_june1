<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJReport,coursereport
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\Data\DataObject;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * Skill user map report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelSkillUserMapsReport extends TjreportsModelReports
{
	protected $default_order_dir = 'ASC';

	public $showSearchResetButton = false;

	protected $lmsparams;

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
		JLoader::import('administrator.components.com_tjcompetency.helpers.tjcompetency', JPATH_SITE);

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjcompetency', $base_dir);

		$this->columns = array(
			'user_id'             => array('table_column' => 'a.user_id', 'title' => 'PLG_TJREPORTS_SKILLUSERMAPSREPORT_USER_ID', 'not_show_hide' => false),
			'user_name'             => array('table_column' => 'u.name', 'title' => 'PLG_TJREPORTS_SKILLUSERMAPSREPORT_USER'),
			'skill_title'        => array('table_column' => 'a.skill_title', 'title' => 'PLG_TJREPORTS_SKILLUSERMAPSREPORT_SKILL'),
			'skill_id'           => array('title' => 'PLG_TJREPORTS_SKILLUSERMAPSREPORT_SKILL_ID', 'table_column' => 'a.skill_id', 'not_show_hide' => false),
			'path'  => array('disable_sorting' => true, 'table_column' => 's.path', 'title' => 'PLG_TJREPORTS_SKILLUSER_SKILL_PATH'),
			'scale_set_id'       => array('table_column' => 'a.scale_set_id', 'title' => 'PLG_TJREPORTS_SKILLUSERMAPSREPORT_SCALE_ID', 'not_show_hide' => false),
			'scale_set_title'    => array('table_column' => 'a.scale_set_title', 'title' => 'PLG_TJREPORTS_SKILLUSERMAPSREPORT_SCALE')
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
		$detail = array('client' => 'com_tjcompetency', 'title' => Text::_('PLG_TJREPORTS_SKILLUSERMAPSREPORT_TITLE'));

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

		$userFilterArray[] = HTMLHelper::_('select.option', '', Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_FILTER_USER_SELECT'));

		if (!empty($userFilter))
		{
			foreach ($userFilter as $key => $value)
			{
				$userFilterArray[] = HTMLHelper::_('select.option', $value->value, $value->text);
			}
		}

		$skillFilter      = TjCompetencyHelper::getSkillFilter();
		$skillFilterArray = array();

		$skillFilterArray[] = HTMLHelper::_('select.option', '', Text::_('COM_COMPETENCY_COMPETENCY_SKILLCONTENTUSERMAP_SKILL_FIELD_SELECT'));

		if (!empty($skillFilter))
		{
			foreach ($skillFilter as $key => $value)
			{
				$skillFilterArray[] = HTMLHelper::_('select.option', $value->value, $value->text);
			}
		}

		$dispFilters = array(
			array(
				'user_name' => array(
					'search_type' => 'select', 'select_options' => $userFilterArray, 'type' => 'equal', 'searchin' => 'a.user_id'
				),
				'skill_title' => array(
					'search_type' => 'select', 'select_options' => $skillFilterArray, 'type' => 'equal', 'searchin' => 'a.skill_id'
				)
			)
		);

		$filters = $this->getState('filters');

		if (count($reportOptions) > 1)
		{
			$dispFilters[1] = array();
			$dispFilters[1]['report_filter'] = array(
				'search_type' => 'select', 'select_options' => $reportOptions
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
		$db        = $this->_db;
		$query     = parent::getListQuery();
		$filters   = $this->getState('filters');
		$user      = Factory::getUser();
		$userId    = $user->id;

		$colToshow = (array) $this->getState('colToshow');

		// Must have columns to get details of non linked data like completion
		$query->from($db->quoteName('#__tjcompetency_skill_user_map', 'a'));
		$query->join('INNER', $db->quoteName('#__users', 'u') . ' ON (' . $db->quoteName('a.user_id') . ' = ' . $db->quoteName('u.id') . ')');
		$query->join('INNER', $db->quoteName('#__tjcompetency_scales', 'c') . ' ON (' . $db->quoteName('a.scale_set_id') . ' = ' . $db->quoteName('c.scale_set_id') . ' AND ' . $db->quoteName('a.max_sequence_number') . ' = ' . $db->quoteName('c.sequence_number') . '  )' );
		$query->join('INNER', $db->quoteName('#__tjcompetency_skills', 's') . ' ON (' . $db->quoteName('s.id') . ' = ' . $db->quoteName('a.skill_id') . ')');

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
		// Add additional columns which are not part of the query
		$items = parent::getItems();

		$colToshow = $this->getState('colToshow');

		if (empty($items))
		{
			return;
		}

		$items = $this->sortCustomColumns($items);

		return $items;
	}
}
