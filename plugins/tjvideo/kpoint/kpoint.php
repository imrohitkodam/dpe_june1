<?php
/**
 * @package     Tjlms.Plugin
 * @subpackage  Tjlms,TJVideo,kpoint
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
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
use Joomla\Registry\Registry;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Http\Transport\StreamTransport;

$lang = Factory::getLanguage();
$lang->load('plg_tjvideo_kpoint', JPATH_ADMINISTRATOR);

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjvideoKpoint extends CMSPlugin
{
	protected $challenge = '';

	/**
	 * Plugin that supports uploading and tracking the videos for jWplayer plugin
	 *
	 * @param   string   &$subject  The context of the content being passed to the plugin.
	 * @param   integer  $config    Optional page number. Unused. Defaults to zero.
	 *
	 * @since 1.0.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->xtencode = $this->getKapsuleUrl();
	}

	/**
	 * Function to get needed data for this API
	 *
	 * @return  id from tjlms_lesson_tracking
	 *
	 * @since 1.0.0
	 */
	public function updateData()
	{
		$input = Factory::getApplication()->input;
		$mode  = $input->get('mode', '', 'STRING');

		if ($mode != 'preview')
		{
			$post          = $input->post;
			$lesson_id     = $post->get('lesson_id', 0, 'INT');
			$oluser_id     = Factory::getUser()->id;
			$lesson_status = $post->get('lesson_status', '', 'STRING');

			$trackObj                   = new stdClass;
			$trackObj->attempt          = $post->get('attempt', 0, 'INT');
			$trackObj->score            = 0;
			$trackObj->total_content    = $post->get('total_content', '', 'FLOAT');
			$trackObj->current_position = $post->get('current_position', '', 'FLOAT');
			$trackObj->time_spent       = $post->get('time_spent', '', 'FLOAT');
			$trackObj->lesson_status    = $lesson_status;

			require_once JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';

			$comtjlmstrackingHelper = new comtjlmstrackingHelper;
			$trackingid = $comtjlmstrackingHelper->update_lesson_track($lesson_id, $oluser_id, $trackObj);

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
	 * @since 1.0.0
	 */
	public function getSubFormat_tjvideoContentInfo($config = array('kpoint'))
	{
		if (!in_array($this->_name, $config))
		{
			return;
		}

		$obj 			= array();
		$obj['name']	= $this->params->get('plugin_name', 'kpoint player');
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
	 * @return  string
	 *
	 * @since 1.0.0
	 */
	public function getSubFormat_KpointContentHTML($mod_id, $lesson_id, $lesson, $comp_params)
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
	 * @since 1.0.0
	 */
	public function kpointrenderPluginHTML($config)
	{
		$input        = Factory::getApplication()->input;
		$mode         = $input->get('mode', '', 'STRING');
		$scriptfile   = Uri::root(true) . '/plugins/tjvideo/kpoint/assets/js/kpoint.js';
		$playerScript = 'https://assets.kpoint.com/orca/media/embed/player-cdn.js';

		// YOUR CODE TO RENDER HTML
		$fileId = $config['file'];
		$fileId = trim($fileId);

		$domain = $this->params->get('domain_name', 'https://dev13.kpoint.com/');
		$domain = preg_replace("#^[^:/.]*[:/]+#i", "", $domain);

		$user           = Factory::getUser();
		$clientId       = $this->params->get('client_id');
		$showLike       = $this->params->get('show_like') ? '' : 'like';
		$showSeekbar    = $this->params->get('show_seekbar') ? '' : '{"hide": "seekbar"}';
		$authentication = $this->params->get('authentication');

		$email         = ($user->email) ? ($user->email) : ($this->params->get('email_id'));
		$accountNumber = ($user->username) ? ($user->username) : ($this->params->get('display_name'));
		$accountNumber = str_replace(array('+', '-'), '', $accountNumber);
		$displayname   = ($user->name) ? ($user->name) : ($this->params->get('display_name'));

		$b64token = $this->createToken($email, $displayname, $accountNumber);

		$xtencode = "client_id=$clientId&user_email=$email&user_name=$displayname
		&challenge=$this->challenge&user_account_number=$accountNumber&xauth_token=$b64token";

		if ($authentication == 'email')
		{
			$xtencode = "client_id=$clientId&user_email=$email&user_name=$displayname&challenge=$this->challenge&xauth_token=$b64token";
		}
		elseif ($authentication == 'account_number' && $accountNumber)
		{
			$xtencode = "client_id=$clientId&user_name=$displayname&challenge=$this->challenge&user_account_number=$accountNumber&xauth_token=$b64token";
		}

		$xt = base64_encode($xtencode);
		$xt = str_replace("=", "", $xt);
		$xt = str_replace("+", "-", $xt);
		$xt = str_replace("/", "_", $xt);

		$html = '
		<script type="text/javascript">
		var plugdataObject = {
			plgtype: "' . $this->_type . '",
			plgname: "' . $this->_name . '",
			plgtask:"updateData",
			lesson_id: ' . $config['lesson_id'] . ',
			attempt: ' . $config['attempt'] . ',
			file_id : "' . $fileId . '",
			seekTo : ' . $config['current'] . ',
			mode:  "' . $mode . '",
			domain : "' . $domain . '",
			client_id : "' . $this->params->get('client_id', '') . '",
			xauth_token : "' . $xt . '",
			email_id : "' . $email . '",
			display_name : "' . $displayname . '",
			challenge : "' . time() . '",
			show_like : "' . $showLike . '",
			show_seekbar : ' . $showSeekbar . '
		};
		</script>

		<div id="main_kapsule_container">
			<div id="main_kapsule"></div>
		</div>
		<div id="main_kapsule_container-ie">
			<div id="main_kapsule-ie"></div>
		</div>

		 <div id="player-container" style="width:100%; height:80%">
		 </div>

		<div id="controls" class="tb">
			<span id="timerId" href="#"></span>
		</div>
		<script src="' . $playerScript . '"></script>
		<script src="' . $scriptfile . '"></script>
		';

		// This may be an iframe directlys
		return $html;
	}

	/**
	 * Function to check if the scorm tables has been uploaded while adding lesson
	 *
	 * @param   INT    $lessonId  lessonId
	 * @param   MIXED  $mediaObj  media object
	 *
	 * @return  MIXED object of format and subformat
	 *
	 * @since 1.0.0
	 */
	public function additionalkpointFormatCheck($lessonId, $mediaObj)
	{
		return $mediaObj;
	}

	/**
	 * Function to render create video view.
	 *
	 * @return  MIXED html along with script is return.
	 *
	 * @since 1.0.0
	 */
	public function getCreateVideoHtml()
	{
		return $this->getKapsuleCreator();
	}

	/**
	 * Function to render kapsule list.
	 *
	 * @return  string html along with script is return.
	 *
	 * @since 1.0.0
	 */
	public function getHtml()
	{
		$token    = $this->createToken();
		$listData = $this->getSearchList($token);

		echo $this->buildLayout($listData);
	}

	/**
	 * Function to render kapsule list.
	 *
	 * @return  string html along with script is return.
	 *
	 * @since 1.0.0
	 */
	public function getHtmlAjax()
	{
		$token    = $this->createToken();
		$listData = $this->getSearchList($token);

		echo $this->buildLayout($listData);
		jexit();
	}

	/**
	 * Internal use functions
	 *
	 * @param   STRING  $layout  layout
	 *
	 * @return  string
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 */
	public function buildLayout($vars, $layout = 'default')
	{
		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath($layout);
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Builds token for api access
	 *
	 * @param   STRING  $email          vars to be used
	 * @param   STRING  $displayname    layout
	 * @param   STRING  $accountNumber  account number
	 *
	 * @return  string
	 *
	 * @since 1.0.0
	 */
	public function createToken($email = null, $displayname = null, $accountNumber = '')
	{
		$CLIENT_ID = $this->params->get('client_id');
		$SECRET_KEY = $this->params->get('secret_key');
		$authentication = $this->params->get('authentication');

		if ($email == null)
		{
			$email = $this->params->get('email_id');
		}

		if ($displayname == null)
		{
			$displayname = $this->params->get('display_name');
		}

		$this->challenge = time();
		$data = "$CLIENT_ID:$email:$displayname:$this->challenge";

		if (($authentication == 'both') && $accountNumber && $email)
		{
			$data = "$CLIENT_ID:$email:$displayname:$this->challenge:$accountNumber";
		}
		elseif ($accountNumber && ($authentication == 'account_number'))
		{
			$email = '';
			$data  = "$CLIENT_ID:$email:$displayname:$this->challenge:$accountNumber";
		}

		$token = hash_hmac("md5", $data, $SECRET_KEY, true);

		$b64token = base64_encode($token);
		$b64token = str_replace("=", "", $b64token);
		$b64token = str_replace("+", "-", $b64token);
		$b64token = str_replace("/", "_", $b64token);

		return $b64token;
	}

	/**
	 * Get list of video in kpoint of admin user
	 *
	 * @param   STRING  $token  token for api access
	 *
	 * @return  MIXED
	 *
	 * @since 1.0.0
	 */
	public function getSearchList($token)
	{
		$application = Factory::getApplication();
		$input = $application->input;

		$KPOINT_HOST = $this->params->get('domain_name');
		$CLIENT_ID = $this->params->get('client_id');
		$SECRET_KEY = $this->params->get('secret_key');
		$email = $this->params->get('email_id');
		$displayname = $this->params->get('display_name');

		$qtext = $input->get('qtext', '', 'string');
		$first = $input->get('first', '', 'INT');
		$max = $input->get('max', '', 'INT');

		if (empty($KPOINT_HOST) || empty($CLIENT_ID) || empty($SECRET_KEY) || empty($email) || empty($displayname))
		{
			return 1;
		}

		if ($qtext && empty($first))
		{
			$xtencode = "?qtext=" . urlencode($qtext) . "&client_id=" . $CLIENT_ID . "&user_email=" . $email . "&user_name=";
			$xtencode .= $displayname . "&challenge=" . $this->challenge . "&xauth_token=" . $token;
		}
		elseif ($first && empty($qtext))
		{
			$xtencode = "?client_id=" . $CLIENT_ID . "&user_email=" . $email . "&user_name=";
			$xtencode .= $displayname . "&challenge=" . $this->challenge . "&xauth_token=" . $token . "&first=" . $first . "&max=" . $max;
		}
		elseif ($first && $qtext)
		{
			$xtencode = "?client_id=" . $CLIENT_ID . "&user_email=" . $email . "&user_name=";
			$xtencode .= $displayname . "&challenge=" . $this->challenge . "&xauth_token=" . $token;
			$xtencode .= "&qtext=" . urlencode($qtext) . "&first=" . $first . "&max=" . $max;
		}
		else
		{
			$xtencode = "?client_id=" . $CLIENT_ID . "&user_email=" . $email . "&user_name=";
			$xtencode .= $displayname . "&challenge=" . $this->challenge . "&xauth_token=" . $token . "";
		}

		$last_letter_URL = rtrim($KPOINT_HOST, "/");
		$url = $last_letter_URL . '/api/v1/xapi/search' . $xtencode;

		$http = new Http;

		try
		{
			$result = $http->post($url, '');

			return json_decode($result->body, true);
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
	}

	/**
	 * Get list of video in kpoint of admin user
	 *
	 * @return  MIXED
	 *
	 * @since 1.0.0
	 */
	public function getKapsuleCreator()
	{
		// $xtencode = $this->getKapsuleUrl();

		$last_letter_URL = rtrim($this->params->get('domain_name'), "/");
		$url = $last_letter_URL . '/kapsule/kstudio/new' . $this->xtencode;

		return $url;
	}

	/**
	 * Get status of video in kpoint of admin user
	 *
	 * @param   STRING  $source  kapsule key
	 *
	 * @return  MIXED
	 *
	 * @since 1.0.0
	 */
	public function getKapsuleStatus($source)
	{
		if ($source)
		{
			// $xtencode = $this->getKapsuleUrl();

			$last_letter_URL = rtrim($this->params->get('domain_name'), "/");
			$url = $last_letter_URL . '/api/v1/xapi/kapsule/' . $source . $this->xtencode;

			$options   = new Registry;
			$transport = new StreamTransport($options);

			// Create a 'stream' transport.
			$http = new Http($options, $transport);

			try
			{
				// #echo "<pre>".$url."</pre>";

				$result = $http->get($url);

				return json_decode($result->body, true);
			}
			catch (Exception $e)
			{
				return $e->getMessage();
			}
		}
	}

	/**
	 * Update status
	 *
	 * @return  MIXED
	 *
	 * @since 1.0.0
	 */
	public function getKapsuleStatusUpdate()
	{
		$input  = Factory::getApplication()->input;
		$source = $input->get('source', '', 'string');

		if ($source)
		{
			// $xtencode = $this->getKapsuleUrl();

			$last_letter_URL = rtrim($this->params->get('domain_name'), "/");
			$url = $last_letter_URL . '/api/v1/xapi/kapsule/' . $source . '/publish' . $this->xtencode;

			$options   = new Registry;
			$transport = new StreamTransport($options);

			// Create a 'stream' transport.
			$http = new Http($options, $transport);

			try
			{
				$http->put($url, $source);

				echo 1;

				jexit();
			}

			catch (Exception $e)
			{
				return $e->getMessage();
			}
		}
	}

	/**
	 * Download video
	 *
	 * @return  String
	 *
	 * @since 1.0.0
	 */
	public function getKapsuleDownload()
	{
		$input = Factory::getApplication()->input;
		$source = $input->get('source', '', 'string');

		if ($source)
		{
			$input = Factory::getApplication()->input;
			$download_video = $this->params->get('download_video');

			if ($download_video == 1)
			{
				// $xtencode = $this->getKapsuleUrl();

				$last_letter_URL = rtrim($this->params->get('domain_name'), "/");
				$url = $last_letter_URL . '/files/download/video.mp4';

				return $url;
			}
		}
	}

	/**
	 * Get Data
	 *
	 * @return  MIXED
	 *
	 * @since 1.0.0
	 */
	public function getKapsuleData()
	{
		$input = Factory::getApplication()->input;
		$source = $input->get('source', '', 'string');

		if ($source)
		{
			// $xtencode = $this->getKapsuleUrl();

			$last_letter_URL = rtrim($this->params->get('domain_name'), "/");
			$url = $last_letter_URL . '/api/v1/xapi/kapsule/' . $source . $this->xtencode;

			$options   = new Registry;
			$transport = new StreamTransport($options);

			// Create a 'stream' transport.
			$http = new Http($options, $transport);

			try
			{
				$result = $http->get($url);

				echo $result->body;
				jexit();
			}
			catch (Exception $e)
			{
				return $e->getMessage();
			}
		}
	}

	/**
	 * Return token Url
	 *
	 * @return  MIXED
	 *
	 * @since 1.0.0
	 */
	public function getKapsuleUrl()
	{
		$token       = $this->createToken();
		$KPOINT_HOST = $this->params->get('domain_name');
		$CLIENT_ID   = $this->params->get('client_id');
		$SECRET_KEY  = $this->params->get('secret_key');
		$email       = $this->params->get('email_id');
		$displayname = $this->params->get('display_name');

		if (empty($KPOINT_HOST) || empty($CLIENT_ID) || empty($SECRET_KEY) || empty($email) || empty($displayname))
		{
			return 1;
		}
		else
		{
			$xtencode = "?client_id=" . $CLIENT_ID . "&user_email=" . $email . "&user_name=";
			$xtencode .= $displayname . "&challenge=" . $this->challenge . "&xauth_token=" . $token;
		}

		return $xtencode;
	}

	/**
	 * Update Lesson state
	 *
	 * @return  MIXED
	 *
	 * @since 1.0.0
	 */
	public function updateLessonState()
	{
		$input        = Factory::getApplication()->input;
		$lesson_state = $input->get('lesson_state', '', 'INT');
		$lesson_id    = $input->get('lesson_id', '', 'INT');

		// Here need to change the lesson state

		/*
		require_once JPATH_SITE . '/components/com_tjlms/helpers/lesson.php';
		$TjlmsLessonHelper = new TjlmsLessonHelper; */

		try
		{
			// Here need to change the lesson state

			/*
			$lid = $TjlmsLessonHelper->update_lesson_state($lesson_id, $lesson_state);
			echo json_encode($lid);
			*/
			jexit();
		}
		catch (Exception $e)
		{
			return $e->getMessage();
			jexit();
		}
	}

	/**
	 * Function to load more videos.
	 *
	 * @return  html
	 *
	 * @since 1.0.0
	 */
	public function loadMore()
	{
		$token    = $this->createToken();
		$listData = $this->getSearchList($token);

		$application = Factory::getApplication();
		$input       = $application->input;
		$formId      = $input->get('form_id', '', 'string');

		$html = '';

		foreach ($listData['list'] as $key => $value)
		{
			$html .= '<div class="controls controls-row kpoint_video_tr"><div class="span2 video_img">
			<a href="javascript:void(0)" onclick="parent.bindKpoint(\'' . $formId . '\', \'' . $value['kapsule_id'] . '\',\''
			. $value['displayname'] . '\',\'' . $value['thumbnail_url'] . '\',\''
			. trim($value['description']) . '\',\'' . $value['owner_displayname'] . '\')"><img src="'
			. $value['images']['thumb'] . '" class="img-polaroid" height="200" width="200"></a></div>
			<div class="span4"><div class="video_link"><strong>'
			. $value['displayname'] . '</strong></div><div class="video_desc"><i class="icon-user pull-left"></i>'
			. $value['owner_displayname'] . '</div></div></div>';
		}

		echo $html;
	}

	/**
	 * Get external url of lesson
	 *
	 * @param   STRING  $kapsuleId  kapsule id for video
	 *
	 * @return  string
	 *
	 * @since 1.3.34
	 */
	public function getKpointExternalURL($kapsuleId)
	{
		$showExternalURL = $this->params->get('show_external_url');
		$url             = '';

		if ($showExternalURL)
		{
			$domainName  = rtrim($this->params->get('domain_name'), "/");
			$url = $domainName . '/app/video/' . $kapsuleId;
		}

		return $url;
	}
}
