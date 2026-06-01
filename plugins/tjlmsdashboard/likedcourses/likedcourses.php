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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Plugin\PluginHelper;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjlmsdashboard_likedcourses', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjlmsdashboardLikedcourses extends CMSPlugin
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
	public function onlikedcoursesRenderPluginHTML($plg_data, $layout = 'default')
	{
		$plgresult = $this->getData($plg_data);
		$yourLikedCourses = $plgresult[0];
		$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

		if (!class_exists('comtjlmsHelper'))
		{
			JLoader::register('comtjlmsHelper', $path);
			JLoader::load('comtjlmsHelper');
		}

		$comtjlmsHelper = new comtjlmsHelper;

		$this->dash_icons_path = Uri::root(true) . '/media/com_tjlms/images/default/icons/';

		// Load the layout & push variables
		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, $this->params->get('layout', $layout));
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
		$no_of_courses = $this->params->get('no_of_courses');
		$db    = Factory::getDBO();
		$query  = $db->getQuery(true);
		$query->select('c.title,c.url,course.image,course.id');
		$query->from('#__jlike_content as c');
		$query->join('INNER', '#__jlike_likes as l ON l.content_id=c.id');
		$query->join('INNER', '#__tjlms_courses as course ON course.id=c.element_id');
		$query->leftjoin('`#__categories` as cat ON cat.id = course.catid');
		$query->where('cat.published=1');
		$query->where('c.element="com_tjlms.course" AND course.state=1 AND l.like=1 AND l.userid=' . $plg_data->user_id . ' LIMIT 0,' . $no_of_courses);
		$db->setQuery($query);
		$yourLike = $db->loadObjectlist();

		$query  = $db->getQuery(true);
		$query->select('course.id');
		$query->from('#__jlike_content as c');
		$query->join('INNER', '#__jlike_likes as l ON l.content_id=c.id');
		$query->join('INNER', '#__tjlms_courses as course ON course.id=c.element_id');
		$query->leftjoin('`#__categories` as cat ON cat.id = course.catid');
		$query->where('c.element="com_tjlms.course" AND course.state=1 AND l.like=1 AND cat.published=1 AND l.userid=' . $plg_data->user_id);
		$db->setQuery($query);
		$likes = $db->loadColumn();
		$yourLikedCourses = array($yourLike,$likes);

		return $yourLikedCourses;
	}
}
