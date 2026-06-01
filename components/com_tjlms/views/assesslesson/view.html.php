<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjlms
 * @copyright  Copyright (C) 2009 -2010 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       http://www.techjoomla.com
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Component\ComponentHelper;
jimport('joomla.application.component.view');

/**
 * Class for Lesson View
 *
 * @since  1.0.0
 */
class TjlmsViewAssesslesson extends HtmlView
{
	protected $user;

	protected $user_id;

	protected $canView;

	protected $canEdit;

	protected $reviewStatus;

	protected $reviewer;

	protected $tjlmsLessonHelper;

	protected $tjlmshelperObj;

	protected $lessonModel;

	protected $assessModel;

	protected $ltId;

	protected $lessonTrack;

	protected $lesson;

	protected $canAssess;

	protected $canEditOwnAssessment;

	protected $canEditAllAssess;

	protected $lessonAssessment;

	protected $trackRatings;

	protected $trackReviews;

	protected $submissionsCnt;

	protected $submissions;

	protected $lesson_typedata;

	protected $formatid;

	protected $format;

	protected $sub_format;

	protected $source;

	protected $pluginToTrigger;

	protected $params;

	protected $additionalReqData;

	protected $comparams;

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
		$app   = Factory::getApplication();
		$input = Factory::getApplication()->input;
		$this->user = $this->reviewer = Factory::getUser();
		$this->user_id  = Factory::getUser()->id;

		if (!$this->user_id)
		{
			$msg = Text::_('COM_TJLMS_MESSAGE_LOGIN_FIRST');

			// Get current url.
			$current = Uri::getInstance()->toString();
			$url = base64_encode($current);
			$app->redirect(Route::_('index.php?option=com_users&view=login&return=' . $url, false), $msg);
		}

		$this->tjlmsLessonHelper = new TjlmsLessonHelper;
		$this->tjlmshelperObj = new comtjlmsHelper;
		$this->tjlmshelperObj->getLanguageConstant();

		$this->lessonModel = BaseDatabaseModel::getInstance('lesson', 'TjlmsModel');
		$this->assessModel = BaseDatabaseModel::getInstance('assessments', 'TjlmsModel');

		$newSubmission = $oldSubmission = 0;

		// Get lesson lesson track id
		if ($input->get('reviewId', 0, 'INT'))
		{
			$assessment_id = $input->get('reviewId', 0, 'INT');
			$assessment_data = $this->assessModel->getLessonTrack($assessment_id);
			$this->ltId = $assessment_data[0];
			$this->reviewer  = Factory::getUser($assessment_data[1]);
			$oldSubmission = 1;
		}
		elseif ($input->get('ltId', 0, 'INT'))
		{
			$this->ltId = $input->get('ltId', 0, 'INT');
			$newSubmission = 1;
		}

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
		$lessonTrackTable = Table::getInstance('Lessontrack', 'TjlmsTable');
		$lessonTrackTable->load($this->ltId);
		$properties = $lessonTrackTable->getProperties(1);
		$this->lessonTrack = Joomla\Utilities\ArrayHelper::toObject($properties, 'JObject');

		$lessonId = $this->lessonTrack->lesson_id;

		$lessonTable = Table::getInstance('lesson', 'TjlmsTable');
		$lessonTable->load($lessonId);
		$properties = $lessonTable->getProperties(1);
		$this->lesson = Joomla\Utilities\ArrayHelper::toObject($properties, 'JObject');

		if (!$this->lesson->id || !$this->lessonTrack->id)
		{
			$app->enqueueMessage(Text::_('COM_TJLMS_LESSON_INVALID_URL'), 'warning');

			return false;
		}

		JLoader::register('TjlmsHelper', JPATH_ADMINISTRATOR . '/components/com_tjlms/helpers/tjlms.php');
		$this->canAssess = TjlmsHelper::canDoAssessment($lessonTable->course_id, $this->user_id);

		JLoader::import('administrator.components.com_tjlms.helpers.tjlms', JPATH_SITE);
		$hasUsers = TjlmsHelper::getSubusers();

		if (!$this->canAssess && empty($hasUsers))
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

			return false;
		}

		$this->canEditOwnAssessment = TjlmsHelper::canEditOwnAssessment($lessonTable->course_id, $this->user->id);
		$this->canEditAllAssess = TjlmsHelper::canEditAllAssessment($lessonTable->course_id, $this->user->id);

		$this->lessonAssessment = $this->assessModel->getLessonAssessments($this->lesson->id);
		$this->trackRatings = $this->assessModel->getTrackRating($this->ltId, $this->reviewer->id);
		$this->trackReviews = $this->assessModel->getTrackReviews($this->ltId, $this->reviewer->id);

		/*Get assessments submitted for attempt*/
		$this->submissionsCnt = $this->assessModel->getAssessmentSubmissionsCount($this->ltId);

		if ($this->_layout == 'submissions')
		{
			$this->submissions = $this->assessModel->getAssessmentSubmissions($this->ltId);

			if (!$this->canEditOwnAssessment  && !$this->canEditAllAssess)
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

				return false;
			}
		}
		else
		{
			/*This is to check if user is adding new submission*/
			if (empty($this->trackReviews))
			{
				if ($newSubmission && $this->submissionsCnt >= $this->lessonAssessment->assessment_attempts)
				{
					$app->enqueueMessage(Text::_('COM_TJLMS_LESSON_ASSESSMENT_ATTEMPTS_EXHAUSTED'), 'warning');

					return false;
				}
				elseif ($oldSubmission)
				{
					$app->enqueueMessage(Text::_('COM_TJLMS_LESSON_ASSESSMENT_SUBMISSION_NOTFOUND'), 'warning');

					return false;
				}
			}

			$canView = $canEdit = 1;

			$this->reviewStatus = 0;

			if (!empty($this->trackReviews))
			{
				if (!$this->trackReviews->id)
				{
					$canView = $canEdit = 1;
				}
				else
				{
					if ($this->reviewer->id == $this->user->id)
					{
						$canEdit = 0;

						if ($this->trackReviews->review_status == 0 || ($this->trackReviews->review_status == 1 && $this->canEditOwnAssessment))
						{
							$canEdit = 1;
						}
					}
					else
					{
						$canView = $canEdit = 0;

						if ($this->trackReviews->review_status == 1 && $this->canEditAllAssess)
						{
							$canView = $canEdit = 1;
						}
					}
				}

				$this->reviewStatus = $this->trackReviews->review_status;
			}

			$this->canView = $canView;
			$this->canEdit = $canEdit;

			if (!$this->canView  && !$this->canEdit)
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');

				return false;
			}
		}

		// Get lesson type data according to type
		$this->lesson_typedata = $this->lessonModel->getlesson_typedata($this->lesson->id, $this->lesson->format);
		$this->formatid = $this->lesson_typedata->id;
		$this->format = $format = $this->lesson_typedata->format;
		$this->sub_format = $this->lesson_typedata->sub_format;
		$this->source = $this->lesson_typedata->source;
		$this->pluginToTrigger = $this->lesson_typedata->pluginToTrigger;
		$this->params = json_decode($this->lesson_typedata->params);

		if (!empty($this->lesson_typedata->$format))
		{
			$this->additionalReqData = (array) $this->lesson_typedata->$format;
		}

		// Get component params
		$this->comparams = ComponentHelper::getParams('com_tjlms');

		parent::display($tpl);
	}
}
