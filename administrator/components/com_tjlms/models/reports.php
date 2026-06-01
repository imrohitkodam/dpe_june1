<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Utilities\ArrayHelper;
jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelReports extends ListModel
{
	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   ARRAY   $filters      The Filters which are used
	 * @param   ARRAY   $colNames     The columns which need to show
	 * @param   int     $rowsTofetch  Total number of rows to fetch
	 * @param   int     $limit_start  Fetch record fron nth row
	 * @param   STRING  $sortCol      The column which has to be sorted
	 * @param   STRING  $sortOrder    The order of sorting
	 * @param   STRING  $action       Which action has cal this function
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.0
	 */
	public function getData($filters = array(), $colNames = array(), $rowsTofetch = 20, $limit_start = 0,  $sortCol = '', $sortOrder = '', $action = '')
	{
		$input = Factory::getApplication()->input;
		$mainframe  = Factory::getApplication('admin');
		$reportName = $input->get('reportToBuild', '', 'STRING');

		if (empty($reportName))
		{
			$reportName = $mainframe->getUserState('com_tjlms' . '.reportName', '');
		}

		$isSaveQuery = $input->get('savedQuery', '0', 'INT');

		if ($isSaveQuery == 1)
		{
			// Get saved data
			$queryId = $input->get('queryId', '0', 'INT');

			if ($queryId != 0)
			{
				$queryData = $this->getQueryData($queryId, '');

				if (!empty($queryData))
				{
					$reportName = $queryData->report_name;
					$colNames = json_decode($queryData->colToshow);
					$filters = json_decode($queryData->filters);
					$filters = (array) $filters;
					$sort = json_decode($queryData->sort);

					$sortCol = '';
					$sortOrder = '';

					if (!empty($sort))
					{
						$sortCol = $sort[0];
						$sortOrder = $sort[1];
					}
				}
			}
		}

		if (empty($colNames))
		{
			$colNames = $this->getColNames();
		}

		if (!array_key_exists("0", $colNames) && isset($colNames))
		{
			foreach ($colNames as $colName)
			{
				$allColName[] = $colName;
			}

			unset($colNames);
			$colNames = $allColName;
		}

		$this->setAllUserPreference($reportName, $sortCol, $sortOrder, $colNames, $filters);

		// Get all fields
		$colNames      = $mainframe->getUserState('com_tjlms' . '.' . $reportName . '_table_colNames', '');
		$filters       = $mainframe->getUserState('com_tjlms' . '.' . $reportName . '_table_filters', '');
		$sortCol       = $mainframe->getUserState('com_tjlms' . '.' . $reportName . '_table_sortCol', '');
		$sortOrder     = $mainframe->getUserState('com_tjlms' . '.' . $reportName . '_table_sortOrder', '');

		PluginHelper::importPlugin('tjlmsreports');
		$data = Factory::getApplication()->triggerEvent('plg' . $reportName . 'RenderPluginHTML', array
																					(
																						$filters, $colNames, $rowsTofetch, $limit_start, $sortCol, $sortOrder, $action, ''
																					)
									);

		if (isset($data[0]) && !empty($data[0]))
		{
			return $data[0];
		}

		return false;
	}

	/**
	 * Save user preferences
	 *
	 * @param   STRING  $reportName  The name of the report
	 * @param   STRING  $sortCol     The column which has to be sorted
	 * @param   STRING  $sortOrder   The order of sorting
	 * @param   ARRAY   $colNames    The columns which need to show
	 * @param   ARRAY   $filters     The Filters which are used
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.0
	 */
	public function setAllUserPreference($reportName, $sortCol, $sortOrder, $colNames, $filters)
	{
		$mainframe = Factory::getApplication('admin');

		$mainframe->setUserState('com_tjlms' . '.reportName', $reportName);
		$mainframe->setUserState('com_tjlms' . '.' . $reportName . '_table_colNames', $colNames);
		$mainframe->setUserState('com_tjlms' . '.' . $reportName . '_table_filters', $filters);

		if (!empty($sortCol) && !empty($sortOrder))
		{
			$mainframe->setUserState('com_tjlms' . '.' . $reportName . '_table_sortCol', $sortCol);
			$mainframe->setUserState('com_tjlms' . '.' . $reportName . '_table_sortOrder', $sortOrder);
		}
	}

	/**
	 * Get all columns names
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */
	public function getColNames()
	{
		$input = Factory::getApplication()->input;
		$reportName = $input->get('reportToBuild', '', 'STRING');

		if (empty($reportName))
		{
			$mainframe  = Factory::getApplication('admin');
			$reportName = $mainframe->getUserState('com_tjlms' . '.reportName', '');
		}

		PluginHelper::importPlugin('tjlmsreports');
		$data = Factory::getApplication()->triggerEvent('plg' . $reportName . 'getColNames', array());

		if (isset($data[0]) && !empty($data[0]))
		{
			return $data[0];
		}

		return false;
	}

	/**
	 * Get all saved queries
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */
	public function getSavedQueries()
	{
		$ol_user = Factory::getUser()->id;

		$query = $this->_db->getQuery(true);
		$query->select($this->_db->qn(array('id', 'report_name')));
		$query->select($this->_db->qn('query_name', 'name'));
		$query->from($this->_db->qn('#__tjlms_reports_queries'));
		$query->where($this->_db->qn('creator_id') . '=' . $this->_db->q((int) $ol_user));

		$this->_db->setQuery($query);
		$savedQueries = $this->_db->loadObjectList();

		return $savedQueries;
	}

	/**
	 * Get all columns names
	 *
	 * @param   INT    $queryId      Query ID
	 * @param   ARRAy  $colToSelect  Columns to be selected from query
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */
	public function getQueryData($queryId, $colToSelect)
	{
		$ol_user = Factory::getUser()->id;
		$this->_db    = $this->_db;
		$query = $this->_db->getQuery(true);

		if (!empty($colToSelect))
		{
			$colToSelect = implode(',', $colToSelect);
			$query->select($colToSelect);
		}
		else
		{
			$query->select('*');
		}

		$query->from($this->_db->qn('#__tjlms_reports_queries'));
		$query->where($this->_db->qn('creator_id') . '=' . $this->_db->q((int) $ol_user));
		$query->where($this->_db->qn('id') . '=' . $this->_db->q((int) $queryId));

		$this->_db->setQuery($query);
		$queryData = $this->_db->loadObject();

		return $queryData;
	}

	/**
	 * Get all plugins names
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */
	public function getenableReportPlugins()
	{
		$query = $this->_db->getQuery(true);
		$condtion = array(0 => '\'tjlmsreports\'');
		$condtionatype = join(',', $condtion);

		// $query = "SELECT extension_id as id,name,element,enabled as published FROM #__extensions WHERE folder in (" . $condtionatype . ") AND enabled=1";

		$query->select($this->_db->qn(array('name','element')));
		$query->select($this->_db->qn(array('extension_id','enabled'), array('id','published')));
		$query->from($this->_db->qn('#__extensions'));
		$query->where($this->_db->qn('folder') . ' IN(' . $this->_db->q($condtionatype) . ')');
		$query->where($this->_db->qn('enabled') . '=1');
		$this->_db->setQuery($query);
		$reportPlugins = $this->_db->loadobjectList();

		return $reportPlugins;
	}

	/**
	 * Function to get the course filter
	 *
	 * @param   INT    $created_by   Fetch creators courses
	 * @param   Array  $courseState  Fetch course according given states
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getCourseFilter($created_by = 0, $courseState = array())
	{
		$query = $this->_db->getQuery(true);

		$query->select('DISTINCT(id) as id,title');
		$query->from($this->_db->qn('#__tjlms_courses'));

		if ($created_by)
		{
			$query->where($this->_db->qn('created_by') . ' = ' . (int) $created_by);
		}

		if (!empty($courseState) && is_array($courseState))
		{
			$query->where($this->_db->qn('state') . ' IN (' . implode(',', $courseState) . ')');
		}

		$this->_db->setQuery($query);
		$courses = $this->_db->loadObjectList();

		$courseFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_COURSE'));

		if (!empty($courses))
		{
			foreach ($courses as $eachCourse)
			{
				$courseFilter[] = HTMLHelper::_('select.option', $eachCourse->id, $eachCourse->title);
			}
		}

		return $courseFilter;
	}

	/**
	 * Function to get the lesson filter
	 *
	 * @param   INT  $created_by  Fetch creators courses lesson
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getLessonFilter($created_by = 0)
	{
		$query = $this->_db->getQuery(true);

		$query->select('DISTINCT(l.id) as id,l.title');
		$query->from($this->_db->qn('#__tjlms_lessons', 'l'));

		if ($created_by)
		{
			$query->join('INNER', $this->_db->qn('#__tjlms_courses', 'c') . ' on(' . $this->_db->qn('c.id') . '=' . $this->_db->qn('l.course_id') . ')');
			$query->where($this->_db->qn('c.created_by') . ' = ' . (int) $created_by);
		}

		$this->_db->setQuery($query);
		$lessons = $this->_db->loadObjectList();

		$lessonFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_LESSON'));

		if (!empty($lessons))
		{
			foreach ($lessons as $eachLessons)
			{
				$lessonFilter[] = HTMLHelper::_('select.option', $eachLessons->id, $eachLessons->title);
			}
		}

		return $lessonFilter;
	}

	/**
	 * Function to get the user filter
	 *
	 * @param   Boolean  $myteam  Fetch only my team users
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getUserFilter($myteam = false)
	{
		$query = $this->_db->getQuery(true);
		$query->select($this->_db->qn(array('u.id','u.username')));
		$query->from($this->_db->qn('#__users', 'u'));
		$query->where($this->_db->qn('u.block') . ' <> 1');

		if ($myteam)
		{
			JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);
			$hasUsers = TjlmsHelper::getSubusers();
			ArrayHelper::toInteger($hasUsers);

			if (!empty($hasUsers))
			{
				$query->where($this->_db->qn('u.id') . ' IN(' . implode(',', $hasUsers) . ')');
			}
		}

		$this->_db->setQuery($query);
		$users = $this->_db->loadObjectList();

		$userFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_USER'));

		foreach ($users as $eachUser)
		{
			$userFilter[] = HTMLHelper::_('select.option', $eachUser->id, $eachUser->username);
		}

		return $userFilter;
	}

	/**
	 * Function to get the category filter
	 *
	 * @param   Boolean  $default  Add option of 'Select default category'
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getCatFilter($default = true)
	{
		$categories = JHtmlCategory::categories('com_tjlms');

		// Remove add to Root from category list
		array_pop($categories);

		if ($default)
		{
			$obj = new stdClass;
			$obj->value = '';
			$obj->text = Text::_('COM_TJLMS_FILTER_SELECT_COURSE_CATEGORY');
			$obj->disable = '';
			array_unshift($categories, $obj);
		}

		return $categories;
	}

	/**
	 * Function to get the user filter
	 *
	 * @param   Boolean  $myteam  Fetch only my team users
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getNameFilter($myteam = false)
	{
		$query = $this->_db->getQuery(true);
		$query->select($this->_db->qn(array('u.id', 'u.name')));
		$query->from($this->_db->qn('#__users', 'u'));
		$query->where($this->_db->qn('u.block') . ' <> 1');

		if ($myteam)
		{
			JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);
			$hasUsers = TjlmsHelper::getSubusers();
			ArrayHelper::toInteger($hasUsers);

			if (!empty($hasUsers))
			{
				$query->where($this->_db->qn('u.id') . ' IN(' . implode(',', $hasUsers) . ')');
			}
		}

		$this->_db->setQuery($query);
		$names = $this->_db->loadObjectList();

		$nameFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_USER'));

		foreach ($names as $eachName)
		{
			$nameFilter[] = HTMLHelper::_('select.option', $eachName->id, $eachName->name);
		}

		return $nameFilter;
	}

	/**
	 * Function to get the payment processor filter
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getPaymentProcessorFilter()
	{
		$com_params = ComponentHelper::getParams('com_tjlms');
		$gatewaysconfig = $com_params->get('gateways', '', 'ARRAY');
		$options = array();
		$options[] = HTMLHelper::_('select.option', '', Text::_('COM_TJLMS_FILTER_SELECT_PAYMENT_PROCESSOR'));

		foreach ($gatewaysconfig as $gateway)
		{
			$options[] = HTMLHelper::_('select.option', $gateway);
		}

		return $options;
	}
}
