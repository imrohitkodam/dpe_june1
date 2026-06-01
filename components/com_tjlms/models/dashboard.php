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

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

jimport('joomla.access.access');
jimport('joomla.application.component.model');

/**
 * Methods supporting a list of Tjlms records.
 *
 * @since  1.0.0
 */
class TjlmsModelDashboard extends BaseDatabaseModel
{
	/**
	 * constructor function
	 *
	 * @since  1.0
	 */
	public function __construct()
	{
		$this->tjlmsCoursesHelper = new tjlmsCoursesHelper;
		parent::__construct();
	}

	/**
	 * Fucntion used to get all data of the user
	 *
	 * @return  obj  $dashboardData
	 *
	 * @since  1.0.0
	 */
	public function getdashboardData()
	{
		try
		{
			$db = Factory::getDbo();
			$user_id = Factory::getUser()->id;
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__tjlms_dashboard'));
			$query->where($db->quoteName('user_id') . ' = ' . $db->quote((int) $user_id));
			$query->order('ordering ASC');
			$db->setQuery($query);
			$pluginsToShow = $db->loadObjectList();

			if (empty($pluginsToShow))
			{
				$query = $db->getQuery(true);
				$query->select('*');
				$query->from($db->quoteName('#__tjlms_dashboard'));
				$query->where($db->quoteName('user_id') . ' = 0');
				$query->order('ordering ASC');
				$db->setQuery($query);
				$pluginsToShow = $db->loadObjectList();
			}

			// Get data from the plugins
			if (!empty($pluginsToShow))
			{
				foreach ($pluginsToShow as $index => $eachPlugin)
				{
					$size = 'col-xs-12';

					if ($eachPlugin->size == 'span6')
					{
						$size  .= ' col-sm-6 col-md-6 col-lg-6';
					}
					elseif ($eachPlugin->size == 'span4')
					{
						$size  .= ' col-sm-4 col-md-4 col-lg-4';
					}
					elseif ($eachPlugin->size == 'span3')
					{
						$size  .= ' col-sm-3 col-md-3 col-lg-3';
					}
					elseif ($eachPlugin->size == 'span2')
					{
						$size  .= ' col-sm-2 col-md-2 col-lg-2';
					}

					$eachPlugin->size = $size;
					$eachPlugin->user_id = $user_id;

					try
					{
						PluginHelper::importPlugin('tjlmsdashboard');
						$eachPlugin->html = Factory::getApplication()->triggerEvent('on' . $eachPlugin->plugin_name . 'RenderPluginHTML', array($eachPlugin));

						if (empty($eachPlugin->html[0]))
						{
							unset($pluginsToShow[$index]);
						}
					}
					catch (Exception $e)
					{
						unset($pluginsToShow[$index]);
					}
				}
			}

			return $pluginsToShow;
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}
	}
}
