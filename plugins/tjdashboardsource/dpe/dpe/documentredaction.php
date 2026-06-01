<?php
/**
 * @package     TJDashboard
 * @subpackage  tjdashboardsource
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_dpe', JPATH_ADMINISTRATOR);

/**
 * DPE plugin for tjdashboardsource
 *
 * @since  __DEPLOY_VERSION__
 */

class DpeDocumentRedactionDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_DOCUMENT_REDACTION";

	/**
	 * Get Data for Tabulator Table
	 *
	 * @return string dataArray
	 *
	 * @since   __DEPLOY_VERSION__
	 * */
	public function getDataCountlinkboxTjdashcount()
	{
		$app              = Factory::getApplication();
		$menu             = $app->getMenu();
		$docReductionMenu = $menu->getItems('link', 'index.php?option=com_dpe&view=redaction&tmpl=component', true);
		$docReductionLink = Route::_($docReductionMenu->link . '&Itemid=' . $docReductionMenu->id);

		$urls[Text::_('PLG_TJDASHBOARDSOURCE_DOCUMENT_REDACTION')] = $docReductionLink;

		$items = [];
		$items['data'] = ['link'  => $urls
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
		return array('countlinkbox.tjdashcount' => "PLG_TJDASHBOARDRENDERER_COUNTLINKBOX");
	}
}
