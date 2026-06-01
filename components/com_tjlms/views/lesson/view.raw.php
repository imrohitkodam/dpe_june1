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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
jimport('joomla.application.component.view');

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
		$input = Factory::getApplication()->input;
		$this->model = $this->getModel();
		$this->tjlmshelperObj = new comtjlmsHelper;
		$this->tjlmsdbhelper = new tjlmsdbhelper;
		$this->comtjlmsScormHelper = new comtjlmsScormHelper;
		$this->user_id = Factory::getUser()->id;
		$params = ComponentHelper::getParams('com_tjlms');
		$this->allowAssocFiles = $params->get('allow_associate_files', '0', 'INT');

		/*
		Get lesson data from tjlms_lesson table
		$this->lesson_id = $input->get('lesson_id', '', 'INT');
		$this->attempt = $attempts = $input->get('attempt', '', 'INT');
		$this->askforattempt = $attempts = $input->get('askforattempt', '0', 'INT');
		$this->coursetrack = $input->get('coursetrack', 1, 'INT');
		$this->fullscreen = $input->get('fs', '0', 'INT');
		$this->mode = $input->get('mode', '', 'STRING');
		$this->lesson_data = $model->getlessondata($this->lesson_id);

		If last attempt is incomplete, ask user for input of old/new attempt
		if ($this->askforattempt == 1)
		{
			$this->lastattempt = $input->get('lastattempt', '1', 'INT');

			Show user how much content he has viewed/accessed in last attempt
			$this->lastattempttracking_data = $model->gettrackingData($this->lesson_id, $this->user_id, $this->lastattempt);
		}
		elseif ($this->attempt > 0)
		{
			Get lesson type data according to type
			$this->lesson_typedata = $model->getlesson_typedata($this->lesson_id, $this->lesson_data->format);

			if ($this->lesson_data->format == 'scorm')
			{
				if ($this->lesson_typedata->scormtype == 'native')
				{
					$this->format = 'scorm';
					$this->formatid = $this->lesson_typedata->id;
					$this->scorm = $this->formatid;
					$this->entrysco = $input->get('sco_id', $this->lesson_typedata->entry, 'INT');
					$this->scorm_data = $this->lesson_typedata;
				}
			}
			elseif ($this->lesson_data->format == 'tmtQuiz')
			{
				$this->format = 'quiz';
				$this->formatid = $this->lesson_typedata->id;

				Factory::getApplication()->input->set('id',$this->lesson_typedata->test_id);
				jimport('joomla.application.component.modelform');
				JLoader::import('testpremise', JPATH_SITE . DS . 'components' . DS . 'com_tmt' . DS . 'models');

				$testsModel = new TmtModelTestpremise;
				$this->item = $testsModel->getData($this->lesson_typedata->lesson_id, $this->attempt);
				$lang = Factory::getLanguage();
				$lang->load('com_tmt', JPATH_SITE);
			}
			else
			{
				$this->formatid = $this->lesson_data->media_id;
				$this->format = $this->lesson_typedata->format;
				$this->sub_format = $this->lesson_typedata->sub_format;
				$this->formatid = $this->lesson_typedata->id;
				$this->source = $this->lesson_typedata->source;
				$this->pluginToTrigger = $this->lesson_typedata->pluginToTrigger;
				$params = json_decode($this->lesson_typedata->params);

				if (isset($params->document_id))
				{
					$this->document_id = $params->document_id;
				}

				if ($this->user_id)
				{
					Show user how much content he has viewed/accessed in last attempt
					$this->lastattempttracking_data = $model->gettrackingData($this->lesson_id, $this->user_id, $this->attempt);
				}
			}

			if ($this->mode != 'preview')
			{
				$dispatcher = JDispatcher::getInstance();
				PluginHelper::importPlugin('system');
				$dispatcher->trigger('onAfterLessonAttemptstarted', array( $this->lesson_id, $this->attempt, $this->user_id ));
			}
		}

		Get itemidof all courses view
		$this->allcousresItemid = $this->tjlmshelperObj->getItemId('index.php?option=com_tjlms&view=courses&layout=all');

		/*
		if(JRequest::getVar('layout')=='attempts')
		{
		$attempt_details=	$this->get('AttemptsByUser');
		$this->assignRef('attempt_details', $attempt_details);
		}
		/*$coursedetails=comtjlmsHelper::getDetails();
		$this->assignRef('coursedetails', $coursedetails);*/
		/*$SCOData = $model->getSCOData(JPATH_SITE.'/components/com_tjlms/courses/'.$SCOInstanceID.'/course/imsmanifest.xml');
		 $this->assignRef('SCOData', $SCOData);	*/

		/*if ($input->get('action', '', 'string') == 'edit' || $input->get('action', '', 'string') == 'add')
		{
			$this->lesson_typedata = $model->getlesson_typedata($this->lesson_id, $this->lesson_data->format);
		}*/

		parent::display($tpl);
	}
}
