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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
$lang      = Factory::getLanguage();
$lang->load('plg_tjdashboardsource_tjlms', JPATH_ADMINISTRATOR);

/**
 * TjLms plugin for shika
 *
 * @since  1.0.0
 */

class TjlmsLikedlessonDatasource
{
	public $dataSourceName = "PLG_TJDASHBOARDSOURCE_TJLMS_LIKED_LESSON";

	protected $tjlmsLessonHelper;

	/**
	 * Function to get data of the whole block
	 *
	 * @return Array data.
	 *
	 * @since 1.0.0
	 */
	public function getData()
	{
		try
		{
			$path = JPATH_SITE . '/components/com_tjlms/helpers/lesson.php';

			if (!class_exists('TjlmsLessonHelper'))
			{
				// Require_once $path;
				JLoader::register('TjlmsLessonHelper', $path);
				JLoader::load('TjlmsLessonHelper');
			}

			$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

			if (!class_exists('ComtjlmsHelper'))
			{
				// Require_once $path;
				JLoader::register('ComtjlmsHelper', $path);
				JLoader::load('ComtjlmsHelper');
			}

			$this->tjlmsLessonHelper = new TjlmsLessonHelper;
			$comtjlmsHelper = new ComtjlmsHelper;
			$no_of_lessons = 5;

			$user = Factory::getUser();
			$olUserid = $user->id;
			$db    = Factory::getDBO();
			$query  = $db->getQuery(true);
			$query->select('c.id,lesson.title as title,lesson.id,lesson.format,lesson.no_of_attempts,lesson.course_id');
			$query->from('#__jlike_content as c');
			$query->join('INNER', '#__jlike_likes as l ON l.content_id=c.id');
			$query->join('INNER', '#__tjlms_lessons as lesson ON lesson.id=c.element_id');

			// Durgesh Added for checking course state before showin lesson

			$query->join('LEFT', '#__tjlms_courses as course ON course.id=lesson.course_id');
			$query->where(' course.state = 1');

			$query->leftjoin('`#__categories` as cat ON cat.id = course.catid');
			$query->where(' cat.published=1');

			$query->join('INNER', '#__tjlms_modules as md  ON md.id=lesson.mod_id');
			$query->where('md.state = 1');

			// Durgesh Added for checking course state before showin lesson

			$query->where('c.element="com_tjlms.lesson" AND l.like=1 AND l.userid=' . $olUserid . ' LIMIT 0,' . $no_of_lessons);

			$db->setQuery($query);
			$yourLikedLessons = $db->loadObjectlist();

			$params = ComponentHelper::getParams('com_tjlms');
			$launch_lesson_full_screen = $params->get('launch_full_screen', '0', 'INT');	/* Open lesson in lightbox*/

			foreach ($yourLikedLessons as $ind => $lesson)
			{
					$yourLikedLessons[$ind]->attemptsdonebyuser = $this->tjlmsLessonHelper->getlesson_total_attempts_done($lesson->id, $olUserid);
					$hovertitle = " title='" . Text::_('COM_TJLMS_LAUNCH_LESSON_TOOLTIP') . "'";
					$active_btn_class = 'btn-small btn-primary';

					$lesson_url = $comtjlmsHelper->tjlmsRoute("index.php?option=com_tjlms&view=lesson&lesson_id=" . $lesson->id . "&tmpl=component&cid=" . $lesson->course_id, false);

					$onclick =	"open_lessonforattempt('" . addslashes($lesson_url) . "','" . $launch_lesson_full_screen . "');";

					$yourLikedLessons[$ind]->content = '
														<div class="col-xs-8">
															' . $lesson->title . '
														</div>
														<div class="col-xs-2">
															<a ' . $hovertitle . ' class="btn ' . $active_btn_class . ' " href="' . $lesson_url . '" target=”_blank”>
																<span class="lesson_attempt_action hidden-xs hidden-sm">' . Text::_("COM_TJLMS_LAUNCH") . '</span>
																<span class="glyphicon glyphicon-play hidden visible-sm visible-xs" aria-hidden="true"></span>
															</a>
														</div>
														';
			}
		}
		catch (Exception $e)
		{
			throw new Exception($e->getMessage());
		}

		return $yourLikedLessons;
	}

	/**
	 * Get Data for Tabulator Table
	 *
	 * @return string dataArray
	 *
	 * @since   1.0
	 * */
	public function getDataTabulatorTjdashtable()
	{
		$items = [];
		$items['data'] = $this->getData();

		$items['columns'] = array(
								array("title" => 'Lesson Title',"field" => 'content',"formatter" => 'html'),
								);

		return json_encode($items);
	}

	/**
	 * Get supported Renderers List
	 *
	 * @return array supported renderes for this data source
	 *
	 * @since   1.0
	 * */
	public function getSupportedRenderers()
	{
		return array('tabulator.tjdashtable' => "PLG_TJDASHBOARDRENDERER_TABULATOR");
	}
}
