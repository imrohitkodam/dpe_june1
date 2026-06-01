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

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjtextmedia_htmlcreator', JPATH_ADMINISTRATOR);

/**
 * Content builder plugin
 *
 * @since  1.0.0
 */
class PlgTjtextmediaHtmlcreator extends CMSPlugin
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
	public function onGetSubFormat_tjtextmediaContentInfo($config = array('htmlcreator'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			= array();
		$obj['name']	= $this->params->get('plugin_name', 'Drag & Drop Page Builder');
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
	public function onGetSubFormat_htmlcreatorContentHTML($mod_id , $lesson_id, $lesson, $comp_params)
	{
		$html = '';
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
	 * Function to get needed data for this API
	 *
	 * @param   MIXED  $data  array
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function getData($data)
	{
		return true;
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
	public function onAdditionalhtmlcreatorFormatCheck($lessonId, $mediaObj)
	{
		return $mediaObj;
	}

	/**
	 * Function to render the document
	 *
	 * @return  complete html along with script is return.
	 *
	 * @since 1.0.0
	 */
	public function ongetpluginHtml()
	{
		/*$config['file'] = "http://player.vimeo.com/video/110168157";
		$config['lesson_id'] = 23;
		$config['attempt'] = 1;
		$config['current'] = 1;*/

		// Hardcoded for now
		$config['plgtask'] = 'html_updatedata';
		$config['plgtype'] = $this->_type;
		$config['plgname'] = $this->_name;
		$config['template'] = PluginHelper::getLayoutPath($this->_type, $this->_name, 'template');

		require_once JPATH_SITE . '/components/com_tjlms/helpers/lesson.php';

		$input = Factory::getApplication()->input;
		$lesson_id = $input->get('lesson_id', '0', 'INT');
		$config['creator_id'] = $input->get('creator_id', '0', 'INT');

		$config['source'] = '';
		$config['media_id'] = 0;
		$tjlmsLessonHelper = new TjlmsLessonHelper;
		$formatMedia = $tjlmsLessonHelper->getLessonFormatdata($lesson_id, 'm.id, m.source');

		if (!empty($formatMedia))
		{
			$config['source'] = $formatMedia->source;
			$config['media_id'] = $formatMedia->id;
		}

		$sub_layout = $input->get('sub_layout', 'default', 'STRING');
		$html = $this->buildLayout($config, $sub_layout);

		// YOUR CODE ENDS
		// This may be an iframe directlys
		return $html;
	}

	/**
	 * Function to render the HTML
	 *
	 * @param   ARRAY  $config  Data to display
	 *
	 * @return  complete html along with script is return.
	 *
	 * @since 1.0.0
	 */
	public function onhtmlcreatorrenderPluginHTML($config)
	{
		/*$config['file'] = "http://player.vimeo.com/video/110168157";
		$config['lesson_id'] = 23;
		$config['attempt'] = 1;
		$config['current'] = 1;*/

		// Hardcoded for now
		$config['plgtask'] = 'html_updatedata';
		$config['plgtype'] = $this->_type;
		$config['plgname'] = $this->_name;
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
	 * Function to save images for html content
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function onsaveHtmlImages()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Access-Control-Allow-Origin: *');
		header('Content-type:  text/html');
		$dir = JPATH_SITE . '/media/com_tjlms/lessons/public/';

		// STEP 2: Specify url path
		$path = 'media/com_tjlms/lessons/public/';

		$input = Factory::getApplication()->input;
		$post = $input->post;

		$count = $input->get('count', '1', 'INT');
		$b64str = $post->get('hidimg-' . $count, '1', 'STRING');
		$imgname = $post->get('hidname-' . $count, '1', 'STRING');
		$imgtype = $post->get('hidtype-' . $count, '1', 'STRING');

		// Generate random file name here
		if ($imgtype == 'png')
		{
			$image = $imgname . '-' . base_convert(rand(), 10, 36) . '.png';
		}
		else
		{
			$image = $imgname . '-' . base_convert(rand(), 10, 36) . '.jpg';
		}

		// Save image
		$success = file_put_contents($dir . $image, base64_decode($b64str));

		if ($success === false)
		{
			if (!file_exists($dir))
			{
				echo "<html><body onload=\"alert('Saving image to folder failed. Folder " . $dir . " not exists.')\"></body></html>";
			}
			else
			{
				echo "<html>
						<body onload=\"alert('Saving image to folder failed. Please check write permission on " . $dir . "')\">
						</body>
					</html>";
			}
		}
		else
		{
			// Replace image src with the new saved file
			echo "<html>
					<body onload=\"parent.document.getElementById('img-" . $count . "').setAttribute('src','" . $path . $image . "');
					parent.document.getElementById('img-" . $count . "').removeAttribute('id') \">
					</body>
				</html>";
		}

		jexit();
	}

	/**
	 * function used to save time spent for html content
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function onhtml_updatedata()
	{
		header('Content-type: application/json');
		$input = Factory::getApplication()->input;

		$post = $input->post;
		$lesson_id = $post->get('lesson_id', '', 'INT');
		$user_id = Factory::getUser()->id;

		$trackObj = new stdClass;

		$trackObj->current_position = $post->get('current_position', '', 'INT');
		$trackObj->total_content = $post->get('total_content', '', 'INT');
		$trackObj->time_spent = $post->get('time_spent', '', 'FLOAT');

		$trackObj->attempt = $post->get('attempt', '', 'INT');
		$trackObj->score = 0;
		$trackObj->lesson_status = $post->get('lesson_status', '', 'STRING');
		$trackObj->format = 'textmedia';

		require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';

		$comtjlmstrackingHelper = new comtjlmstrackingHelper;
		$trackingid = $comtjlmstrackingHelper->update_lesson_track($lesson_id, $user_id, $trackObj);

		/*$trackingid = $comtjlmstrackingHelper->update_lesson_track($lesson_id,$attempt,$score,
		 * $lesson_status,$user_id,$total_content,$current_position,$time_spent);*/

		$trackingid = json_encode($trackingid);
		echo $trackingid;
		jexit();
	}

	/**
	 * Function to save html content
	 *
	 * @return  void
	 *
	 * @since 1.0.0
	 */
	public function onsaveHtmlContent()
	{
		header('Content-type: application/json');
		$input = Factory::getApplication()->input;
		$post = $input->post;
		$db = Factory::getDBO();

		$media_id = $post->get('media_id', '', 'INT');
		$lesson_id = $post->get('lesson_id', '', 'INT');
		$created_by = $post->get('user_id', '', 'STRING');

		// Save Html content in media object
		$obj = new stdclass;
		$obj->source    = $post->get('htmlcontent', '', 'RAW');
		$obj->created_by = !empty($created_by) ? $created_by : Factory::getUser()->id;
		$obj->format = 'textmedia';
		$obj->sub_format = $this->_name . '.source';
		$obj->storage = 'local';
		$obj->org_filename = '';
		$obj->saved_filename = '';
		$obj->path = '';
		$obj->params = '';

		// Save if no media ID is present. Hence consider as new data
		if ($media_id == 0)
		{
			$obj->id = '';

			if (!$db->insertObject('#__tjlms_media', $obj, 'id'))
			{
				echo $db->stderr();
			}

			$id = $db->insertid();
		}
		else // Update if media ID present
		{
			$obj->id = $media_id;

			if (!$db->updateObject('#__tjlms_media', $obj, 'id'))
			{
				echo $db->stderr();
			}

			$id = $media_id;
		}

		if (!class_exists('TjlmsModellesson'))
		{
			$path = JPATH_SITE . '/components/com_tjlms/models/lesson.php';
			JLoader::register('TjlmsModellesson', $path);
		}

		$TjlmsModellesson = new TjlmsModellesson;
		$TjlmsModellesson->saveMediaForlesson($id, $lesson_id, 'textmedia');

		$media_id = json_encode($id);
		echo $media_id;
		jexit();
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
