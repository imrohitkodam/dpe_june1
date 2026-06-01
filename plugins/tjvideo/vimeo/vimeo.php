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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

$lang = Factory::getLanguage();
$lang->load('plg_tjvideo_vimeo', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjvideoVimeo extends CMSPlugin
{
	/**
	 * Function to get needed data for this API
	 *
	 * @return  id from tjlms_lesson_tracking
	 *
	 * @since 1.0.0
	 */
	public function onupdateData()
	{
		$db = Factory::getDBO();
		$input = Factory::getApplication()->input;

		$mode = $input->get('mode', '', 'STRING');
		$trackingid = '';

		if ($mode != 'preview')
		{
			$post = $input->post;
			$lesson_id = $post->get('lesson_id', '', 'INT');
			$oluser_id = Factory::getUser()->id;

			$trackObj = new stdClass;
			$trackObj->attempt = $post->get('attempt', '', 'INT');
			$trackObj->score = 0;
			$trackObj->total_content = '';
			$trackObj->current_position = '';
			$trackObj->time_spent = '';

			$lesson_status	=	$post->get('lesson_status', '', 'STRING');

			if (!empty($lesson_status))
			{
				$trackObj->lesson_status = $lesson_status;
			}

			$current_position = $post->get('current_position', '', 'FLOAT');

			if (!empty($current_position))
			{
				$trackObj->current_position = round($current_position, 2);
			}

			$total_content = $post->get('total_content', '', 'FLOAT');

			if (!empty($total_content))
			{
				$trackObj->total_content = round($total_content, 2);
			}

			$time_spent = $post->get('time_spent', '', 'FLOAT');

			if (!empty($time_spent))
			{
				$trackObj->time_spent = round($time_spent, 2);
			}

			/*if ($type == 'started')
			{
				Update the total content of video
				$trackObj->total_content = round($post->get('duration', '', 'FLOAT'), 2);
				$trackObj->lesson_status = 'started';
			}
			elseif ($type == 'update_current')
			{
				$trackObj->current_position = round($post->get('duration', '', 'FLOAT'), 2);
				$trackObj->time_spent = round($post->get('spent', '', 'FLOAT'), 2);
				$trackObj->lesson_status = 'incomplete';
			}
			elseif ($type == 'update_pause')
			{
				Update current_position of video
				$trackObj->current_position = round($post->get('duration', '', 'FLOAT'), 2);
				$trackObj->time_spent = round($post->get('spent', '', 'FLOAT'), 2);
				$trackObj->lesson_status = 'incomplete';
			}
			elseif ($type == 'update_spent')
			{
				Update current_position of video & total spent
				$trackObj->time_spent = round($post->get('duration', '', 'FLOAT'), 2);
				$trackObj->current_position = round($post->get('current', 0, 'FLOAT'), 2);
				$trackObj->lesson_status = 'completed';
			}
		*/
			require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';
			$comtjlmstrackingHelper = new comtjlmstrackingHelper;
			$trackingid = $comtjlmstrackingHelper->update_lesson_track($lesson_id, $oluser_id, $trackObj);
		}

		return $trackingid;
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
	public function onGetSubFormat_tjvideoContentInfo($config = array('vimeo'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			= array();
		$obj['name']	= $this->params->get('plugin_name', 'Vimeo player');
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
	public function onGetSubFormat_vimeoContentHTML($mod_id, $lesson_id, $lesson, $comp_params)
	{
		/*$result = array();
		$plugin_name = $this->_name;
		$source = (isset($lesson->format_details['source'])) ? $lesson->format_details['source'] : '';
		$html = '
		<div class="control-label">' . Text::_("COM_TJLMS_VIDEO_FORMAT_URL_OPTIONS") . '</div>

		<div  class="controls">
			<input type="hidden" class="class_video_format"
						id="lesson_format' . $plugin_name . 'video_source"
						name="lesson_format[' . $plugin_name . '][video_source]"
						value="url"/>
			<div id="video_textarea" >
				<textarea 	id="video_url"
							class="input-block-level" placeholder="' . Text::_("PLG_TJVIDEO_VIMEO_VIDEO_PLACEHOLDER") . '" cols="50" rows="2"
							name="lesson_format[' . $plugin_name . '][video_format_source]" >' . trim($source) . '</textarea>
			</div>
		</div>';

		return $html;*/

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
	 * Function to check if the scorm tables has been uploaded while adding lesson
	 *
	 * @param   INT  $lessonId  lessonId
	 * @param   OBJ  $mediaObj  media object
	 *
	 * @return  media object of format and subformat
	 *
	 * @since 1.0.0
	 */
	public function onAdditionalvimeoFormatCheck($lessonId, $mediaObj)
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
	public function onvimeorenderPluginHTML($config)
	{
		$input = Factory::getApplication()->input;
		$mode = $input->get('mode', '', 'STRING');
		$scriptfile = JURI::root(true) . '/plugins/tjvideo/vimeo/vimeo/assets/tjvimeo.js';

		// YOUR CODE TO RENDER HTML
		$file_id = substr($config['file'], strrpos($config['file'], '/') + 1);
		$file_id = trim($file_id);

		$embedMethod = $this->params->get('embedding_option', 'iframe-script');

		$html = '<div id="shika_vimeoplayer">';

		if ($embedMethod == 'iframe-script')
		{
			$html .= Text::_("PLG_TJVIDEO_VIMEO_VIDEO_LOADING");
		}
		else
		{
			$html .= '<iframe src="https://player.vimeo.com/video/' . $file_id .
			'" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		}

		$html .= '</div>

		<script src="https://player.vimeo.com/api/player.js"></script>
		<script type="text/javascript">
		jQuery("#appsloading").css("height", "100%");
		var plugdataObject = {
			plgtype: "' . $this->_type . '",
			plgname: "' . $this->_name . '",
			plgtask:"updateData",
			lesson_id: ' . $config['lesson_id'] . ',
			attempt: ' . $config['attempt'] . ',
			file_id : "' . $file_id . '",
			seekTo : ' . $config['current'] . ',
			mode:  "' . $mode . '",
			embedMethod:  "' . $embedMethod . '"
		};
		</script>
		<script src="' . $scriptfile . '"></script>';

		// This may be an iframe directlys
		return $html;
	}
}
