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
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

FD::import('admin:/includes/apps/apps');

/**
 * Course APP class
 *
 * @since  1.0.0
 */
class SocialUserAppCourse extends SocialAppItem
{
	/**
	 * Responsible to return the favicon object
	 *
	 * @return   OBJECT
	 *
	 * @since  1.0.0
	 */
	public function getFavIcon()
	{
		$obj            = new stdClass;
		$obj->color     = '#DC554F';
		$obj->icon      = 'fa fa-book';
		$obj->label     = 'APP_USER_COURSE_STREAM_TOOLTIP';

		return $obj;
	}

	/*public function onPrepareActivityLog(SocialStreamItem &$stream, $includePrivacy = true)
	{
		if ($stream->context != 'course') {
			return ;
		}

		$params = $this->getParams();

		$registry = FD::registry($stream->params);

		$this->set('type', $registry->get('type'));
		$this->set('actor', $stream->actor);
		$this->set('target', $stream->targets[0]);

		$stream->title = parent::display('streams/' . $stream->verb . '.title');

		if ($includePrivacy) {
			$my = FD::user();
			$privacy = FD::privacy($my->id);
			$stream->privacy = $privacy->form($stream->contextId, 'course', $stream->actor->id, 'core.view', false, $stream->aggregatedItems[0]->uid);
		}

		return true;
	}*/

	/**
	 * Prepares the stream item
	 *
	 * @param   SocialStreamItem  &$stream         The stream object.
	 * @param   bool              $includePrivacy  Determines if we should respect the privacy
	 *
	 * @return  Boolean
	 *
	 * @since   1.0
	 */
	public function onPrepareStream(SocialStreamItem &$stream , $includePrivacy = true)
	{
		// Since every stream item is triggered, we only want to process known context.
		if ($stream->context != 'course')
		{
			return;
		}

		$db = Factory::getDbo();

		// Get the actor of the stream item. This is a SocialUser object.
		$actor = $stream->actor;

		// Get the verb of the stream item since we need to have different outputs based on the verb.
		$verb = strtolower($stream->verb);

		// Add Table Path for courses...@to do ....for lessons
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

		if ($verb == 'enroll' || $verb == 'course_created' || $verb == 'course_completed' || $verb == 'course_recommended')
		{
			$course = Table::getInstance('course', 'TjlmsTable', array('dbo', $db));
			$course->load($stream->contextId);

			$this->set('actor', $stream->actor);
			$this->set('course', $course);
			$this->set('verb', $stream->verb);

			if ($verb == 'course_recommended')
			{
				$this->set('target', $stream->targets[0]);

				// Set the title of the stream
				$stream->title = parent::display('streams/course.recommend.title');
			}
			else
			{
				// Set the title of the stream
				$stream->title = parent::display('streams/course.title');
			}

			// We want to display eht contents of the textbook
			$stream->content = parent::display('streams/course.content');
		}
		elseif ($verb == 'attempt' || $verb == 'attempt_end')
		{
			$lesson = Table::getInstance('lesson', 'TjlmsTable', array('dbo', $db));
			$lesson->load($stream->contextId);

			$course = Table::getInstance('course', 'TjlmsTable', array('dbo', $db));
			$course->load($lesson->course_id);

			$this->set('actor', $stream->actor);
			$this->set('lesson', $lesson);
			$this->set('verb', $stream->verb);
			$this->set('content', $stream->content);
			$this->set('course', $course);

			// Set the title of the stream
			$stream->title = parent::display('streams/lesson.title');
		}

		return true;
	}
}
