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
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\File;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjsurvey_surveyforce', JPATH_ADMINISTRATOR);

/**
 * Survey plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjsurveySurveyforce extends CMSPlugin
{
	/**
	 * Plugin that supports uploading and tracking the videos for jWplayer plugin
	 *
	 * @param   string   &$subject  The context of the content being passed to the plugin.
	 * @param   integer  $config    Optional page number. Unused. Defaults to zero.
	 *
	 * @return  void.
	 *
	 * @since 1.0.0
	 */

	public function plgTjsurveySurveyforce(&$subject, $config)
	{
		parent::__construct($subject, $config);
	}

	/**
	 * Function to check if the scorm tables has been uploaded while adding lesson
	 *
	 * @param   INT  $lessonId  lessonId
	 * @param   OBJ  $mediaObj  media object
	 *
	 * @return  media object of format and subformat
	 *
	 * @since 1.0.0
	 */
	public function additionalsurveyforceFormatCheck($lessonId, $mediaObj)
	{
		return $mediaObj;
	}

	/**
	 * Function to get Sub Format options when creating / editing lesson format
	 * the name of function should follow standard getSubFormat_<plugin_type>ContentInfo
	 *
	 * @param   ARRAY  $config  config specifying allowed plugins
	 *
	 * @return  object.
	 *
	 * @since 1.0.0
	 */
	public function GetSubFormat_tjsurveyContentInfo($config = array('surveyforce'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			= array();
		$obj['name']	= $this->params->get('plugin_name', 'surveyforce');
		$obj['id']		= $this->_name;

		return $obj;
	}

	/**
	 * Function to get Sub Format HTML when creating / editing lesson format
	 * the name of function should follow standard getSubFormat_<plugin_name>ContentHTML
	 *
	 * @param   INT    $mod_id       id of the module to which lesson belongs
	 * @param   INT    $lesson_id    id of the lesson
	 * @param   MIXED  $lesson       Object of lesson
	 * @param   ARRAY  $comp_params  Params of component
	 *
	 * @return  html
	 *
	 * @since 1.0.0
	 */
	public function GetSubFormat_surveyforceContentHTML($mod_id, $lesson_id, $lesson, $comp_params)
	{
		$result = array();
		$plugin_name = $this->_name;
		$surveylist = $this->getSurveyList();

		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath('creator');
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function to render the video
	 *
	 * @param   ARRAY  $config  data to be used to play video
	 *
	 * @return  complete html along with script is return.
	 *
	 * @since 1.0.0
	 */
	public function renderPluginHTML($config)
	{
		$input = Factory::getApplication()->input;
		$launch_details = json_decode($config['params']);
		$layout = new FileLayout('default', $basePath = JPATH_ROOT . '/components/com_surveyforce/views/survey');
		$html = $layout->render($data);

		$lesson = $config['lesson_data'];
		$lesson_typedata = $config['lesson_typedata'];

		$html = '<script>

				jQuery( document ).ready(function()
				{
					hideImage();
					jQuery("iframe").height( jQuery(window).height() - 200);
					jQuery("iframe").width( jQuery(window).width());
					jQuery(window).resize(function()
					{
						jQuery("iframe").height( jQuery(this).height() );
					});
				});
				var lessonStartTime = new Date();
				var plugdataObject = {
					plgtype:"' . $this->_type . '",
					plgname:"' . $this->_name . '",
					plgtask:"html_updatedata",
					lesson_id: "' . $lesson->id . '",
					mode: "0"
					};
				updateData(plugdataObject);
				plugdataObject["lesson_status"] = "completed";
				</script>';

		$url = Uri::root() . 'index.php?option=com_surveyforce&view=survey&id=' . $lesson_typedata->source . '&tmpl=component';

		$html .= '<iframe name="eventFrame" id="id_eventFrame" src="' . $url . '"  width="100%" >
		 </iframe>';

		// This may be an iframe directly
		return $html;
	}

	/**
	 * function used to save time spent for html content
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 * */
	public function html_updatedata()
	{
		$input = Factory::getApplication()->input;
		$post = $input->post;
		$lesson_id = $post->get('lesson_id', '', 'INT');
		$user_id = Factory::getUser()->id;

		if (!empty ($post->get('time_spent', '', 'FLOAT')))
		{
			$db = Factory::getDBO();
			$query = $db->getQuery('true');
			$query->select('lt.*');
			$query->from('#__tjlms_lesson_track as lt');
			$query->where('lt.lesson_id = ' . $lesson_id);
			$query->where('lt.user_id = ' . $user_id);
			$db->setQuery($query);

			$usertrack = $db->loadObject();

			if (!empty ($usertrack ))
			{
				$trackObj = new stdClass;
				$trackObj->id = $usertrack->id;
				$trackObj->status = $usertrack->status;
				$trackObj->attempt = $usertrack->attempt;
				$trackObj->time_spent = $post->get('time_spent', '', 'FLOAT');

				require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';
				$comtjlmstrackingHelper = new comtjlmstrackingHelper;
				$trackingid = $comtjlmstrackingHelper->update_lesson_track($lesson_id, $user_id, $trackObj);
			}
		}

		$trackingid = json_encode($trackingid);
		echo $trackingid;
		jexit();
	}

	/**
	 * Internal use functions
	 *
	 * @return  file
	 *
	 * @since 1.0.0
	 */
	public function getSurveyList()
	{
		$db = Factory::getDBO();
		$query = $db->getQuery('true');

		$query->select('a.*');
		$query->from('#__survey_force_survs as a');
		$query->where('a.published = 1');
		$db->setQuery($query);

		return $db->loadObjectList();
	}

	/**
	 * Launch HTML function
	 *
	 * @param   INT  $eventid  eventid
	 *
	 * @return  file
	 *
	 * @since 1.0.0
	 */
	public function launchHtml($eventid)
	{
	}

	/**
	 * Internal use functions
	 *
	 * @param   STRING  $layout  layout
	 *
	 * @return  file
	 *
	 * @since 1.0.0
	 */
	public function buildLayoutPath($layout)
	{
		$app = Factory::getApplication();
		$core_file 	= dirname(__FILE__) . '/' . $this->_name . '/tmpl/' . $layout . '.php';
		$override = JPATH_BASE . '/templates/' . $app->getTemplate() . '/html/plugins/' . $this->_type . '/' . $this->_name . '/' . $layout . '.php';

		if (File::exists($override))
		{
			return $override;
		}
		else
		{
			return $core_file;
		}
	}

	/**
	 * Internal use functions
	 *
	 * @param   INT  $sid  survey id
	 *
	 * @return  file
	 *
	 * @since 1.0.0
	 */
	public function onAfterSurveyStart($sid)
	{
		$user = Factory::getUser();

		$jinput	= Factory::getApplication()->input;
		$cid 	=	$jinput->get('id', 0, 'INT');
		$attempt = 1;
		$flag = 1;

		$db = Factory::getDBO();
		$query = $db->getQuery('true');
		$query->select('ls.*');
		$query->from('#__tjlms_lessons as ls');
		$query->leftjoin('#__tjlms_media as lm ON lm.id = ls.media_id');
		$query->where('lm.source = ' . $sid);
		$query->where('lm.format LIKE "survey"');
		$db->setQuery($query);
		$lesson = $db->loadObject();

		$db = Factory::getDBO();
		$query = $db->getQuery('true');
		$query->select('lt.*');
		$query->from('#__tjlms_lesson_track lt');
		$query->where('lt.lesson_id = ' . $lesson->id);
		$query->where('lt.user_id = ' . $user->id);
		$query->order('lt.id DESC');
		$db->setQuery($query);
		$lessontrack = $db->loadObject();

		if (!empty ($lessontrack))
		{
			if ($lessontrack->lesson_status == 'completed')
			{
				$attempt = $lessontrack->attempt + 1;
			}
			else
			{
				$flag = 0;
			}
		}

		if ($flag == 1)
		{
			/* Update tjlms_lesson_tracking */
			$trackObj   = new stdClass;
			$trackObj->lesson_id		= $lesson->id;
			$trackObj->attempt			= $attempt;
			$trackObj->mode				= '';
			$trackObj->lesson_status	= 'incomplete';
			$trackObj->time_spent		= 0;

			$lesson_track_id 			= $this->updateData($user->id, $trackObj);
		}
	}

	/**
	 * Internal use functions
	 *
	 * @param   INT  $sid  survey id
	 *
	 * @return  file
	 *
	 * @since 1.0.0
	 */
	public function onAfterSurveyFinish($sid)
	{
		$user = Factory::getUser();

		$db = Factory::getDBO();
		$query = $db->getQuery('true');
		$query->select('ls.*');
		$query->from('#__tjlms_lessons as ls');
		$query->leftjoin('#__tjlms_media as lm ON lm.id = ls.media_id');
		$query->where('lm.source = ' . $sid);
		$query->where('lm.format LIKE "survey"');
		$db->setQuery($query);
		$lesson = $db->loadObject();

		if (!empty($lesson))
		{
			$query = $db->getQuery('true');
			$query->select('lt.*');
			$query->from('#__tjlms_lesson_track as lt');
			$query->where('lt.lesson_id = ' . $lesson->id);
			$query->where('lt.user_id = ' . $user->id);
			$query->order('lt.id DESC');
			$db->setQuery($query);
			$usertrack = $db->loadObject();

			$trackObj   = new stdClass;

			if (!empty($usertrack))
			{
				$trackObj->id = $usertrack->id;
				$trackObj->lesson_status	= 'completed';
				$result = $db->updateObject('#__tjlms_lesson_track', $trackObj, 'id');
			}
		}
	}

	/**
	 * Function to get needed data for this API
	 *
	 * @param   INT    $uid   user_id
	 * @param   ARRAY  $data  lesson track data
	 *
	 * @return  id from tjlms_lesson_tracking
	 *
	 * @since 1.0.0
	 */
	public function updateData($uid, $data)
	{
		$db = Factory::getDBO();

		$trackingid = '';

		$lesson_id = $data->lesson_id;
		$oluser_id = $uid;

		$trackObj = new stdClass;
		$trackObj->attempt = $data->attempt;
		$trackObj->score = 0;
		$trackObj->total_content = '';
		$trackObj->current_position = '';
		$trackObj->time_spent = '';

		if (!empty ($data->id))
		{
			$trackObj->id = $data->id;
		}

		$lesson_status	=	$data->lesson_status;

		if (!empty($lesson_status))
		{
			$trackObj->lesson_status = $lesson_status;
		}
/*
		$current_position = $data->current_position;

		if (!empty($current_position))
		{
			$trackObj->current_position = round($current_position, 2);
		}

		$total_content = $data->total_content;

		if (!empty($total_content))
		{
			$trackObj->total_content = round($total_content, 2);
		} */

		$time_spent = $data->time_spent;

		if (!empty($time_spent))
		{
			$trackObj->time_spent = round($time_spent, 2);
		}

		/*
		 $score = $data->score;
		 */

		if (!empty($score))
		{
			$trackObj->score = $score;
		}

		require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';
		$comtjlmstrackingHelper = new comtjlmstrackingHelper;

		$trackingid = $comtjlmstrackingHelper->update_lesson_track($lesson_id, $oluser_id, $trackObj);

		return $trackingid;
	}
}
