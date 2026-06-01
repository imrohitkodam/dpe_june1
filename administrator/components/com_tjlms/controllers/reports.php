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
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;

jimport('joomla.application.component.controlleradmin');

/**
 * Courses list controller class.
 *
 * @since  1.0.0
 */
class TjlmsControllerReports extends AdminController
{
	/**
	 * Function used to get filtered data
	 *
	 * @return  jexit
	 *
	 * @since   1.0.0
	 */
	public function getFilterData()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');
		$input = Factory::getApplication()->input;
		$post  = $input->post;

		$filterData  = $post->get('filterValue', '', 'ARRAY');
		$filterTitle = $post->get('filterName', '', 'ARRAY');

		$limit     = $post->get('limit', '20', 'INT');
		$page      = $post->get('page', '0', 'INT');
		$sortCol   = $post->get('sortCol', '', 'STRING');
		$sortOrder = $post->get('sortOrder', '', 'STRING');
		$colNames  = $post->get('colToShow', '', 'ARRAY');
		$action    = $post->get('action', '', 'STRING');

		$limit_start = 0;

		if ($page > 0)
		{
			$limit_start = $limit * ($page - 1);
		}

		$filters = array();
		$count   = count($filterData);
		$i       = 0;

		for ($i = 0; $i <= $count - 1; $i++)
		{
			if (isset($filterTitle[$i]) && isset($filterData[$i]))
			{
				$filters[$filterTitle[$i]] = $filterData[$i];
			}
		}

		$model = $this->getModel('reports');
		$data  = $model->getData($filters, $colNames, $limit, $limit_start, $sortCol, $sortOrder, $action);

		echo json_encode($data, true);
		jexit();
	}

	/**
	 * Function used to export data in csv format
	 *
	 * @return  jexit
	 *
	 * @since   1.0.0
	 */
	public function csvexport()
	{
		$mainframe  = Factory::getApplication('admin');
		$input = Factory::getApplication()->input;
		$reportName = $input->get('reportToBuild', '', 'STRING');

		if (empty($reportName))
		{
			$reportName = $mainframe->getUserState('com_tjlms' . '.reportName', '');
		}

		$colNames      = $mainframe->getUserState('com_tjlms' . '.' . $reportName . '_table_colNames', '');
		$filters       = $mainframe->getUserState('com_tjlms' . '.' . $reportName . '_table_filters', '');
		$sortCol       = $mainframe->getUserState('com_tjlms' . '.' . $reportName . '_table_sortCol', 'asc');
		$sortOrder     = $mainframe->getUserState('com_tjlms' . '.' . $reportName . '_table_sortOrder', 'asc');
		$rows_to_fetch = 'all';
		$limit_start   = 0;
		$action        = 'csv';

		PluginHelper::importPlugin('tjlmsreports');
		$data = Factory::getApplication()->triggerEvent('plg' . $reportName . 'GetData', array(
																				$filters,
																				$colNames,
																				$rows_to_fetch,
																				$limit_start,
																				'',
																				$sortCol,
																				$sortOrder,
																				$action
																			)
									);

		$data = $data[0];

		$csvData     = null;
		$csvData_arr = array();

		foreach ($data['colToshow'] as $eachColumn)
		{
			$calHeading    = strtoupper($eachColumn);
			$plgReport     = strtoupper($reportName);
			$calHeading    = 'PLG_TJLMSREPORTS_' . $plgReport . '_' . $calHeading;
			$csvData_arr[] = Text::_($calHeading);
		}

		$csvData .= implode('	', $csvData_arr);
		$csvData .= "\n";
		echo $csvData;

		$csvData  = '';
		$filename = "lms_" . $reportName . "_report_" . date("Y-m-d_H-i", time());

		// Set CSV headers
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=" . $filename . ".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		foreach ($data['items'] as $key => $value)
		{
			$csvData      = '';
			$csvData_arr1 = array();

			foreach ($value as $index => $finalValue)
			{
				if (in_array($index, $data['colToshow']))
				{
					$csvData_arr1[] = $finalValue;
				}
			}

			// TRIGGER After csv body add extra fields
			$csvData = implode('	', $csvData_arr1);
			echo $csvData . "\n";
		}

		jexit();
	}

	/**
	 * Save a query for report engine
	 *
	 * @return true
	 *
	 * @since 1.0.0
	 */
	public function saveQuery()
	{
		$db        = Factory::getDBO();
		$mainframe  = Factory::getApplication('admin');
		$input = Factory::getApplication()->input;
		$reportName = $input->get('reportToBuild', '', 'STRING');

		if (empty($reportName))
		{
			$reportName = $mainframe->getUserState('com_tjlms' . '.reportName', '');
		}

		$colNames      = $mainframe->getUserState('com_tjlms' . '.' . $reportName . '_table_colNames', '');
		$filters       = $mainframe->getUserState('com_tjlms' . '.' . $reportName . '_table_filters', '');
		$sortCol       = $mainframe->getUserState('com_tjlms' . '.' . $reportName . '_table_sortCol', '');
		$sortOrder     = $mainframe->getUserState('com_tjlms' . '.' . $reportName . '_table_sortOrder', '');
		$rows_to_fetch = 'all';
		$limit_start   = 0;

		$post = $input->post;

		$sort = array();

		if (!empty($sortCol) && !empty($sortOrder))
		{
			$sort[] = $sortCol;
			$sort[] = $sortOrder;
		}

		$currentTime = new Date('now');
		$queryName = $post->get('queryName', '', 'STRING');
		$res                   = new stdClass;
		$res->id               = '';
		$res->query_name = $queryName;
		$res->colToshow          = json_encode($colNames);
		$res->sort          = json_encode($sort);
		$res->filters          = json_encode($filters);
		$res->report_name          = $reportName;
		$res->plugin_name          = $reportName;
		$res->creator_id          = Factory::getUser()->id;
		$res->privacy          = '';
		$res->created_on          = $currentTime;
		$res->last_accessed_on          = $currentTime;
		$res->hash          = '';

		if (!$db->insertObject('#__tjlms_reports_queries', $res, 'id'))
		{
			echo $db->stderr();

			return false;
		}

		echo json_encode(1);
		jexit();
	}
}
