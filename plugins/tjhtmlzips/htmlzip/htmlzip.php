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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\File;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjhtmlzips_htmlzip', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjhtmlzipsHtmlzip extends CMSPlugin
{
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
	public function onGetSubFormat_tjhtmlzipsContentInfo($config = array('htmlzip'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			= array();
		$obj['name']	= $this->params->get('plugin_name', 'Html Zip');
		$obj['id']		= $this->_name;
		$obj['assessment'] = $this->params->get('assessment', '0');

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
	public function onGetSubFormat_htmlzipContentHTML($mod_id, $lesson_id, $lesson, $comp_params)
	{
		$result = array();
		$plugin_name = $this->_name;

		// Load the layout & push variables
		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, 'creator');
		include $layout;

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Function to upload a file on server
	 * This is blank as we do not upload file on jwplayer
	 *
	 * @param   INTERGER  $lesson_id  lesson id
	 * @param   STRING    $filename   file name
	 * @param   STRING    $filepath   file path
	 *
	 * @return  true
	 *
	 * @since 1.0.0
	 */
	public function onUpload_filesOnhtmlzip($lesson_id, $filename = '', $filepath = '')
	{
		$extract_dir = JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson_id;

		if (Folder::exists($extract_dir))
		{
			Folder::delete($extract_dir);
		}

		jimport('techjoomla.common');
		$SocialLibraryObject = new TechjoomlaCommon;

		if (!$SocialLibraryObject->extractCourse($extract_dir, $filepath))
		{
			return false;
		}

		// Create htaccess file in each lessons folder to allow direct files access of the folder
		$htaccessFile	=	JPATH_SITE . '/media/com_tjlms/lessons/' . $lesson_id . '/.htaccess';

		if (!File::exists($htaccessFile))
		{
			$content = "allow from all";
			$result = File::write($htaccessFile, $content);

			if (!$result)
			{
				$app->enqueueMessage('Could not create file - ' . $htaccessFile, 'warning');
			}
		}

		return true;
	}

	/**
	 * Function to check if related tables has been uploaded while adding lesson
	 *
	 * @param   INT  $lessonId  lessonId
	 * @param   OBJ  $mediaObj  media object
	 *
	 * @return  media object of format and subformat
	 *
	 * @since 1.0.0
	 */
	public function onAdditionalhtmlzipFormatCheck($lessonId, $mediaObj)
	{
		return $mediaObj;
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
	public function onhtmlziprenderPluginHTML($config)
	{
		$input = Factory::getApplication()->input;
		$launch_details = $config['file'];

		$config['plgtask'] = 'HtmlzipUpdate';
		$config['plgtype'] = $this->_type;
		$config['plgname'] = $this->_name;
		$config['mode'] = $input->get('mode', '', 'STRING');

		$input = Factory::getApplication()->input;
		$sub_layout = $input->get('sub_layout', 'default', 'STRING');

		$assessment = $this->params->get('assessment', '0');

		$config['assessment'] = 0;

		if ($assessment)
		{
			$lesson_id = $config['lesson_id'];
			require_once JPATH_ROOT . '/components/com_tjlms/models/assessments.php';
			$assessmentModel = new TjlmsModelAssessments;

			$lessonAssessment = $assessmentModel->getLessonAssessSet($lesson_id);

			if ($lessonAssessment)
			{
				$config['assessment'] = 1;
			}
		}

		/*if ($sub_layout == 'creator')
		{
			$config['template'] = $this->buildLayoutPath('template');
		}*/

		$html = $this->buildLayout($config, $sub_layout);

		// YOUR CODE ENDS
		// This may be an iframe directlys
		return $html;
	}

	/**
	 * function used to save HtmlZIp
	 *
	 * @return id of tjlms_lesson_track
	 *
	 * @since 1.0.0
	 * */
	public function onHtmlzipUpdate()
	{
		$db = Factory::getDBO();
		$input = Factory::getApplication()->input;

		$mode = $input->get('mode', '', 'STRING');

		$trackingid = '';

		if ($mode != 'preview')
		{
			$post = $input->post;

			$lesson_id = $post->get('lesson_id', 0, "INT");
			$oluser_id = Factory::getUser()->id;

			$trackObj = new stdClass;
			$trackObj->attempt = $post->get('attempt', 0, "INT");
			$trackObj->score = $post->get('score', '', "INT");
			$trackObj->lesson_status = $post->get('lesson_status', '', "STRING");
			$trackObj->current_position = $post->get('current_position', 0, "FLOAT");
			$trackObj->time_spent = round($post->get('time_spent', 0, "STRING"), 2);

			require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';
			$comtjlmstrackingHelper = new comtjlmstrackingHelper;

			$trackingid = $comtjlmstrackingHelper->update_lesson_track($lesson_id, $oluser_id, $trackObj);
		}

		return $trackingid;
	}

	/**
	 * Builds the layout to be shown, along with hidden fields.
	 *
	 * @param   ARRAY   $vars    vars to be used
	 * @param   STRING  $layout  layout
	 *
	 * @return  html
	 *
	 * @since 1.0.0
	 */
	public function buildLayout($vars, $layout = 'default')
	{
		// Load the layout & push variables
		ob_start();
		$layout = PluginHelper::getLayoutPath($this->_type, $this->_name, $layout);
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}
}
