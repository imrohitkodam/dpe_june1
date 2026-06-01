<?php
/**
 * @package     TJLms
 * @subpackage  com_shika
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView;
jimport('joomla.application.component.view');

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

HTMLHelper::_('script', 'media/com_tjlms/js/tjlms.js');

/**
 * Class for Course View
 *
 * @since  1.0.0
 */
class TjlmsViewcourse extends HtmlView
{
	public $enable_tags;

	protected $tjlmsLessonHelper;

	protected $allowFlexiEnrolments;

	protected $eCtrackingData;

	public $form;

	public $lessonModel;

	public $enrollmentModel;

	public $customFields;

	public $openModuleId;

	public $checkIfUserEnroled;

	public $userCanAccess;

	public $allowCreator;

	public $courseUserOrderInfo;

	public $autoEnroll;

	public $canAutoEnroll = 0;

	public $canEnroll;

	public $modCourseBlocksParams;

	public $certificateExpired;

	public $certficateId;

	public $courseLayout;

	public $checkPrerequisiteCourseStatus;

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
		$app             = Factory::getApplication();
		$model           = $this->getModel();
		$input           = $app->input;
		$this->oluser    = Factory::getUser();
		$this->oluser_id = $this->oluser->id;
		$this->form      = $this->get('Form');
		$this->item      = $this->get('Data');
		$this->state     = $this->get('State');
		$document        = Factory::getDocument();

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			$app->enqueueMessage(implode("\n", $errors), 'warning');

			return false;
		}

		$this->tjlmsFrontendHelper    = new comtjlmsHelper;
		$this->tjlmshelperObj         = new comtjlmsHelper;
		$this->tjlmsLessonHelper      = new TjlmsLessonHelper;
		$this->tjlmsCoursesHelper     = new TjlmsCoursesHelper;
		$this->lessonModel            = BaseDatabaseModel::getInstance('lesson', 'TjlmsModel');
		$this->enrollmentModel        = BaseDatabaseModel::getInstance('Enrolment', 'TjlmsModel');
		$this->comtjlmstrackingHelper = new comtjlmstrackingHelper;
		$this->techjoomlaCommon       = new TechjoomlaCommon;

		$courseLink    = 'index.php?option=com_tjlms&view=course&id=' . $this->item->id;
		$itemId        = $this->tjlmsFrontendHelper->getitemid($courseLink);

		$absCourseurl  = $this->tjlmshelperObj->tjlmsRoute($courseLink, false);
		$this->absrurl = Uri::root() . substr($absCourseurl, strlen(Uri::base(true)) + 1);
		$this->relrUrl = base64_encode($courseLink . '&Itemid=' . $itemId);

		/**
		 * Check for no 'access-view,
		 * - Redirect guest users to login
		 * - Deny access to logged users with 403 code
		 */
		if ($this->item->params->get('access-view') == false )
		{
			$return = base64_encode(Uri::getInstance());

			if ($this->oluser->get('guest'))
			{
				$login_url_with_return = Route::_('index.php?option=com_users&view=login&return=' . $return);
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
				$app->redirect($login_url_with_return, 403);
			}
			else
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
				$app->setHeader('status', 403, true);

				return;
			}
		}

		$this->modCourseBlocksParams = new Registry;

		if (ModuleHelper::isEnabled('mod_lms_course_blocks'))
		{
			$module = ModuleHelper::getModule('mod_lms_course_blocks');
			$this->modCourseBlocksParams->loadString($module->params);
		}

		// Get TjLms params
		$this->tjlmsparams = $this->state->get('params');

		// Get whether the admin has allowed flexi enrolment
		$this->allowFlexiEnrolments = $this->tjlmsparams->get('allow_flexi_enrolments', '0', 'INT');

		// Get Social integration set
		$this->integration = $this->tjlmsparams->get('social_integration');

		// Get if launch lesson in full screen is set
		$this->launch_lesson_full_screen = $this->tjlmsparams->get('launch_full_screen');

		// Get auto enroll is set
		$this->autoEnroll = $this->tjlmsparams->get('auto_enroll');

		$courseImageSize = $this->tjlmsparams->get('course_detail_view_image_size', 'S_');

		if ($courseImageSize != 'S_')
		{
			$this->item->image = $this->item->originalImage;

			$this->item->image = $this->tjlmsCoursesHelper->getCourseImage((array) $this->item, $courseImageSize);
		}

		/*$this->course_id = $input->get('id', '', 'INT');

		if (!$this->course_id)
		{
			$this->course_id = $input->get('course_id', '', 'INT');
		}

		$course_id  = $this->course_id;*/

		// @if ($this->course_id)
		// {

			// $this->assignment_due_date = $model->getAssignedDueDate($course_id, $this->oluser_id);
			// $this->course_info = $model->getcourseinfo($course_id);

		$this->course_id = $this->item->id;

		// Course layout Start
		$active = $app->getMenu()->getActive();

		// $temp         = clone $this->tjlmsparams;
		// $item = new stdClass;

		if ($active)
		{
			$currentLink = $active->link;

			// If the current view is the active item and an course view for this course, then the menu item params take priority
			if (strpos($currentLink, 'view=course') && (strpos($currentLink, '&id=' . (string) $this->course_id)))
			{
				// Load layout from active query (in case it is an alternative menu item)
				if (isset($active->query['layout']))
				{
					$this->setLayout($active->query['layout']);
				}
				// Check for alternative layout of course
				elseif ($layout = $this->item->params->get('course_layout'))
				{
					$this->setLayout($layout);
				}
			}
			else
			{
				/*$temp->merge($this->item->params);
				$item->params = $temp;*/

				if (!empty($this->item->params->get('course_layout')))
				{
					$this->setLayout($this->item->params->get('course_layout'));
				}
			}
		}
		else
		{
			/*$temp->merge($this->item->params);
			$item->params = $temp;*/

			if (!empty($this->item->params->get('course_layout')))
			{
				$this->setLayout($this->item->params->get('course_layout'));
			}
		}

		// Get the enrolment details
		// $this->enrolDetails = $this->enrollmentModel->getEnrolmentDetails($this->oluser, $this->item);

		$this->courseTrack = $this->comtjlmstrackingHelper->getCourseTrackEntry($this->item->id, $this->oluser_id);

		$this->totalEnrolledUsers = $this->tjlmsFrontendHelper->getCourseEnrolledUsers($this->item->id);

		$courseTrack = TjLms::Coursetrack($this->oluser_id, $this->item->id);
		$this->courseProgress = $courseTrack->getProgress($this->courseTrack);
		$this->moduleData = $courseTrack->getCourseResumeModule($this->item->toc);
		$this->lessonCompletionData = $courseTrack->getModuleProgress($this->moduleData['currentModule']);

		// No of Moduels present in lesson
		$this->modules_present = count($this->item->toc);

		// No of lessons present in the course
		$this->lesson_count = $this->item->lesson_count;

		// Get the last accessed module to keep it open
		$tempArr = array_keys($this->item->toc);
		$this->openModuleId = 0;

		if (!empty($tempArr[0]))
		{
			$this->openModuleId = $tempArr[0];
		}

		$lastAccessed = $model->getLastAccessedFromCourse($this->course_id, $this->oluser_id);
		$this->lastAttemptedLessonId = $lastAccessed['lessonId'];

		if (!empty($lastAccessed['moduleId']) && array_key_exists($lastAccessed['moduleId'], $this->item->toc))
		{
			$this->openModuleId = $lastAccessed['moduleId'];
		}

		$this->checkIfUserEnroled = $model->checkifuserenroled($this->course_id, $this->oluser_id, $this->item->type);

		// Get the course categories to be shown on mobile view
		$this->course_categories = tjlmsCoursesHelper::getCatHierarchyLink($this->item->catid, 'com_tjlms');

		// Decide if user can access the lessons of the course and also if LMS should track the attempts
		$this->userCanAccess = 0;

		if ($this->oluser_id && $this->checkIfUserEnroled == 1)
		{
			$this->userCanAccess = 1;
		}

		$this->allowCreator = $this->tjlmsparams->get('allow_creator');

		if ($this->allowCreator == 1)
		{
			if ($this->oluser_id == $this->item->created_by)
			{
				$this->userCanAccess = 1;
			}
		}

		// Check for auto enrollment
		if ($this->oluser_id && !$this->userCanAccess && !$this->item->enrolled)
		{
			$this->canAutoEnroll = 1;
		}

		// Check enrollment permisson to user.
		$this->canEnroll  = $this->oluser->authorise('core.enroll', 'com_tjlms.course.' . $this->course_id);

		// Course access 1=public 5=guest
		if (empty($this->oluser_id) && ($this->item->access == 1 || $this->item->access == 5) && $this->item->type == 0)
		{
			$this->userCanAccess = 1;
		}

		// If course is paid,
		if ($this->item->type == 1)
		{
			// Get its order info
			$this->courseUserOrderInfo = $model->course_user_order_info($this->course_id);
		}

		// Get course details URL
		$this->courseItemId     = $this->tjlmsFrontendHelper->getItemId('index.php?option=com_tjlms&view=course&id=' . $this->item->id);
		$courseDetailsUrl       = $this->tjlmshelperObj->tjlmsRoute('index.php?option=com_tjlms&view=course&id=' . $this->item->id, false);
		$this->courseDetailsUrl = Uri::root() . substr($courseDetailsUrl, strlen(Uri::base(true)) + 1);

		// Get reviews and comment html
		PluginHelper::importPlugin('content');
		$this->onAftercourseContent = Factory::getApplication()->triggerEvent('onAftercourseContent', array("com_tjlms.course", $this->item->id, $this->item->title));

		if (!empty($this->onAftercourseContent))
		{
			$this->onAftercourseContent = trim(implode("\n", $this->onAftercourseContent));
		}

		$this->courseRating = Factory::getApplication()->triggerEvent('onGetTjlmsCourseAvgRating', array("com_tjlms.course", $this->item->id, $this->item->title));

		if (!empty($this->courseRating))
		{
			$this->courseRating = trim(implode("\n", $this->courseRating));
		}

		// Get certificate data for display certificate
		$certificateData = $model->checkCertificateIssued($this->course_id, $this->oluser_id);
		$this->certficateId = !empty($certificateData[0]->id) ? $certificateData[0]->id : '';

		if ($this->certficateId)
		{
			JLoader::import('components.com_tjcertificate.includes.tjcertificate', JPATH_ADMINISTRATOR);
			$tjCert              = TJCERT::Certificate();
			$certificateObj      = $tjCert->validateCertificate($certificateData[0]->unique_certificate_id);

			if (!$certificateObj->id)
			{
				$this->certificateExpired = 1;
			}
		}

		// Create object for send data for google analytics.
		$this->eCtrackingData     = array();
		$courseData               = new stdClass;
		$courseData->id           = $this->item->id;
		$courseData->title        = $this->item->title;
		$categoryTitle            = strip_tags(implode("-", $this->course_categories));
		$courseData->category     = $categoryTitle;
		$courseData->price        = '';
		$courseData->variant      = '';
		$courseData->step_number  = 1;
		$courseData->quantity     = 1;
		$courseData->brand        = '';
		$courseData->option       = '';
		$courseData->subscription = '';

		$dimension = $this->item->params->get('ga_product_type_dimension');

		$courseData->productTypeDimensionValue = $dimension ? $dimension : '';

		$this->eCtrackingData[] = $courseData;

		Factory::getApplication()->triggerEvent('onContentPrepare', array('com_tjlms.course', &$this->item, &$this->item->params, 0));

		$this->checkPrerequisiteCourseStatus = true;

		if (PluginHelper::isEnabled('tjlms', 'courseprerequisite'))
		{
			PluginHelper::importPlugin('tjlms');
			$this->checkPrerequisiteCourseStatus = Factory::getApplication()->triggerEvent('onCheckPrerequisiteCourseStatus', array($this->item->id, $this->oluser_id));
			$this->checkPrerequisiteCourseStatus = $this->checkPrerequisiteCourseStatus[0];
		}

		$jcFields           = isset($this->item->jcfields) ? $this->item->jcfields : array();
		$this->customFields = array();
		$courseLayout = $this->getLayout();

		foreach ($jcFields as $customField)
		{
			$this->customFields[$customField->name] = $customField;
		}

		$document->addScriptDeclaration("
			var openModuleId= " . $this->openModuleId . ";
			var courseData= " . json_encode($this->eCtrackingData) . ";
			var courseLayout='" . $courseLayout . "';
			courseData = JSON.stringify(courseData);
			courseData = JSON.parse(courseData);
			tjlms.course.init(openModuleId, courseData, courseLayout);
		");

		$this->_prepareDocument();

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
		$app = Factory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		// We need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			// Because the application sets a default page title,
			$this->tjlmsparams->def('page_heading', $this->tjlmsparams->get('page_title', $menu->title));
		}
		else
		{
			$this->tjlmsparams->def('page_heading', Text::_('COM_TJLMS_DEFAULT_PAGE_TITLE'));
		}

		if (!empty($this->course_info))
		{
			$title = $this->course_info->title;

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

			if ($this->course_info->metadesc)
			{
				$this->document->setDescription($this->course_info->metadesc);
			}
			elseif (!$this->course_info->metadesc && $this->tjlmsparams->get('menu-meta_description'))
			{
				$this->document->setDescription($this->tjlmsparams->get('menu-meta_description'));
			}

			if ($this->course_info->metakey)
			{
				$this->document->setMetadata('keywords', $this->course_info->metakey);
			}
			elseif (!$this->course_info->metakey && $this->tjlmsparams->get('menu-meta_keywords'))
			{
				$this->document->setMetadata('keywords', $this->tjlmsparams->get('menu-meta_keywords'));
			}

			if ($this->tjlmsparams->get('robots'))
			{
				$this->document->setMetadata('robots', $this->tjlmsparams->get('robots'));
			}
		}
	}
}
