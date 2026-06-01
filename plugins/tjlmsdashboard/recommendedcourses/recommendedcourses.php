<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,tjlmsdashboard,recommendedcourses
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\User\User;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjlmsdashboard_recommendedcourses', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjlmsdashboardRecommendedcourses extends CMSPlugin
{
	/**
	 * Plugin that supports creating the tjlms dashboard
	 *
	 * @param   string   &$subject  The context of the content being passed to the plugin.
	 * @param   integer  $config    Optional page number. Unused. Defaults to zero.
	 *
	 * @since 1.0.0
	 */

	public function __construct(&$subject, $config)
	{
		$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

		if (!class_exists('comtjlmsHelper'))
		{
			JLoader::register('comtjlmsHelper', $path);
			JLoader::load('comtjlmsHelper');
		}

		$comtjlmsHelper = new comtjlmsHelper;

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
	public function onrecommendedcoursesRenderPluginHTML($plg_data, $layout = 'default')
	{
		$recommcoursedata   = $this->getData($plg_data);
		$recommcourse       = array();
		$totalCount         = 0;
		$noOfCourses        = $this->params->get('no_of_courses');
		$app                = Factory::getApplication();
		$tjlmsparams        = $app->getParams('com_tjlms');
		$showUserOrUsername = $tjlmsparams->get('show_user_or_username', 'name');

		if (isset($recommcoursedata) && !empty($recommcoursedata))
		{
			$recommcourse = $recommcoursedata;
		}

		if (isset($recommcoursedata['totalCount']) && !empty($recommcoursedata['totalCount']))
		{
			$totalCount = $recommcoursedata['totalCount'];
		}

		$comtjlmsHelper        = new comtjlmsHelper;

		$this->dash_icons_path = Uri::root(true) . '/media/com_tjlms/images/default/icons/';

		// Load the layout & push variables
		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, $this->params->get('layout', $layout));
		include $layout;
		$html   = ob_get_contents();
		ob_end_clean();

		return $html;
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
		$noOfCourses    = $this->params->get('no_of_courses');
		$comtjlmsHelper = new comtjlmsHelper;

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jlike/models', 'JlikeModel');
		$model = BaseDatabaseModel::getInstance('Recommendations', 'JlikeModel', array('ignore_request' => true));
		$model->setState('type', 'recommendtome');
		$model->setState("user_id", $plg_data->user_id);
		$model->setState("client", 'com_tjlms.course');
		$model->setState("list.ordering", "a.created_date");
		$model->setState("list.direction", "DESC");
		$model->setState("list.limit", $noOfCourses);

		$recommendedcourse = $model->getItems();
		$recommendedcourse['totalCount'] = count($recommendedcourse);

		if (!empty($recommendedcourse))
		{
			foreach ($recommendedcourse as $eachrecord)
			{
				if (isset($eachrecord->assigned_by))
				{
					$table = User::getTable();

					if ($table->load($eachrecord->assigned_by))
					{
						$userWhoRecommend                   = Factory::getUser($eachrecord->assigned_by);
						$eachrecord->userId                 = $userWhoRecommend->id;
						$eachrecord->name                   = $userWhoRecommend->name;
						$eachrecord->username               = $userWhoRecommend->username;
						$eachrecord->userWhoRecommendavatar = $comtjlmsHelper->sociallibraryobj->getAvatar($userWhoRecommend);

						$eachrecord->userWhoRecommendprofileurl = '';

						if ($comtjlmsHelper->sociallibraryobj->getProfileUrl($userWhoRecommend))
						{
							$eachrecord->userWhoRecommendprofileurl = Route::_($comtjlmsHelper->sociallibraryobj->getProfileUrl($userWhoRecommend));
						}
					}
				}
			}
		}

		return $recommendedcourse;
	}
}
