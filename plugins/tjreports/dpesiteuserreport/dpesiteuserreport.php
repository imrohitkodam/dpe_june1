<?php
/**
 * @package    DPE
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Data\DataObject;
use Joomla\CMS\Component\ComponentHelper;

JLoader::import('com_tjreports.models.reports', JPATH_SITE . '/components');

/**
 * User report plugin of TJReport
 *
 * @since  1.0.0
 */
class TjreportsModelDpeSiteUserreport extends TjreportsModelReports
{
	protected $default_order = 'Name';

	protected $default_order_dir = 'ASC';

	public $showSearchResetButton = false;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     BaseDatabaseModel
	 * @since   1.0
	 */
	public function __construct($config = array())
	{
		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);

		$lang = Factory::getLanguage();
		$base_dir = JPATH_SITE . '/administrator';
		$lang->load('com_tjlms', $base_dir);

		$this->columns = array(
			'Name' => array('title' => 'COM_TJLMS_ENROLMENT_USER_NAME', 'table_column' => 'Name'),
			'Username' => array('title' => 'COM_TJLMS_REPORT_USERUSERNAME', 'table_column' => 'Username'),
			'Email' => array('title' => 'COM_TJLMS_ENROLMENT_USER_EMAIL', 'table_column' => 'Email'),
			'School' => array('title' => 'School', 'table_column' => 'School'),
			'UserRole' => array('title' => 'Role', 'table_column' => 'UserRole')
		);

		parent::__construct($config);
	}

	/**
	 * Get client of this plugin
	 *
	 * @return Array Client
	 *
	 * @since   2.0
	 * */
	public function getPluginDetail()
	{
		$detail = array('client' => 'com_tjlms', 'title' => Text::_('PLG_DPE_SITEUSERREPORT_TITLE'));

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
		return array(
			Uri::root(true) . '/media/com_tjlms/css/tjlms_backend.css',
			Uri::root(true) . '/media/com_tjlms/font-awesome/css/font-awesome.min.css'
		);
	}

	/**
	 * Create an array of filters
	 *
	 * @return  ARRAY of filters
	 *
	 * @since    1.0
	 */
	public function displayFilters()
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$multiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel');
		$agencyFilter 		= $multiagencyModel->getAgencyFilter();
		$user = Factory::getUser();

		// Added in school dropdown list
		$extraFilter = array();

		if (empty($agencyFilter[0]->value))
		{
			unset($agencyFilter[0]);
		}

		// Initialize array option
		$extraFilter[] = HTMLHelper::_(
		'select.option', strtolower(Text::_('PLG_DPE_SELECT_ALL_AGENCY')), Text::_('PLG_DPE_SELECT_ALL_AGENCY')
		);

		if ($user->authorise('core.admin'))
		{
			$extraFilter[] = HTMLHelper::_(
			'select.option', strtolower(Text::_('PLG_DPE_SELECT_NONE')), Text::_('PLG_DPE_SELECT_NONE')
			);
		}

		$agencyFilter = array_merge($extraFilter, $agencyFilter);

		$dispFilters = array(
			array(
				'Name' => array(
					'search_type' => 'text',  'searchin' => 'a.name'
				),
				'Username' => array(
					'search_type' => 'text', 'searchin' => 'a.username'
				),
				'Email' => array(
					'search_type' => 'text', 'searchin' => 'a.email'
				),
				'UserRole' => array(
					'search_type' => 'text', 'type' => 'custom', 'searchin' => 'r.name'
				)
			),
			array(
				'School' => array(
						'search_type' => 'select', 'select_options' => $agencyFilter, 'type' => 'select', 'searchin' => 'c.id'
				),
			)
		);

		return $dispFilters;
	}

	/**
	 * Method to get a DataObjectbaseQuery object for retrieving the data set from a database.
	 *
	 * @return  DataObjectbaseQuery  A DataObjectbaseQuery object to retrieve the data set.
	 *
	 * @since   1.0
	 */
	protected function getListQuery()
	{
		$db        = $this->_db;
		$colToshow = $this->getState('colToshow');
		$user = Factory::getUser();
		$displayFilters = (array) $this->displayFilters();
		$filters = $this->getState('filters');
		$app = Factory::getApplication();
		$input = Factory::getApplication()->input;

		$limit = $input->get('limit', $app->get('list_limit', 0), 'uint');
		$offset = $input->get('limitstart', 0, 'uint');

		$unionQuery = true;
		$orphanUser = false;

		// Select the orphan users.
		$queryselect = $db->getQuery(true);
		$queryselect->select('DISTINCT a.name as Name,a.username as Username, a.email as Email, "" as School, "" as UserRole')
			->from($db->qn('#__users', 'a'))
			->join('LEFT', $db->qn('#__tjsu_users', 'b') . ' ON (' . $db->qn('a.id') . ' = ' . $db->qn('b.user_id') . ')')
			->where('b.user_id IS NULL');

		// Select the users with school details.
		$query = $db->getQuery(true);

		$query->select('DISTINCT a.name as Name,a.username as Username, a.email as Email, c.title as School, r.name as UserRole');

		$query->from($db->qn('#__users', 'a'));

		$query->join('INNER', $db->qn('#__tjsu_users', 'b') .
		' ON (' . $db->qn('a.id') . ' = ' . $db->qn('b.user_id') . ' AND ' . $db->qn('b.client') . ' = "com_multiagency" )');

		$query->join('INNER', $db->qn('#__tjmultiagency_multiagency', 'c') .
		' ON (' . $db->qn('b.client_id') . ' = ' . $db->qn('c.id') . ' AND ' . $db->qn('b.client') . " = 'com_multiagency' )");

		$query->join('INNER', $db->qn('#__tjsu_roles', 'r') .
		' ON (' . $db->qn('r.id') . ' = ' . $db->qn('b.role_id') . ' AND ' . $db->qn('r.state') . ' = 1 )');

		$query->group($db->quoteName('b.user_id'));

		$query->group($db->quoteName('c.id'));

		$query->where($db->qn('a.block') . ' = 0');

		if (!$user->authorise('core.admin') && !isset($filters['School']))
		{
			$whereClause = $this->applyAgencyFilter(strtolower(Text::_('PLG_DPE_SELECT_ALL_AGENCY')), $user);
			$unionQuery = false;

			if (!empty($whereClause))
			{
				$query->where($whereClause);
			}
		}

		// Loop through different levels of filters
		foreach ($displayFilters as $displayFilter)
		{
			foreach ($displayFilter as $key => $dispFilter)
			{
				if (!isset($dispFilter['searchin']))
				{
					continue;
				}

				// Check if any of the filter is set
				if (in_array($key, $colToshow) && ((isset($filters[$key]) && $filters[$key] != '') || (substr($dispFilter['search_type'], -6) === '.range')))
				{
					$columnName = $dispFilter['searchin'];

					if (isset($dispFilter['type']))
					{
						if ($dispFilter['type'] == 'custom')
						{
							$search = $db->Quote('%' . $db->escape($filters[$key], true) . '%');
							$query->where($db->quoteName($columnName) . ' LIKE (' . $search . ')');
						}
						elseif ($dispFilter['type'] == 'select')
						{
							if ($user->authorise('core.admin'))
							{
								if (!empty($filters[$key]) && $filters[$key] == strtolower(Text::_('PLG_DPE_SELECT_NONE')))
								{
									$orphanUser = true;
								}
							}

							$unionQuery = false;

							$whereClause = $this->applyAgencyFilter($filters[$key], $user);

							if (!empty($whereClause))
							{
								$query->where($whereClause);
							}
						}
						else
						{
							$query->where($db->quoteName($columnName) . '=' . $db->quote($filters[$key]));
							$queryselect->where($db->quoteName($columnName) . '=' . $db->quote($filters[$key]));
						}
					}
					else
					{
						$search = $db->Quote('%' . $db->escape($filters[$key], true) . '%');
						$query->where($db->quoteName($columnName) . ' LIKE (' . $search . ')');
						$queryselect->where($db->quoteName($columnName) . ' LIKE (' . $search . ')');
					}
				}
			}
		}

		if ($unionQuery && !$orphanUser)
		{
			$queryselect->union($query);
		}
		elseif (!$orphanUser)
		{
			$queryselect = $query;
		}

		// Add the list ordering clause.
		$sortKey  = $this->getState('list.ordering');
		$orderDir = $this->getState('list.direction');

		if (!empty($sortKey))
		{
			$queryselect->order($sortKey . ' ' . $orderDir);
		}

		$queryselect->setLimit($limit, $offset);

		return $queryselect;
	}

	/**
	 * Method applyAgencyFilter to get string for query where clause.
	 *
	 * @param   string  $agencies  selected school value.
	 *
	 * @param   object  $user      passded current user object.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function applyAgencyFilter($agencies,$user = null)
	{
		$whereClause = null;

		if (empty($user))
		{
			$user = Factory::getUser();
		}

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_multiagency/models');
		$MultiagencyModel = BaseDatabaseModel::getInstance('Multiagency', 'MultiagencyModel', array('ignore_request' => true));
		$params = ComponentHelper::getParams('com_multiagency');
		$memberRole = $params->get('member_role_id', '0', 'INT');
		$agenciesList = $MultiagencyModel->getAllocatedAgencies($user->id, array($memberRole));

		$allocatedAgency = array();

		if (count($agenciesList) > 0)
		{
			foreach ($agenciesList as $agency)
			{
				$allocatedAgency[] = $agency->id;
			}
		}

		if (!empty($agencies) && in_array($agencies, $allocatedAgency))
		{
			$whereClause = 'c.id = ' . (int) $agencies;
		}
		elseif (!empty($agencies) && $agencies == strtolower(Text::_('PLG_DPE_SELECT_ALL_AGENCY')))
		{
			if (!$user->authorise('core.admin'))
			{
				$whereClause = 'c.id  IN ( ' . implode(',', $allocatedAgency) . ')';
			}
		}
		else
		{
			$whereClause = 'c.id = ' . (int) $agencies . ' AND a.id = ' . $user->id;
		}

		return $whereClause;
	}
}
