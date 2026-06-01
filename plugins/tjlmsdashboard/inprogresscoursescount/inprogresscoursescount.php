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
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjlmsdashboard_inprogresscoursescount', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjlmsdashboardInprogresscoursescount extends CMSPlugin
{
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
	public function oninprogresscoursescountRenderPluginHTML($plg_data, $layout = 'default')
	{
		$inprogressCourses = $this->getData($plg_data);

		// Get plugin params
		$plg_data->background_color = $this->params->get('background_color');
		$plg_data->border_color = $this->params->get('border_color');
		$plg_data->text_color = $this->params->get('text_color');

		// Load the layout & push variables
		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, $this->params->get('layout', 'default'));
		include $layout;

		$html = ob_get_contents();
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
		$inprogressCourses = 0;

		try
		{
			// Get count of enrolled courses for the user
			$db = Factory::getDbo();

			// Create a new query object.
			$query = $db->getQuery(true);

			// Select all records from the user profile table where key begins with "custom.".
			// Order it by the ordering field.
			$query->select('COUNT(eu.id) as totalInCompletedCourses');
			$query->from($db->quoteName('#__tjlms_enrolled_users') . ' as eu');
			$query->join('INNER', $db->quoteName('#__tjlms_courses') . ' as c ON c.id=eu.course_id');
			$query->join('LEFT', $db->quoteName('#__tjlms_course_track', 'ct') . ' ON ((' . $db->qn('ct.course_id') . ' = ' . $db->qn('eu.course_id') . '
						) AND  (' . $db->qn('ct.user_id') . ' = ' . $db->qn('eu.user_id') . '))');
			$query->join('INNER', $db->qn('#__categories', 'cat') . ' ON (' . $db->qn('cat.id') . ' = ' . $db->qn('c.catid') . ')');
			$query->where($db->qn('cat.published') . ' = 1 ');
			$query->where($db->quoteName('ct.user_id') . ' = ' . $plg_data->user_id);
			$query->where($db->quoteName('ct.status') . ' != "C"');
			$query->where($db->quoteName('c.state') . ' = 1');
			$query->where($db->qn('eu.state') . '=1');

			// Reset the query using our newly populated query object.
			$db->setQuery($query);

			// Load the results as a list of stdClass objects (see later for more options on retrieving data).
			$inprogressCourses = $db->loadresult();
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $inprogressCourses;
	}
}
