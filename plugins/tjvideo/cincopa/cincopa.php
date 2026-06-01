<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJVideo,cincopa
 *
 * @copyright   Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Http\Http;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Http\Transport\StreamTransport;
use Joomla\CMS\Language\Text;


define("GLAPI", "https://api.cincopa.com/v2/gallery.list.json?api_token=");
define("VDAPI", "https://api.cincopa.com/v2/gallery.get_items.json?api_token=");
define("SEARCHAPI", "https://api.cincopa.com/v2/asset.list.json?api_token=");

/**
 * Cincopa plugin from techjoomla
 *
 * @since  4.0.0
 */

class PlgTjvideoCincopa extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Function to get data from video and update video tracking data.
	 *
	 * @return  json trackingid from tjlms_lesson_tracking
	 *
	 * @since 4.0.0
	 */
	public function updateData()
	{
		$input = Factory::getApplication()->input;
		$mode  = $input->get('mode', '', 'STRING');

		if ($mode != 'preview')
		{
			$post         = $input->post;
			$lessonId     = $post->get('lesson_id', 0, 'INT');
			$oluserId     = Factory::getUser()->id;
			$lessonStatus = $post->get('lesson_status', '', 'STRING');
			$trackObj                   = new stdClass;
			$trackObj->attempt          = $post->get('attempt', 0, 'INT');
			$trackObj->score            = 0;
			$trackObj->total_content    = $post->get('total_content', '', 'FLOAT');
			$trackObj->current_position = $post->get('current_position', '', 'FLOAT');
			$trackObj->time_spent       = $post->get('time_spent', '', 'FLOAT');
			$trackObj->lesson_status    = $lessonStatus;
			require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';
			$comtjlmstrackingHelper = new comtjlmstrackingHelper;
			$trackingid = $comtjlmstrackingHelper->update_lesson_track($lessonId, $oluserId, $trackObj);
			echo json_encode($trackingid);
			jexit();
		}
	}

	/**
	 * Function to get Sub Format options when creating / editing lesson format
	 * the name of function should follow standard getSubFormat_<plugin_type>ContentInfo
	 *
	 * @param   ARRAY  $config  config specifying allowed plugins
	 *
	 * @return  MIXED
	 *
	 * @since 4.0.0
	 */

	public function getSubFormat_tjvideoContentInfo($config = array('cincopa'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			= array();
		$obj['name']	= $this->params->get('plugin_name', 'cincopaPlayer');
		$obj['id']		= $this->_name;
		$obj['assessment'] = $this->params->get('assessment', '0');

		return $obj;
	}

	/**
	 * Function to get Sub Format HTML when creating / editing lesson format
	 * the name of function should follow standard getSubFormat_<plugin_name>ContentHTML
	 *
	 * @param   INT    $mod_id       id of the module to which lesson belongs
	 * @param   INT    $lessonId     id of the lesson
	 * @param   MIXED  $lesson       Object of lesson
	 * @param   ARRAY  $comp_params  Params of component
	 *
	 * @return  string
	 *
	 * @since 4.0.0
	 */
	public function getSubFormat_CincopaContentHTML($mod_id, $lessonId, $lesson, $comp_params)
	{
		$result      = array();
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
	 * Function to render the video
	 *
	 * @param   ARRAY  $config  data to be used to play video
	 *
	 * @return  string html along with script is return.
	 *
	 * @since 4.0.0
	 */
	public function cincoparenderPluginHTML($config)
	{
		$input        	= Factory::getApplication()->input;
		$mode         	= $input->get('mode', '', 'STRING');
		$scriptfile   	= Uri::root(true) . '/plugins/tjvideo/cincopa/assets/js/cincopa.js';

		// YOUR CODE TO RENDER HTML
		$fileId 		= trim($config['file']);
		$fileId 		= trim($fileId);
		
		$html = '
		 <video width="100%" id="myVideo"    controls controlsList="nodownload" autoplay muted oncontextmenu="return false;">
		  <source src = "' . $fileId . '">
		</video>';
		$html .= '<script type="text/javascript">
		var plugdataObject = {
			plgtype: "' . $this->_type . '",
			plgname: "' . $this->_name . '",
			plgtask:"updateData",
			lesson_id: ' . $config['lesson_id'] . ',
			attempt: ' . $config['attempt'] . ',
			file_id : "' . $fileId . '",
			seekTo : "' . $config['current'] . '",
			mode:  "' . $mode . '"
		};
		</script>';

		// This may be an iframe directlys
		$html .= "<script src=" . $scriptfile . "></script>";

		return $html;
	}

	/**
	 * Function to check if the related tables has been uploaded while adding lesson
	 *
	 * @param   INT    $lessonId  lessonId
	 * @param   MIXED  $mediaObj  media object
	 *
	 * @return  MIXED object of format and subformat
	 *
	 * @since 4.0.0
	 */
	public function additionalcincopaFormatCheck($lessonId, $mediaObj)
	{
		return $mediaObj;
	}

	/**
	 * Function to render create video view.
	 *
	 * @return  MIXED html along with script is return.
	 *
	 * @since 4.0.0
	 */
	public function getCreateVideoHtml()
	{
		return true;
	}

	/**
	 * Function to render cincopa video list.
	 *
	 * @return  string html along with script is return.
	 *
	 * @since 4.0.0
	 */
	public function getHtml()
	{
		$input = Factory::getApplication()->input;
		$fid = $input->get('fid', '', 'STRING');
		$qtext = $input->get('qtext', '', 'STRING');
		$qtextVideo = $input->get('qtextVideo', '', 'STRING');
		$type = $input->get('type', '', 'STRING');
		$page = $input->get('page', '1', 'INT');
		$listData = $this->getSearchList($fid, $qtext, $qtextVideo, $page);
		echo $this->buildLayout($listData);

		if (isset($type) && $type == 'ajax')
		{
			jexit();
		}
	}

	/**
	 * Internal use functions
	 *
	 * @param   STRING  $layout  layout
	 *
	 * @return  string
	 *
	 * @since 4.0.0
	 */
	public function buildLayoutPath($layout)
	{
		$app       = Factory::getApplication();
		$core_file = dirname(__FILE__) . '/' . '/tmpl/' . $layout . '.php';
		$override  = JPATH_BASE . '/templates/' . $app->getTemplate() . '/html/plugins/' . $this->_type . '/' . $this->_name . '/' . $layout . '.php';

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
	 * Builds the layout to be shown, along with hidden fields.
	 *
	 * @param   MIXED   $vars    vars to be used
	 * @param   STRING  $layout  layout
	 *
	 * @return  string
	 *
	 * @since 4.0.0
	 */
	public function buildLayout($vars, $layout = 'default')
	{
		// Load the layout & push variables
		
		$layout = $this->buildLayoutPath($layout);	

		if (file_exists($layout))
		{	
			ob_start();
			include $layout;
			$html = ob_get_contents();
			ob_end_clean();
			return $html;		
		}
		else
		{
			return Text::_('PLG_TJVIDEO_CINCOPA_LAYOUT_ERROR');
		}	

			
	}

	/**
	 * Fetch gallery and Video List
	 *
	 * @param   STRING  $fid         get the gallery id .
	 * @param   STRING  $paramText   get text for search from gallery.
	 * @param   STRING  $qtextVideo  text for search from video.
	 * @param   STRING  $page        to get page number for pagination.
	 * 
	 * @return  string
	 *
	 * @since 4.0.0
	 */
	public function getSearchList($fid = '', $paramText = '', $qtextVideo = '' , $page = '')
	{
		$max = $this->params->get('max_search');
		$vdoApi = VDAPI;
		$galleryApi = GLAPI;
		$searchApi = SEARCHAPI;

		if (!isset($page))
		{
			$page = 1;
		}

		$secretKey = $this->params->get('secret_key');

		$http = new Http;

		if (!empty($fid))
		{
			$url = $vdoApi . $secretKey . '&fid=' . $fid;
		}
		elseif(!empty($paramText))
		{
			$url = $galleryApi . $secretKey . '&search=' . $paramText;
		}
		elseif(!empty($qtextVideo))
		{
			$url = $searchApi . $secretKey . '&search=' . $qtextVideo;
		}
		elseif($page > 1)
		{
			$url = $galleryApi . $secretKey . '&items_per_page=' . $max . '&page=' . $page;
		}
		else
		{
			$url = $galleryApi . $secretKey . '&items_per_page=' . $max;
		}

			try
			{
				$result = $http->post($url, '');
				$data = $result->body;

				return $data;
			}
			catch (Exception $e)
			{
				return $e->getMessage();
			}
	}

	/**
	 * Update Lesson state
	 *
	 * @return  MIXED
	 *
	 * @since 4.0.0
	 */
	public function updateLessonState()
	{
		$input        = Factory::getApplication()->input;
		$lessonState = $input->get('lesson_state', '', 'INT');
		$lessonId    = $input->get('lesson_id', '', 'INT');

		// Here need to change the lesson state
		// require_once JPATH_SITE . '/components/com_tjlms/helpers/lesson.php';
		// $TjlmsLessonHelper = new TjlmsLessonHelper;

		try
		{
			// Here need to change the lesson state		
			// $lid = $TjlmsLessonHelper->update_lesson_state($lessonId, $lessonState);
			// echo json_encode($lid);
			jexit();
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
	}

	/**
	 * Function to load more videos.
	 *
	 * @return  string html
	 *
	 * @since 4.0.0
	 */
	public function loadMore()
	{
		$input        = Factory::getApplication()->input;
		$fid          = $input->get('fid', '', 'string');
		$qtext        = $input->get('qtext', '', 'string');
		$qtextVideo   = $input->get('qtextVideo', '', 'string');
		$page         = $input->get('page', '', 'int');
		$listData     = $this->getSearchList($fid, $qtext, $qtextVideo, $page);
		$var          = json_decode($listData, true);
		$application  = Factory::getApplication();
		$input        = $application->input;
		$formId       = $input->get('form_id', '', 'string');
		$lessonId     = $input->get('lesson_id', '', 'string');
		$html         = '';
		$galleryCount = count($var['galleries']);

			for ($data = 0; $data < $galleryCount; $data++)
			{
			$fid = $var['galleries'][$data]['fid'];
			$link = '"' . Uri::root() . 'index.php?option=com_tjlms&task=callSysPlgin&plgType=tjvideo&plgName=cincopa&plgtask=getHtml&callType=1&lesson_id='
			. $lessonId . '&fid=' . $fid . '&form_id=' . $formId . '"';
			$html .= "<br><div class='controls controls-row cincopa_video_tr'>
						<div class = 'span2 video_img' style='width: 20px;'>" .
						"<img src = " . Uri::root() . "/media/com_tjlms/images/default/icons/video.png class = 'img-polaroid' >
						</div> 
						<div class='span4'>
							<div class='video_link' style='font-size: 16px;'>"
						. "<a href = 'javascript:void(0)' onClick = 'openListVideos_all(this," . $link . ")'>		
						<strong>" . $var['galleries'][$data]['name'] . "</strong>
								</a>
							</div>					
						</div>
						</div>
						<br>";
			}

			echo $html;
	}
}
