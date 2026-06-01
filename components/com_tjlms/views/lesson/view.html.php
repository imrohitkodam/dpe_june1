<?php
/**
 * @package     Shika
 * @subpackage  com_tjlms
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2021 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.view');

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Class for Lesson View
 *
 * @since  1.0.0
 */
class TjlmsViewlesson extends HtmlView
{
	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 *
	 * @since  1.0.0
	 */
	public function display($tpl = null)
	{
		$app                       = Factory::getApplication();
		$input                     = $app->input;
		$this->model               = $this->getModel();
		$this->tjlmshelperObj      = new comtjlmsHelper;
		$this->tjlmsdbhelper       = new tjlmsdbhelper;
		$this->comtjlmsScormHelper = new comtjlmsScormHelper;
		$this->tjlmsCoursesHelper  = new TjlmsCoursesHelper;
		$this->tjlmsLessonHelper   = new TjlmsLessonHelper;
		$this->comtjlmsHelper      = new comtjlmsHelper;
		$this->olUser              = Factory::getUser();
		$this->user_id             = $this->olUser->id;
		$courseModel               = BaseDatabaseModel::getInstance('Course', 'TjlmsModel');
		$isAdmin                   = $input->get('isAdmin', 0);

		// Get TjLms params
		$this->tjlmsparams = ComponentHelper::getParams('com_tjlms');

		// Check if debug is on
		$jConfig      = Factory::getConfig();
		$this->jDebug = $jConfig->get('debug');

		// Get component params
		$params                          = ComponentHelper::getParams('com_tjlms');
		$this->allowAssocFiles           = $params->get('allow_associate_files', '0', 'INT');	/* Allowed associating files for lesson*/
		$this->launch_lesson_full_screen = $params->get('launch_full_screen', '0', 'INT');	/* Open lesson in lightbox*/
		$this->showPlaylist              = $params->get('tjlms_lesson_playlist', '0', 'INT');	/* Show lesson playlist*/

		// Get lesson id
		$this->lesson_id = $input->get('lesson_id', 0, 'INT');
		$this->course_id = $input->get('cid', 0, 'INT');

		// Get callback URL
		$callbackUrl       = $input->get('returnUrl', '', 'STRING');

		// Mode=preview is defined if we are viewing the lesson in preview in backend
		$this->mode = $input->get('mode', '', 'STRING');

		$this->lesson      = $this->model->getlessondata($this->lesson_id);

		if (!$this->lesson_id || (!$this->lesson->course_id && !$this->lesson->in_lib && $this->mode != 'preview'))
		{
			$app->enqueueMessage(Text::_('COM_TJLMS_LESSON_FORMAT_NOT_ADDED'), 'warning');
            $app->setHeader('status', 500, true);

			return false;
		}

		$this->lesson_data = $this->lesson;

		// Check if callback URL present
		if (!empty($callbackUrl))
		{
			$this->returnUrl = base64_decode($callbackUrl);
		}
		else
		{
			$this->returnUrl   = $this->tjlmshelperObj->tjlmsRoute('index.php?option=com_tjlms&view=lessons', false);

			if (!$this->course_id)
			{
				$this->course_id = $this->lesson->course_id;
			}

			if ($this->course_id)
			{
				$this->course = $courseModel->getcourseinfo($this->course_id);

				if (!$this->course->id || ($this->course->state == 0 && !$isAdmin) || ($this->mode != 'preview' && $this->course->authorized == 0))
				{
					$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            		$app->setHeader('status', 500, true);

					return false;
				}

				$this->returnUrl = $this->tjlmshelperObj->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $this->course->id, false);
			}
		}

		$this->inValidUrl              = 1;
		$this->attempt                 = 1;
		$this->openModuleId            = $this->askforinput = 0;
		$this->usercanAccess['access'] = 1;

		if (!empty($this->lesson))
		{
			// Lesson layout Start
			$active       = $app->getMenu()->getActive();
			$temp         = clone $this->tjlmsparams;

			// Convert the params field to an array.
			$item     = new stdclass;
			$registry = new Registry;
			$registry->loadString($this->lesson->params);

			$item->params = $registry;

			if ($active)
			{
				$currentLink = $active->link;

				// If the current view is the active item and an lesson view for this lesson, then the menu item params take priority
				if (strpos($currentLink, 'view=lesson') && (strpos($currentLink, '&lesson_id=' . (string) $this->lesson->id)))
				{
					// Load layout from active query (in case it is an alternative menu item)
					if (isset($active->query['layout']))
					{
						$this->setLayout($active->query['layout']);
					}
					// Check for alternative layout of lesson
					elseif ($layout = $item->params->get('lesson_layout'))
					{
						$this->setLayout($layout);
					}
				}
				else
				{
					$temp->merge($item->params);
					$item->params = $temp;

					if (!empty($item->params->get('lesson_layout')))
					{
						$this->setLayout($item->params->get('lesson_layout'));
					}
				}
			}
			else
			{
				$temp->merge($item->params);
				$item->params = $temp;

				if (!empty($item->params->get('lesson_layout')))
				{
					$this->setLayout($item->params->get('lesson_layout'));
				}
			}

			if (!($this->lesson->format && $this->lesson->media_id))
			{
				$app->enqueueMessage(Text::_('COM_TJLMS_LESSON_FORMAT_NOT_ADDED'), 'error');
				$app->setHeader('status', 500, true);

				return false;
			}

			$this->inValidUrl = 0;
			$this->format     = $this->lesson->format;

			if (LMS_COURSE_SCROLLTOLASTACCESSEDLESSON == '1' && empty($callbackUrl))
			{
				$this->returnUrl .= "#" . $this->lesson->alias;
			}

			if ($this->mode == 'preview')
			{
				$this->attempt      = 1;
				$this->showPlaylist = 0;
			}

			if ($this->mode != 'preview')
			{
				$this->usercanAccess = $this->model->canUserLaunch($this->lesson->id, $this->user_id);
			}

			if ($this->usercanAccess['access'] == 1 && $this->mode != 'preview')
			{
				/* Get data required for launching the lesson*/
				if ($this->user_id > 0)
				{
					// Get total attempts done by user
					$this->attemptsdonebyuser = $this->tjlmsLessonHelper->getlesson_total_attempts_done($this->lesson_id, $this->user_id);

					$this->allowedAttepmts    = $this->lesson->no_of_attempts;

					/* Check for last attempt
					ATTEMPT == 0 --> FOR OLD ATTEMPT....WILL ASK FOR RESUME
					ATTEMPT + 1 --> NEW ATTEMPT
					ATTEMPT -1 --> ATTMEPT NOT ALLOWED*/
					$attemptDone  = $this->attemptsdonebyuser;
					$attemptcheck = $this->tjlmsLessonHelper->getAttempttobeLaunched($this->lesson->id);

					if ($attemptcheck > 0)
					{
						$this->attempt = $attemptcheck;
					}
					elseif ($attemptcheck == 0)
					{
						$this->attempt = $this->attemptsdonebyuser;

						if ( $input->get('lessonscreen', '', 'INT') != 1)
						{
							// The askforinput is set if last attempt is incomplete
							$this->askforinput = 1;

							$lesson_typedata = $this->model->getlesson_typedata($this->lesson_id, $this->lesson->format);
							$subformat       = explode('.', $lesson_typedata->sub_format);

							$quizArray = array("tmtQuiz", "form", "quiz", "exercise", "feedback");

							if (in_array($this->lesson->format, $quizArray))
							{
								$this->askforinput = 0;
							}

							if (!empty($subformat))
							{
								$plugin            = PluginHelper::getPlugin('tj' . $lesson_typedata->format, $subformat[0]);
								$params            = new Registry($plugin->params);
								$this->askforinput = (int) $params->get('show_resume', '1');
							}

							$this->lastattempttracking_data = $this->model->gettrackingData($this->lesson, $this->user_id, $this->attempt);
						}
					}
				}

				// JLike params used to show the Jlike toolbar
				$this->jLikepluginParams = '';

				$jLikeplugin = PluginHelper::getPlugin('content', 'jlike_tjlmslesson');

				if (!empty($jLikeplugin))
				{
					/*$this->jLikepluginParams = json_decode($jLikeplugin->params);*/

					// Get Params each component
					PluginHelper::importPlugin('content', 'jlike_tjlmslesson');
					$paramsArray             = Factory::getApplication()->triggerEvent('onJlikeTjlmslessonGetParams', array());
					$this->jLikepluginParams = !empty ($paramsArray[0]) ? $paramsArray[0] : '';
				}

				// Get comment count
				BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_jlike/models');
				$jlikemodel           = BaseDatabaseModel::getInstance('jlike_Likes', 'JlikeModel', array('ignore_request' => true));
				$this->comments_count = $jlikemodel->getCommentsCount($this->lesson_id, 'com_tjlms.lesson', '0');

				if (is_array($this->comments_count))
				{
					$this->comments_count = $this->comments_count[0];
				}
			}

			if ($this->askforinput == 0)
			{
				if ($this->attempt > 0)
				{
					// Get lesson type data according to type
					$this->lesson_typedata = $this->model->getlesson_typedata($this->lesson_id, $this->lesson->format);

					$this->formatid        = $this->lesson_typedata->id;
					$this->format          = $format = $this->lesson_typedata->format;
					$this->sub_format      = $this->lesson_typedata->sub_format;
					$this->source          = $this->lesson_typedata->source;
					$this->sourcefilename  = $this->lesson_typedata->sourcefilename;
					$this->pluginToTrigger = $this->lesson_typedata->pluginToTrigger;
					$this->params          = json_decode($this->lesson_typedata->params);

					if (!empty($this->lesson_typedata->$format))
					{
						$this->additionalReqData = (array) $this->lesson_typedata->$format;
					}

					if ($this->user_id)
					{
						// Show user how much content he has viewed/accessed in last attempt
						$this->lastattempttracking_data = $this->model->gettrackingData($this->lesson, $this->user_id, $this->attempt);
					}
				}
			}

			if ($this->showPlaylist == 1)
			{
				$this->course_categories   = tjlmsCoursesHelper::getCatHierarchyLink($this->course->catid, 'com_tjlms');

				$TjlmsModelcourse          = BaseDatabaseModel::getInstance('course', 'TjlmsModel');
				$this->course_trainingdata = $TjlmsModelcourse->getCourseTocdetails($this->course_id);

				$this->module_data         = $this->course_trainingdata['toc'];

				$this->modules_present     = count($this->module_data);

				// No of Moduels present in lesson
				$this->modules_present     = count($this->module_data);

				// No of lessons present in the course
				$this->lesson_count        = $this->course_trainingdata['lesson_count'];

				// If only one lesson present in the course do not show playlist
				$this->showPlaylist        = ($this->lesson_count > 1) ? 1 : 0;

				$temp               = array_keys($this->module_data);
				$firstModuleId      = $temp[0];

				$lastAccessedModule = $this->tjlmsLessonHelper->getLessonColumn(
										$this->lesson_id, array('mod_id')
										);

				if (isset($lastAccessedModule->mod_id) && $lastAccessedModule->mod_id && array_key_exists($lastAccessedModule->mod_id, $this->module_data))
				{
					$firstModuleId = $lastAccessedModule->mod_id;
				}

				$this->openModuleId = $firstModuleId;
			}
			/* end playlist */

			$this->_prepareDocument();
		}

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	protected function _prepareDocument()
	{
		$app   = Factory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		/*$title = $this->tjlmsparams->get('page_title', '');*/

		$title = $this->lesson->title;

		if (empty($title))
		{
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}

		$this->document->setTitle($title);
	}
}
