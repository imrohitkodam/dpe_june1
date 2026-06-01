<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_Community_Course
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
jimport('joomla.plugin.plugin');

if (!defined('DS'))
{
	define('DS', DIRECTORY_SEPARATOR);
}

/**
 * PlgCommunitycourse class.
 *
 * @since  1.8
 */
class PlgCommunitycourse extends CApplications
{
	/**
	 * Function used to create a stream
	 *
	 * @param   OBJECT  $act  Activity object
	 *
	 * @return  object
	 *
	 * @since  1.0.0
	 */
	public function onCommunityStreamRender($act)
	{
		$db = Factory::getDbo();

		// Atach stylesheet
		$document 	= Factory::getDocument();
		$css		= JURI::base() . 'plugins/community/course/assets/style.css';
		$document->addStyleSheet($css);

		// Only use if theres any language file
		CMSPlugin::loadLanguage('plg_community_course', JPATH_ADMINISTRATOR);

		$actor = CFactory::getUser($act->actor);
		$actorLink = '<a class="cStream-Author" href="' . CUrlHelper::userLink($actor->id) . '">' . $actor->getDisplayName() . '</a>';
		$targetLink = '';

		if (isset($act->target) && $act->target)
		{
			$target = CFactory::getUser($act->target);
			$targetLink = '<a class="cStream-Author" href="' . CUrlHelper::userLink($target->id) . '">' . $target->getDisplayName() . '</a>';
		}

		$stream    = new stdClass;
		$stream->actor  = $actor;

		// Add Table Path for courses...@to do ....for lessons
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

		$verb = strtolower($act->app);
		$params = json_decode($act->params);

		if ($verb == 'course.enroll' || $verb == 'course.course_created' || $verb == 'course.course_completed' || $verb == 'course.course_recommended')
		{
			$stream->headline = Text::sprintf('COM_TJLMS_COURSE_' . strtoupper($act->app), $actorLink, $targetLink);
			$stream->message = $this->_getCourseHtml($params->id);
		}
		elseif ($verb == 'course.attempt' || $verb == 'course.attempt_end')
		{
			$lesson = Table::getInstance('lesson', 'TjlmsTable', array('dbo', $db));
			$lesson->load($params->lessonId);
			$course = Table::getInstance('course', 'TjlmsTable', array('dbo', $db));
			$course->load($params->id);

			$courseLink = '<a href="' . $course->getCourseUrl() . '"><b>' . $course->title . '</b></a>';
			$stream->headline = Text::sprintf('COM_TJLMS_COURSE_' . strtoupper($act->app), $actorLink, $params->attempt, $lesson->name, $courseLink);
			$stream->message = '';
		}

		return $stream;
	}

	/**
	 * Function used to get the html for the course activity
	 *
	 * @param   INT  $courseId  Course ID
	 *
	 * @return  html
	 *
	 * @since  1.0.0
	 */
	public function _getCourseHtml($courseId)
	{
		$db = Factory::getDbo();

		$course = Table::getInstance('course', 'TjlmsTable', array('dbo', $db));
		$course->load($courseId);

		$html = '<div class="media tjlms-jom-activity-pin">
					<a class="pull-left" href="' . $course->getCourseUrl() . '">
						<img class="media-object" src="' . $course->getCourseImage('S_') . '" alt="' . $course->title . '" >
					</a>
					<div class="media-body">
						<div class="media-heading"> <a href="' . $course->getCourseUrl() . '"><b>' . $course->title . '</b></a> </div>
						<div class="media">' . $course->short_desc . '</div>
					</div>
				</div>';

		return $html;
	}
}
