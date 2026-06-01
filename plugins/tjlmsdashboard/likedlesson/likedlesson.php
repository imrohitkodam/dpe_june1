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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjlmsdashboard_likedlesson', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjlmsdashboardLikedlesson extends CMSPlugin
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
	public function onlikedlessonRenderPluginHTML($plg_data, $layout = 'default')
	{
		$yourLikedLessons = $this->getData($plg_data);
		$comtjlmsHelper = new comtjlmsHelper;
		$this->dash_icons_path = Uri::root(true) . '/media/com_tjlms/images/default/icons/';

		$params = ComponentHelper::getParams('com_tjlms');
		$launch_lesson_full_screen = $params->get('launch_full_screen', '0', 'INT');	/* Open lesson in lightbox*/

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
		$path = JPATH_SITE . '/components/com_tjlms/helpers/lesson.php';

		if (!class_exists('TjlmsLessonHelper'))
		{
			// Require_once $path;
			JLoader::register('TjlmsLessonHelper', $path);
			JLoader::load('TjlmsLessonHelper');
		}

		$this->tjlmsLessonHelper = new TjlmsLessonHelper;

		$no_of_lessons = $this->params->get('no_of_lessons');
		$db    = Factory::getDBO();
		$query  = $db->getQuery(true);
		$query->select('c.id,lesson.title as title,lesson.id,lesson.format,lesson.no_of_attempts,lesson.course_id');
		$query->from('#__jlike_content as c');
		$query->join('INNER', '#__jlike_likes as l ON l.content_id=c.id');
		$query->join('INNER', '#__tjlms_lessons as lesson ON lesson.id=c.element_id');

		// Durgesh Added for checking course state before showin lesson

		$query->join('LEFT', '#__tjlms_courses as course ON course.id=lesson.course_id');
		$query->where('course.state = 1');

		$query->leftjoin('`#__categories` as cat ON cat.id = course.catid');
		$query->where(' cat.published=1');

		$query->join('INNER', '#__tjlms_modules as md  ON md.id=lesson.mod_id');
		$query->where('md.state = 1');

		// Durgesh Added for checking course state before showin lesson

		$query->where('c.element="com_tjlms.lesson" AND l.like=1 AND l.userid=' . $plg_data->user_id . ' LIMIT 0,' . $no_of_lessons);

		$db->setQuery($query);
		$yourLikedLessons = $db->loadObjectlist();

		foreach ($yourLikedLessons as $ind => $lesson)
		{
				$yourLikedLessons[$ind]->attemptsdonebyuser = $this->tjlmsLessonHelper->getlesson_total_attempts_done($lesson->id, $plg_data->user_id);
		}

		return $yourLikedLessons;
	}
}
