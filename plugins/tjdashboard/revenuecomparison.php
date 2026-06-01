<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjdashboardRevenuecomparison extends CMSPlugin
{
	/**
	 * Plugin that supports creating the tj dashboard
	 *
	 * @param   string   &$subject  The context of the content being passed to the plugin.
	 * @param   integer  $config    Optional page number. Unused. Defaults to zero.
	 *
	 * @since 1.0.0
	 */

	public function __construct(&$subject, $config)
	{
		$lang = Factory::getLanguage();
		$lang->load('plg_tjdashboard_revenuecomparison', JPATH_ADMINISTRATOR);

		parent::__construct($subject, $config);
	}

	/**
	 * Function to render the whole block
	 *
	 * @param   ARRAY  $plg_data  data to be used to create whole block
	 * @param   ARRAY  $layout    Layout to be used
	 *
	 * @return  complete html.
	 *
	 * @since 1.0.0
	 */
	public function revenuecomparisonRenderPluginHTML($plg_data, $layout = 'default')
	{
		$revenueData = $this->getData($plg_data);

		if ($revenueData === false)
		{
			return;
		}

		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath($layout);
		include $layout;

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function to get the layout for the block
	 *
	 * @param   ARRAY  $layout  Layout to be used
	 *
	 * @return  File path
	 *
	 * @since  1.0.0
	 */
	protected function buildLayoutPath($layout)
	{
		if (empty($layout))
		{
			$layout = "default";
		}

		$app = Factory::getApplication();
		$core_file 	= dirname(__FILE__) . '/' . $this->_name . '/' . 'tmpl' . '/' . $layout . '.php';
		$override		= JPATH_BASE . '/templates/' . $app->getTemplate() . '/html/plugins/' . $this->_type . '/' . $this->_name . '/' . $layout . '.php';

		if (File::exists($override))
		{
			return $override;
		}
		else
		{
			return  $core_file;
		}
	}

	/**
	 * Function to get data of the whole block
	 *
	 * @param   ARRAY  $plg_data  data to be used to create whole block
	 *
	 * @return  data.
	 *
	 * @since 1.0.0
	 */
	public function getData($plg_data)
	{
		$no_of_years  = $this->params->get('no_of_years', 3);
		$current_year = date('Y');
		$start_year   = date('Y') - (int) $no_of_years + 1;

		$months_titles = array(
			Text::_('JANUARY_SHORT'),
			Text::_('FEBRUARY_SHORT'),
			Text::_('MARCH_SHORT'),
			Text::_('APRIL_SHORT'),
			Text::_('MAY_SHORT'),
			Text::_('JUNE_SHORT'),
			Text::_('JULY_SHORT'),
			Text::_('AUGUST_SHORT'),
			Text::_('SEPTEMBER_SHORT'),
			Text::_('OCTOBER_SHORT'),
			Text::_('NOVEMBER_SHORT'),
			Text::_('DECEMBER_SHORT')
		);

		$finalData 		 = array();
		$finalData[0] 	 = array();
		$finalData[0][0] = Text::_('PLG_TJDASHBOARD_REVENUE_COMPARISION_MONTH_TITLE');
		$j = 1;

		for ($i = $start_year; $i <= $current_year; $i++)
		{
			$finalData[0][$j] = (string) $i;
			$j++;
		}

		$course_ids = array();
		$user_id    = Factory::getUser()->id;
		$isSuper	= Factory::getUser()->authorise('core.admin', 'com_config');
		$db         = Factory::getDBO();

		// If not sup
		if (!$isSuper)
		{
			$query = $db->getQuery(true);
			$query->select('c.id');
			$query->from('`#__tjlms_courses` as c');
			$query->where('c.created_by=' . (int) $user_id);
			$db->setQuery($query);
			$course_ids = (int) $db->loadColumn();

			if (empty($course_ids))
			{
				return false;
			}
		}

		foreach ($months_titles as $key => $month)
		{
			$index 					= $key + 1;
			$finalData[$index]		= Array();
			$finalData[$index][0] 	= $month;

			for ($i = $start_year; $i <= $current_year; $i++)
			{
				$query = $db->getQuery(true);
				$query->select('sum(o.amount)');
				$query->from('`#__tjlms_orders` as o');
				$query->where('status="C"');
				$query->where('MONTH(cdate)=' . (int) $index);
				$query->where('YEAR(cdate)=' . (int) $i);

				if (!empty($course_ids))
				{
					$query->where('course_id IN(' . implode(',', $course_ids) . ')');
				}

				$db->setQuery($query);
				$amount = (int) $db->loadResult();
				array_push($finalData[$index], $amount);

				/* array_push($finalData[$index], rand(100, 999)); */
			}
		}

		$final = array();
		$final['data'] = $finalData;
		$final['start_year'] = $start_year;
		$final['current_year'] = $current_year;

		return $final;
	}
}
