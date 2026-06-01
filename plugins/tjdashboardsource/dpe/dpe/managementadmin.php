<?php
/**
 * @package     TJDashboard
 * @subpackage  tjdashboardsource
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dpe', JPATH_ADMINISTRATOR);

/**
 * DPE plugin for tjdashboardsource to get Report detais
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeManagementAdminDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DPE_MANAGEMENT_ADMIN";

	/**
	 * Get Data for Tabulator Table
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getDataLabelboxTjdashdata()
	{
		$items = [];
		$items['data'] = ['widgetlabel' => Text::_('PLG_TJDASHBOARDSOURCE_DPE_MANAGEMENT_ADMIN_DEC')
		];

		return json_encode($items);
	}

	/**
	 * Get supported Renderers List
	 *
	 * @return array supported renderes for this data source
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getSupportedRenderers()
	{
		return array('labelbox.tjdashdata' => "PLG_TJDASHBOARDRENDERER_LABELBOX");
	}
}
